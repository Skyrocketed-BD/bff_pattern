<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\ClosingAdjustmentRequest;
use App\Models\finance\ClosingAdjustment;
use App\Models\finance\Coa;
use App\Models\finance\GeneralLedger;
use App\Models\finance\JournalAdjustment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ClosingAdjustmentController extends Controller
{
    /**
     * @OA\Get(
     *  path="/closing-adjustment",
     *  summary="Get the list of closing adjusments",
     *  tags={"Finance - Closing Adjustments"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $data = [];

        $year  = Carbon::parse($request->period)->format('Y');
        $month = Carbon::parse($request->period)->format('m');

        $closing_adjustment = ClosingAdjustment::with(['toJournalAdjustment'])->where('month', $month)->where('year', $year)->orderBy('date', 'asc')->get();

        foreach ($closing_adjustment as $closing) {
            $data[] = [
                "date"              => $closing->date,
                "transaction_number" => $closing->transaction_number,
                "description"       => $closing->toJournalAdjustment->description,
                "value"             => $closing->toJournalAdjustment->value,
            ];
        }
        return ApiResponseClass::sendResponse($data, 'Adjustment Closing Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/closing-adjustment",
     *  summary="Create a new closing adjustment",
     *  tags={"Finance - Closing Adjustments"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="period",
     *                  type="integer",
     *                  description="Period"
     *              ),
     *              required={"period"},
     *              example={
     *                  "period": "2022-01"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $period       = $request->period;                              // format: Y-m
            $carbonPeriod = Carbon::parse($period);
            $year         = $carbonPeriod->year;
            $month        = $carbonPeriod->month;
            $end_date     = $carbonPeriod->endOfMonth()->format('Y-m-d');

            $response = $this->_checkClosingPeriod($month, $year);
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response;
            }

            $gl_adjustments = [];
            $closing_adjustments = [];

            $journal_adjustments = JournalAdjustment::whereDoesntHave('toClosingAdjustment', function ($query) use ($month, $year) {
                $query->where('month', $month)->where('year', $year);
            })
                ->where('transaction_type', 'recurring')
                ->where('remaining', '>', 0)
                ->where('date', '<=', $end_date)
                ->get();

            foreach ($journal_adjustments as $key => $journal) {
                foreach ($journal->toJournalAdjustmentSet as $key => $journal_set) {
                    $transaction_number = generate_number('finance', 'closing_adjustment', 'transaction_number', 'ADJ-CLS');
                    // tampung journal general ledger
                    $gl_adjustments[] = [
                        'transaction_number' => $transaction_number,
                        'date'               => $end_date,
                        'coa'                => $journal_set->toCoa->coa,
                        'type'               => $journal_set->type,
                        'value'              => $journal_set->value,
                        'description'        => $journal->description,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'created_by'         => auth('api')->user()->id_users
                    ];
                }

                $journal->remaining = ($journal->remaining - 1);
                $journal->save();

                $closing_adjustments[] = ClosingAdjustment::create([
                    'date'                  => $end_date,
                    'id_journal_adjustment' => $journal->id_journal_adjustment,
                    'transaction_number'    => $transaction_number,
                    'month'                 => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'year'                  => $year,
                ]);
            }

            GeneralLedger::insert($gl_adjustments);

            ActivityLogHelper::log('finance:closing_adjustment', 1, [
                'description'   => 'Successfully closed the period ' . $request->period,
            ]);

            DB::connection('finance')->commit();
            return ApiResponseClass::sendResponse($closing_adjustments, 'Journal Entry Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:closing_adjustment', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *  path="/closing-adjustment/open",
     *  summary="Open Period Closing Adjustment",
     *  tags={"Finance - Closing Adjustments"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="period",
     *                  type="integer",
     *                  description="Period"
     *              ),
     *              required={"period"},
     *              example={
     *                  "period": "2022-01"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function open(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $year  = Carbon::parse($request->period)->format('Y');
            $month = Carbon::parse($request->period)->format('m');

            // check closing next period
            $check_closing_next_period = ClosingAdjustment::where('year', $year)->where('month', '>', $month)->first();

            if ($check_closing_next_period) {
                return Response::json([
                    'success' => false,
                    'message' => 'Cannot Open, There is some closing adjustment in the next period!',
                ], 400);
            }

            $check_closing_adjustment = ClosingAdjustment::where('year', $year)->where('month', $month)->first();

            if ($check_closing_adjustment) {
                $phase  = 'opr';

                GeneralLedger::where('transaction_number', $check_closing_adjustment->transaction_number)->where('phase', $phase)->delete();

                $check_closing_adjustment->delete();

                ActivityLogHelper::log('finance:closing_adjustment', 1, [
                    'description'   => 'Successfully opened the period ' . $request->period,
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_closing_adjustment, 'Journal Entry Created Successfully');
            } else {
                return Response::json([
                    'success' => false,
                    'message' => 'Closing Adjustment Not Found',
                ], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:closing_adjustment', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Check if the closing period is valid
     *
     * @param int $month
     * @param int $year
     * 
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function _checkClosingPeriod($month, $year)
    {
        // Ambil semua bulan yang sudah closing di tahun ini
        $closedMonths = ClosingAdjustment::where('year', $year)->pluck('month')->sort()->values()->toArray(); // sort agar urut
        $currentMonth = Carbon::now()->month;

        // Ambil semua bulan yang punya journal valid
        $journals = JournalAdjustment::where('transaction_type', 'recurring')
            ->where('remaining', '>', 0)
            ->whereYear('date', $year)
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->date)->month;
            });

        $existingJournalMonths = array_keys($journals->toArray()); // format [1, 2, 3, 4]

        // Tidak boleh closing bulan sebelum bulan closing terakhir
        if (!empty($closedMonths)) {
            // check apakah period sudah di-close sebelumnya
            if (in_array($month, $closedMonths)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This period has already been closed!',
                ], 400);
            }

            // check apakah period tidak lebih besar dari bulan sekarang
            if ($month > $currentMonth) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot close a future period!',
                ], 400);
            }

            // just in case, check apakah period yang mau di-close tidak lebih kecil dari bulan terakhir yang sudah di-close
            $lastClosed = max($closedMonths);
            if ($month < $lastClosed) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot close a period that is already in the past!',
                ], 400);
            }
        }

        // jika journal tidak ada di bulan yang mau di-closing
        if (!in_array($month, $existingJournalMonths)) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing to close for the selected period!',
            ], 400);
        }

        // check bulan terendah sebelum closing
        $monthToClose = !empty($existingJournalMonths) ? min($existingJournalMonths) : null;

        if ($month > $monthToClose) {
            // Jika bulan yang mau di-closing lebih besar dari bulan terendah yang punya journal
            if (!in_array($monthToClose, $closedMonths)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please close period ' . Carbon::createFromDate($year, $monthToClose, 1)->format('F') . ' first!',
                ], 400);
            }
        }
    }

    private function _checkPreviousPeroid($month, $year)
    {
        $current = Carbon::createFromDate($year, $month, 1);
        $firstPeriod = ClosingAdjustment::orderBy('year')
            ->orderBy('month')
            ->first();

        if (!$firstPeriod) {
            return response()->json([
                'message' => 'No periods found in the database.',
            ], 400);
        }

        $start = Carbon::createFromDate($firstPeriod->year, $firstPeriod->month, 1);

        $allPreviousPeriods = [];
        $check = $start->copy();

        while ($check->lt($current)) {
            $allPreviousPeriods[] = [
                'year' => $check->year,
                'month' => $check->month,
            ];
            $check->addMonth();
        }

        // Get all closed periods from DB in one shot
        $closed = ClosingAdjustment::where(function ($q) use ($allPreviousPeriods) {
            foreach ($allPreviousPeriods as $p) {
                $q->orWhere(function ($sub) use ($p) {
                    $sub->where('year', $p['year'])
                        ->where('month', $p['month']);
                });
            }
        })->get();

        if (count($closed) < count($allPreviousPeriods)) {
            return false;
        }

        return true;
    }
}
