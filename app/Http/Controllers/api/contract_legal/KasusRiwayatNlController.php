<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\KasusRiwayatNlRequest;
use App\Http\Resources\contract_legal\KasusRiwayatNlResource;
use App\Models\contract_legal\KasusNl;
use App\Models\contract_legal\KasusRiwayatNl;
use App\Models\contract_legal\UploadKasusRiwayatNl;
use Illuminate\Support\Facades\DB;

class KasusRiwayatNlController extends Controller
{
    /**
     * @OA\Get(
     *      path="/kasus_riwayat_nl",
     *      summary="Get the list of kasus_riwayat_nl",
     *      tags={"Contract Legal - Kasus Riwayat Non Litigasi"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = KasusRiwayatNl::latest()->get();

        return ApiResponseClass::sendResponse(KasusRiwayatNlResource::collection($data), 'Kasus Riwayat Non Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/kasus_riwayat_nl",
     *      summary="Create a new kasus_riwayat_nl",
     *      tags={"Contract Legal - Kasus Riwayat Non Litigasi"},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="id_kasus_nl",
     *                      type="integer",
     *                  ),
     *                  @OA\Property(
     *                      property="id_tahapan_nl",
     *                      type="integer",
     *                  ),
     *                  @OA\Property(
     *                      property="nama",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="tanggal",
     *                      type="date",
     *                  ),
     *                  @OA\Property(
     *                      property="deskripsi",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="judul",
     *                      type="array",
     *                      @OA\Items(
     *                          type="string",
     *                      ),
     *                  ),
     *                  @OA\Property(
     *                      property="file",
     *                      type="array",
     *                      @OA\Items(
     *                          type="string",
     *                          format="binary"
     *                      ),
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function store(KasusRiwayatNlRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $kasus_riwayat_nl                = new KasusRiwayatNl();
            $kasus_riwayat_nl->id_kasus_nl   = $request->id_kasus_nl;
            $kasus_riwayat_nl->id_tahapan_nl = $request->id_tahapan_nl;
            $kasus_riwayat_nl->nama          = $request->nama;
            $kasus_riwayat_nl->tanggal       = $request->tanggal;
            $kasus_riwayat_nl->deskripsi     = $request->deskripsi;
            $kasus_riwayat_nl->save();

            $judul = $request->judul;
            $file  = $request->file;

            for ($i = 0; $i < count($judul); $i++) {
                $attachment = add_file($file[$i], 'kasus_riwayat_nl/');

                $file = new UploadKasusRiwayatNl();
                $file->id_kasus_riwayat_nl = $kasus_riwayat_nl->id_kasus_riwayat_nl;
                $file->judul               = $judul[$i];
                $file->file                = $attachment;
                $file->save();
            }

            KasusNl::find($request->id_kasus_nl)->update([
                'id_tahapan_nl' => $request->id_tahapan_nl
            ]);

            ActivityLogHelper::log('contract:non_litigation_case_stages_create', 1, [
                'contract:stages' => $kasus_riwayat_nl->toTahapanNl->name,
                'description'     => $kasus_riwayat_nl->deskripsi
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($kasus_riwayat_nl, 'Kasus Riwayat Non Litigasi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:non_litigation_case_stages_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/kasus_riwayat_nl/{id}",
     *      summary="Get the detail of kasus_riwayat_nl",
     *      tags={"Contract Legal - Kasus Riwayat Non Litigasi"},
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
        $data = KasusRiwayatNl::with(['toKasusNl', 'toTahapanNl', 'toUploadKasusRiwayatNl'])->find($id);

        return ApiResponseClass::sendResponse(KasusRiwayatNlResource::make($data), 'Kasus Riwayat Non Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *      path="/kasus_riwayat_nl/detail/{id}",
     *      summary="Get the detail of kasus_riwayat_nl",
     *      tags={"Contract Legal - Kasus Riwayat Non Litigasi"},
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
    public function detail($id)
    {
        $data = KasusRiwayatNl::with(['toKasusNl', 'toTahapanNl', 'toUploadKasusRiwayatNl'])->where('id_kasus_nl', $id)->get();

        return ApiResponseClass::sendResponse(KasusRiwayatNlResource::collection($data), 'Kasus Riwayat Non Litigasi Retrieved Successfully');
    }
}
