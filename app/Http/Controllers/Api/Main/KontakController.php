<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\main\KontakRequest;
use App\Http\Resources\main\KontakResource;
use App\Http\Resources\main\OptionResource;
use App\Models\main\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;


class KontakController extends Controller
{
    /**
     * @OA\Get(
     *  path="/kontak",
     *  summary="Get the list of kontak",
     *  tags={"Main - Kontak"},
     *  @OA\Parameter(
     *      name="id_kontak_jenis",
     *      in="query",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="is_company",
     *      in="query",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="type",
     *      in="query",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          enum={"option"}
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $query = Kontak::query();

        $query->with(['toKontrak', 'toKontakJenis']);

        if (isset($request->id_kontak_jenis)) {
            $query->where('id_kontak_jenis', $request->id_kontak_jenis);
        }

        if (isset($request->is_company)) {
            $query->where('is_company', $request->is_company);
        }

        $query->orderBy('name', 'asc');

        $data = $query->get();

        if (isset($request->type) && $request->type == 'option') {
            return ApiResponseClass::sendResponse(
                (OptionResource::using('id_kontak', 'name'))::collection($data),
                'Kontak Retrieved Successfully'
            );
        }

        return ApiResponseClass::sendResponse(KontakResource::collection($data), 'Kontak Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/kontak",
     *  summary="Update kontak",
     *  tags={"Main - Kontak"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_kontrak",
     *                  type="integer",
     *                  description="ID Kontrak"
     *              ),
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
     *                  property="npwp",
     *                  type="string",
     *                  description="NPWP Kontak"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone Kontak"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email Kontak"
     *              ),
     *              @OA\Property(
     *                  property="website",
     *                  type="string",
     *                  description="Website Kontak"
     *              ),
     *              @OA\Property(
     *                  property="address",
     *                  type="string",
     *                  description="Address Kontak"
     *              ),
     *              @OA\Property(
     *                  property="postal_code",
     *                  type="integer",
     *                  description="Postal Code Kontak"
     *              ),
     *              @OA\Property(
     *                  property="is_company",
     *                  type="string",
     *                  description="Is Company Kontak"
     *              ),
     *              @OA\Property(
     *                  property="jenis",
     *                  type="string",
     *                  description="Jenis Kontak"
     *              ),
     *              required={"id_kontrak", "id_perusahaan", "id_kontak_jenis", "name", "npwp", "phone", "email", "address", "postal_code", "is_company"},
     *              example={
     *                  "id_kontrak": 1,
     *                  "id_perusahaan": 1,
     *                  "id_kontak_jenis": 1,
     *                  "name": "John Doe",
     *                  "npwp": "1234567890",
     *                  "phone": "1234567890",
     *                  "email": "4fH4o@example.com",
     *                  "website": "https://example.com",
     *                  "address": "Jl. Raya No. 1",
     *                  "postal_code": 12345,
     *                  "is_company": "1"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(KontakRequest $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $kontak                = new Kontak();
            if($request->hasKontrak){
                $kontak->id_kontrak    = $request->id_kontrak;
            } else {
                $kontak->id_kontrak    = NULL;
            }
            $kontak->id_perusahaan   = $request->id_perusahaan;
            $kontak->id_kontak_jenis = $request->id_kontak_jenis;
            $kontak->name            = $request->name;
            $kontak->npwp            = $request->npwp;
            $kontak->phone           = $request->phone;
            $kontak->email           = $request->email;
            $kontak->website         = $request->website;
            $kontak->address         = $request->address;
            $kontak->postal_code     = $request->postal_code;
            $kontak->is_company      = $request->is_company;
            $kontak->save();

            ActivityLogHelper::log('admin:contact_create', 1, [
                'name'               => $kontak->name,
                'admin:has_contract' => $request->hasKontrak ? 'Yes' : 'No',
                'admin:entity_type'  => $kontak->is_company ? 'Company' : 'Personal',
            ]);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($kontak, 'Kontak Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:contact_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/kontak/{id}",
     *  summary="Get kontak",
     *  tags={"Main - Kontak"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = Kontak::find($id);

        return ApiResponseClass::sendResponse($data, 'Kontak Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/kontak/{id}",
     *  summary="Update kontak",
     *  tags={"Main - Kontak"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_kontrak",
     *                  type="integer",
     *                  description="ID Kontrak"
     *              ),
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
     *                  property="npwp",
     *                  type="string",
     *                  description="NPWP Kontak"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone Kontak"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email Kontak"
     *              ),
     *              @OA\Property(
     *                  property="website",
     *                  type="string",
     *                  description="Website Kontak"
     *              ),
     *              @OA\Property(
     *                  property="address",
     *                  type="string",
     *                  description="Address Kontak"
     *              ),
     *              @OA\Property(
     *                  property="postal_code",
     *                  type="integer",
     *                  description="Postal Code Kontak"
     *              ),
     *              @OA\Property(
     *                  property="is_company",
     *                  type="string",
     *                  description="Is Company Kontak"
     *              ),
     *              @OA\Property(
     *                  property="jenis",
     *                  type="string",
     *                  description="Jenis Kontak"
     *              ),
     *              required={"id_kontrak", "id_perusahaan", "id_kontak_jenis", "name", "npwp", "phone", "email", "address", "postal_code", "is_company"},
     *              example={
     *                  "id_kontrak": 1,
     *                  "id_perusahaan": 1,
     *                  "id_kontak_jenis": 1,
     *                  "name": "John Doe",
     *                  "npwp": "1234567890",
     *                  "phone": "1234567890",
     *                  "email": "4fH4o@example.com",
     *                  "website": "https://example.com",
     *                  "address": "Jl. Raya No. 1",
     *                  "postal_code": 12345,
     *                  "is_company": "1"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(KontakRequest $request, $id)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $kontak                = Kontak::findOrFail($id);
            if($request->hasKontrak){
                $kontak->id_kontrak    = $request->id_kontrak;
            } else {
                $kontak->id_kontrak    = NULL;
            }
            $kontak->id_perusahaan   = $request->id_perusahaan;
            $kontak->id_kontak_jenis = $request->id_kontak_jenis;
            $kontak->name            = $request->name;
            $kontak->npwp            = $request->npwp;
            $kontak->phone           = $request->phone;
            $kontak->email           = $request->email;
            $kontak->website         = $request->website;
            $kontak->address         = $request->address;
            $kontak->postal_code     = $request->postal_code;
            $kontak->is_company      = $request->is_company;
            $kontak->save();

            ActivityLogHelper::log('admin:contact_update', 1, [
                'name'               => $kontak->name,
                'admin:has_contract' => $request->hasKontrak ? 'Yes' : 'No',
                'admin:entity_type'  => $kontak->is_company ? 'Company' : 'Personal',
            ]);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($kontak, 'Kontak Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:contact_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/kontak/{id}",
     *  summary="Delete kontak",
     *  tags={"Main - Kontak"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $kontak = Kontak::findOrFail($id);

            $kontak->delete();

            return ApiResponseClass::sendResponse($kontak, 'Kontak Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

}
