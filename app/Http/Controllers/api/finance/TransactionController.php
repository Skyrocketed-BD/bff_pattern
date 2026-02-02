<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\TransactionRequest;
use App\Http\Resources\finance\TransactionResource;
use App\Models\finance\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class TransactionController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/transactions",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
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
     *
     * @OA\Get(
     *  path="/transactions/{type}",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="type",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      )
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
     */
    public function index(Request $request, $type = null)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $query = Transaction::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if ($type) {
            $query->whereCategory($type);
        }

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse(TransactionResource::collection($data), 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/transactions",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="string",
     *                  description="From or to"
     *              ),
     *              @OA\Property(
     *                  property="id_invoice_bill",
     *                  type="integer",
     *                  description="Invoice Bill ID"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              @OA\Property(
     *                  property="from_or_to",
     *                  type="string",
     *                  description="From or to"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="text",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total"
     *              ),
     *              @OA\Property(
     *                  property="in_ex_tax",
     *                  type="string",
     *                  description="In or Ex Tax"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Category (penerimaan, pengeluaran)"
     *              ),
     *              example={
     *                  "id_kontak": 1,
     *                  "id_journal": 1,
     *                  "id_invoice_bill": 1,
     *                  "reference_number": "INV-001",
     *                  "from_or_to": "Naruto",
     *                  "description": "Transaction description",
     *                  "date": "2022-01-01",
     *                  "total": 3000,
     *                  "in_ex_tax": "n",
     *                  "category": "penerimaan"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TransactionRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generateFinNumber('transaction', 'transaction_number', 'INV');

            $result = _count_journal($request, $transaction_number);

            $hasPpn = in_array('y', array_column($result, 'ppn'), true);

            $ppnValues = array_column(
                array_filter($result, fn($row) => $row['ppn'] === 'y'),
                'value'
            );

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

                if ($request->in_ex_tax === 'y' && $hasPpn) {
                    $request->merge([
                        'value' => $request->total + array_sum($ppnValues),
                    ]);
                } else {
                    $request->merge([
                        'value' => $request->total,
                    ]);
                }

                insert_transaction($request, $transaction_number, $request->reference_number);

                insert_general_ledger($general_ledger, $transaction_number, $request->reference_number);

                ActivityLogHelper::log('finance:transaction_create', 1, [
                    'date'                     => $request->date,
                    'finance:reference_number' => $request->reference_number,
                    'total'                    => $request->value,
                    'finance:from_or_to'       => $request->from_or_to
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($general_ledger, 'Transaction Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/transactions",
     *  summary="Delete transaction",
     *  tags={"Finance - Transaction"},
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
                $check_transaction = Transaction::where('transaction_number', $transaction_number)->first();

                $check_asset_head = _check_transaction($check_transaction->id_transaction, 'asset_head');

                if (!$check_asset_head) {
                    return Response::json(['success' => false, 'message' => 'Transaction is used for assets !'], 400);
                }

                $check_transaction->status = 'deleted';
                $check_transaction->save();

                del_file($check_transaction->attachment, 'transaction/');

                delete_general_ledger($transaction_number);

                ActivityLogHelper::log('finance:transaction_delete', 1, [
                    'transaction_number' => $transaction_number
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_delete', 0, ['transaction_number' => $transaction_number]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/transactions/details",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="ref",
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
        $request->validate([
            'ref' => 'required|string',
        ]);

        $reference_number = $request->ref;

        if (!$reference_number) {
            return ApiResponseClass::throw('Ref is required', 400);
        }

        $transaction = Transaction::where('reference_number', $reference_number)->where('status', 'valid')->orderBy('date', 'desc')->get();

        $result = [];
        foreach ($transaction as $key => $value) {
            $detail   = [];
            $no       = 0;
            $detail[] = [
                'no'                 => $no,
                'id_transaction'     => $value->id_transaction,
                'transaction_number' => $value->transaction_number,
                'description'        => $value->description,
                'date'               => $value->date,
                'value'              => (int) $value->value,
            ];
            $no++;

            if ($value->toReceipts) {
                foreach ($value->toReceipts->where('status', 'valid')->sortBy('date') as $key => $val) {
                    $detail[] = [
                        'no'                 => $no++,
                        'id_transaction'     => '',
                        'id_receipt'         => $val->id_receipt,
                        'journal'            => $val->toJournal->name,
                        'reference_number'   => $val->reference_number,
                        'transaction_number' => $val->transaction_number,
                        'date'               => $val->date,
                        'receive_from'       => $val->receive_from,
                        'pay_type'           => Config::get('constants.pay_type')[$val->pay_type],
                        'value'              => $val->value,
                        'description'        => $val->description,
                        'canceled'           => $val->canceled,
                    ];
                }
            }

            if ($value->toExpenditure) {
                foreach ($value->toExpenditure->where('status', 'valid')->sortBy('date') as $key => $val) {
                    $detail[] = [
                        'no'                 => $no++,
                        'id_transaction'     => '',
                        'id_expenditure'     => $val->id_expenditure,
                        'journal'            => $val->toJournal->name,
                        'reference_number'   => $val->reference_number,
                        'transaction_number' => $val->transaction_number,
                        'date'               => $val->date,
                        'outgoing_to'        => $val->outgoing_to,
                        'pay_type'           => Config::get('constants.pay_type')[$val->pay_type],
                        'value'              => $val->value,
                        'description'        => $val->description,
                        'canceled'           => $val->canceled,
                    ];
                }
            }

            $result = [
                'id_transaction'     => $value->id_transaction,
                'id_kontak'          => $value->id_kontak,
                'id_journal'         => $value->id_journal,
                'category'           => $value->category,
                'journal'            => $value->toJournal->name,
                'transaction_number' => $value->transaction_number,
                'reference_number'   => $value->reference_number,
                'from_or_to'         => $value->from_or_to,
                'description'        => $value->description,
                'date'               => $value->date,
                'value'              => (int) $value->value,
                'canceled'           => $value->canceled,
                'detail'             => $detail
            ];
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/transactions/filter/{category}",
     *  summary="Get the list of transactions",
     *  tags={"Finance - Transaction"},
     *  @OA\Parameter(
     *      name="category",
     *      in="path",
     *      required=true,
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function filter($category)
    {
        $data = Transaction::with([
            'toKontak',
            'toTransactionTerm' => function ($query) {
                $query->whereNull('id_receipt');
            },
        ])->where('category', $category)->where('status', 'valid')->orderBy('id_transaction', 'desc')->get();

        $result = [];
        foreach ($data as $key => $value) {
            $category = $value->category;
            $terbayar = 0;

            if ($category === 'penerimaan') {
                $terbayar = $value->toReceipts->where('status', 'valid')->sum('value');
            }

            if ($category === 'pengeluaran') {
                $terbayar = $value->toExpenditure->where('status', 'valid')->sum('value');
            }

            $sisa = ($value->value - $terbayar);

            if ($sisa !== 0) {
                $result[] = [
                    'id_transaction'       => $value->id_transaction,
                    'id_kontak'            => $value->id_kontak,
                    'id_journal'           => $value->id_journal,
                    'category'             => $value->category,
                    'transaction_category' => $value->category,
                    'journal'              => $value->toJournal->name,
                    'transaction_number'   => $value->transaction_number,
                    'reference_number'     => $value->reference_number,
                    'from_or_to'           => $value->toKontak->name ?? null,
                    'description'          => $value->description,
                    'date'                 => $value->date,
                    'value'                => (int) $value->value,
                    'terbayar'             => (int) $terbayar,
                    'sisa'                 => (int) $sisa,
                    'transaction_term'     => $value->toTransactionTerm,
                ];
            }
        }

        return ApiResponseClass::sendResponse($result, 'Transaction Retrieved Successfully');
    }
}
