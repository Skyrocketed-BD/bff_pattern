<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\AssetGroupRequest;
use App\Models\finance\AssetGroup;
use Illuminate\Support\Facades\DB;

class AssetGroupController extends Controller
{
    /**
     * @OA\Get(
     *  path="/asset/group",
     *  summary="Get the list of asset group",
     *  tags={"Finance - Asset Group"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = AssetGroup::orderBy('id_asset_group', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Asset Coa Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/asset/group",
     *  summary="Add a new asset group",
     *  tags={"Finance - Asset Group"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Asset name"
     *              ),
     *              @OA\Property(
     *                  property="rate",
     *                  type="integer",
     *                  description="Rate"
     *              ),
     *              @OA\Property(
     *                  property="benefit",
     *                  type="string",
     *                  description="Benefit"
     *              ),
     *              @OA\Property(
     *                  property="group",
     *                  type="string",
     *                  description="Group (bangunan, bukan_bangunan)"
     *              ),
     *              required={"name", "rate", "benefit", "group"},
     *              example={
     *                  "name": "Asset",
     *                  "rate": 1,
     *                  "benefit": "5 Tahun",
     *                  "group": "bangunan"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(AssetGroupRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $asset_group          = new AssetGroup();
            $asset_group->name    = $request->name;
            $asset_group->rate    = $request->rate;
            $asset_group->benefit = $request->benefit;
            $asset_group->group   = $request->group;
            $asset_group->save();

            ActivityLogHelper::log('finance:asset_group_create', 1, [
                'name'            => $asset_group->name,
                'finance:rate'    => $asset_group->rate,
                'finance:benefit' => $asset_group->benefit,
                'group'           => $asset_group->group
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($asset_group, 'Asset Group Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_group_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/asset/group/{id}",
     *  summary="Get the detail of asset group",
     *  tags={"Finance - Asset Group"},
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
        $data = AssetGroup::find($id);

        return ApiResponseClass::sendResponse($data, 'Asset Group Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/asset/group/{id}",
     *  summary="Update the asset group",
     *  tags={"Finance - Asset Group"},
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
     *                  description="Asset name"
     *              ),
     *              @OA\Property(
     *                  property="rate",
     *                  type="integer",
     *                  description="Rate"
     *              ),
     *              @OA\Property(
     *                  property="benefit",
     *                  type="string",
     *                  description="Benefit"
     *              ),
     *              @OA\Property(
     *                  property="group",
     *                  type="string",
     *                  description="Group (bangunan, bukan_bangunan)"
     *              ),
     *              required={"name", "rate", "benefit", "group"},
     *              example={
     *                  "name": "Asset",
     *                  "rate": 1,
     *                  "benefit": "5 Tahun",
     *                  "group": "bangunan"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(AssetGroupRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = AssetGroup::find($id);

            $data->update([
                'name'    => $request->name,
                'rate'    => $request->rate,
                'benefit' => $request->benefit,
                'group'   => $request->group,
            ]);

            ActivityLogHelper::log('finance:asset_group_update', 1, [
                'name'            => $request->name,
                'finance:rate'    => $request->rate,
                'finance:benefit' => $request->benefit,
                'group'           => $request->group
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Asset Group Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_group_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/asset/group/{id}",
     *  summary="Delete the asset group",
     *  tags={"Finance - Asset Group"},
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
            $data = AssetGroup::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:asset_group_delete', 1, [
                'name'            => $data->name,
                'finance:rate'    => $data->rate,
                'finance:benefit' => $data->benefit,
                'group'           => $data->group
            ]);

            return ApiResponseClass::sendResponse($data, 'Asset Group Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_group_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
