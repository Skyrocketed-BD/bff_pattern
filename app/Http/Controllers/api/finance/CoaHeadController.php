<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\CoaHeadRequest;
use App\Http\Resources\finance\CoaHeadResource;
use App\Models\finance\CoaHead;
use Illuminate\Support\Facades\DB;

class CoaHeadController extends Controller
{
    /**
     * @OA\Get(
     *  path="/coa/heads",
     *  summary="Get the list of coa heads",
     *  tags={"Finance - Coa Head"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = CoaHead::orderBy('id_coa_head', 'asc')->get();

        return ApiResponseClass::sendResponse(CoaHeadResource::collection($data), 'Coa Head Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/coa/heads",
     *  summary="Add a new coa head",
     *  tags={"Finance - Coa Head"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa_group",
     *                  type="integer",
     *                  description="Coa group id"
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
     *              required={"id_coa_group", "name", "coa", "default"},
     *              example={
     *                  "id_coa_group": 1,
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
    public function store(CoaHeadRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $coa_head = new CoaHead();
            $coa_head->id_coa_group = $request->id_coa_group;
            $coa_head->name         = $request->name;
            $coa_head->coa          = $request->coa;
            $coa_head->save();

            ActivityLogHelper::log('finance:coa_head_create', 1, [
                'finance:coa_head_name' => $coa_head->name,
                'finance:coa'           => $coa_head->coa
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($coa_head, 'Coa Head Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_head_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/coa/heads/{id}",
     *  summary="Get the detail of coa head",
     *  tags={"Finance - Coa Head"},
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
        $data = CoaHead::find($id);

        return ApiResponseClass::sendResponse(CoaHeadResource::make($data), 'Coa Head Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/coa/heads/{id}",
     *  summary="Update the detail of coa head",
     *  tags={"Finance - Coa Head"},
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
     *                  property="id_coa_group",
     *                  type="integer",
     *                  description="Coa group id"
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
     *              required={"id_coa_group", "name", "coa", "default"},
     *              example={
     *                  "id_coa_group": 1,
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
    public function update(CoaHeadRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = CoaHead::find($id);

            $data->update([
                'id_coa_group' => $request->id_coa_group,
                'name'         => $request->name,
                'coa'          => $request->coa,
            ]);

            ActivityLogHelper::log('finance:coa_head_update', 1, [
                'finance:coa_head_name' => $data->name,
                'finance:coa'           => $data->coa
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Coa Head Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_head_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/coa/heads/{id}",
     *  summary="Delete a coa head",
     *  tags={"Finance - Coa Head"},
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
            $data = CoaHead::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:coa_head_delete', 1, [
                'finance:coa_head_name' => $data->name,
                'finance:coa'           => $data->coa
            ]);

            return ApiResponseClass::sendResponse($data, 'Coa Head Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_head_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/coa/heads/details/{id}",
     *  summary="Get the detail of coa head",
     *  tags={"Finance - Coa Head"},
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
        $data = CoaHead::whereIdCoaGroup($id)->get();

        return ApiResponseClass::sendResponse(CoaHeadResource::collection($data), 'Coa Head Retrieved Successfully');
    }
}
