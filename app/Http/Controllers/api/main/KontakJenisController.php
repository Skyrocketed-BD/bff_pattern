<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\main\KontakJenisRequest;
use App\Models\main\KontakJenis;
use Illuminate\Support\Facades\DB;

class KontakJenisController extends Controller
{
    /**
     * @OA\Get(
     *  path="/kontak-jenis",
     *  summary="Get the list of kontak jenis",
     *  tags={"Main - Kontak Jenis"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = KontakJenis::latest()->get();

        return ApiResponseClass::sendResponse($data, 'Kontak Jenis Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/kontak-jenis/{id}",
     *  summary="Get kontak jenis",
     *  tags={"Main - Kontak Jenis"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = KontakJenis::find($id);

        return ApiResponseClass::sendResponse($data, 'Kontak Jenis Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/kontak-jenis",
     *  summary="Create kontak jenis",
     *  tags={"Main - Kontak Jenis"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name Kontak Jenis"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(KontakJenisRequest $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $kontak_jenis       = new KontakJenis();
            $kontak_jenis->name = $request->name;
            $kontak_jenis->save();

            DB::connection('mysql')->commit();

            ActivityLogHelper::log('main:kontak_jenis_create', 1, [
                'name' => $request->name
            ]);

            return ApiResponseClass::sendResponse($kontak_jenis, 'Kontak Jenis Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('main:kontak_jenis_create', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Put(
     *  path="/kontak-jenis/{id}",
     *  summary="Update kontak jenis",
     *  tags={"Main - Kontak Jenis"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name Kontak Jenis"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(KontakJenisRequest $request, $id)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $kontak_jenis       = KontakJenis::findOrFail($id);
            $kontak_jenis->name = $request->name;
            $kontak_jenis->save();

            DB::connection('mysql')->commit();

            ActivityLogHelper::log('main:kontak_jenis_update', 1, [
                'name' => $request->name
            ]);

            return ApiResponseClass::sendResponse($kontak_jenis, 'Kontak Jenis Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('main:kontak_jenis_update', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/kontak-jenis/{id}",
     *  summary="Delete kontak jenis",
     *  tags={"Main - Kontak Jenis"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            if ($id == 1 || $id == 2) {
                return ApiResponseClass::throw('Cannot delete data or it is being used', 409, 'Cannot delete data or it is being used');
            }

            $kontak_jenis = KontakJenis::findOrFail($id);
            $kontak_jenis->delete();

            ActivityLogHelper::log('main:kontak_jenis_delete', 1, [
                'name' => $kontak_jenis->name
            ]);

            return ApiResponseClass::sendResponse($kontak_jenis, 'Kontak Jenis Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('main:kontak_jenis_delete', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
