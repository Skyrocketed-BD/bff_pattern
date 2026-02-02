<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\TahapanNlRequest;
use App\Http\Resources\contract_legal\TahapanNlResource;
use App\Models\contract_legal\TahapanNl;
use Illuminate\Support\Facades\DB;

class TahapanNlController extends Controller
{
    /**
     * @OA\Get(
     *      path="/tahapan_nl",
     *      summary="Get the list of tahapan_nl",
     *      tags={"Contract Legal - Tahapan Non Litigasi"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = TahapanNl::orderBy('id_tahapan_nl', 'asc')->get();

        return ApiResponseClass::sendResponse(TahapanNlResource::collection($data), 'Tahapan Non Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/tahapan_nl",
     *      summary="Create a new tahapan_nl",
     *      tags={"Contract Legal - Tahapan Non Litigasi"},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      description="Tahapan name"
     *                  ),
     *                  required={"name"},
     *                  example={
     *                      "name": "Tahapan"
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TahapanNlRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $tahapan_nl = new TahapanNl();
            $tahapan_nl->name = $request->name;
            $tahapan_nl->save();

            ActivityLogHelper::log('contract:stages_non_litigation_create', 1, [
                'name' => $tahapan_nl->name
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($tahapan_nl, 'Tahapan Non Litigasi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_non_litigation_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/tahapan_nl/{id}",
     *      summary="Get the tahapan_nl",
     *      tags={"Contract Legal - Tahapan Non Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = TahapanNl::find($id);

        return ApiResponseClass::sendResponse(TahapanNlResource::make($data), 'Tahapan Non Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *      path="/tahapan_nl/{id}",
     *      summary="Update the tahapan_nl",
     *      tags={"Contract Legal - Tahapan Non Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      description="Tahapan name"
     *                  ),
     *                  required={"name"},
     *                  example={
     *                      "name": "Tahapan"
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TahapanNlRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = TahapanNl::find($id);

            $data->update([
                'name' => $request->name,
            ]);

            ActivityLogHelper::log('contract:stages_non_litigation_update', 1, [
                'name' => $data->name
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Tahapan Non Litigasi Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_non_litigation_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *      path="/tahapan_nl/{id}",
     *      summary="Delete the tahapan_nl",
     *      tags={"Contract Legal - Tahapan Non Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = TahapanNl::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:stages_non_litigation_delete', 1, [
                'name' => $data->name
            ]);

            return ApiResponseClass::sendResponse($data, 'Tahapan Non Litigasi Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_non_litigation_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
