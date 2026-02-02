<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\FinanceController;
use App\Http\Requests\finance\TransactionFullRequest;
use App\Http\Resources\finance\TransactionFullResource;
use App\Models\finance\JournalAdjustment;
use App\Models\finance\TransactionFull;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class TransactionFullController extends FinanceController
{
    /**
     * @OA\Get(
     *  path="/transaction-fulls",
     *  summary="Get the list of transaction fulls",
     *  tags={"Finance - Transaction Full"},
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
     *  path="/transaction-fulls/{category}/{type}",
     *  summary="Get the list of transaction fulls",
     *  tags={"Finance - Transaction Full"},
     *  @OA\Parameter(
     *      name="category",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      ),
     *  ),
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
    public function index(Request $request, $category = null, $type = null)
    {
        $start_date = start_date_month($request->start_date);
        $end_date   = end_date_month($request->end_date);

        $query = TransactionFull::query();

        $query->whereBetweenMonth($start_date, $end_date);

        if ($category) {
            $query->whereCategory($category);
        }

        if ($type) {
            $query->whereRecordType($type);
        }

        if (isset($request->status)) {
            $query->whereStatus($request->status);
        }

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse(TransactionFullResource::collection($data), 'Transaction Full Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/transaction-fulls",
     *  summary="Create a new transaction full",
     *  tags={"Finance - Transaction Full"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="integer",
     *                  description="Contact ID"
     *              ),
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="id_invoice_bill",
     *                  type="integer",
     *                  description="Invoice Bill ID"
     *              ),
     *              @OA\Property(
     *                  property="invoice_number",
     *                  type="string",
     *                  description="Invoice Number"
     *              ),
     *              @OA\Property(
     *                  property="efaktur_number",
     *                  type="string",
     *                  description="EFaktur Number"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="string",
     *                  format="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="from_or_to",
     *                  type="string",
     *                  description="From or to"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="number",
     *                  format="double",
     *                  description="Total"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Category"
     *              ),
     *              @OA\Property(
     *                  property="record_type",
     *                  type="string",
     *                  description="Record Type"
     *              ),
     *              @OA\Property(
     *                  property="in_ex_tax",
     *                  type="string",
     *                  enum={"y", "n", "o"},
     *                  description="In or Ex Tax (y, n, or o)"
     *              ),
     *              @OA\Property(
     *                  property="dataBeban",
     *                  type="array",
     *                  description="Data Beban",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(
     *                          property="coa",
     *                          type="string",
     *                          description="COA"
     *                      ),
     *                      @OA\Property(
     *                          property="amount",
     *                          type="number",
     *                          format="double",
     *                          description="Amount"
     *                      ),
     *                      @OA\Property(
     *                          property="posisi",
     *                          type="string",
     *                          description="Posisi"
     *                      )
     *                  )
     *              ),
     *              required={"id_journal", "invoice_number", "date", "total", "category", "record_type"},
     *              example={
     *                  "id_kontak": 1,
     *                  "id_journal": 1,
     *                  "id_invoice_bill": 1,
     *                  "invoice_number": "INV-001",
     *                  "efaktur_number": "EF-001",
     *                  "date": "2020-01-01",
     *                  "from_or_to": "from",
     *                  "total": 1000,
     *                  "description": "description",
     *                  "category": "penerimaan",
     *                  "record_type": "bank",
     *                  "in_ex_tax": "n",
     *                  "dataBeban": {
     *                      {"coa": "D", "amount": 1000, "posisi": "y"},
     *                      {"coa": "D", "amount": 1000, "posisi": "y"},
     *                      {"coa": "D", "amount": 1000, "posisi": "y"}
     *                  }
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(
     *      response=201,
     *      description="Transaction Full Created Successfully"
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TransactionFullRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generateFinNumber('transaction_full', 'transaction_number', 'FU');

            $result = _count_journal($request, $transaction_number);

            // mau diperjelas terkait ppn jika jurnal memiliki ppn
            // $hasPpn = in_array('y', array_column($result, 'ppn'), true);

            // $ppnValues = array_column(
            //     array_filter($result, fn($row) => $row['ppn'] === 'y'),
            //     'value'
            // );

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

                // mau diperjelas terkait ppn jika jurnal memiliki ppn
                // if ($request->in_ex_tax === 'y' && $hasPpn) {
                //     $request->value = $request->total + array_sum($ppnValues);
                // } else {
                $request->merge([
                    'value' => $request->total,
                ]);
                // }

                insert_transaction_full($request, $transaction_number);

                insert_general_ledger($general_ledger, $transaction_number, $transaction_number);

                ActivityLogHelper::log('finance:transaction_full_create', 1, [
                    'date'                       => $request->date,
                    'finance:transaction_number' => $transaction_number,
                    'finance:from_or_to'         => $request->from_or_to,
                    'total'                      => $request->total,
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($general_ledger, 'Transaction Full Created Successfully');
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_full_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/transaction-fulls",
     *  summary="Delete transaction full detail",
     *  tags={"Finance - Transaction Full"},
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
                $check_transaction = TransactionFull::where('transaction_number', $transaction_number)->first();

                $check_asset_head = _check_transaction($check_transaction->id_transaction_full, 'asset_head');

                if (!$check_asset_head) {
                    return Response::json(['success' => false, 'message' => 'Transaction is used for assets !'], 400);
                }

                $check_transaction->status = 'deleted';
                $check_transaction->save();

                del_file($check_transaction->attachment, 'transaction_full/');

                delete_general_ledger($transaction_number);

                ActivityLogHelper::log('finance:transaction_full_delete', 1, [
                    'transaction_number' => $transaction_number
                ]);

                DB::connection('finance')->commit();

                return ApiResponseClass::sendResponse($check_transaction, 'Transaction Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_full_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/transaction-fulls/details",
     *  summary="Get the list of detail transaction fulls",
     *  tags={"Finance - Transaction Full"},
     *  @OA\Parameter(
     *      name="invoice_number",
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
            'invoice_number' => 'required|string',
        ]);

        $invoice_number = $request->invoice_number;

        if (!$invoice_number) {
            return ApiResponseClass::throw('Invoice Number is required', 400);
        }

        $transaction_fulls = TransactionFull::where('invoice_number', $invoice_number)->where('status', 'valid')->get();

        $result  = [];
        $details = [];
        $no      = 0;

        foreach ($transaction_fulls as $key => $value) {
            $details[] = [
                'no'                 => $no,
                'id_transaction'     => $value->id_transaction_full,
                'efaktur_number'     => $value->efaktur_number,
                'transaction_number' => $value->transaction_number,
                'invoice_number'     => $value->invoice_number,
                'description'        => $value->description,
                'date'               => $value->date,
                'value'              => (int) $value->value,
            ];
            $no++;
        }

        $result = [
            'invoice_number' => $invoice_number,
            'details'        => $details,
        ];

        return ApiResponseClass::sendResponse($result, 'Transaction Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/transaction-fulls/adjustment/{category}",
     *  summary="Get the list of transaction fulls",
     *  tags={"Finance - Transaction Full"},
     *  @OA\Parameter(
     *      name="category",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          enum={"penerimaan", "pengeluaran"}
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function adjustment($category = null)
    {
        $data = JournalAdjustment::all();

        $reference_number = [];
        foreach ($data as $key => $value) {
            if ($value->reference_number) {
                $reference_number[] = $value->reference_number;
            }
        }

        $query = TransactionFull::query();

        if ($category) {
            $query->whereCategory($category);
        }

        $query->whereNotIn('transaction_number', $reference_number);

        $data = $query->orderBy('date', 'asc')->get();

        return ApiResponseClass::sendResponse(TransactionFullResource::collection($data), 'Transaction Full Retrieved Successfully');
    }
}
