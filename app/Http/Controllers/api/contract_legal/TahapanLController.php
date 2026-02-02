<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\TahapanLRequest;
use App\Http\Resources\contract_legal\TahapanLResource;
use App\Models\contract_legal\TahapanL;
use Illuminate\Support\Facades\DB;

class TahapanLController extends Controller
{
    /**
     * @OA\Get(
     *      path="/tahapan_l",
     *      summary="Get the list of tahapan_l",
     *      tags={"Contract Legal - Tahapan Litigasi"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = TahapanL::orderBy('id_tahapan_l', 'asc')->get();

        return ApiResponseClass::sendResponse(TahapanLResource::collection($data), 'Tahapan Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/tahapan_l",
     *      summary="Create a new tahapan_l",
     *      tags={"Contract Legal - Tahapan Litigasi"},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      description="Tahapan name"
     *                  ),
     *                  @OA\Property(
     *                      property="category",
     *                      type="string",
     *                      description="Tahapan category (gugatan, pidana, praperadilan)"
     *                  ),
     *                  required={"name", "category"},
     *                  example={
     *                      "name": "Tahapan",
     *                      "category": "gugatan"
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function store(TahapanLRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $tahapan_l = new TahapanL();
            $tahapan_l->name     = $request->name;
            $tahapan_l->category = $request->category;
            $tahapan_l->save();

            ActivityLogHelper::log('contract:stages_litigation_create', 1, [
                'name'     => $request->name,
                'category' => $request->category
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($tahapan_l, 'Tahapan Litigasi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_litigation_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/tahapan_l/{id}",
     *      summary="Get the tahapan_l",
     *      tags={"Contract Legal - Tahapan Litigasi"},
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
        $data = TahapanL::find($id);

        return ApiResponseClass::sendResponse(TahapanLResource::make($data), 'Tahapan Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *      path="/tahapan_l/{id}",
     *      summary="Update the tahapan_l",
     *      tags={"Contract Legal - Tahapan Litigasi"},
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
     *                  @OA\Property(
     *                      property="category",
     *                      type="string",
     *                      description="Tahapan category (gugatan, pidana, praperadilan)"
     *                  ),
     *                  required={"name", "category"},
     *                  example={
     *                      "name": "Tahapan",
     *                      "category": "gugatan"
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function update(TahapanLRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = TahapanL::find($id);

            $data->update([
                'name'     => $request->name,
                'category' => $request->category,
            ]);

            ActivityLogHelper::log('contract:stages_litigation_update', 1, [
                'name'     => $request->name,
                'category' => $request->category
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Tahapan Litigasi Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_litigation_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *      path="/tahapan_l/{id}",
     *      summary="Delete the tahapan_l",
     *      tags={"Contract Legal - Tahapan Litigasi"},
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
            $data = TahapanL::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:stages_litigation_delete', 1, [
                'name'     => $data->name,
                'category' => $data->category
            ]);

            return ApiResponseClass::sendResponse($data, 'Tahapan Litigasi Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:stages_litigation_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/tahapan_l/category/{category}",
     *      summary="Get the tahapan_l by category",
     *      tags={"Contract Legal - Tahapan Litigasi"},
     *      @OA\Parameter(
     *          name="category",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function category($category)
    {
        $data = TahapanL::where('category', $category)->get();

        return ApiResponseClass::sendResponse(TahapanLResource::collection($data), 'Tahapan Litigasi Retrieved Successfully');
    }
}
