<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Events\UserEventTriggered;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\PlanBargingRequest;
use App\Http\Resources\operation\PlanBargingDetailResource;
use App\Models\operation\PlanBarging;
use App\Models\operation\PlanBargingDetail;
use App\Models\operation\StokPsi;
use App\Repositories\operation\PlanBargingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;


class PlanBargingController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/plan_barging",
     *  summary="Get the list of plan barging",
     *  tags={"Operation - Plan Barging"},
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
    public function index(Request $request, PlanBargingRepository $repository)
    {
        $data = $repository->getAll($request, $this->id_kontraktor);

        return ApiResponseClass::sendResponse($data, 'Plan Barging Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/plan_barging",
     *  summary="Add a new plan barging",
     *  tags={"Operation - Plan Barging"},
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date",
     *                  type="string",
     *                  format="date",
     *                  description="Date of plan_barging"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment of stok_psi"
     *              ),
     *
     *              @OA\Property(
     *                  property="id_stok_eto[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="id_stok_efo[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *
     *              @OA\Property(
     *                  property="eto_ni[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_ni[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *
     *              @OA\Property(
     *                  property="eto_fe[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_fe[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *
     *              @OA\Property(
     *                  property="eto_co[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_co[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *
     *              @OA\Property(
     *                  property="eto_sio2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_sio2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *
     *              @OA\Property(
     *                  property="eto_mgo2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_mgo2[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *
     *              @OA\Property(
     *                  property="eto_tonage[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_tonage[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *
     *              @OA\Property(
     *                  property="eto_ritasi[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_ritasi[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="eto_mc[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="efo_mc[]",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description=""
     *              ),
     *              @OA\Property(
     *                  property="shipping_method",
     *                  type="string",
     *                  description="Shipping method",
     *                  enum={"cif", "fob"}
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(PlanBargingRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $attachment = add_file($request->attachment, 'plan_barging/');
            $pb_name    = generateDomeNumber('operation', 'plan_bargings', $this->id_kontraktor, 'pb_name', 'PB-' . $this->initial);

            $plan_barging_detail = [];

            $ni     = [];
            $fe     = [];
            $co     = [];
            $sio2   = [];
            $mgo2   = [];
            $tonage = [];
            $ritasi = [];
            $mc     = [];

            if (isset($request->id_stok_eto)) {
                $id_stok_eto = $request->id_stok_eto;
                $eto_ni      = $request->eto_ni;
                $eto_fe      = $request->eto_fe;
                $eto_co      = $request->eto_co;
                $eto_sio2    = $request->eto_sio2;
                $eto_mgo2    = $request->eto_mgo2;
                $eto_tonage  = $request->eto_tonage;
                $eto_ritasi  = $request->eto_ritasi;
                $eto_mc      = $request->eto_mc;

                foreach ($id_stok_eto as $key => $value) {
                    $ni[]     = $eto_ni[$key];
                    $fe[]     = $eto_fe[$key];
                    $co[]     = $eto_co[$key];
                    $sio2[]   = $eto_sio2[$key];
                    $mgo2[]   = $eto_mgo2[$key];
                    $tonage[] = $eto_tonage[$key];
                    $ritasi[] = $eto_ritasi[$key];
                    $mc[]     = $eto_mc[$key];

                    $stok_eto = StokPsi::whereIdStokEto($value)->first();

                    $plan_barging_detail[] = [
                        'id_stok_eto' => $value,
                        'id_stok_efo' => null,
                        'id_dom_eto'  => $stok_eto->id_dom_eto,
                        'id_dom_efo'  => null,
                        'type'        => 'eto',
                        'ni'          => $eto_ni[$key],
                        'fe'          => $eto_fe[$key],
                        'co'          => $eto_co[$key],
                        'sio2'        => $eto_sio2[$key],
                        'mgo2'        => $eto_mgo2[$key],
                        'tonage'      => $eto_tonage[$key],
                        'ritasi'      => $eto_ritasi[$key],
                        'mc'          => $eto_mc[$key],
                    ];
                }
            }

            if (isset($request->id_stok_efo)) {
                $id_stok_efo = $request->id_stok_efo;
                $efo_ni      = $request->efo_ni;
                $efo_fe      = $request->efo_fe;
                $efo_co      = $request->efo_co;
                $efo_sio2    = $request->efo_sio2;
                $efo_mgo2    = $request->efo_mgo2;
                $efo_tonage  = $request->efo_tonage;
                $efo_ritasi  = $request->efo_ritasi;
                $efo_mc      = $request->efo_mc;

                foreach ($id_stok_efo as $key => $value) {
                    $ni[]     = $efo_ni[$key];
                    $fe[]     = $efo_fe[$key];
                    $co[]     = $efo_co[$key];
                    $sio2[]   = $efo_sio2[$key];
                    $mgo2[]   = $efo_mgo2[$key];
                    $tonage[] = $efo_tonage[$key];
                    $ritasi[] = $efo_ritasi[$key];
                    $mc[]     = $efo_mc[$key];

                    $stok_efo = StokPsi::whereIdStokEfo($value)->first();

                    $plan_barging_detail[] = [
                        'id_stok_eto' => null,
                        'id_stok_efo' => $value,
                        'id_dom_eto'  => null,
                        'id_dom_efo'  => $stok_efo->id_dom_efo,
                        'type'        => 'efo',
                        'ni'          => $efo_ni[$key],
                        'fe'          => $efo_fe[$key],
                        'co'          => $efo_co[$key],
                        'sio2'        => $efo_sio2[$key],
                        'mgo2'        => $efo_mgo2[$key],
                        'tonage'      => $efo_tonage[$key],
                        'ritasi'      => $efo_ritasi[$key],
                        'mc'          => $efo_mc[$key],
                    ];
                }
            }

            $count_ni     = (sumProductArray($ni, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($ni, $tonage) / array_sum($tonage)), 2);
            $count_fe     = (sumProductArray($fe, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($fe, $tonage) / array_sum($tonage)), 2);
            $count_co     = (sumProductArray($co, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($co, $tonage) / array_sum($tonage)), 2);
            $count_sio2   = (sumProductArray($sio2, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($sio2, $tonage) / array_sum($tonage)), 2);
            $count_mgo2   = (sumProductArray($mgo2, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($mgo2, $tonage) / array_sum($tonage)), 2);
            $count_tonage = array_sum($tonage);
            $count_ritasi = array_sum($ritasi);
            $count_mc     = (sumProductArray($mc, $tonage) == 0 || array_sum($tonage) == 0) ? 0 : round((sumProductArray($mc, $tonage) / array_sum($tonage)), 2);

            $plan_barging = new PlanBarging();
            $plan_barging->id_kontraktor   = $this->id_kontraktor;
            $plan_barging->pb_name         = $pb_name;
            $plan_barging->date            = $request->date;
            $plan_barging->attachment      = $attachment;
            $plan_barging->shipping_method = $request->shipping_method;
            $plan_barging->ni              = $count_ni;
            $plan_barging->fe              = $count_fe;
            $plan_barging->co              = $count_co;
            $plan_barging->sio2            = $count_sio2;
            $plan_barging->mgo2            = $count_mgo2;
            $plan_barging->mc              = $count_mc;
            $plan_barging->tonage          = $count_tonage;
            $plan_barging->ritasi          = $count_ritasi;
            $plan_barging->save();

            foreach ($plan_barging_detail as $key => $value) {
                $detail_plan_barging = new PlanBargingDetail();
                $detail_plan_barging->id_plan_barging = $plan_barging->id_plan_barging;
                $detail_plan_barging->id_stok_eto     = $value['id_stok_eto'];
                $detail_plan_barging->id_stok_efo     = $value['id_stok_efo'];
                $detail_plan_barging->id_dom_eto      = $value['id_dom_eto'];
                $detail_plan_barging->id_dom_efo      = $value['id_dom_efo'];
                $detail_plan_barging->type            = $value['type'];
                $detail_plan_barging->ni              = $value['ni'];
                $detail_plan_barging->fe              = $value['fe'];
                $detail_plan_barging->co              = $value['co'];
                $detail_plan_barging->sio2            = $value['sio2'];
                $detail_plan_barging->mgo2            = $value['mgo2'];
                $detail_plan_barging->tonage          = $value['tonage'];
                $detail_plan_barging->ritasi          = $value['ritasi'];
                $detail_plan_barging->mc              = $value['mc'];
                $detail_plan_barging->save();
            }

            ActivityLogHelper::log('operation:barging_plan_create', 1, [
                'operation:barging_plan_name' => $pb_name,
                'operation:shipping_method'   => $request->shipping_method
            ]);

            DB::connection('operation')->commit();

            UserEventTriggered::trigger("new_plan_barge", $plan_barging->pb_name . " Created.");

            return ApiResponseClass::sendResponse($plan_barging, 'Plan Barging Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:barging_plan_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/plan_barging/details/{id}",
     *  summary="Get the list of plan barging details",
     *  tags={"Operation - Plan Barging"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          description="Id of plan barging"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function details($id)
    {
        $data = PlanBargingDetail::with([
            'toDomEto',
            'toDomEfo',
        ])->whereIdPlanBarging($id)->get();

        return ApiResponseClass::sendResponse(PlanBargingDetailResource::collection($data), 'Stok Psi Details Retrieved Successfully');
    }

    /**
     * @OA\Delete(
     *  path="/plan_barging/{id}",
     *  summary="Delete plan barging by id",
     *  tags={"Operation - Plan Barging"},
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
    public function destroy($id)
    {
        try {
            $data = PlanBarging::with(['toShippingInstruction.toShippingInstructionApprove', 'toInvoiceFob.toTransaction'])->find($id);

            // untuk cek apa bila memiliki invoice fob
            if ($data->toInvoiceFob) {
                // untuk cek apa bila memiliki transaction
                if ($data->toInvoiceFob->toTransaction) {
                    return Response::json(['success' => false, 'message' => 'A financial transaction already exists!'], 400);
                } else {
                    if ($data->toShippingInstruction) {
                        $data->toShippingInstruction->delete();
                    }

                    $data->delete();

                    del_file($data->file, 'plan_barging/');

                    ActivityLogHelper::log('operation:barging_plan_delete', 1, [
                        'operation:barging_plan_name' => $data->pb_name
                    ]);

                    return ApiResponseClass::sendResponse($data, 'Plan Barging Deleted Successfully');
                }
            } else {
                if ($data->toShippingInstruction) {
                    $data->toShippingInstruction->delete();
                }

                $data->delete();

                del_file($data->file, 'plan_barging/');

                ActivityLogHelper::log('operation:barging_plan_delete', 1, [
                    'operation:barging_plan_name' => $data->pb_name
                ]);

                return ApiResponseClass::sendResponse($data, 'Plan Barging Deleted Successfully');
            }
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:barging_plan_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
