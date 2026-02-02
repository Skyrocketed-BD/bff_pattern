<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\TransactionTermRequest;
use App\Http\Resources\finance\TransactionTermResource;
use App\Models\finance\InvoiceBill;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionTerm;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class TransactionTermController extends Controller
{
    /**
     * @OA\Get(
     *  path="/transaction-terms",
     *  summary="Get the list of transaction terms",
     *  tags={"Finance - Transaction Term"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $query = TransactionTerm::query();

        $data = $query->orderBy('id_transaction_term', 'asc')->get();

        return ApiResponseClass::sendResponse(TransactionTermResource::collection($data), 'Transaction Term Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/transaction-terms",
     *  summary="Create a transaction term",
     *  tags={"Finance - Transaction Term"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_invoice_bill",
     *                  type="string",
     *                  description="Invoice Bill ID"
     *              ),
     *              @OA\Property(
     *                  property="id_transaction",
     *                  type="integer",
     *                  description="Transaction ID"
     *              ),
     *              @OA\Property(
     *                  property="nama",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="percent",
     *                  type="integer",
     *                  description="Percent"
     *              ),
     *              @OA\Property(
     *                  property="value_ppn",
     *                  type="integer",
     *                  description="Value PPN"
     *              ),
     *              @OA\Property(
     *                  property="value_pph",
     *                  type="integer",
     *                  description="Value PPH"
     *              ),
     *              @OA\Property(
     *                  property="value_percent",
     *                  type="integer",
     *                  description="Value PPH"
     *              ),
     *              @OA\Property(
     *                  property="value_deposit",
     *                  type="integer",
     *                  description="Value Deposit"
     *              ),
     *              @OA\Property(
     *                  property="deposit",
     *                  type="string",
     *                  description="Deposit"
     *              ),
     *              required={"id_invoice_bill", "nama", "date", "percent", "value_ppn", "value_pph", "value_percent", "value_deposit", "deposit"},
     *              example={
     *                  "id_invoice_bill": 1,
     *                  "nama": "Termin 1",
     *                  "date": "2022-01-01",
     *                  "percent": 10,
     *                  "value_ppn": 100000,
     *                  "value_pph": 100000,
     *                  "value_percent": 100000,
     *                  "value_deposit": 100000,
     *                  "deposit": "down_payment"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TransactionTermRequest $request)
    {
        DB::connection('finance')->beginTransaction();

        try {
            $transaction_number = generateFinNumber('transaction', 'transaction_number', 'INV');

            $invoice_bill = InvoiceBill::whereIdInvoiceBill($request->id_invoice_bill)->first();

            // jika belum actual
            if (!$invoice_bill->is_actual) {
                $result = _count_journal($invoice_bill, $transaction_number);

                if ($result) {
                    $general_ledger = [];

                    foreach ($result as $key => $val) {
                        $general_ledger[] = [
                            'id_kontak'          => $invoice_bill->id_kontak,
                            'id_journal'         => $invoice_bill->id_journal,
                            'transaction_number' => $transaction_number,
                            'date'               => date('Y-m-d'),
                            'coa'                => $val['coa'],
                            'type'               => $val['type'],
                            'value'              => $val['value'],
                            'description'        => $invoice_bill->description,
                            'reference_number'   => $transaction_number,
                            'phase'              => 'opr',
                            'calculated'         => $val['calculated'],
                        ];
                    }

                    $transaction = insert_transaction($invoice_bill, $transaction_number, $invoice_bill->reference_number);

                    insert_general_ledger($general_ledger, $transaction_number, $invoice_bill->reference_number);

                    $invoice_bill->update(['is_actual' => true]);
                } else {
                    return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
                }
            }

            if (isset($request->id_transaction) || $request->id_transaction !== null) {
                $id_transaction = $request->id_transaction;
            } else {
                $id_transaction =  $transaction->id_transaction ?? Transaction::whereIdInvoiceBill($invoice_bill->id_invoice_bill)->first()->id_transaction;
            }

            $invoice_number = generateFinNumber('transaction_term', 'invoice_number', 'INV/TERM');

            $transaction_term                 = new TransactionTerm();
            $transaction_term->id_transaction = $id_transaction;
            $transaction_term->invoice_number = $invoice_number;
            $transaction_term->nama           = $request->nama;
            $transaction_term->date           = $request->date;
            $transaction_term->percent        = $request->percent;
            $transaction_term->value_ppn      = $request->value_ppn;
            $transaction_term->value_pph      = $request->value_pph;
            $transaction_term->value_percent  = $request->value_percent;
            $transaction_term->value_deposit  = $request->value_deposit;
            $transaction_term->deposit        = $request->deposit;
            $transaction_term->final          = $request->final ?? '0';
            $transaction_term->save();

            ActivityLogHelper::log('finance:transaction_term_create', 1, [
                'finance:term_name' => $transaction_term->nama,
                'date'              => $transaction_term->date,
                'final'             => $transaction_term->final
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($transaction_term, 'Transaction Term Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_term_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/transaction-terms/{id}",
     *  summary="Get a transaction term",
     *  tags={"Finance - Transaction Term"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Id of transaction term",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a resource"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $transaction_term = TransactionTerm::find($id);

        if (!$transaction_term) {
            return Response::json([
                'success' => false,
                'message' => 'Invoice Fob Not Found'
            ], 404);
        }

        return ApiResponseClass::sendResponse(TransactionTermResource::make($transaction_term), 'Transaction Term Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/transaction-terms/{id}",
     *  summary="Update a transaction term",
     *  tags={"Finance - Transaction Term"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      description="Transaction Term ID",
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_invoice_bill",
     *                  type="integer",
     *                  description="Invoice Bill ID"
     *              ),
     *              @OA\Property(
     *                  property="nama",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="string",
     *                  format="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="percent",
     *                  type="integer",
     *                  description="Percent"
     *              ),
     *              @OA\Property(
     *                  property="value_ppn",
     *                  type="integer",
     *                  description="Value PPN"
     *              ),
     *              @OA\Property(
     *                  property="value_pph",
     *                  type="integer",
     *                  description="Value PPH"
     *              ),
     *              @OA\Property(
     *                  property="value_percent",
     *                  type="integer",
     *                  description="Value Percent"
     *              ),
     *              @OA\Property(
     *                  property="value_deposit",
     *                  type="integer",
     *                  description="Value Deposit"
     *              ),
     *              @OA\Property(
     *                  property="deposit",
     *                  type="string",
     *                  description="Deposit"
     *              ),
     *              @OA\Property(
     *                  property="final",
     *                  type="string",
     *                  enum={"0", "1"},
     *                  description="Final status (0 or 1)"
     *              ),
     *              required={"id_invoice_bill", "nama", "date", "percent", "value_ppn", "value_pph", "value_percent", "value_deposit", "deposit"},
     *              example={
     *                  "id_invoice_bill": 1,
     *                  "nama": "Termin 1 Updated",
     *                  "date": "2022-01-15",
     *                  "percent": 15,
     *                  "value_ppn": 150000,
     *                  "value_pph": 150000,
     *                  "value_percent": 150000,
     *                  "value_deposit": 150000,
     *                  "deposit": "advance_payment",
     *                  "final": "0"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(
     *      response=200,
     *      description="Transaction Term Updated Successfully"
     *  ),
     *  @OA\Response(
     *      response=404,
     *      description="Transaction Term not found"
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TransactionTermRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_term                = TransactionTerm::findOrFail($id);
            $transaction_term->nama          = $request->nama;
            $transaction_term->date          = $request->date;
            $transaction_term->percent       = $request->percent;
            $transaction_term->value_ppn     = $request->value_ppn;
            $transaction_term->value_pph     = $request->value_pph;
            $transaction_term->value_percent = $request->value_percent;
            $transaction_term->value_deposit = $request->value_deposit;
            $transaction_term->deposit       = $request->deposit;
            $transaction_term->save();

            ActivityLogHelper::log('finance:transaction_term_update', 1, [
                'finance:term_name' => $transaction_term->nama,
                'date'              => $transaction_term->date,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse(TransactionTermResource::make($transaction_term), 'Transaction Term Updated Successfully');
        } catch (ModelNotFoundException $e) {
            return Response::json([
                'success' => false,
                'message' => 'Transaction Term not found'
            ], 404);
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_term_update', 0, [
                'error' => $e->getMessage()
            ]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/transaction-terms/{id}",
     *  summary="Delete a transaction terms",
     *  tags={"Finance - Transaction Term"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Id of transaction terms",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a resource"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_term = TransactionTerm::find($id);

            if (!$transaction_term) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invoice Bill Not Found'
                ], 404);
            }

            $transaction_term->delete();

            ActivityLogHelper::log('finance:transaction_term_delete', 1, [
                'finance:invoice_number' => $transaction_term->invoice_number,
                'type'                   => $transaction_term->type,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse(TransactionTermResource::make($transaction_term), 'Transaction Term Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:transaction_term_delete', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/transaction-terms/details",
     *  summary="Get transaction term details",
     *  tags={"Finance - Transaction Term"},
     *  @OA\Parameter(
     *      name="id_transaction",
     *      in="query",
     *      description="Transaction ID",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_invoice_bill",
     *      in="query",
     *      description="Invoice Bill ID",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function detail(Request $request)
    {
        if (isset($request->id_invoice_bill)) {
            $id_transaction = Transaction::where('id_invoice_bill', $request->id_invoice_bill)->first()->id_transaction ?? null;
        } else {
            $id_transaction = $request->id_transaction;
        }

        $data = TransactionTerm::with(['toTransaction'])->whereIdTransaction($id_transaction)->get();

        return ApiResponseClass::sendResponse(TransactionTermResource::collection($data), 'Transaction Term Detail Retrieved Successfully');
    }
}
