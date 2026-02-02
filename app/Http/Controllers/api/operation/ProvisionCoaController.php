<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\ProvisionCoaRequest;
use App\Http\Resources\operation\ProvisionCoaResource;
use App\Models\finance\GeneralLedger;
use App\Models\finance\GeneralLedgerLog;
use App\Models\finance\Transaction;
use App\Models\operation\Provision;
use App\Models\operation\ProvisionCoa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ProvisionCoaController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/provision_coa",
     *  summary="Get the list of provision coa",
     *  tags={"Operation - Provision Coa"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
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

        $query = ProvisionCoa::query();

        $query->with(['toProvision']);

        $query->whereBetween('date', [$start_date, $end_date]);

        $data = $query->get();

        return ApiResponseClass::sendResponse(ProvisionCoaResource::collection($data), 'Provision Coa Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/provision_coa",
     *  summary="Update a provision coa",
     *  tags={"Operation - Provision Coa"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
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
     *                  property="id_provision",
     *                  type="integer",
     *                  description="ID Provision"
     *              ),
     *              @OA\Property(
     *                  property="no_invoice",
     *                  type="string",
     *                  description="Invoice Number"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date"
     *              ),
     *              @OA\Property(
     *                  property="price",
     *                  type="integer",
     *                  description="Price"
     *              ),
     *              @OA\Property(
     *                  property="pay_pnbp",
     *                  type="integer",
     *                  description="Price"
     *              ),
     *              @OA\Property(
     *                  property="hpm",
     *                  type="integer",
     *                  description="Hpm"
     *              ),
     *              @OA\Property(
     *                  property="hma",
     *                  type="integer",
     *                  description="Hma"
     *              ),
     *              @OA\Property(
     *                  property="kurs",
     *                  type="integer",
     *                  description="Kurs"
     *              ),
     *              @OA\Property(
     *                  property="ni_final",
     *                  type="number",
     *                  format="float",
     *                  description="Ni"
     *              ),
     *              @OA\Property(
     *                  property="fe_final",
     *                  type="number",
     *                  format="float",
     *                  description="Fe"
     *              ),
     *              @OA\Property(
     *                  property="co_final",
     *                  type="number",
     *                  format="float",
     *                  description="Co"
     *              ),
     *              @OA\Property(
     *                  property="sio2_final",
     *                  type="number",
     *                  format="float",
     *                  description="Sio2"
     *              ),
     *              @OA\Property(
     *                  property="mgo2_final",
     *                  type="number",
     *                  format="float",
     *                  description="Mgo2"
     *              ),
     *              @OA\Property(
     *                  property="mc_final",
     *                  type="integer",
     *                  description="Mgo2"
     *              ),
     *              @OA\Property(
     *                  property="tonage_final",
     *                  type="integer",
     *                  description="Tonage"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ProvisionCoaRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $provision = Provision::with([
                'toProvisionCoa',
                'toShippingInstruction.toPlanBarging.toInvoiceFob'
            ])->find($request->id_provision);

            // untuk mengecek invoice fob apa bila tersedia
            if ($provision->method_sales === 'fob') {
                if (!$provision->toShippingInstruction->toPlanBarging->toInvoiceFob) {
                    return Response::json([
                        'success' => false,
                        'message' => 'Invoice FOB Belum Ada',
                    ], 400);
                }
            }

            $request->id_kontak    = $provision->id_kontak;
            $request->total        = $request->price;
            $request->description  = $request->description . ' Harga Jual = ' . $provision->selling_price . ', Ni = ' . $request->ni_final . ', Fe = ' . $request->fe_final . ', Co = ' . $request->co_final . ', Sio2 = ' . $request->sio2_final . ', Mgo2 = ' . $request->mgo2_final . ', Mc = ' . $request->mc_final . '. Tug Boat / Barge = ' . $provision->toShippingInstruction->tug_boat . ' / ' . $provision->toShippingInstruction->barge;
            $request->from_or_to   = $provision->buyer_name;
            $request->method_sales = $provision->method_sales;

            $provision_check = $provision->toProvisionCoa->count();

            if ($provision_check === 0) {
                // apabila provision coa belum ada
                if ($request->id_journal !== null) {
                    // apabila journal ada
                    if ($provision->toShippingInstruction->toPlanBarging->toInvoiceFob->count() !== 0) {
                        $this->_journal_update($request);

                        if ($request->method_sales == 'fob') {
                            $data = $this->_coa_muat($request);
                        } else {
                            $data = $this->_coa_bongkar($request);
                        }
                    } else {
                        $this->_journal_invoice($request);

                        $data = $this->_coa_muat($request);
                    }
                } else {
                    // apabila journal tidak ada
                    $data = $this->_coa_muat($request);
                }
            } else {
                // apabila provision coa sudah ada
                $method_coa = '';
                foreach ($provision->toProvisionCoa as $key => $value) {
                    $method_coa = $value->method_coa;
                }

                if ($request->method_sales === 'cif' && $method_coa === 'coa_muat' && $provision_check <= 2) {
                    if ($request->id_journal !== null) {
                        $this->_journal_update($request);

                        if ($request->method_sales == 'fob') {
                            $data = $this->_coa_muat($request);
                        } else {
                            $data = $this->_coa_bongkar($request);
                        }
                    } else {
                        $data = $this->_coa_bongkar($request);
                    }
                }

                $data = [];
            }

            ActivityLogHelper::log('operation:provision_coa_create', 1, [
                'finance:invoice_number' => $request->no_invoice,
                'date'                   => $request->date,
            ]);

            DB::connection('operation')->commit();

            return Response::json([
                'success' => true,
                'message' => 'Provision Coa Telah Diproses',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:provision_coa_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/provision_coa/{id}?_method=PUT",
     *     summary="Update a provision coa",
     *     tags={"Operation - Provision Coa"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the Provision Coa to update",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="attachment_pnbp_final",
     *                     type="string",
     *                     format="binary",
     *                     description="Attachment for pnbp final"
     *                 ),
     *                 @OA\Property(
     *                     property="pay_pnbp",
     *                     type="number",
     *                     description="Pay pnbp"
     *                 ),
     *                 required={"attachment_pnbp_final", "pay_pnbp"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Provision Coa updated successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function update(ProvisionCoaRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $data = ProvisionCoa::find($id);

            $attachement_pnbp_final = add_file($request->attachment_pnbp_final, 'provision-coa/', 'pnbp_final');

            $data->update([
                'attachment_pnbp_final' => $attachement_pnbp_final,
                'pay_pnbp'              => $request->pay_pnbp
            ]);

            ActivityLogHelper::log('operation:provision_coa_update', 1, [
                'pay_pnbp' => $request->pay_pnbp,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Provision Coa Updated Successfully');
        } catch (\Throwable $th) {
            ActivityLogHelper::log('operation:provision_coa_update', 0, ['error' => $th->getMessage()]);
            return ApiResponseClass::rollback($th);
        }
    }

    private function _coa_muat($request)
    {
        $attachment = add_file($request->attachment, 'provision-coa/', 'coa');

        $data               = new ProvisionCoa();
        $data->id_provision = $request->id_provision;
        $data->id_journal   = $request->id_journal;
        $data->no_invoice   = $request->no_invoice;
        $data->method_coa   = 'coa_muat';
        $data->attachment   = $attachment;
        $data->date         = $request->date;
        $data->hpm          = $request->hpm;
        $data->hma          = $request->hma;
        $data->kurs         = $request->kurs;
        $data->price        = $request->price;
        $data->pay_pnbp     = $request->pay_pnbp;
        $data->ni_final     = $request->ni_final;
        $data->fe_final     = $request->fe_final;
        $data->co_final     = $request->co_final;
        $data->sio2_final   = $request->sio2_final;
        $data->mgo2_final   = $request->mgo2_final;
        $data->mc_final     = $request->mc_final;
        $data->tonage_final = $request->tonage_final;
        $data->description  = $request->description;
        $data->save();

        return $data;
    }

    private function _coa_bongkar($request)
    {
        $attachment = add_file($request->attachment, 'provision-coa/', 'coa');

        $data = new ProvisionCoa();
        $data->id_provision = $request->id_provision;
        $data->id_journal   = $request->id_journal;
        $data->no_invoice   = $request->no_invoice;
        $data->method_coa   = 'coa_bongkar';
        $data->attachment   = $attachment;
        $data->date         = $request->date;
        $data->hpm          = $request->hpm;
        $data->hma          = $request->hma;
        $data->kurs         = $request->kurs;
        $data->price        = $request->price;
        $data->pay_pnbp     = $request->pay_pnbp;
        $data->ni_final     = $request->ni_final;
        $data->fe_final     = $request->fe_final;
        $data->co_final     = $request->co_final;
        $data->sio2_final   = $request->sio2_final;
        $data->mgo2_final   = $request->mgo2_final;
        $data->mc_final     = $request->mc_final;
        $data->tonage_final = $request->tonage_final;
        $data->description  = $request->description;
        $data->save();

        return $data;
    }

    private function _journal_invoice($request)
    {
        $transaction_number = generate_number('finance', 'transaction', 'transaction_number', 'INV');

        $result = _count_journal($request, $transaction_number);

        if ($result) {
            foreach ($result as $key => $val) {
                $data[] = [
                    'id_journal'         => $request->id_journal,
                    'transaction_number' => $transaction_number,
                    'date'               => $request->date,
                    'coa'                => $val['coa'],
                    'type'               => $val['type'],
                    'value'              => $val['value'],
                    'description'        => $request->description,
                    'reference_number'   => $request->no_invoice,
                    'phase'              => 'opr',
                    'calculated'         => $val['calculated'],
                    'created_by'         => auth('api')->user()->id_users,
                ];
            }

            $transaction                      = new Transaction();
            $transaction->id_kontak           = $request->id_kontak;
            $transaction->id_journal          = $request->id_journal;
            $transaction->category            = 'penerimaan';
            $transaction->transaction_number  = $transaction_number;
            $transaction->from_or_to          = $request->from_or_to;
            $transaction->description         = $request->description;
            $transaction->date                = $request->date;
            $transaction->reference_number    = $request->no_invoice;
            $transaction->value               = $request->total;
            $transaction->save();

            GeneralLedger::insert($data);

            return;
        } else {
            return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
        }
    }

    private function _journal_update($request)
    {
        $reference_number = $request->no_invoice;

        $check_transaction = Transaction::where('transaction_number', $reference_number)->whereOr('reference_number', $reference_number)->first();

        $transaction_number = $check_transaction->transaction_number;

        $result = _count_journal($request, $transaction_number);

        if ($result) {
            $check_general_ledger = GeneralLedger::where('transaction_number', $transaction_number)->get();

            $count_general_ledger_logs = DB::connection('finance')
                ->table('general_ledger_logs')
                ->select('transaction_number')
                ->where('transaction_number', $transaction_number)
                ->orWhere('reference_number', $transaction_number)
                ->groupBy('transaction_number', 'revision')
                ->get()
                ->count();

            $id_general_ledger   = [];
            $general_ledger_logs = [];

            foreach ($check_general_ledger as $key => $row) {
                $id_general_ledger[] = $row->id_general_ledger;

                $general_ledger_logs[] = [
                    'transaction_number' => $transaction_number,
                    'date'               => $row->date,
                    'coa'                => $row->coa,
                    'type'               => $row->type,
                    'value'              => $row->value,
                    'description'        => $row->description,
                    'reference_number'   => $row->reference_number,
                    'revision'           => $count_general_ledger_logs + 1,
                    'created_by'         => auth('api')->user()->id_users,
                ];
            }

            GeneralLedgerLog::insert($general_ledger_logs);

            $check_transaction->value = $request->total;
            $check_transaction->save();

            foreach ($id_general_ledger as $key => $row) {
                $gl              = GeneralLedger::find($row);
                $gl->type        = $result[$key]['type'];
                $gl->value       = $result[$key]['value'];
                $gl->description = $request->description;
                $gl->save();
            }

            return;
            // if ($request->method_sales === 'fob') {
            //     return $this->_coa_muat($request);
            // } else {
            //     return $this->_coa_bongkar($request);
            // }
        } else {
            return Response::json(['success' => false, 'message' => 'Invalid Amount, Not Enough Balance'], 400);
        }
    }
}
