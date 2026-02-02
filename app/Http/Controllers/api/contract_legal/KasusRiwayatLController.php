<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\KasusRiwayatLRequest;
use App\Http\Resources\contract_legal\KasusRiwayatLResource;
use App\Models\contract_legal\JadwalSidang;
use App\Models\contract_legal\KasusL;
use App\Models\contract_legal\KasusRiwayatL;
use App\Models\contract_legal\UploadKasusRiwayatL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasusRiwayatLController extends Controller
{
    /**
     * @OA\Get(
     *      path="/kasus_riwayat_l",
     *      summary="Get the list of kasus_riwayat_l",
     *      tags={"Contract Legal - Kasus Riwayat Litigasi"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = KasusRiwayatL::with(['toJadwalSidang'])->latest()->get();

        return ApiResponseClass::sendResponse(KasusRiwayatLResource::collection($data), 'Kasus Riwayat Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/kasus_riwayat_l",
     *      summary="Create a new kasus_riwayat_l",
     *      tags={"Contract Legal - Kasus Riwayat Litigasi"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="id_kasus_l",
     *                      type="integer",
     *                  ),
     *                  @OA\Property(
     *                      property="id_tahapan_l",
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
    public function store(KasusRiwayatLRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $kasus_riwayat_l = new KasusRiwayatL();
            $kasus_riwayat_l->id_kasus_l   = $request->id_kasus_l;
            $kasus_riwayat_l->id_tahapan_l = $request->id_tahapan_l;
            $kasus_riwayat_l->nama         = $request->nama;
            $kasus_riwayat_l->tanggal      = $request->tanggal;
            $kasus_riwayat_l->deskripsi    = $request->deskripsi;
            $kasus_riwayat_l->save();

            $judul = $request->judul;
            $file  = $request->file;

            for ($i = 0; $i < count($judul); $i++) {
                $attachment = add_file($file[$i], 'kasus_riwayat_l/');

                $file = new UploadKasusRiwayatL();
                $file->id_kasus_riwayat_l = $kasus_riwayat_l->id_kasus_riwayat_l;
                $file->judul              = $judul[$i];
                $file->file               = $attachment;
                $file->save();
            }

            KasusL::find($request->id_kasus_l)->update([
                'id_tahapan_l' => $request->id_tahapan_l
            ]);

            ActivityLogHelper::log('contract:litigation_case_stages_create', 1, [
                'contract:stages' => $kasus_riwayat_l->toTahapanL->name,
                'description'     => $kasus_riwayat_l->deskripsi
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($kasus_riwayat_l, 'Kasus Riwayat Litigasi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:litigation_case_stages_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/kasus_riwayat_l/{id}",
     *      summary="Get the list of kasus_riwayat_l",
     *      tags={"Contract Legal - Kasus Riwayat Litigasi"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Kasus Riwayat Litigasi ID",
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
        $data = KasusRiwayatL::with(['toKasusL', 'toTahapanL', 'toUploadKasusRiwayatL'])->find($id);

        return ApiResponseClass::sendResponse(KasusRiwayatLResource::make($data), 'Kasus Riwayat Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *      path="/kasus_riwayat_l/detail/{id}",
     *      summary="Get the detail of kasus_riwayat_l",
     *      tags={"Contract Legal - Kasus Riwayat Litigasi"},
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
        $data = KasusRiwayatL::with(['toKasusL', 'toTahapanL', 'toUploadKasusRiwayatL'])->where('id_kasus_l', $id)->get();

        return ApiResponseClass::sendResponse(KasusRiwayatLResource::collection($data), 'Kasus Riwayat Litigasi Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/kasus_riwayat_l/jadwal",
     *      summary="Create Jadwal Kasus Riwayat Litigasi",
     *      tags={"Contract Legal - Kasus Riwayat Litigasi"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="id_kasus_riwayat_l",
     *                      type="integer",
     *                  ),
     *                  @OA\Property(
     *                      property="tgl_waktu_sidang",
     *                      type="string",
     *                  ),
     *                  required={"id_kasus_riwayat_l", "tgl_waktu_sidang"},
     *                  example={
     *                      "id_kasus_riwayat_l": 1,
     *                      "tgl_waktu_sidang": "2022-01-01 00:00:00",
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function jadwal(Request $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $kasus = KasusRiwayatL::with(['toKasusL'])->find($request->id_kasus_riwayat_l);

            $jadwal_sidang = new JadwalSidang();
            $jadwal_sidang->id_kasus_riwayat_l = $request->id_kasus_riwayat_l;
            $jadwal_sidang->no                 = $kasus->toKasusL->no;
            $jadwal_sidang->nama               = $kasus->toKasusL->nama;
            $jadwal_sidang->tgl_waktu_sidang   = $request->tgl_waktu_sidang;
            $jadwal_sidang->keterangan         = $kasus->toKasusL->keterangan;
            $jadwal_sidang->save();

            ActivityLogHelper::log('contract:court_schedule_create', 1, [
                'contract:case_number' => $kasus->toKasusL->no,
                'schedule'             => $request->tgl_waktu_sidang,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($jadwal_sidang, 'Jadwal Tahapan Litigasi Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:court_schedule_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
