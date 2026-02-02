<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\ExpenditureRequest;
use App\Http\Resources\finance\ExpenditureResource;
use App\Models\finance\Expenditure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ExpenditureController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/expenditures",
     *  summary="Get the list of expenditures",
     *  tags={"Finance - Expenditures"},
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
     *      name="canceled",
     *      in="query",
     *      description="Canceled",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Get(
     *  path="/expenditures/{type}",
     *  summary="Get the list of expenditures",
     *  tags={"Finance - Expenditures"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"bank", "cash", "petty_cash"}
     *      ),
     *  ),
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
    public function index(Request $request, $type = null)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $query = Expenditure::query();

        $query->with(['toKontak', 'toJournal']);

        $query->whereBetweenMonth($start_date, $end_date);

        if ($type) {
            $query->whereRecordType($type);
        }

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse(ExpenditureResource::collection($data), 'Expenditures Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/expenditures",
     *  summary="Create a new expenditure",
     *  tags={"Finance - Expenditures"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="integer",
     *                  description="Kontak ID"
     *              ),
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Expenditure Date"
     *              ),
     *              @OA\Property(
     *                  property="from_or_to",
     *                  type="string",
     *                  description="From or to"
     *              ),
     *              @OA\Property(
     *                  property="pay_type",
     *                  type="string",
     *                  description="Pay Type"
     *              ),
     *              @OA\Property(
     *                  property="record_type",
     *                  type="string",
     *                  description="Record Type"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total"
     *              ),
     *              @OA\Property(
     *                  property="in_ex_tax",
     *                  type="string",
     *                  description="In or Ex Tax (y, n, or o)"
     *              ),
     *              @OA\Property(
     *                  property="deposit",
     *                  type="string",
     *                  description="Deposit"
     *              ),
     *              @OA\Property(
     *                  property="deposit_total",
     *                  type="integer",
     *                  description="Deposit Total"
     *              ),
     *              @OA\Property(
     *                  property="dataBeban",
     *                  type="array",
     *                  description="Data Beban",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="coa",
     *                          type="string",
     *                          description="COA"
     *                      ),
     *                      @OA\Property(
     *                          property="amount",
     *                          type="integer",
     *                          description="Amount"
     *                      ),
     *                      @OA\Property(
     *                          property="posisi",
     *                          type="string",
     *                          description="Posisi"
     *                      ),
     *                  ),
     *              ),
     *              example={
     *                  "id_kontak": 1,
     *                  "id_journal": 1,
     *                  "reference_number": "12345",
     *                  "date": "2022-01-01",
     *                  "outgoing_to": "John Doe",
     *                  "pay_type": "c",
     *                  "record_type": "bank",
     *                  "description": "Expenditure description",
     *                  "total": 3000,
     *                  "in_ex_tax": "n",
     *                  "deposit": "down_payment",
     *                  "deposit_total": 1000,
     *                  "dataBeban": {{"coa": "D", "amount": 1000, "posisi": "y" }, {"coa": "D", "amount": 1000, "posisi": "y" }, {"coa": "D", "amount": 1000, "posisi": "y" }}
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ExpenditureRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generateFinNumber('expenditures', 'transaction_number', 'BKU');
            $transaction_number_dp = "DP-" . $transaction_number;

            if (isset($request->deposit)) {
                if ($request->deposit == 'down_payment') {
                    $result = _count_down_payment($request, get_arrangement('down_payment_adjustment_journal'));
                } else if ($request->deposit == 'advance_payment') {
                    $result = _count_advance_payment($request);
                }
            } else {
                $result = _count_journal($request, $transaction_number);
            }

            if ($result) {
                $general_ledger = [];

                foreach ($result as $key => $val) {
                    $general_ledger[] = [
                        'id_kontak'          => $request->id_kontak,
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $request->date,
                        'coa'                => $val['coa'],
                        'type'               => $val['type'],
                        'value'              => $val['value'],
                        'description'        => $request->description,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                    ];
                }

                insert_expenditure($request, $transaction_number, $request->reference_number);

                insert_general_ledger($general_ledger, $transaction_number, $request->reference_number);

                // untuk insert pada deposit
                if (isset($request->deposit)) {
                    if ($request->deposit == 'down_payment') {
                        _insert_down_payment($request, $transaction_number_dp);
                    } else if ($request->deposit == 'advance_payment') {
                        _insert_advance_payment($request, $transaction_number_dp);
                    }
                }

                // untuk update status pada invoice bill
                _update_invoice_bill($request->reference_number);

                ActivityLogHelper::log('finance:expenditure_create', 1, [
                    'finance:transaction_number' => $transaction_number
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($general_ledger, 'Expenditure Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:expenditure_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/expenditures",
     *  summary="Delete expenditure",
     *  tags={"Finance - Expenditures"},
     *  @OA\Parameter(
     *      name="transaction_number",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = $request->transaction_number;

            // check if transaction is closed
            if ($this->isTransactionClosed($transaction_number)) {
                return Response::json(['success' => false, 'message' => 'Cannot Delete, Transaction Already Closed !'], 400);
            } else {
                $check_transaction = Expenditure::where('transaction_number', $transaction_number)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                ActivityLogHelper::log('finance:expenditure_delete', 1, [
                    'finance:reference_number' => $transaction_number
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Expenditure Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:expenditure_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
