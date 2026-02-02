<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\InvoiceFobRequest;
use App\Http\Resources\operation\InvoiceFobResource;
use App\Models\finance\InvoiceBill;
use App\Models\operation\InvoiceFob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InvoiceFobController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/invoice_fob",
     *  summary="Get the list of invoice fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $query = InvoiceFob::query();

        $query->with(['toPlanBarging', 'toJournal', 'toInvoiceBill']);

        $query->orderBy('id_invoice_fob', 'asc');

        $data = $query->get();

        return ApiResponseClass::sendResponse(InvoiceFobResource::collection($data), 'Invoice Fob Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/invoice_fob",
     *  summary="Create a new invoice fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_plan_barging",
     *                  type="integer",
     *                  description="ID Plan Barging"
     *              ),
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="integer",
     *                  description="ID Kontak"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="string",
     *                  format="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="hpm",
     *                  type="number",
     *                  format="double",
     *                  description="Hpm"
     *              ),
     *              @OA\Property(
     *                  property="hma",
     *                  type="number",
     *                  format="double",
     *                  description="Hma"
     *              ),
     *              @OA\Property(
     *                  property="kurs",
     *                  type="number",
     *                  format="double",
     *                  description="Kurs"
     *              ),
     *              @OA\Property(
     *                  property="price",
     *                  type="number",
     *                  format="double",
     *                  description="Price"
     *              ),
     *              @OA\Property(
     *                  property="mc",
     *                  type="number",
     *                  format="double",
     *                  description="Mc"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="number",
     *                  format="double",
     *                  description="Tonage"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="number",
     *                  format="double",
     *                  description="NI"
     *              ),
     *              required={"id_plan_barging", "id_journal", "id_kontak", "date", "price", "tonage"},
     *              example={
     *                  "id_plan_barging": 1,
     *                  "id_journal": 1,
     *                  "id_kontak": 1,
     *                  "date": "2023-01-01",
     *                  "hpm": 1000.50,
     *                  "hma": 1500.75,
     *                  "kurs": 15000.00,
     *                  "ni": 1.25,
     *                  "mc": 100.50,
     *                  "tonage": 1000.00,
     *                  "price": 50000.00,
     *                  "description": "Invoice description",
     *                  "reference_number": "REF-001"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(
     *      response=201,
     *      description="Invoice Fob Created Successfully"
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(InvoiceFobRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        DB::connection('finance')->beginTransaction();
        try {
            $transaction_number = generateFinNumber('invoice_bills', 'transaction_number', 'BILL-OUT');

            $request->merge([
                'total' => $request->price,
            ]);

            $result = _count_journal($request);

            if (!$result) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invalid Amount, Not Enough Balance'
                ], 400);
            }

            $invoice_fob                     = new InvoiceFob();
            $invoice_fob->id_plan_barging    = $request->id_plan_barging;
            $invoice_fob->id_journal         = $request->id_journal;
            $invoice_fob->id_kontak          = $request->id_kontak;
            $invoice_fob->transaction_number = $transaction_number;
            $invoice_fob->date               = $request->date;
            $invoice_fob->hpm                = $request->hpm;
            $invoice_fob->hma                = $request->hma;
            $invoice_fob->kurs               = $request->kurs;
            $invoice_fob->ni                 = $request->ni;
            $invoice_fob->mc                 = $request->mc;
            $invoice_fob->tonage             = $request->tonage;
            $invoice_fob->price              = $request->price;
            $invoice_fob->description        = $request->description;
            $invoice_fob->reference_number   = $request->reference_number;
            $invoice_fob->save();

            $invoice_bill                     = new InvoiceBill();
            $invoice_bill->id_kontak          = $request->id_kontak;
            $invoice_bill->id_journal         = $request->id_journal;
            $invoice_bill->transaction_number = $transaction_number;
            $invoice_bill->reference_number   = $request->reference_number;
            $invoice_bill->inv_date           = $request->date;
            $invoice_bill->due_date           = $request->date;
            $invoice_bill->total              = $request->total;
            $invoice_bill->description        = $request->description;
            $invoice_bill->category           = 'penerimaan';
            $invoice_bill->type               = 'transaction';
            $invoice_bill->is_outstanding     = true;
            $invoice_bill->save();

            $details = collect($result)->map(fn($item) => [
                'coa'    => $item['coa'],
                'amount' => $item['value'],
                'type'   => $item['type'],
            ]);

            $invoice_bill->toInvoiceBillDetail()->createMany($details);

            ActivityLogHelper::log('operation:invoice_fob_create', 1, [
                'operation:invoice_number' => $invoice_fob->transaction_number,
                'type'                     => $invoice_bill->type,
            ]);

            DB::connection('operation')->commit();
            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse(InvoiceFobResource::make($invoice_fob), 'Invoice Bill Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:invoice_fob_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/invoice_fob/{id}",
     *  summary="Get a invoice fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Id of invoice fob",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a resource"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $invoice_fob = InvoiceFob::find($id);

        if (!$invoice_fob) {
            return Response::json([
                'success' => false,
                'message' => 'Invoice Fob Not Found'
            ], 404);
        }

        return ApiResponseClass::sendResponse(InvoiceFobResource::make($invoice_fob), 'Invoice Fob Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/invoice_fob/{id}",
     *  summary="Update an existing invoice fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      description="Invoice FOB ID",
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_plan_barging",
     *                  type="integer",
     *                  description="ID Plan Barging"
     *              ),
     *              @OA\Property(
     *                  property="id_journal",
     *                  type="integer",
     *                  description="Journal ID"
     *              ),
     *              @OA\Property(
     *                  property="id_kontak",
     *                  type="integer",
     *                  description="ID Kontak"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="string",
     *                  format="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="hpm",
     *                  type="number",
     *                  format="double",
     *                  description="Hpm"
     *              ),
     *              @OA\Property(
     *                  property="hma",
     *                  type="number",
     *                  format="double",
     *                  description="Hma"
     *              ),
     *              @OA\Property(
     *                  property="kurs",
     *                  type="number",
     *                  format="double",
     *                  description="Kurs"
     *              ),
     *              @OA\Property(
     *                  property="price",
     *                  type="number",
     *                  format="double",
     *                  description="Price"
     *              ),
     *              @OA\Property(
     *                  property="mc",
     *                  type="number",
     *                  format="double",
     *                  description="Mc"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="number",
     *                  format="double",
     *                  description="Tonage"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *              @OA\Property(
     *                  property="reference_number",
     *                  type="string",
     *                  description="Reference Number"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="number",
     *                  format="double",
     *                  description="NI"
     *              ),
     *              required={"id_plan_barging", "id_journal", "id_kontak", "date", "price", "tonage"},
     *              example={
     *                  "id_plan_barging": 1,
     *                  "id_journal": 1,
     *                  "id_kontak": 1,
     *                  "date": "2023-01-01",
     *                  "hpm": 1000.50,
     *                  "hma": 1500.75,
     *                  "kurs": 15000.00,
     *                  "ni": 1.25,
     *                  "mc": 100.50,
     *                  "tonage": 1000.00,
     *                  "price": 50000.00,
     *                  "description": "Invoice description updated",
     *                  "reference_number": "REF-001"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(
     *      response=200,
     *      description="Invoice Fob Updated Successfully"
     *  ),
     *  @OA\Response(
     *      response=404,
     *      description="Invoice Fob not found"
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error"
     *  ),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(InvoiceFobRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();
        DB::connection('finance')->beginTransaction();

        try {
            $invoiceFob = InvoiceFob::on('operation')->findOrFail($id);

            $request->total = $request->price;

            $result = _count_journal($request);

            if (!$result) {
                return Response::json([
                    'success' => false,
                    'message' => 'Invalid Amount, Not Enough Balance'
                ], 400);
            }

            $invoiceFob->update([
                'id_plan_barging'  => $request->id_plan_barging,
                'id_journal'       => $request->id_journal,
                'id_kontak'        => $request->id_kontak,
                'date'             => $request->date,
                'hpm'              => $request->hpm,
                'hma'              => $request->hma,
                'kurs'             => $request->kurs,
                'ni'               => $request->ni,
                'mc'               => $request->mc,
                'tonage'           => $request->tonage,
                'price'            => $request->price,
                'description'      => $request->description,
                'reference_number' => $request->reference_number,
            ]);

            $invoiceBill = InvoiceBill::on('finance')
                ->where('transaction_number', $invoiceFob->transaction_number)
                ->firstOrFail();

            $invoiceBill->update([
                'id_kontak'        => $request->id_kontak,
                'id_journal'       => $request->id_journal,
                'reference_number' => $request->reference_number,
                'inv_date'         => $request->date,
                'due_date'         => $request->date,
                'total'            => $request->price,
                'description'      => $request->description,
            ]);

            $invoiceBill->toInvoiceBillDetail()->delete();

            $details = collect($result)->map(fn($item) => [
                'coa'    => $item['coa'],
                'amount' => $item['value'],
                'type'   => $item['type'],
            ]);

            $invoiceBill->toInvoiceBillDetail()->createMany($details);

            ActivityLogHelper::log('operation:invoice_fob_update', 1, [
                'operation:invoice_number' => $invoiceFob->transaction_number,
                'type'                     => $invoiceBill->type,
            ]);

            DB::connection('operation')->commit();
            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse(InvoiceFobResource::make($invoiceFob->fresh()), 'Invoice Bill Updated Successfully');

        } catch (ModelNotFoundException $e) {
            DB::connection('operation')->rollBack();
            DB::connection('finance')->rollBack();

            return Response::json([
                'success' => false,
                'message' => 'Invoice Fob not found'
            ], 404);
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:invoice_fob_update', 0, [
                'error' => $e->getMessage()
            ]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/invoice_fob/check/{transaction_number}",
     *  summary="Get the list of invoice_fob",
     *  tags={"Operation - Invoice Fob"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function check($transaction_number)
    {
        $query = InvoiceFob::query();

        $query->with(['toPlanBarging.toPlanBargingDetail', 'toPlanBarging.toKontraktor', 'toTransaction']);

        $query->where('transaction_number', $transaction_number);

        $data = $query->first();

        return ApiResponseClass::sendResponse(InvoiceFobResource::make($data), 'Invoice Fob Retrieved Successfully');
    }
}
