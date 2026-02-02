<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\AssetCoaRequest;
use App\Http\Resources\finance\AssetCoaResource;
use App\Models\finance\AssetCoa;
use App\Models\finance\Coa;
use Illuminate\Support\Facades\DB;

class AssetCoaController extends Controller
{
    /**
     * @OA\Get(
     *  path="/asset/coa",
     *  summary="Get the list of asset coa",
     *  tags={"Finance - Asset Coa"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = AssetCoa::orderBy('id_asset_coa', 'asc')->get();

        return ApiResponseClass::sendResponse(AssetCoaResource::collection($data), 'Asset Coa Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/asset/coa",
     *  summary="Add a new asset coa",
     *  tags={"Finance - Asset Coa"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Asset name"
     *              ),
     *              required={"id_coa", "name"},
     *              example={
     *                  "id_coa": 1,
     *                  "name": "Asset"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(AssetCoaRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $asset_coa                    = new AssetCoa();
            $asset_coa->name              = $request->name;
            $asset_coa->id_coa            = $request->id_coa;
            $asset_coa->id_coa_acumulated = $request->id_coa_acumulated;
            $asset_coa->id_coa_expense    = $request->id_coa_expense;
            $asset_coa->save();

            ActivityLogHelper::log('finance:asset_coa_create', 1, [
                'name'                   => $request->name,
                'finance:asset_coa'      => Coa::find($request->id_coa)->name,
                'finance:coa_acumulated' => Coa::find($request->id_coa_acumulated)->name,
                'finance:coa_expense'    => Coa::find($request->id_coa_expense)->name,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($asset_coa, 'Asset Coa Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_coa_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/asset/coa/{id}",
     *  summary="Get the detail of asset coa",
     *  tags={"Finance - Asset Coa"},
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
        $data = AssetCoa::find($id);

        return ApiResponseClass::sendResponse(AssetCoaResource::make($data), 'Asset Coa Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/asset/coa/{id}",
     *  summary="Update the asset coa",
     *  tags={"Finance - Asset Coa"},
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
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Asset name"
     *              ),
     *              required={"id_coa", "name"},
     *              example={
     *                  "id_coa": 1,
     *                  "name": "Asset"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(AssetCoaRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = AssetCoa::find($id);

            $data->update([
                'name'              => $request->name,
                'id_coa'            => $request->id_coa,
                'id_coa_acumulated' => $request->id_coa_acumulated,
                'id_coa_expense'    => $request->id_coa_expense,
            ]);

            DB::connection('finance')->commit();

            ActivityLogHelper::log('finance:asset_coa_update', 1, [
                'name'                   => $data->name,
                'finance:asset_coa'      => Coa::find($request->id_coa)->name,
                'finance:coa_acumulated' => Coa::find($request->id_coa_acumulated)->name,
                'finance:coa_expense'    => Coa::find($request->id_coa_expense)->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Asset Coa Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_coa_update', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/asset/coa/{id}",
     *  summary="Delete the asset coa",
     *  tags={"Finance - Asset Coa"},
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
            $data = AssetCoa::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:asset_coa_delete', 1, [
                'name'                   => $data->name,
                'finance:asset_coa'      => Coa::find($data->id_coa)->name,
                'finance:coa_acumulated' => Coa::find($data->id_coa_acumulated)->name,
                'finance:coa_expense'    => Coa::find($data->id_coa_expense)->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Asset Coa Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:asset_coa_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
