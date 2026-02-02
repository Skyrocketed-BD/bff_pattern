<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\StokEfoRequest;
use App\Http\Resources\operation\StokEfoDetailResource;
use App\Models\operation\DomEfo;
use App\Models\operation\Kontraktor;
use App\Models\operation\StokEfo;
use App\Models\operation\StokEfoDetail;
use App\Models\operation\StokEto;
use App\Models\operation\StokInPit;
use App\Repositories\operation\StokEfoRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokEfoController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/stok_efos",
     *  summary="Get the list of stok efos",
     *  tags={"Operation - Stok Efo"},
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
     *  @OA\Parameter(
     *      name="id_kontraktor",
     *      in="query",
     *      description="Id Kontraktor",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="grade",
     *      in="query",
     *      description="Grade",
     *      required=false,
     *      @OA\Schema(
     *          type="string"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request, StokEfoRepository $stok_efo_repository)
    {
        $data = $stok_efo_repository->getAll($request, $this->id_kontraktor);

        return ApiResponseClass::sendResponse($data, 'Stok Efo Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/stok_efos",
     *  summary="Add a new stok efos",
     *  tags={"Operation - Stok Efo"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date_in",
     *                  type="string",
     *                  format="date",
     *                  description="Date In of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="tonage_after",
     *                  type="integer",
     *                  description="Tonage After of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="mining_recovery_type",
     *                  type="string",
     *                  description="Mining Recovery Type",
     *                  enum={"truck_factor", "survey", "timbangan"}
     *              ),
     *              @OA\Property(
     *                  property="mining_recovery_value",
     *                  type="integer",
     *                  description="Mining Recovery Value of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment file for stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="number",
     *                  format="float",
     *                  description="Ni of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="fe",
     *                  type="number",
     *                  format="float",
     *                  description="Fe of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="co",
     *                  type="number",
     *                  format="float",
     *                  description="Co of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="sio2",
     *                  type="number",
     *                  format="float",
     *                  description="Sio2 of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="mgo2",
     *                  type="number",
     *                  format="float",
     *                  description="Mgo2 of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="number",
     *                  format="float",
     *                  description="Tonage of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="ritasi",
     *                  type="number",
     *                  format="float",
     *                  description="Ritasi of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="id_stok_eto",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description="List of Id Stok Eto"
     *              ),
     *              @OA\Property(
     *                  property="id_stok_in_pit",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description="List of Id Stok In Pit"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Success"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(StokEfoRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $attachment = add_file($request->attachment, 'stok_efo/');

            $dom_efo                = new DomEfo();
            $dom_efo->id_kontraktor = $this->id_kontraktor;
            $dom_efo->name          = $request->dome_name;
            $dom_efo->save();

            $stok_efo                        = new StokEfo();
            $stok_efo->id_kontraktor         = $this->id_kontraktor;
            $stok_efo->id_dom_efo            = $dom_efo->id_dom_efo;
            $stok_efo->date_in               = $request->date_in;
            $stok_efo->tonage_after          = $request->tonage_after;
            $stok_efo->mining_recovery_type  = $request->mining_recovery_type;
            $stok_efo->mining_recovery_value = $request->mining_recovery_value;
            $stok_efo->attachment            = $attachment;
            $stok_efo->ni                    = $request->ni;
            $stok_efo->fe                    = $request->fe;
            $stok_efo->co                    = $request->co;
            $stok_efo->sio2                  = $request->sio2;
            $stok_efo->mgo2                  = $request->mgo2;
            $stok_efo->tonage                = $request->tonage;
            $stok_efo->ritasi                = $request->ritasi;
            $stok_efo->save();

            $stok_efo_detail = [];

            // untuk input stok eto
            if (isset($request->id_stok_eto)) {
                $id_stok_eto = is_array($request->id_stok_eto) ? $request->id_stok_eto : explode(',', $request->id_stok_eto);

                foreach ($id_stok_eto as $key => $value) {
                    $stok_eto_detail = _getStokEtoDetail($value);

                    $stok_efo_detail[] = [
                        'id_stok_efo'    => $stok_efo->id_stok_efo,
                        'id_stok_eto'    => $value,
                        'id_dom_eto'     => $stok_eto_detail['id_dom_eto'],
                        'id_stok_in_pit' => null,
                        'id_dom_in_pit'  => null,
                        'ni'             => $stok_eto_detail['ni'],
                        'fe'             => $stok_eto_detail['fe'],
                        'co'             => $stok_eto_detail['co'],
                        'sio2'           => $stok_eto_detail['sio2'],
                        'mgo2'           => $stok_eto_detail['mgo2'],
                        'tonage'         => $stok_eto_detail['tonage'],
                        'ritasi'         => $stok_eto_detail['ritasi'],
                    ];

                    StokEto::whereIdStokEto($value)->update([
                        'date_out' => $request->date_in,
                    ]);
                }

                StokEto::whereIn('id_stok_eto', $id_stok_eto)->delete();
            }

            // untuk input stok in pit
            if (isset($request->id_stok_in_pit)) {
                $id_stok_in_pit = is_array($request->id_stok_in_pit) ? $request->id_stok_in_pit : explode(',', $request->id_stok_in_pit);

                foreach ($id_stok_in_pit as $key => $value) {
                    $stok_in_pit = StokInPit::find($value);

                    $stok_efo_detail[] = [
                        'id_stok_efo'    => $stok_efo->id_stok_efo,
                        'id_stok_eto'    => null,
                        'id_dom_eto'     => null,
                        'id_stok_in_pit' => $value,
                        'id_dom_in_pit'  => $stok_in_pit->id_dom_in_pit,
                        'ni'             => $stok_in_pit->ni,
                        'fe'             => $stok_in_pit->fe,
                        'co'             => $stok_in_pit->co,
                        'sio2'           => $stok_in_pit->sio2,
                        'mgo2'           => $stok_in_pit->mgo2,
                        'tonage'         => $stok_in_pit->tonage,
                        'ritasi'         => $stok_in_pit->ritasi,
                    ];
                }

                StokInPit::whereIn('id_stok_in_pit', $id_stok_in_pit)->delete();
            }

            foreach ($stok_efo_detail as $key => $value) {
                $detail_stok_efo                 = new StokEfoDetail();
                $detail_stok_efo->id_stok_efo    = $value['id_stok_efo'];
                $detail_stok_efo->id_stok_eto    = $value['id_stok_eto'];
                $detail_stok_efo->id_dom_eto     = $value['id_dom_eto'];
                $detail_stok_efo->id_stok_in_pit = $value['id_stok_in_pit'];
                $detail_stok_efo->id_dom_in_pit  = $value['id_dom_in_pit'];
                $detail_stok_efo->ni             = $value['ni'];
                $detail_stok_efo->fe             = $value['fe'];
                $detail_stok_efo->co             = $value['co'];
                $detail_stok_efo->sio2           = $value['sio2'];
                $detail_stok_efo->mgo2           = $value['mgo2'];
                $detail_stok_efo->tonage         = $value['tonage'];
                $detail_stok_efo->ritasi         = $value['ritasi'];
                $detail_stok_efo->save();
            }

            ActivityLogHelper::log('operation:stock_efo_create', 1, [
                'operation:dome_efo' => $dom_efo->name,
                'operation:date_in'  => $request->date_in,
                'operation:tonnage'  => $request->tonage,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_efo, 'Stok Efo Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_efo_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *  path="/stok_efos/{id}",
     *  summary="Update stok efo",
     *  tags={"Operation - Stok Efo"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          description="Id of stok eto"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="_method",
     *      in="query",
     *      description="HTTP Method",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          default="PUT"
     *      ),
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date_in",
     *                  type="string",
     *                  format="date",
     *                  description="Date In of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="tonage_after",
     *                  type="integer",
     *                  description="Tonage After of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="mining_recovery_type",
     *                  type="string",
     *                  description="Mining Recovery Type",
     *                  enum={"truck_factor", "survey", "timbangan"}
     *              ),
     *              @OA\Property(
     *                  property="mining_recovery_value",
     *                  type="integer",
     *                  description="Mining Recovery Value of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment file for stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="number",
     *                  format="float",
     *                  description="Ni of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="fe",
     *                  type="number",
     *                  format="float",
     *                  description="Fe of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="co",
     *                  type="number",
     *                  format="float",
     *                  description="Co of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="sio2",
     *                  type="number",
     *                  format="float",
     *                  description="Sio2 of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="mgo2",
     *                  type="number",
     *                  format="float",
     *                  description="Mgo2 of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="number",
     *                  format="float",
     *                  description="Tonage of stok_efos"
     *              ),
     *              @OA\Property(
     *                  property="ritasi",
     *                  type="number",
     *                  format="float",
     *                  description="Ritasi of stok_efos"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(StokEfoRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $data = StokEfo::find($id);

            if ($request->hasFile('attachment')) {
                $attachment = upd_file($request->attachment, $data->attachment, 'stok_efo/');
            } else {
                $attachment = $data->file;
            }

            $data->id_kontraktor         = $this->id_kontraktor;
            $data->date_in               = $request->date_in;
            $data->tonage_after          = $request->tonage_after;
            $data->mining_recovery_type  = $request->mining_recovery_type;
            $data->mining_recovery_value = $request->mining_recovery_value;
            $data->attachment            = $attachment;
            $data->ni                    = $request->ni;
            $data->fe                    = $request->fe;
            $data->co                    = $request->co;
            $data->sio2                  = $request->sio2;
            $data->mgo2                  = $request->mgo2;
            $data->tonage                = $request->tonage;
            $data->ritasi                = $request->ritasi;
            $data->save();

            ActivityLogHelper::log('operation:stock_efo_update', 1, [
                'operation:dome_efo' => $data->toDomEfo->name,
                'operation:date_in'  => $request->date_in,
                'operation:tonnage'  => $request->tonage,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Stok Eto Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_efo_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/stok_efos/{id}",
     *  summary="Delete stok efo",
     *  tags={"Operation - Stok Efo"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          description="Id of stok efo"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $data = StokEfo::with(['toStokEfoDetail', 'toDomEfo'])->find($id);

            foreach ($data->toStokEfoDetail as $key => $val) {
                if ($val->id_stok_in_pit != null) {
                    $stok_in_pit = StokInPit::onlyTrashed()->find($val->id_stok_in_pit);
                    $stok_in_pit->deleted_at = null;
                    $stok_in_pit->save();
                }

                if ($val->id_stok_eto != null) {
                    $stok_eto = StokEto::onlyTrashed()->find($val->id_stok_eto);
                    $stok_eto->deleted_at = null;
                    $stok_eto->save();
                }

                $val->delete();
            }

            if ($data->toDomEfo) {
                $data->id_dom_efo = null;

                $data->save();

                $data->delete();

                $data->toDomEfo->delete();
            }

            ActivityLogHelper::log('operation:stock_efo_delete', 1, [
                'operation:dome_efo'              => $data->toDomEfo->name,
                'operation:contractor'            => Kontraktor::find($data->id_kontraktor)->company,
                'operation:date_in'               => $data->date_in,
                'operation:tonnage'               => $data->tonage,
                'operation:mining_recovery_type'  => $data->mining_recovery_type,
                'operation:mining_recovery_value' => $data->mining_recovery_value,
                'operation:tonnage_after'         => $data->tonage_after,
                'operation:ritasion'              => $data->ritasi,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Stok Efo Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_efo_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/stok_efos/details/{id}",
     *  summary="Get the list of stok efos details",
     *  tags={"Operation - Stok Efo"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          description="Id of stok_eto"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function details($id)
    {
        $data = StokEfoDetail::whereIdStokEfo($id)->get();

        return ApiResponseClass::sendResponse(StokEfoDetailResource::collection($data), 'Stok Eto Details Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/stok_efos/transfer",
     *  summary="Transfer stok efos",
     *  tags={"Operation - Stok Efo"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_stok_efo",
     *                  type="array",
     *                  description="Id of stok_efos",
     *                  @OA\Items(type="integer")
     *              ),
     *              @OA\Property(
     *                  property="id_kontraktor_sender",
     *                  type="integer",
     *                  description="Id Kontraktor Sender"
     *              ),
     *              @OA\Property(
     *                  property="id_kontraktor_receipt",
     *                  type="integer",
     *                  description="Id Kontraktor Receipt"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function transfer(Request $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $stok_efos             = $request->id_stok_efo;
            $id_kontraktor_sender  = $request->id_kontraktor_sender;
            $id_kontraktor_receipt = $request->id_kontraktor_receipt;

            foreach ($stok_efos as $key => $value) {
                $stok_efos = StokEfo::where('id_stok_efo', $value)->where('id_kontraktor', $id_kontraktor_sender)->first();

                if ($stok_efos) {
                    $stok_efos->update([
                        'id_kontraktor' => $id_kontraktor_receipt,
                    ]);
                }
            }

            ActivityLogHelper::log('operation:stock_efo_transfer', 1, [
                'operation:contractor_sender'  => Kontraktor::find($id_kontraktor_sender)->company,
                'operation:contractor_receipt' => Kontraktor::find($id_kontraktor_receipt)->company
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_efos, 'Stok Efo Transfered Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_efo_transfer', 0, ['error' => $e->getMessage()]);
            DB::connection('operation')->rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function generate()
    {
        try {
            $generate_dom = generateDomeNumber('operation', 'dom_efo', $this->id_kontraktor, 'name', $this->initial . '-EFO');
            $name         = substr($generate_dom, 0, -4);
            $number       = substr($generate_dom, -4);

            $response = [
                'name'   => $name,
                'number' => $number,
            ];
            return ApiResponseClass::sendResponse($response, 'Dome Name Generate Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }
}
