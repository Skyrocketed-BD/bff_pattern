<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\JournalAdjustmentRequest;
use App\Models\finance\GeneralLedger;
use App\Models\finance\JournalAdjustment;
use App\Models\finance\JournalAdjustmentSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class JournalAdjustmentController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/journal-adjustment",
     *  summary="Get the list of adjustment journal",
     *  tags={"Finance - Journal Adjustment"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date of data entry",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="status",
     *      in="query",
     *      description="Status",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $data = JournalAdjustment::whereBetweenMonth($start_date, $end_date)->get();

        return ApiResponseClass::sendResponse($data, 'Adjustment Journal Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/journal-adjustment",
     *  summary="Create a new journal adjustment",
     *  tags={"Finance - Journal Adjustment"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="duration",
     *                  type="string",
     *                  description="Duration"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="coa",
     *                  type="array",
     *                  @OA\Items(type="integer", example=1),
     *                  description="COA Code"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="array",
     *                  @OA\Items(type="string", example="D"),
     *                  description="Type"
     *              ),
     *              @OA\Property(
     *                  property="amount",
     *                  type="array",
     *                  @OA\Items(type="integer", example=10000),
     *                  description="Amount"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              required={"date", "duration", "description", "coa", "type", "amount", "reference_number"},
     *              example={
     *                  "date": "2022-01-01",
     *                  "description": "description",
     *                  "duration": 12,
     *                  "reference_number": "REF-001",
     *                  "coa": {1, 2},
     *                  "type": {"D", "K"},
     *                  "amount": {20000, 20000}
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(JournalAdjustmentRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'journal_adjustments', 'transaction_number', 'JP');

            $journal_adjustment                     = new JournalAdjustment();
            $journal_adjustment->transaction_number = $transaction_number;
            $journal_adjustment->date               = $request->date;
            $journal_adjustment->description        = $request->description;
            $journal_adjustment->transaction_type   = $request->transaction_type;
            $journal_adjustment->duration           = $request->duration;

            $remaining  = 0;
            if ($request->transaction_type == 'one_time') {
                $remaining = 0;
            } else {
                $remaining = $request->duration;
                $journal_adjustment->reference_number = $request->reference_number;
            }

            $journal_adjustment->remaining  = $remaining;

            $id_coa = $request->id_coa;
            $type   = $request->type;
            $amount = $request->amount;

            $debit  = [];
            $credit = [];

            for ($i = 0; $i < count($id_coa); $i++) {
                if ($type[$i] === 'K') {
                    $credit[] = $amount[$i];
                } else {
                    $debit[] = $amount[$i];
                }
            }

            $sum_debit  = array_sum($debit);
            $sum_credit = array_sum($credit);
            $balance    = ($sum_debit - $sum_credit);
            $value      = $sum_credit;

            if ($balance != 0) {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            } else {
                $journal_adjustment->value = $value;
                $journal_adjustment->save();

                $serial_number = 1;

                foreach ($id_coa as $key => $value) {
                    $data[] = [
                        'id_journal_adjustment' => $journal_adjustment->id_journal_adjustment,
                        'id_coa'                => $id_coa[$key],
                        'type'                  => $type[$key],
                        'value'                 => $amount[$key],
                        'serial_number'         => $serial_number++,
                        'created_by'            => auth('api')->user()->id_users
                    ];
                }

                JournalAdjustmentSet::insert($data);

                // begin:: insert ke GL
                if ($request->transaction_type == 'one_time') {
                    $coa_adjustment = JournalAdjustment::with(['toJournalAdjustmentSet'])
                        ->where('id_journal_adjustment', $journal_adjustment->id_journal_adjustment)
                        ->get();

                    foreach ($coa_adjustment as $key => $value) {
                        foreach ($value->toJournalAdjustmentSet as $key => $value2) {
                            $adjustment[] = [
                                'transaction_number' => $transaction_number,
                                'date'               => $request->date,
                                'coa'                => $value2->toCoa->coa,
                                'type'               => $value2->type,
                                'value'              => $value2->value,
                                'description'        => 'Jurnal Penyesuaian | ' . $request->description,
                                'reference_number'   => $transaction_number,
                                'phase'              => 'opr',
                                'created_by'         => auth('api')->user()->id_users
                            ];
                        }
                    }

                    GeneralLedger::insert($adjustment);
                }
                // end:: insert ke GL

                 ActivityLogHelper::log('finance:journal_adjustment_create', 1, [
                    'finance:transaction_number' => $transaction_number,
                    'date'                       => $request->date,
                    'value'                      => $journal_adjustment->value
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($data, 'Journal Adjustment Created Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:journal_adjustment_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/journal-adjustment/{no_transaction}",
     *  summary="Delete journal adjustment",
     *  tags={"Finance - Journal Adjustment"},
     *  @OA\Parameter(
     *      name="no_transaction",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($no_transaction)
    {
        DB::connection('finance')->beginTransaction();
        try {
            // check if transaction is closed
            if ($this->isTransactionClosed($no_transaction)) {
                return Response::json(['success' => false, 'message' => 'Cannot Delete, Transaction Already Closed !'], 400);
            } else {
                $check_transaction = JournalAdjustment::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);
                
                ActivityLogHelper::log('finance:journal_adjustment_delete', 1, ['finance:reference_number' => $no_transaction]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:journal_adjustment_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/journal-adjustment/search-by-set",
     *  summary="Get the list of adjustment journal",
     *  tags={"Finance - Journal Adjustment"},
     *  @OA\Parameter(
     *      name="type",
     *      in="query",
     *      description="Type",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function searchByIdSet(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer',
            ], [
                'id.required' => 'ID Set is required',
                'id.integer'  => 'ID Set must be an integer',
            ]);
        } catch (ValidationException $e) {
            return ApiResponseClass::throw($e->validator->errors()->first(), 422);
        }

        $id = $request->id;

        $data = JournalAdjustmentSet::where('id_journal_adjustment', $id)
            ->get();

        if ($data->isEmpty()) {
            return ApiResponseClass::sendResponse([], 'No records found for the specified ID');
        }

        $responseData = $data->map(function ($value) {
            return [
                "id_coa"         => $value->id_coa,
                "coa_number"     => $value->toCoa->coa,
                "name"           => $value->toCoa->name,
                "type"           => $value->type,
                "value"          => $value->value
            ];
        });

        // Return the filtered data
        return ApiResponseClass::sendResponse($responseData, 'Filtered Journal Set Retrieved Successfully');
    }
}
