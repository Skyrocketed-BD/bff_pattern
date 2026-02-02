<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\CoaGroupRequest;
use App\Models\finance\CoaGroup;
use App\Models\finance\CoaHead;
use Illuminate\Support\Facades\DB;

class CoaGroupController extends Controller
{
    /**
     * @OA\Get(
     *  path="/coa/groups",
     *  summary="Get the list of coa groups",
     *  tags={"Finance - Coa Group"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = CoaGroup::orderBy('id_coa_group', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Coa Group Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/coa/groups",
     *  summary="Add a new coa group",
     *  tags={"Finance - Coa Group"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Coa group name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "Asset"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(CoaGroupRequest $request)
    {
        try {
            $count = CoaGroup::count() + 1;
            $coa   = str_pad($count, get_arrangement('coa_digit'), '0', STR_PAD_RIGHT);

            $coa_group       = new CoaGroup();
            $coa_group->name = $request->name;
            $coa_group->coa  = $coa;
            $coa_group->save();

            if ($request->isSkip) {
                $coa_head               = new CoaHead();
                $coa_head->id_coa_group = $coa_group->id_coa_group;
                $coa_head->name         = $request->name;
                $coa_head->coa          = substr_replace($coa, '1', 1, 1);
                $coa_head->save();
            }

            ActivityLogHelper::log('finance:coa_group_create', 1, [
                'finance:coa_group_name' => $coa_group->name,
                'finance:coa_head_skip'  => $request->isSkip ? 'Yes' : 'No',
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($coa_group, 'Coa Group Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_group_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/coa/groups/{id}",
     *  summary="Get coa group by id",
     *  tags={"Finance - Coa Group"},
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
        $data = CoaGroup::find($id);

        return ApiResponseClass::sendResponse($data, 'Coa Group Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/coa/groups/{id}",
     *  summary="Update coa group by id",
     *  tags={"Finance - Coa Group"},
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
     *                  property="name",
     *                  type="string",
     *                  description="Coa group name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "Asset"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(CoaGroupRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = CoaGroup::find($id);

            $data->update([
                'name' => $request->name,
            ]);

            ActivityLogHelper::log('finance:coa_group_update', 1, [
                'finance:coa_group_name' => $data->name
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Coa Group Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_group_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/coa/groups/{id}",
     *  summary="Delete coa group by id",
     *  tags={"Finance - Coa Group"},
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
            $data = CoaGroup::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:coa_group_delete', 1, [
                'finance:coa_group_name' => $data->name
            ]);

            return ApiResponseClass::sendResponse($data, 'Coa Group Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_group_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
