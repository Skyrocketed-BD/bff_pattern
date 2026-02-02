<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\KasusNlRequest;
use App\Http\Resources\contract_legal\KasusNlResource;
use App\Models\contract_legal\KasusL;
use App\Models\contract_legal\KasusNl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasusNlController extends Controller
{
    /**
     * @OA\Get(
     *      path="/kasus_nl",
     *      summary="Get the list of kasus_nl",
     *      tags={"Contract Legal - Kasus Non Litigasi"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = KasusNl::with(['toTahapanNl', 'toKasusRiwayatNl'])->latest()->get();

        return ApiResponseClass::sendResponse(KasusNlResource::collection($data), 'Kasus Non Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/kasus_nl",
     *      summary="Create a new kasus_nl",
     *      tags={"Contract Legal - Kasus Non Litigasi"},
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
    public function store(KasusNlRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $kasus_nl = new KasusNl();
            $kasus_nl->no         = $request->no;
            $kasus_nl->nama       = $request->nama;
            $kasus_nl->tanggal    = $request->tanggal;
            $kasus_nl->keterangan = $request->keterangan;
            $kasus_nl->save();

            ActivityLogHelper::log('contract:non_litigation_create', 1, [
                'number'             => $request->no,
                'contract:case_name' => $request->nama,
                'date'               => $request->tanggal,
                'description'        => $request->keterangan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($kasus_nl, 'Kasus Non Litigasi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:non_litigation_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/kasus_nl/{id}",
     *      summary="Get the list of kasus_nl",
     *      tags={"Contract Legal - Kasus Non Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Non Litigasi ID",
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
        $data = KasusNl::find($id);

        return ApiResponseClass::sendResponse(KasusNlResource::make($data), 'Kasus Non Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *      path="/kasus_nl/{id}",
     *      summary="Update the kasus_nl",
     *      tags={"Contract Legal - Kasus Non Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Non Litigasi ID",
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
    public function update(KasusNlRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KasusNl::find($id);

            $data->update([
                'no'         => $request->no,
                'nama'       => $request->nama,
                'tanggal'    => $request->tanggal,
                'keterangan' => $request->keterangan,
            ]);

            ActivityLogHelper::log('contract:non_litigation_update', 1, [
                'number'             => $request->no,
                'contract:case_name' => $request->nama,
                'date'               => $request->tanggal,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Kasus Non Litigasi Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:non_litigation_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *      path="/kasus_nl/{id}",
     *      summary="Delete the kasus_nl",
     *      tags={"Contract Legal - Kasus Non Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Non Litigasi ID",
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
            $data = KasusNl::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:non_litigation_delete', 1, [
                'number'             => $data->no,
                'contract:case_name' => $data->nama,
                'date'               => $data->tanggal,
                'description'        => $data->keterangan,
                'status'             => $data->status
            ]);

            return ApiResponseClass::sendResponse($data, 'Kasus Non Litigasi Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:non_litigation_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/kasus_nl/transfer",
     *      summary="Transfer the kasus_nl",
     *      tags={"Contract Legal - Kasus Non Litigasi"},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="id_kasus_nl",
     *                      type="integer",
     *                      description="Kasus Non Litigasi ID"
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
     *                  required={"id_kasus_nl", "tanggal", "keterangan"},
     *                  example={
     *                      "id_kasus_nl": 1,
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
    public function transfer(Request $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $kasus_nl = KasusNl::find($request->id_kasus_nl);

            $kasus_l = new KasusL();
            $kasus_l->id_kasus_nl = $kasus_nl->id_kasus_nl;
            $kasus_l->no          = $kasus_nl->no;
            $kasus_l->nama        = $kasus_nl->nama;
            $kasus_l->tanggal     = $request->tanggal;
            $kasus_l->keterangan  = $request->keterangan;
            $kasus_l->save();

            $kasus_nl->update([
                'status' => 'transfer',
            ]);

            ActivityLogHelper::log('contract:non_litigation_transfer', 1, [
                'number'             => $kasus_nl->no,
                'contract:case_name' => $kasus_nl->nama,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($kasus_l, 'Kasus Non Litigasi Transferred To Kasus Litigasi Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:non_litigation_transfer', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *      path="/kasus_nl/cabut/{id}",
     *      summary="Cabut the kasus_nl",
     *      tags={"Contract Legal - Kasus Non Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Non Litigasi ID",
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
            $data = KasusNl::find($id);

            $data->update([
                'status' => 'cabut'
            ]);

            ActivityLogHelper::log('contract:non_litigation_cancel', 1, [
                'number'         => $data->no,
            ]);

            return ApiResponseClass::sendResponse($data, 'Kasus Non Litigasi Cabut Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:non_litigation_cancel', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
