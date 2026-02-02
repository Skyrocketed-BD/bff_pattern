<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\CoaBodyRequest;
use App\Http\Resources\finance\CoaBodyResource;
use App\Models\finance\CoaBody;
use Illuminate\Support\Facades\DB;

class CoaBodyController extends Controller
{
    /**
     * @OA\Get(
     *  path="/coa/bodies",
     *  summary="Get the list of coa bodies",
     *  tags={"Finance - Coa Body"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = CoaBody::orderBy('id_coa_body', 'asc')->get();

        return ApiResponseClass::sendResponse(CoaBodyResource::collection($data), 'Coa Body Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/coa/bodies",
     *  summary="Add a new coa body",
     *  tags={"Finance - Coa Body"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa_head",
     *                  type="integer",
     *                  description="Coa head id"
     *              ),
     *              @OA\Property(
     *                  property="id_coa_clasification",
     *                  type="integer",
     *                  description="Coa clasification id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Coa name"
     *              ),
     *              @OA\Property(
     *                  property="coa",
     *                  type="integer",
     *                  description="Coa code"
     *              ),
     *              required={"id_coa_head", "id_coa_clasification", "name", "coa"},
     *              example={
     *                  "id_coa_head": 1,
     *                  "id_coa_clasification": 1,
     *                  "name": "Asset",
     *                  "coa": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(CoaBodyRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $coa_body                       = new CoaBody();
            $coa_body->id_coa_head          = $request->id_coa_head;
            $coa_body->id_coa_clasification = $request->id_coa_clasification;
            $coa_body->name                 = $request->name;
            $coa_body->coa                  = $request->coa;
            $coa_body->save();

            ActivityLogHelper::log('finance:coa_body_create', 1, [
                'finance:coa_body'           => $coa_body->name,
                'finance:coa'                => $coa_body->coa,
                'finance:coa_classification' => $coa_body->toCoaClasification->name
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($coa_body, 'Coa Body Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_body_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/coa/bodies/{id}",
     *  summary="Get the detail of coa body",
     *  tags={"Finance - Coa Body"},
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
    public function show($id)
    {
        $data = CoaBody::find($id);

        return ApiResponseClass::sendResponse(CoaBodyResource::make($data), 'Coa Body Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/coa/bodies/{id}",
     *  summary="Update coa body",
     *  tags={"Finance - Coa Body"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa_head",
     *                  type="integer",
     *                  description="Coa head id"
     *              ),
     *              @OA\Property(
     *                  property="id_coa_clasification",
     *                  type="integer",
     *                  description="Coa clasification id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Coa name"
     *              ),
     *              @OA\Property(
     *                  property="coa",
     *                  type="integer",
     *                  description="Coa code"
     *              ),
     *              required={"id_coa_head", "id_coa_clasification", "name", "coa"},
     *              example={
     *                  "id_coa_head": 1,
     *                  "id_coa_clasification": 1,
     *                  "name": "Asset",
     *                  "coa": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(CoaBodyRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = CoaBody::find($id);

            $data->update([
                'id_coa_head'          => $request->id_coa_head,
                'id_coa_clasification' => $request->id_coa_clasification,
                'name'                 => $request->name,
                'coa'                  => $request->coa,
            ]);

            ActivityLogHelper::log('finance:coa_body_update', 1, [
                'finance:coa_body'           => $data->name,
                'finance:coa'                => $data->coa,
                'finance:coa_classification' => $data->toCoaClasification->name
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Coa Body Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_body_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/coa/bodies/{id}",
     *  summary="Delete a coa body",
     *  tags={"Finance - Coa Body"},
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
            $data = CoaBody::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:coa_body_delete', 1, [
                'finance:coa_body'           => $data->name,
                'finance:coa'                => $data->coa,
                'finance:coa_classification' => $data->toCoaClasification->name
            ]);

            return ApiResponseClass::sendResponse($data, 'Coa Body Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_body_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/coa/bodies/details/{id}",
     *  summary="Get the detail of coa body",
     *  tags={"Finance - Coa Body"},
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
    public function details($id)
    {
        $data = CoaBody::whereIdCoaHead($id)->get();

        return ApiResponseClass::sendResponse(CoaBodyResource::collection($data), 'Coa Body Retrieved Successfully');
    }
}
