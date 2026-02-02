<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\AssetCategoryRequest;
use App\Http\Resources\finance\AssetCategoryResource;
use App\Models\finance\AssetCategory;
use Illuminate\Support\Facades\DB;

class AssetCategoryController extends Controller
{
    /**
     * @OA\Get(
     *  path="/asset/category",
     *  summary="Get the list of asset category",
     *  tags={"Finance - Asset Category"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = AssetCategory::with(['toAssetHead.toAssetItem'])->orderBy('name', 'asc')->get();

        return ApiResponseClass::sendResponse(AssetCategoryResource::collection($data), 'Asset Category Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/asset/category",
     *  summary="Add a new asset category",
     *  tags={"Finance - Asset Category"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Asset category name"
     *              ),
     *              @OA\Property(
     *                  property="presence",
     *                  type="string",
     *                  description="Asset category presence"
     *              ),
     *              required={"name", "presence"},
     *              example={
     *                  "name": "Asset Category",
     *                  "presence": "tangible"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(AssetCategoryRequest $request)
    {
        DB::connection('finance')->beginTransaction();

        try {
            $asset_category                 = new AssetCategory();
            $asset_category->name           = $request->name;
            $asset_category->presence       = $request->presence;
            $asset_category->is_depreciable = $request->is_depreciable;
            $asset_category->save();

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:asset_category_create', 1, [
                'name'                => $request->name,
                'finance:presence'    => $request->presence,
                'finance:depreciable' => $request->is_depreciable ? 'Depreciating' : 'Non Depreciating'
            ]);
            return ApiResponseClass::sendResponse($asset_category, 'Asset Category Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_category_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/asset/category/{id}",
     *  summary="Get the detail of asset category",
     *  tags={"Finance - Asset Category"},
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
        $data = AssetCategory::find($id);

        return ApiResponseClass::sendResponse($data, 'Asset Category Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/asset/category/{id}",
     *  summary="Update the asset category",
     *  tags={"Finance - Asset Category"},
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
     *                  description="Asset category name"
     *              ),
     *              @OA\Property(
     *                  property="presence",
     *                  type="string",
     *                  description="Asset category presence"
     *              ),
     *              required={"name", "presence"},
     *              example={
     *                  "name": "Asset Category",
     *                  "presence": "tangible"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(AssetCategoryRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();

        try {
            $data = AssetCategory::find($id);

            $data->update([
                'name'           => $request->name,
                'presence'       => $request->presence,
                'is_depreciable' => $request->is_depreciable,
            ]);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:asset_category_update', 1, [
                'name'                => $request->name,
                'finance:presence'    => $request->presence,
                'finance:depreciable' => $request->is_depreciable ? 'Depreciating' : 'Non Depreciating'
            ]);

            return ApiResponseClass::sendResponse($data, 'Asset Category Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_category_update', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/asset/category/{id}",
     *  summary="Delete the asset category",
     *  tags={"Finance - Asset Category"},
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
            $data = AssetCategory::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:asset_category_delete', 1, [
                'name'                => $data->name,
                'finance:presence'    => $data->presence,
                'finance:depreciable' => $data->is_depreciable ? 'Depreciating' : 'Non Depreciating'
            ]);

            return ApiResponseClass::sendResponse($data, 'Asset Category Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_category_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
