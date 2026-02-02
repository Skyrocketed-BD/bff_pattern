<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickAddController extends Controller
{
    /**
     * @OA\Post(
     *  path="/quick-add/kontak",
     *  summary="Quick add kontak",
     *  tags={"Main - Quick Add"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_perusahaan",
     *                  type="integer",
     *                  description="ID Perusahaan"
     *              ),
     *              @OA\Property(
     *                  property="id_kontak_jenis",
     *                  type="integer",
     *                  description="ID Kontak Jenis"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name Kontak"
     *              ),
     *              @OA\Property(
     *                  property="is_company",
     *                  type="integer",
     *                  description="Is Company"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function kontak(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $kontak = new Kontak();
            $kontak->id_perusahaan   = $request->id_perusahaan;
            $kontak->id_kontak_jenis = $request->id_kontak_jenis;
            $kontak->name            = $request->name;
            $kontak->is_company      = $request->is_company;
            $kontak->save();

            DB::connection('mysql')->commit();

            ActivityLogHelper::log('kontak_quick_add', 1, [
                'name' => $kontak->name
            ]);

            return ApiResponseClass::sendResponse($kontak, 'Kontak Added Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('kontak_quick_add', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

}