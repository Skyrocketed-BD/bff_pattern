<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\finance\AssetCoa;
use App\Models\finance\AssetHead;
use App\Models\finance\ClosingDepreciation;
use App\Models\finance\Coa;
use App\Models\finance\GeneralLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ClosingDepreciationController extends Controller
{
    /**
     * @OA\Get(
     *  path="/closing-depreciations",
     *  summary="Get the list of closing depreciation",
     *  tags={"Finance - Closing Depreciations"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $year  = Carbon::parse($request->period)->format('Y');
        $month = Carbon::parse($request->period)->format('m');

        $closing_depreciation = ClosingDepreciation::where('month', $month)->where('year', $year)->first();

        $data = [];

        if (!$closing_depreciation) {
            return ApiResponseClass::sendResponse($data, 'Not Yet Depreciated or Closed on Selected Period');
        } else {
            $day = Carbon::parse($request->period)->endOfMonth()->format('d');

            $set_date = $year . '-' . $month . '-' . $day;

            $asset_coa = AssetCoa::with(['toAssetHead.toAssetItem'])->get();


            foreach ($asset_coa as $key => $value) {
                $item = [];
                if ($value->toAssetHead) {
                    foreach ($value->toAssetHead as $key => $value2) {
                        if ($value2->toAssetItem) {
                            foreach ($value2->toAssetItem as $key => $value3) {
                                $first_date   = $value3->toAssetHead->tgl;
                                $current_date = Carbon::parse($request->period)->endOfMonth()->format('Y-m-d');

                                $dayDifference = Carbon::parse($first_date)->diffInDays(Carbon::parse($current_date));
                                $lifespan      = get_arrangement('lifespan');

                                $monthDifference = count_cut_off($first_date, $set_date);

                                $rate                = ($value3->toAssetHead->toAssetGroup->rate / 100);
                                $depreciation        = round(($value3->total * $rate) * (1 / 12), 0);
                                $depreciation_amount = ($depreciation * $monthDifference);
                                $gl                  = ($value3->total - $depreciation_amount);

                                if ($lifespan > 0) {
                                    $depreciation        = ($dayDifference < $lifespan ? 0 : $depreciation);
                                    $depreciation_amount = ($dayDifference < $lifespan ? 0 : $depreciation_amount);
                                    $gl                  = ($dayDifference < $lifespan ? 0 : $gl);
                                }

                                $item[] = [
                                    'asset_number'        => $value3->asset_number,
                                    'identity_number'     => $value3->identity_number,
                                    'name'                => $value3->toAssetHead->name,
                                    'date'                => $value3->toAssetHead->tgl,
                                    'qty'                 => $value3->qty,
                                    'price'               => $value3->price,
                                    'total'               => $value3->total,
                                    'group'               => $value3->toAssetHead->toAssetGroup->name,
                                    'rate'                => $value3->toAssetHead->toAssetGroup->rate . '%',
                                    'depreciation'        => $depreciation,
                                    'depreciation_amount' => $depreciation_amount,
                                    'gl'                  => $gl,
                                ];
                            }
                        }
                    }
                }

                $data[] = [
                    'group' => $value->name,
                    'item'  => $item,
                    'total_depreciation' => array_sum(array_column($item, 'depreciation_amount')),
                ];
            }

            return ApiResponseClass::sendResponse($data, 'Closing Entries Retrieved Successfully');
        }
    }

    /**
     * @OA\Post(
     *  path="/closing-depreciations",
     *  summary="Create a new closing depreciation",
     *  tags={"Finance - Closing Depreciations"},
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
            $current_date = Carbon::parse($request->period)->endOfMonth()->format('Y-m-d');
            $year         = Carbon::parse($request->period)->format('Y');
            $month        = Carbon::parse($request->period)->format('m');
            $day          = get_arrangement('cutoff_date');

            $exists = ClosingDepreciation::where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($exists) {
                return Response::json([
                    'success' => false,
                    'message' => 'Already Closed on Selected Period!',
                ], 400);
            }

            // cek period sebelum closing
            if (!$this->_checkPeriod($month, $year)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot skip period or closing on previous period!',
                ], 400);
            }

            $cut_off_date = $year . '-' . $month . '-' . $day;

            $transaction_number = generate_number('finance', 'closing_depreciations', 'transaction_number', 'AD');

            $asset_coa = AssetCoa::with(['toAssetHead.toAssetItem'])
                ->whereHas('toAssetHead.toAssetItem', function ($query) {
                    $query->where('disposal', '0');
                })->get();

            if ($asset_coa->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot Close, No Asset Found!',
                ], 404);
            }

            $closing = ClosingDepreciation::get();

            if ($closing->isEmpty()) {
                $current_period = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
                $exist = AssetHead::where('tgl', '<', $current_period)->exists();

                if ($exist) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot Close, There is some asset in the previous period that has not been closed!',
                    ], 400);
                }
            }

            $total_accumulated_depreciation = [];
            $total_expense_depreciation = [];
            $total_accumulated_amortisation = [];
            $total_expense_amortisation = [];

            $id_coa_adc = [];
            $id_coa_dec = [];

            $id_coa_aac = [];
            $id_coa_aec = [];

            foreach ($asset_coa as $key => $value) {
                $presense = '';
                $is_depreciable = '';

                if ($value->toAssetHead) {
                    foreach ($value->toAssetHead as $key => $value2) {
                        $presense       = $value2->toAssetCategory->presence;
                        $is_depreciable = $value2->toAssetCategory->is_depreciable;

                        if ($value2->toAssetItem) {
                            foreach ($value2->toAssetItem as $key => $value3) {
                                $first_date    = $value3->toAssetHead->tgl;

                                $dayDifference = Carbon::parse($first_date)->diffInDays(Carbon::parse($current_date));
                                $lifespan      = get_arrangement('lifespan');

                                $rate          = ($value3->toAssetHead->toAssetGroup->rate / 100);
                                $depreciation  = round(($value3->total * $rate) * (1 / 12), 0);

                                // hanya asset yang mengalami penyusutan yang dihitung depresiasinya
                                if ($is_depreciable == 1) {
                                    // cek jika tanggal perolehan lebih kecil dari tanggal cut off
                                    if ($first_date < $cut_off_date) {
                                        // pisah antara amortisasi
                                        if ($presense === 'intangible') {
                                            $id_coa_aac[] = $value->id_coa_acumulated;
                                            $id_coa_aec[] = $value->id_coa_expense;

                                            if ($lifespan === '0') {
                                                $total_accumulated_amortisation[$value->id_coa_acumulated][] = $depreciation;
                                                $total_expense_amortisation[$value->id_coa_expense][] = $depreciation;
                                            } else {
                                                $total_accumulated_amortisation[$value->id_coa_acumulated][] = ($dayDifference <= $lifespan ? 0 : $depreciation);
                                                $total_expense_amortisation[$value->id_coa_expense][] = ($dayDifference <= $lifespan ? 0 : $depreciation);
                                            }
                                        } else {
                                            // dan depresiasi
                                            $id_coa_adc[] = $value->id_coa_acumulated;
                                            $id_coa_dec[] = $value->id_coa_expense;

                                            if ($lifespan === '0') {
                                                $total_accumulated_depreciation[$value->id_coa_acumulated][] = $depreciation;
                                                $total_expense_depreciation[$value->id_coa_expense][] = $depreciation;
                                            } else {
                                                $total_accumulated_depreciation[$value->id_coa_acumulated][] = ($dayDifference <= $lifespan ? 0 : $depreciation);
                                                $total_expense_depreciation[$value->id_coa_expense][] = ($dayDifference <= $lifespan ? 0 : $depreciation);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $coa_adc = Coa::with(['toCoaBody.toCoaClasification'])->whereIn('id_coa', $id_coa_adc)->get();
            $coa_dec = Coa::with(['toCoaBody.toCoaClasification'])->whereIn('id_coa', $id_coa_dec)->get();

            $coa_aac = Coa::with(['toCoaBody.toCoaClasification'])->whereIn('id_coa', $id_coa_aac)->get();
            $coa_aec = Coa::with(['toCoaBody.toCoaClasification'])->whereIn('id_coa', $id_coa_aec)->get();

            $journal = [];

            //kumpul coa akumulasi depresiasi
            foreach ($coa_adc as $akumulasi_depresiasi) {
                $journal[] = [
                    'coa'   => $akumulasi_depresiasi->coa,
                    'type'  => $akumulasi_depresiasi->toCoaBody->toCoaClasification->normal_balance, //,//->coa->toCoaBody->toCoaClasification->normal_balance,
                    'value' => array_sum($total_accumulated_depreciation[$akumulasi_depresiasi->id_coa])
                ];
            }

            // kumpul coa beban depresiasi
            foreach ($coa_dec as $beban_depresiasi) {
                $journal[] = [
                    'coa'   => $beban_depresiasi->coa,
                    'type'  => $beban_depresiasi->toCoaBody->toCoaClasification->normal_balance,
                    'value' => array_sum($total_expense_depreciation[$beban_depresiasi->id_coa])
                ];
            }

            // kumpul coa akumulasi amortisasi
            foreach ($coa_aac as $akumulasi_amortisasi) {
                $journal[] = [
                    'coa'   => $akumulasi_amortisasi->coa,
                    'type'  => $akumulasi_amortisasi->toCoaBody->toCoaClasification->normal_balance,
                    'value' => array_sum($total_accumulated_amortisation[$akumulasi_amortisasi->id_coa])
                ];
            }

            // kumpul coa beban amortisasi
            foreach ($coa_aec as $beban_amortisasi) {
                $journal[] = [
                    'coa'   => $beban_amortisasi->coa,
                    'type'  => $beban_amortisasi->toCoaBody->toCoaClasification->normal_balance,
                    'value' => array_sum($total_expense_amortisation[$beban_amortisasi->id_coa])
                ];
            }

            foreach ($journal as $key => $value) {
                $general_ledger[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $current_date,
                    'coa'                => $value['coa'],
                    'type'               => $value['type'],
                    'value'              => $value['value'],
                    'description'        => 'Asset Depreciation',
                    'reference_number'   => $transaction_number,
                    'phase'              => 'acm',
                    'created_by'         => auth('api')->user()->id_users
                ];
            }

            //simpan closing depreciation
            $closing_depreciation = ClosingDepreciation::create([
                'transaction_number' => $transaction_number,
                'month'              => $month,
                'year'               => $year,
            ]);

            //simpan ke buku besar
            GeneralLedger::insert($general_ledger);

            ActivityLogHelper::log('finance:closing_depreciation', 1, [
                'description'   => 'Successfully closed the period ' . $request->period,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($closing_depreciation, 'Journal Entry Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:closing_depreciation', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *  path="/closing-depreciations/open",
     *  summary="Open Period Closing Depreciation",
     *  tags={"Finance - Closing Depreciations"},
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
            $check_closing_next_period = ClosingDepreciation::where('year', $year)->where('month', '>', $month)->first();

            if ($check_closing_next_period) {
                return Response::json([
                    'success' => false,
                    'message' => 'Cannot Open, There is some closing depreciation in the next period!',
                ], 400);
            }

            $check_closing_depreciation = ClosingDepreciation::where('year', $year)->where('month', $month)->first();

            if ($check_closing_depreciation) {
                $phase  = 'acm';

                GeneralLedger::where('transaction_number', $check_closing_depreciation->transaction_number)->where('phase', $phase)->delete();

                $check_closing_depreciation->delete();

                ActivityLogHelper::log('finance:closing_depreciation', 1, [
                    'description'   => 'Successfully opened the period ' . $request->period,
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_closing_depreciation, 'Closing Depreciation Opened Successfully');
            } else {
                return Response::json([
                    'success' => false,
                    'message' => 'Closing Depreciation Not Found',
                ], 400);
            }
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * Check if previous periods are closed
     *
     * @param int $month
     * @param int $year
     */
    private function _checkPeriod($month, $year)
    {
        $newClosing = Carbon::createFromDate($year, $month, 1);

        // Get the latest closed month
        $latest = ClosingDepreciation::orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        if (!$latest) {
            return true;
        }

        $latestDate = Carbon::createFromDate($latest->year, $latest->month, 1);

        // Check if new closing is before latest
        if ($newClosing->lt($latestDate)) {
            return false;
        }

        // Check if jumping more than 1 month
        $diff = $latestDate->diffInMonths($newClosing);
        if ($diff > 1) {
            return false;
        }

        return true;
    }
}
