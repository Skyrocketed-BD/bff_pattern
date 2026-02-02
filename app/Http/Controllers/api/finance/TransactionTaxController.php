<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\finance\GeneralLedger;
use App\Models\finance\GeneralLedgerLog;
use App\Models\finance\TaxRate;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use App\Models\finance\TransactionTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class TransactionTaxController extends Controller
{
    /**
     * @OA\Get(
     *  path="/transaction-tax/{type}",
     *  summary="Get the list of transaction tax",
     *  tags={"Finance - Transaction Tax"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"outstanding", "full"}
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     *
     * @OA\Get(
     *  path="/transaction-tax/{type}/{id}",
     *  summary="Get the list of transaction tax",
     *  tags={"Finance - Transaction Tax"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"outstanding", "full"}
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index($type, $id = null)
    {
        if ($id == null) {
            $get_tax_rate = TaxRate::all();
        } else {
            $get_tax_rate = TaxRate::where('id_tax', $id)->get();
        }

        $id_tax = [];
        if ($type == 'outstanding') {
            $query = Transaction::query();
            $query->with(['toTransactionTax']);

            foreach ($get_tax_rate as $key => $value) {
                $id_tax[] = $value->id_tax;

                $query->where('date', '>=', $value->effective_date);
            }

            $get = $query->where('status', 'valid')->get();
        }

        if ($type == 'full') {
            $query = TransactionFull::query();
            $query->with(['toTransactionTax']);

            foreach ($get_tax_rate as $key => $value) {
                $id_tax[] = $value->id_tax;

                $query->where('date', '>=', $value->effective_date);
            }

            $get = $query->where('status', 'valid')->get();
        }

        $data = [];
        if (!empty($get)) {
            foreach ($get as $key => $value) {
                if ($value->toTransactionTax) {
                    foreach ($value->toTransactionTax as $key2 => $value2) {
                        if (in_array($value2->id_tax, $id_tax) && $value2->rate != $value2->toTaxRate->rate) {
                            $data[] = [
                                'id_transaction_tax' => $value2->id_transaction_tax,
                                'transaction_number' => $value->transaction_number,
                                'date'               => $value->date,
                                'from_or_to'         => $value->from_or_to,
                                'value'              => $value->value,
                                'description'        => $value->description,
                            ];
                        }
                    }
                }
            }
        }

        return ApiResponseClass::sendResponse($data, 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/transaction-tax/{type}/{transaction_number}",
     *  summary="Update transaction tax",
     *  tags={"Finance - Transaction Tax"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"transaction", "transaction_full"}
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="transaction_number",
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
    public function update(Request $request, $type)
    {        
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = $request->transaction_number;

            if ($type == 'outstanding') {
                $get = Transaction::with(['toTransactionTax'])->where('transaction_number', $transaction_number)->first();
            } elseif ($type == 'full') {
                $get = TransactionFull::with(['toTransactionTax'])->where('transaction_number', $transaction_number)->first();
            }

            $transaction_tax = $get->toTransactionTax;

            $new_tax = [];
            foreach ($transaction_tax as $key => $value) {
                if ($value->rate != $value->toTaxRate->rate || $value->toTaxRate->id_tax === 1) {
                    $new_tax[$value->toCoa->coa] = [
                        'transaction_number' => $transaction_number,
                        'id_coa'             => $value->toCoa->id_coa,
                        'id_tax'             => $value->toTaxRate->id_tax,
                        'id_tax_rate'        => $value->toTaxRate->id_tax_rate,
                        'rate'               => $value->toTaxRate->rate
                    ];
                }
            }

            $request = (object) [
                'id_journal' => $get->id_journal,
                'in_ex_tax'  => $get->in_ex,
                'total'      => $get->value,
            ];

            $result = _count_journal($request);

            if ($result) {
                $general_ledger = [];

                foreach ($result as $key => $val) {
                    $general_ledger[] = [
                        'id_journal'         => $request->id_journal,
                        'transaction_number' => $transaction_number,
                        'date'               => $get->date,
                        'coa'                => $val['coa'],
                        'type'               => $val['type'],
                        'value'              => $val['value'],
                        'description'        => $get->description,
                        'reference_number'   => $transaction_number,
                        'phase'              => 'opr',
                        'calculated'         => $val['calculated'],
                    ];
                }

                $check_general_ledger = GeneralLedger::where('transaction_number', $transaction_number)->get();

                $count_general_ledger_logs = DB::connection('finance')
                    ->table('general_ledger_logs')
                    ->select('transaction_number')
                    ->where('transaction_number', $transaction_number)
                    ->orWhere('reference_number', $transaction_number)
                    ->groupBy('transaction_number', 'revision')
                    ->get()
                    ->count();

                $general_ledger_logs = [];
                foreach ($check_general_ledger as $key => $row) {
                    $general_ledger_logs[] = [
                        'transaction_number' => $transaction_number,
                        'date'               => $row->date,
                        'coa'                => $row->coa,
                        'type'               => $row->type,
                        'value'              => $row->value,
                        'description'        => $row->description,
                        'reference_number'   => $row->reference_number,
                        'revision'           => $count_general_ledger_logs + 1
                    ];
                }

                TransactionTax::where('transaction_number', $transaction_number)->delete();

                delete_general_ledger($transaction_number);
                
                GeneralLedgerLog::insert($general_ledger_logs);

                insert_general_ledger($general_ledger, $transaction_number, $get->reference_number ?? $transaction_number);

                TransactionTax::insert($new_tax);

                ActivityLogHelper::log('finance:transaction_tax_update', 1, [
                    'finance:transaction_number' => $transaction_number
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($general_ledger, 'Tax Updated Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_tax_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
