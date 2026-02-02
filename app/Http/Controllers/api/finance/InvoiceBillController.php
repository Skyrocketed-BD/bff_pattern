<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\InvoiceBillRequest;
use App\Http\Resources\finance\InvoiceBillResource;
use App\Models\finance\InvoiceBill;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InvoiceBillController extends Controller
{
    /**
     * @OA\Get(
     *  path="/invoice-bills",
     *  summary="Get the list of invoice bills",
     *  tags={"Finance - Invoice Bills"},
     *  @OA\Parameter(
     *      name="type",
     *      in="query",
     *      description="Filter by type",
     *      required=false,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="category",
     *      in="query",
     *      description="Filter by category",
     *      required=false,
     *      @OA\Schema(
     *          type="string"
     *      )
     *  ),
     *  @OA\Response(
     *      response=200,
     *      description="Return a list of resources"
     *  ),
     *  @OA\Response(
     *      response=401,
     *      description="Unauthorized"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $query = InvoiceBill::query();

        $query->with(['toKontak']);

        if ($request->type) {
            $query->whereType($request->type);
        }

        if ($request->category) {
            $query->whereCategory($request->category);
        }

        $data = $query->get();

        return ApiResponseClass::sendResponse(InvoiceBillResource::collection($data), 'Invoice Bills Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/invoice-bills",
     *  summary="Create a new invoice bill",
     *  tags={"Finance - Invoice Bills"},
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
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference document number"
     *              ),
     *              @OA\Property(
     *                  property="inv_date",
     *                  type="string",
     *                  format="date",
     *                  description="Invoice date (YYYY-MM-DD)"
     *              ),
     *              @OA\Property(
     *                  property="due_date",
     *                  type="string",
     *                  format="date",
     *                  description="Due date (YYYY-MM-DD)"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total invoice amount"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Invoice description"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  enum={"penerimaan","pengeluaran"},
     *                  description="Category (penerimaan = income, pengeluaran = expense)"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  enum={"transaction","transaction_full","down_payment","advance_payment"},
     *                  description="Invoice type"
     *              ),
     *              @OA\Property(
     *                  property="in_ex",
     *                  type="string",
     *                  enum={"y","n","o"},
     *                  description="Include/exclude tax: y=exclude, n=include, o=none"
     *              ),
     *              required={"id_kontak", "id_journal", "reference_number", "inv_date", "due_date", "total", "description", "category", "type", "in_ex"},
     *              example={
     *                  "id_kontak": 10,
     *                  "id_journal": 5,
     *                  "reference_number": "INV-2025-001",
     *                  "inv_date": "2025-01-01",
     *                  "due_date": "2025-01-30",
     *                  "total": 2500000,
     *                  "description": "Invoice for project work",
     *                  "category": "penerimaan",
     *                  "type": "transaction",
     *                  "in_ex": "o"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(
     *      response=200,
     *      description="Invoice bill created successfully",
     *      @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Invoice bill created successfully"),
     *          @OA\Property(property="data", type="object")
     *      )
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(InvoiceBillRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            if ($request->category == 'penerimaan') {
                $transaction_number = generateFinNumber('invoice_bills', 'transaction_number', 'BILL-OUT');
            } else {
                $transaction_number = generateFinNumber('invoice_bills', 'transaction_number', 'BILL-IN');
            }

            $reference_number   = $request->reference_number ?? 'REF-' . $transaction_number;

            if ($request->type === 'advance_payment' || $request->type === 'down_payment') {
                if ($request->type === 'advance_payment') {
                    $request->merge([
                        'id_journal' => get_arrangement('advance_payment_deposit_journal'),
                    ]);
                } else {
                    $request->merge([
                        'id_journal' => get_arrangement('down_payment_deposit_journal'),
                    ]);
                }
            }

            $request->merge([
                'in_ex_tax' => $request->in_ex,
            ]);

            $invoiceData = [
                'id_kontak'          => $request->id_kontak,
                'id_journal'         => $request->id_journal,
                'transaction_number' => $transaction_number,
                'reference_number'   => $reference_number,
                'inv_date'           => $request->inv_date,
                'due_date'           => $request->due_date,
                'total'              => $request->total,
                'description'        => $request->description,
                'category'           => $request->category,
                'type'               => $request->type,
                'in_ex'              => $request->in_ex,
                'is_outstanding'     => ($request->type === 'transaction'),
            ];

            if ($request->id_journal !== null) {
                $result = _count_journal($request);

                if (!$result) {
                    return Response::json([
                        'success' => false,
                        'message' => 'Invalid Amount, Not Enough Balance'
                    ], 400);
                }

                $invoice_bill = InvoiceBill::create($invoiceData);

                $detail = [];
                foreach ($result as $key => $val) {
                    $detail[] = [
                        'id_invoice_bill' => $invoice_bill->id_invoice_bill,
                        'coa'             => $val['coa'],
                        'amount'          => $val['value'],
                        'type'            => $val['type'],
                    ];
                }

                $invoice_bill->toInvoiceBillDetail()->createMany($detail);
            } else {
                $invoice_bill = InvoiceBill::create($invoiceData);
            }

            ActivityLogHelper::log('finance:invoice_bill_create', 1, [
                'finance:invoice_number' => $invoice_bill->transaction_number,
                'type'                   => $invoice_bill->type,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($invoice_bill, 'Invoice Bill Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:invoice_bill_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/invoice-bills/{id}",
     *  summary="Get a invoice bill",
     *  tags={"Finance - Invoice Bills"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Id of invoice bill",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a resource"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $invoice_bill = InvoiceBill::find($id);

        if (!$invoice_bill) {
            return Response::json([
                'success' => false,
                'message' => 'Invoice Bill Not Found'
            ], 404);
        }

        return ApiResponseClass::sendResponse(InvoiceBillResource::make($invoice_bill), 'Invoice Bill Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/invoice-bills/{id}",
     *  summary="Update an existing invoice bill",
     *  tags={"Finance - Invoice Bills"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      description="Invoice Bill ID",
     *      @OA\Schema(type="integer")
     *  ),
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
     *                  nullable=true,
     *                  description="Journal ID (optional)"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference document number"
     *              ),
     *              @OA\Property(
     *                  property="inv_date",
     *                  type="string",
     *                  format="date",
     *                  description="Invoice date (YYYY-MM-DD)"
     *              ),
     *              @OA\Property(
     *                  property="due_date",
     *                  type="string",
     *                  format="date",
     *                  description="Due date (YYYY-MM-DD)"
     *              ),
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total invoice amount"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Invoice description"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  enum={"penerimaan","pengeluaran"},
     *                  description="Category (penerimaan = income, pengeluaran = expense)"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  enum={"transaction","transaction_full","down_payment","advance_payment"},
     *                  description="Invoice type"
     *              ),
     *              @OA\Property(
     *                  property="in_ex",
     *                  type="string",
     *                  enum={"y","n","o"},
     *                  description="Include/exclude tax: y=exclude, n=include, o=none"
     *              ),
     *              required={"id_kontak", "reference_number", "inv_date", "due_date", "total", "description", "category", "type", "in_ex"},
     *              example={
     *                  "id_kontak": 10,
     *                  "id_journal": 5,
     *                  "reference_number": "INV-2025-001",
     *                  "inv_date": "2025-01-01",
     *                  "due_date": "2025-01-30",
     *                  "total": 2500000,
     *                  "description": "Invoice for project work - Updated",
     *                  "category": "penerimaan",
     *                  "type": "transaction",
     *                  "in_ex": "o"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(
     *      response=200,
     *      description="Invoice bill updated successfully",
     *      @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Invoice bill updated successfully"),
     *          @OA\Property(property="data", type="object")
     *      )
     *  ),
     *  @OA\Response(
     *      response=400,
     *      description="Invalid amount or not enough balance"
     *  ),
     *  @OA\Response(
     *      response=404,
     *      description="Invoice bill not found"
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(InvoiceBillRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $invoice_bill = InvoiceBill::findOrFail($id);

            $transaction_number = $invoice_bill->transaction_number;
            $reference_number   = $request->reference_number ?? 'REF-' . $transaction_number;

            if ($request->type === 'advance_payment' || $request->type === 'down_payment') {
                if ($request->type === 'advance_payment') {
                    $request->merge([
                        'id_journal' => get_arrangement('advance_payment_deposit_journal'),
                    ]);
                } else {
                    $request->merge([
                        'id_journal' => get_arrangement('down_payment_deposit_journal'),
                    ]);
                }
            }

            $updateData = [
                'id_kontak'        => $request->id_kontak,
                'id_journal'       => $request->id_journal,
                'reference_number' => $reference_number,
                'inv_date'         => $request->inv_date,
                'due_date'         => $request->due_date,
                'total'            => $request->total,
                'description'      => $request->description,
                'category'         => $request->category,
                'type'             => $request->type,
                'in_ex'            => $request->in_ex,
                'is_outstanding'   => ($request->type === 'transaction'),
            ];

            if ($request->id_journal !== null) {
                $result = _count_journal($request);

                if (!$result) {
                    return Response::json([
                        'success' => false,
                        'message' => 'Invalid Amount, Not Enough Balance'
                    ], 400);
                }

                $invoice_bill->update($updateData);

                $invoice_bill->toInvoiceBillDetail()->delete();
                $detail = [];
                foreach ($result as $key => $val) {
                    $detail[] = [
                        'id_invoice_bill' => $invoice_bill->id_invoice_bill,
                        'coa'             => $val['coa'],
                        'amount'          => $val['value'],
                        'type'            => $val['type'],
                    ];
                }

                $invoice_bill->toInvoiceBillDetail()->createMany($detail);
            } else {
                $invoice_bill->update($updateData);

                $invoice_bill->toInvoiceBillDetail()->delete();
            }

            ActivityLogHelper::log('finance:invoice_bill_update', 1, [
                'finance:invoice_number' => $invoice_bill->transaction_number,
                'type'                   => $invoice_bill->type,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($invoice_bill, 'Invoice Bill Updated Successfully');
        } catch (ModelNotFoundException $e) {
            DB::connection('finance')->rollBack();

            return Response::json([
                'success' => false,
                'message' => 'Invoice Bill Not Found'
            ], 404);
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:invoice_bill_update', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/invoice-bills/{id}",
     *  summary="Delete a invoice bill",
     *  tags={"Finance - Invoice Bills"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Id of invoice bill",
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
            $invoice_bill = InvoiceBill::find($id);

            if (!$invoice_bill) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invoice Bill Not Found'
                ], 404);
            }

            $invoice_bill->delete();

            ActivityLogHelper::log('finance:invoice_bill_delete', 1, [
                'finance:invoice_number' => $invoice_bill->invoice_number,
                'type'                   => $invoice_bill->type,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse(InvoiceBillResource::make($invoice_bill), 'Invoice Bill Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:invoice_bill_delete', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Put(
     *  path="/invoice-bills/actual/{id}",
     *  summary="Actualize invoice bill",
     *  tags={"Finance - Invoice Bills"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Id of invoice bill",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Parameter(
     *      name="_method",
     *      in="query",
     *      description="HTTP Method",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          default="PUT"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="File Attachment"
     *              )
     *          )
     *      )
     *  ),
     *  @OA\Response(
     *      response=200,
     *      description="Invoice Bill Actualized Successfully"
     *  ),
     *  @OA\Response(
     *      response=404,
     *      description="Invoice Bill not found"
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function actual(Request $request, $id)
    {
        $invoice_bill = InvoiceBill::whereIdInvoiceBill($id)->first();

        $transaction_number = match ($invoice_bill->type) {
            'down_payment'     => generateFinNumber('down_payment_details', 'transaction_number', 'DP'),
            'advance_payment'  => generateFinNumber('liability_details', 'transaction_number', 'AP'),
            'transaction'      => generateFinNumber('transaction', 'transaction_number', 'INV'),
            'transaction_full' => generateFinNumber('transaction_full', 'transaction_number', 'FU'),
        };

        if (!$invoice_bill) {
            return Response::json([
                'success' => false,
                'message' => 'Invoice Bill Not Found'
            ], 404);
        }

        $request->merge([
            'id_kontak'          => $invoice_bill->id_kontak,
            'id_journal'         => $invoice_bill->id_journal,
            'id_invoice_bill'    => $invoice_bill->id_invoice_bill,
            'transaction_number' => $transaction_number,
            'reference_number'   => $invoice_bill->reference_number,
            'date'               => $invoice_bill->inv_date,
            'description'        => $invoice_bill->description,
            'total'              => $invoice_bill->total,
            'category'           => $invoice_bill->category,
            'type'               => $invoice_bill->type,
            'in_ex'              => $invoice_bill->in_ex,
            'in_ex_tax'          => $invoice_bill->in_ex,
            'deposit_total'      => $invoice_bill->total,
            'operation'          => '+',
        ]);

        // jika belum actual
        if (!$invoice_bill->is_actual) {
            $result = _count_journal($request, $transaction_number);

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

                match ($request->type) {
                    'down_payment'     => _insert_down_payment($request, $transaction_number),
                    'advance_payment'  => _insert_advance_payment($request, $transaction_number),
                    'transaction_full' => insert_transaction_full($request, $transaction_number),
                    'transaction'      => insert_transaction($request, $transaction_number, $invoice_bill->reference_number),
                };

                insert_general_ledger($general_ledger, $transaction_number, $invoice_bill->reference_number);

                $invoice_bill->update(['is_actual' => true]);

                if ($request->type == 'down_payment' || $request->type == 'advance_payment' || $request->type == 'transaction_full') {
                    $invoice_bill->update(['payment_status' => 'paid']);
                }
            } else {
                return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
            }
        }

        return ApiResponseClass::sendResponse(InvoiceBillResource::make($invoice_bill), 'Invoice Bill Actualized Successfully');
    }

    /**
     * @OA\Get(
     *  path="/invoice-bills/details",
     *  summary="Get the list of detail invoice bills",
     *  tags={"Finance - Invoice Bill"},
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

        $invoice_bill = InvoiceBill::with(['toInvoiceBillDetail'])
            ->where('transaction_number', $transaction_number)
            ->first();

        $data   = [];
        $debit  = [];
        $credit = [];

        foreach ($invoice_bill->toInvoiceBillDetail as $key => $row) {
            $val_debit  = 0;
            $val_credit = 0;

            if ($row->type === 'K') {
                $credit[] = $row->amount;
                $val_credit = $row->amount;
            } else {
                $debit[] = $row->amount;
                $val_debit = $row->amount;
            }

            $data[] = [
                'date'              => $invoice_bill->inv_date,
                'coa'               => $row->toCoa->name,
                'type'              => $row->type,
                'debit'             => (float) $val_debit,
                'credit'            => (float) $val_credit,
                'value'             => (float) $row->amount,
                'description'       => $invoice_bill->description,
            ];
        }

        $response = [
            'record'      => $data,
            'debit'       => array_sum($debit),
            'credit'      => array_sum($credit),
            'description' => $data[0]['description'],
        ];

        return ApiResponseClass::sendResponse($response, 'Invoice Bill Retrieved Successfully');
    }
}
