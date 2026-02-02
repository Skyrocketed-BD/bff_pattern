<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\BankReconciliationRequest;
use App\Models\finance\Coa;
use App\Models\finance\GeneralLedger;
use App\Models\finance\BankReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class BankReconciliationController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/bank-reconciliation",
     *  summary="Get the list of bank reconciliation",
     *  tags={"Finance - Bank Reconciliation"},
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

        $query = BankReconciliation::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Bank Reconciliation Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/bank-reconciliation",
     *  summary="Create a new bank reconciliation",
     *  tags={"Finance - Bank Reconciliation"},
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
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="id_coa_bank",
     *                  type="integer",
     *                  description="ID COA Bank"
     *              ),
     *              @OA\Property(
     *                  property="bank_interest",
     *                  type="integer",
     *                  description="Ammount Bank Interest"
     *              ),
     *              @OA\Property(
     *                  property="bank_interest_tax",
     *                  type="integer",
     *                  description="Ammount Bank Interest Tax"
     *              ),
     *              required={"date", "description", "id_coa_bank", "bank_interest", "bank_interest_tax"},
     *              example={
     *                  "date": "2022-01-01",
     *                  "description": "description",
     *                  "id_coa_bank": 1,
     *                  "bank_interest": 10000,
     *                  "bank_interest_tax": 10000,
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(BankReconciliationRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generate_number('finance', 'bank_reconciliation', 'transaction_number', 'BR');

            $bank_reconciliation                     = new BankReconciliation();
            $bank_reconciliation->transaction_number = $transaction_number;
            $bank_reconciliation->id_coa_bank        = $request->id_coa_bank;
            $bank_reconciliation->date               = $request->date;
            $bank_reconciliation->description        = $request->description;
            $bank_reconciliation->bank_interest      = $request->bank_interest;
            $bank_reconciliation->bank_interest_tax  = $request->bank_interest_tax;

            $id_coa_bank_interest     = get_arrangement('bank_interest_coa');      // pendapatan bunga
            $id_coa_bank_interest_tax = get_arrangement('bank_interest_tax_coa');  // beban bunga pajak
            $id_coa_bank              = $request->id_coa_bank;                     // bank

            $coa_bank_interest     = Coa::whereIdCoa($id_coa_bank_interest)->first();
            $coa_bank_interest_tax = Coa::whereIdCoa($id_coa_bank_interest_tax)->first();
            $coa_bank              = Coa::whereIdCoa($id_coa_bank)->first();

            $bank_interest = $request->bank_interest;
            $bank_interest_tax = $request->bank_interest_tax;

            $journal = [
                [
                    'coa'   => $coa_bank->coa,
                    'type'  => 'D',
                    'value' => $bank_interest,
                    'ref'   => '1'
                ],
                [
                    'coa'   => $coa_bank_interest->coa,
                    'type'  => 'K',
                    'value' => $bank_interest,
                    'ref'   => '1'
                ],

                [
                    'coa'   => $coa_bank_interest_tax->coa,
                    'type'  => 'D',
                    'value' => $bank_interest_tax,
                    'ref'   => '2'
                ],
                [
                    'coa'   => $coa_bank->coa,
                    'type'  => 'K',
                    'value' => $bank_interest_tax,
                    'ref'   => '2'
                ],
            ];

            foreach ($journal as $key => $value) {
                $general_ledger[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $request->date,
                    'coa'                => $value['coa'],
                    'type'               => $value['type'],
                    'value'              => $value['value'],
                    'description'        => $request->description,
                    'reference_number'   => $transaction_number . ' - ' . $value['ref'],
                    'phase'              => 'opr',
                    'created_by'         => auth('api')->user()->id_users
                ];
            }

            $total = $bank_interest - $bank_interest_tax;
            $total = abs($total);

            $bank_reconciliation->value = $total;
            $bank_reconciliation->save();

            GeneralLedger::insert($general_ledger);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:bank_reconciliation_create', 1, [
                'finance:coa_bank'           => Coa::find($request->id_coa_bank)->name,
                'finance:bank_interest'      => $bank_interest,
                'finance:bank_interest_tax'  => $bank_interest_tax,
                'finance:transaction_number' => $transaction_number
            ]);

            return ApiResponseClass::sendResponse($bank_reconciliation, 'Transaction Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_reconciliation_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/bank-reconciliation/{no_transaction}",
     *  summary="Delete bank reconciliation",
     *  tags={"Finance - Bank Reconciliation"},
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
                $check_transaction = BankReconciliation::where('transaction_number', $no_transaction)->first();
                $check_transaction->status = 'deleted';
                $check_transaction->save();

                delete_general_ledger($no_transaction);

                ActivityLogHelper::log('finance:bank_reconciliation_delete', 1, [
                    'finance:transaction_number' => $no_transaction,
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:bank_reconciliation_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/bank-reconciliation/details",
     *  summary="Get the list of bank reconciliation details",
     *  tags={"Finance - Bank Reconciliation"},
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
    public function details(Request $request)
    {
        $transaction_number = $request->transaction_number;

        $gl = GeneralLedger::where('transaction_number', $transaction_number)->orWhere('reference_number', $transaction_number)->get();

        if ($gl->count() == 0) {
            return ApiResponseClass::sendResponse($gl, 'Transaction Number Not Found!');
        } else {
            $get_general_ledger = GeneralLedger::where('transaction_number', $transaction_number)
                ->select('reference_number')
                ->groupBy('reference_number')
                ->get();

            $new_key = [
                'bunga_bank',
                'pajak_bank',
            ];
            
            foreach ($get_general_ledger as $key => $value) {
                $data = GeneralLedger::where('reference_number', $value->reference_number)->get();

                $record[$new_key[$key]] = [];
                $debit[$new_key[$key]] = [];
                $credit[$new_key[$key]] = [];

                foreach ($data as $key2 => $value2) {
                    $val_debit  = 0;
                    $val_credit = 0;

                    if ($value2->type === 'K') {
                        $credit[$new_key[$key]][] = $value2->value;
                        $val_credit = $value2->value;
                    } else {
                        $debit[$new_key[$key]][] = $value2->value;
                        $val_debit = $value2->value;
                    }

                    $record[$new_key[$key]][] = [
                        'id_general_ledger' => $value2->id_general_ledger,
                        'date'              => $value2->date,
                        'coa'               => $value2->toCoa->name,
                        'type'              => $value2->type,
                        'debit'             => (float) $val_debit,
                        'credit'            => (float) $val_credit,
                        'value'             => $value2->value,
                        'description'       => $value2->description,
                    ];
                }

                $result[$new_key[$key]]['record'] = $record[$new_key[$key]];
                $result[$new_key[$key]]['debit'] = array_sum($debit[$new_key[$key]]);
                $result[$new_key[$key]]['credit'] = array_sum($credit[$new_key[$key]]);
            }

            return ApiResponseClass::sendResponse($result, 'General Ledger Retrieved Successfully');
        }
    }
}
