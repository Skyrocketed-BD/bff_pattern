<?php

namespace App\Http\Controllers\Api\Finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\AssetCategoryRequest;
use App\Http\Resources\finance\AssetCategoryResource;
use App\Models\finance\AssetCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AssetCategoryController extends Controller
{
    public function index()
    {
        $data = AssetCategory::with(['toAssetHead.toAssetItem'])->orderBy('name', 'asc')->get();

        return ApiResponseClass::sendResponse(AssetCategoryResource::collection($data), 'Asset Category Retrieved Successfully');
    }

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

            return ApiResponseClass::sendResponse($asset_category, 'Asset Category Created Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function show($id)
    {
        $data = AssetCategory::find($id);

        return ApiResponseClass::sendResponse(AssetCategoryResource::make($data), 'Asset Category Retrieved Successfully');
    }

    public function update(AssetCategoryRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();

        try {
            $data = AssetCategory::find($id);

            if (!$data) {
                return Response::json([
                    'success' => false,
                    'message' => 'Data Not Found',
                ], 404);
            }

            $data->update([
                'name'           => $request->name,
                'presence'       => $request->presence,
                'is_depreciable' => $request->is_depreciable,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Asset Category Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = AssetCategory::find($id);

            if (!$data) {
                return Response::json([
                    'success' => false,
                    'message' => 'Data Not Found',
                ], 404);
            }

            $data->delete();

            return ApiResponseClass::sendResponse($data, 'Asset Category Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
