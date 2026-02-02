<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\StokEtoRequest;
use App\Http\Resources\operation\StokEtoDetailResource;
use App\Models\operation\DomEto;
use App\Models\operation\Kontraktor;
use App\Models\operation\StokEto;
use App\Models\operation\StokEtoDetail;
use App\Models\operation\StokInPit;
use App\Repositories\operation\StokEtoRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokEtoController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/stok_etos",
     *  summary="Get the list of stok etos",
     *  tags={"Operation - Stok Eto"},
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
    public function index(Request $request, StokEtoRepository $stok_eto_repository)
    {
        $data = $stok_eto_repository->getAll($request, $this->id_kontraktor);

        return ApiResponseClass::sendResponse($data, 'Stok Eto Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/stok_etos",
     *  summary="Add a new stok etos",
     *  tags={"Operation - Stok Eto"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="date_in",
     *                  type="string",
     *                  format="date",
     *                  description="Date In of stok_etos"
     *              ),
     *              @OA\Property(
     *                  property="tonage_after",
     *                  type="integer",
     *                  description="Tonage After of stok_etos"
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
     *                  description="Mining Recovery Value of stok_etos"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment of stok_eto (file upload)"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="number",
     *                  format="float",
     *                  description="Ni of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="fe",
     *                  type="number",
     *                  format="float",
     *                  description="Fe of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="co",
     *                  type="number",
     *                  format="float",
     *                  description="Co of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="sio2",
     *                  type="number",
     *                  format="float",
     *                  description="Sio2 of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="mgo2",
     *                  type="number",
     *                  format="float",
     *                  description="Mgo2 of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="number",
     *                  format="float",
     *                  description="Tonage of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="ritasi",
     *                  type="number",
     *                  format="float",
     *                  description="Ritasi of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="id_stok_in_pit",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  description="Id Stok In Pit of stok_etos"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(StokEtoRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $attachment = add_file($request->attachment, 'stok_eto/');

            $dom_eto                = new DomEto();
            $dom_eto->id_kontraktor = $this->id_kontraktor;
            $dom_eto->name          = $request->dome_name;
            $dom_eto->save();

            $stok_eto                        = new StokEto();
            $stok_eto->id_kontraktor         = $this->id_kontraktor;
            $stok_eto->id_dom_eto            = $dom_eto->id_dom_eto;
            $stok_eto->date_in               = $request->date_in;
            $stok_eto->tonage_after          = $request->tonage_after;
            $stok_eto->mining_recovery_type  = $request->mining_recovery_type;
            $stok_eto->mining_recovery_value = $request->mining_recovery_value;
            $stok_eto->attachment            = $attachment;
            $stok_eto->ni                    = $request->ni;
            $stok_eto->fe                    = $request->fe;
            $stok_eto->co                    = $request->co;
            $stok_eto->sio2                  = $request->sio2;
            $stok_eto->mgo2                  = $request->mgo2;
            $stok_eto->tonage                = $request->tonage;
            $stok_eto->ritasi                = $request->ritasi;
            $stok_eto->save();

            $id_stok_in_pit = is_array($request->id_stok_in_pit) ? $request->id_stok_in_pit : explode(',', $request->id_stok_in_pit);

            foreach ($id_stok_in_pit as $key => $value) {
                $stok_in_pit = StokInPit::find($value);

                $stok_eto_deetail                 = new StokEtoDetail();
                $stok_eto_deetail->id_stok_eto    = $stok_eto->id_stok_eto;
                $stok_eto_deetail->id_stok_in_pit = $value;
                $stok_eto_deetail->id_dom_in_pit  = $stok_in_pit->id_dom_in_pit;
                $stok_eto_deetail->ni             = $stok_in_pit->ni;
                $stok_eto_deetail->fe             = $stok_in_pit->fe;
                $stok_eto_deetail->co             = $stok_in_pit->co;
                $stok_eto_deetail->sio2           = $stok_in_pit->sio2;
                $stok_eto_deetail->mgo2           = $stok_in_pit->mgo2;
                $stok_eto_deetail->tonage         = $stok_in_pit->tonage;
                $stok_eto_deetail->ritasi         = $stok_in_pit->ritasi;
                $stok_eto_deetail->save();
            }

            ActivityLogHelper::log('operation:stock_eto_create', 1, [
                'operation:dome_eto' => $dom_eto->name,
                'operation:date_in'  => $stok_eto->date_in,
                'operation:tonnage'  => $stok_eto->tonage,
            ]);

            StokInPit::whereIn('id_stok_in_pit', $id_stok_in_pit)->delete();

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_eto, 'Stok Eto Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_eto_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *  path="/stok_etos/{id}",
     *  summary="Update stok eto",
     *  tags={"Operation - Stok Eto"},
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
     *                  description="Date In of stok_etos"
     *              ),
     *              @OA\Property(
     *                  property="tonage_after",
     *                  type="integer",
     *                  description="Tonage After of stok_etos"
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
     *                  description="Mining Recovery Value of stok_etos"
     *              ),
     *              @OA\Property(
     *                  property="attachment",
     *                  type="string",
     *                  format="binary",
     *                  description="Attachment of stok_eto (file upload)"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="number",
     *                  format="float",
     *                  description="Ni of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="fe",
     *                  type="number",
     *                  format="float",
     *                  description="Fe of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="co",
     *                  type="number",
     *                  format="float",
     *                  description="Co of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="sio2",
     *                  type="number",
     *                  format="float",
     *                  description="Sio2 of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="mgo2",
     *                  type="number",
     *                  format="float",
     *                  description="Mgo2 of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="number",
     *                  format="float",
     *                  description="Tonage of stok_eto"
     *              ),
     *              @OA\Property(
     *                  property="ritasi",
     *                  type="number",
     *                  format="float",
     *                  description="Ritasi of stok_eto"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(StokEtoRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $data = StokEto::find($id);

            if ($request->hasFile('attachment')) {
                $attachment = upd_file($request->attachment, $data->attachment, 'stok_eto/');
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

            ActivityLogHelper::log('operation:stock_eto_update', 1, [
                'operation:dome_eto' => $data->toDomEto->name,
                'operation:date_in'  => $data->date_in,
                'operation:tonnage'   => $data->tonage
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Stok Eto Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok:eto_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/stok_etos/{id}",
     *  summary="Delete stok eto",
     *  tags={"Operation - Stok Eto"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          description="Id of stok eto"
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
            $data = StokEto::with(['toStokEtoDetail', 'toDomEto'])->find($id);

            foreach ($data->toStokEtoDetail as $key => $val) {
                $stok_in_pit = StokInPit::onlyTrashed()->find($val->id_stok_in_pit);
                $stok_in_pit->deleted_at = null;
                $stok_in_pit->save();

                $val->delete();
            }

            if ($data->toDomEto) {
                $data->id_dom_eto = null;

                $data->save();

                $data->delete();

                $data->toDomEto->delete();
            }

            ActivityLogHelper::log('operation:stock_eto_delete', 1, [
                'operation:dome_eto'              => $data->toDomEto->name,
                'operation:contractor'            => Kontraktor::find($data->id_kontraktor)->company,
                'operation:date_in'               => $data->date_in,
                'operation:tonnage'               => $data->tonage,
                'operation:mining_recovery_type'  => $data->mining_recovery_type,
                'operation:mining_recovery_value' => $data->mining_recovery_value,
                'operation:tonnage_after'         => $data->tonage_after,
                'operation:ritasion'              => $data->ritasi
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Stok Eto Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok:eto_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/stok_etos/details/{id}",
     *  summary="Get the list of stok etos details",
     *  tags={"Operation - Stok Eto"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          description="Id of stok eto"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function details($id)
    {
        $data = StokEtoDetail::whereIdStokEto($id)->get();

        return ApiResponseClass::sendResponse(StokEtoDetailResource::collection($data), 'Stok Eto Details Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/stok_etos/transfer",
     *  summary="Transfer stok eto",
     *  tags={"Operation - Stok Eto"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_stok_eto",
     *                  type="array",
     *                  description="Id of stok_eto",
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
            $stok_eto              = $request->id_stok_eto;
            $id_kontraktor_sender  = $request->id_kontraktor_sender;
            $id_kontraktor_receipt = $request->id_kontraktor_receipt;

            foreach ($stok_eto as $key => $value) {
                $stok_eto = StokEto::where('id_stok_eto', $value)->where('id_kontraktor', $id_kontraktor_sender)->first();

                if ($stok_eto) {
                    $stok_eto->update([
                        'id_kontraktor' => $id_kontraktor_receipt,
                    ]);
                }
            }

            ActivityLogHelper::log('operation:stock_eto_transfer', 1, [
                'operation:contractor_sender'  => Kontraktor::find($id_kontraktor_sender)->company,
                'operation:contractor_receipt' => Kontraktor::find($id_kontraktor_receipt)->company
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_eto, 'Stok Eto Transfered Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok:eto_transfer', 0, ['error' => $e->getMessage()]);
            DB::connection('operation')->rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function generate()
    {
        try {
            $generate_dom = generateDomeNumber('operation', 'dom_eto', $this->id_kontraktor, 'name', $this->initial . '-ETO');
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
