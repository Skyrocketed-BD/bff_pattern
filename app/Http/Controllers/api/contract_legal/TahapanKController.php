<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\TahapanKRequest;
use App\Http\Resources\contract_legal\TahapanKResource;
use App\Models\contract_legal\TahapanK;
use Illuminate\Support\Facades\DB;

class TahapanKController extends Controller
{
    /**
     * @OA\Get(
     *     path="/tahapan_k",
     *     tags={"Contract Legal - Tahapan Kontrak"},
     *     summary="Get all Tahapan Kontrak",
     *     description="Get all Tahapan Kontrak",
     *     @OA\Response(
     *         response=200,
     *         description="Tahapan Kontrak Retrieved Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = TahapanK::orderBy('id_tahapan_k', 'asc')->get();

        return ApiResponseClass::sendResponse(TahapanKResource::collection($data), 'Tahapan Kontrak Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/tahapan_k",
     *     summary="Create a new tahapan_k",
     *     tags={"Contract Legal - Tahapan Kontrak"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Tahapan name"
     *                 ),
     *                 required={"name"},
     *                 example={
     *                     "name": "Tahapan"
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Return a list of resources"),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TahapanKRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $tahapan_k       = new TahapanK();
            $tahapan_k->name = $request->name;
            $tahapan_k->save();

            ActivityLogHelper::log('contract:stages_contract_create', 1, ['name' => $tahapan_k->name]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($tahapan_k, 'Tahapan Kontrak Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_contract_create', 0, ['name' => $tahapan_k->name]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/tahapan_k/{id}",
     *     summary="Get the tahapan_k",
     *     tags={"Contract Legal - Tahapan Kontrak"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(response=200, description="Return a list of resources"),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = TahapanK::find($id);

        return ApiResponseClass::sendResponse(TahapanKResource::make($data), 'Tahapan Kontrak Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *     path="/tahapan_k/{id}",
     *     summary="Update the tahapan_k",
     *     tags={"Contract Legal - Tahapan Kontrak"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Tahapan name"
     *                 ),
     *                 required={"name"},
     *                 example={
     *                     "name": "Tahapan"
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tahapan Kontrak Updated Successfully"),
     *     @OA\Response(response=500, description="Internal Server Error"),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TahapanKRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = TahapanK::find($id);

            $data->update([
                'name'     => $request->name,
            ]);

            ActivityLogHelper::log('contract:stages_contract_update', 1, ['name' => $data->name]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Tahapan Kontrak Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_contract_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = TahapanK::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:stages_contract_delete', 1, ['name' => $data->name]);

            return ApiResponseClass::sendResponse($data, 'Tahapan Kontrak Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_contract_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
