<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\KasusLRequest;
use App\Http\Resources\contract_legal\KasusLResource;
use App\Models\contract_legal\KasusL;
use Illuminate\Support\Facades\DB;

class KasusLController extends Controller
{
    /**
     * @OA\Get(
     *      path="/kasus_l",
     *      summary="Get the list of kasus_l",
     *      tags={"Contract Legal - Kasus Litigasi"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = KasusL::with(['toTahapanL', 'toKasusRiwayatL'])->latest()->get();

        return ApiResponseClass::sendResponse(KasusLResource::collection($data), 'Kasus Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/kasus_l",
     *      summary="Create a new kasus_l",
     *      tags={"Contract Legal - Kasus Litigasi"},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="no",
     *                      type="string",
     *                      description="No Kasus"
     *                  ),
     *                  @OA\Property(
     *                      property="nama",
     *                      type="string",
     *                      description="Nama Kasus"
     *                  ),
     *                  @OA\Property(
     *                      property="tanggal",
     *                      type="date",
     *                      description="Tanggal Kasus"
     *                  ),
     *                  @OA\Property(
     *                      property="keterangan",
     *                      type="string",
     *                      description="Tanggal Kasus"
     *                  ),
     *                  required={ "no", "nama", "tanggal", "keterangan"},
     *                  example={
     *                      "no": "No Kasus",
     *                      "nama": "Nama Kasus",
     *                      "tanggal": "2020-01-01",
     *                      "keterangan": "Keterangan"
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function store(KasusLRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $kasus_l = new KasusL();
            $kasus_l->no         = $request->no;
            $kasus_l->nama       = $request->nama;
            $kasus_l->tanggal    = $request->tanggal;
            $kasus_l->keterangan = $request->keterangan;
            $kasus_l->save();

            ActivityLogHelper::log('contract:litigation_create', 1, [
                'number'             => $kasus_l->no,
                'contract:case_name' => $kasus_l->nama,
                'date'               => $kasus_l->tanggal,
                'description'        => $kasus_l->keterangan,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($kasus_l, 'Kasus Litigasi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:litigation_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/kasus_l/{id}",
     *      summary="Get the kasus_l",
     *      tags={"Contract Legal - Kasus Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Litigasi id",
     *          required=true,
     *          in="path",
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
        $data = KasusL::find($id);

        return ApiResponseClass::sendResponse(KasusLResource::make($data), 'Kasus Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *      path="/kasus_l/{id}",
     *      summary="Update the kasus_l",
     *      tags={"Contract Legal - Kasus Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Litigasi id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="no",
     *                      type="string",
     *                      description="No Kasus"
     *                  ),
     *                  @OA\Property(
     *                      property="nama",
     *                      type="string",
     *                      description="Nama Kasus"
     *                  ),
     *                  @OA\Property(
     *                      property="tanggal",
     *                      type="date",
     *                      description="Tanggal Kasus"
     *                  ),
     *                  @OA\Property(
     *                      property="keterangan",
     *                      type="string",
     *                      description="Tanggal Kasus"
     *                  ),
     *                  required={"no", "nama", "tanggal", "keterangan"},
     *                  example={
     *                      "no": "No Kasus",
     *                      "nama": "Nama Kasus",
     *                      "tanggal": "2020-01-01",
     *                      "keterangan": "Keterangan"
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function update(KasusLRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KasusL::find($id);

            $data->update([
                'no'         => $request->no,
                'nama'       => $request->nama,
                'tanggal'    => $request->tanggal,
                'keterangan' => $request->keterangan,
            ]);

            ActivityLogHelper::log('contract:litigation_update', 1, [
                'number'             => $data->no,
                'contract:case_name' => $data->nama,
                'date'               => $data->tanggal,
                'description'        => $data->keterangan,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Kasus Litigasi Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:litigation_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *      path="/kasus_l/{id}",
     *      summary="Delete the kasus_l",
     *      tags={"Contract Legal - Kasus Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Litigasi id",
     *          required=true,
     *          in="path",
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
            $data = KasusL::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:litigation_delete', 1, [
                'number'             => $data->no,
                'contract:case_name' => $data->nama,
                'date'               => $data->tanggal,
                'description'        => $data->keterangan,
            ]);

            return ApiResponseClass::sendResponse($data, 'Kasus Litigasi Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:litigation_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/kasus_l/cabut/{id}",
     *      summary="Cabut the kasus_l",
     *      tags={"Contract Legal - Kasus Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Litigasi ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function cabut($id)
    {
        try {
            $data = KasusL::find($id);

            $data->update([
                'status' => 'cabut'
            ]);

            ActivityLogHelper::log('contract:litigation_cancel', 1, [
                'number'         => $data->no
            ]);

            return ApiResponseClass::sendResponse($data, 'Kasus Non Litigasi Cabut Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:litigation_cancel', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
