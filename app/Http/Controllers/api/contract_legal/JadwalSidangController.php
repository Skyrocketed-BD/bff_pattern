<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\contract_legal\JadwalSidang;
use App\Models\contract_legal\UploadJadwalSidang;
use App\Http\Requests\contract_legal\JadwalSidangRequest;
use App\Http\Resources\contract_legal\JadwalSidangResource;

class JadwalSidangController extends Controller
{
    /**
     * @OA\Get(
     *      path="/jadwal_sidang",
     *      summary="Get the list of jadwal_sidang",
     *      tags={"Contract Legal - Jadwal Sidang"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = JadwalSidang::orderByRaw('case when status = "cabut" then 1 else 0 end, id_jadwal_sidang DESC')
            ->latest()
            ->get();

        return ApiResponseClass::sendResponse(JadwalSidangResource::collection($data), 'Jadwal Sidang Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *      path="/jadwal_sidang",
     *     summary="Create Jadwal Sidang",
     *     tags={"Contract Legal - Jadwal Sidang"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="no",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="nama",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="tgl_waktu_sidang",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="keterangan",
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
    public function store(JadwalSidangRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $jadwal_sidang = new JadwalSidang();
            $jadwal_sidang->no               = $request->no;
            $jadwal_sidang->nama             = $request->nama;
            $jadwal_sidang->tgl_waktu_sidang = $request->tgl_waktu_sidang;
            $jadwal_sidang->keterangan       = $request->keterangan;
            $jadwal_sidang->save();

            $judul = $request->judul;
            $file  = $request->file;

            for ($i = 0; $i < count($judul); $i++) {
                $attachment = add_file($file[$i], 'jadwal_sidang/');

                $file = new UploadJadwalSidang();
                $file->id_jadwal_sidang = $jadwal_sidang->id_jadwal_sidang;
                $file->judul            = $judul[$i];
                $file->file             = $attachment;
                $file->save();
            }

            ActivityLogHelper::log('contract:court_schedule_create', 1, [
                'number'             => $jadwal_sidang->no,
                'contract:case_name' => $jadwal_sidang->nama,
                'schedule'           => $jadwal_sidang->tgl_waktu_sidang,
                'description'        => $jadwal_sidang->keterangan,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($jadwal_sidang, 'Jadwal Sidang Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:court_schedule_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *      path="/jadwal_sidang/{id}",
     *      summary="Get Jadwal Sidang",
     *      tags={"Contract Legal - Jadwal Sidang"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Jadwal Sidang id",
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
        $data = JadwalSidang::with(['toUploadJadwalSidang'])->find($id);

        return ApiResponseClass::sendResponse(JadwalSidangResource::make($data), 'Jadwal Sidang Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *      path="/jadwal_sidang/{id}",
     *      summary="Update the jadwal_sidang",
     *      tags={"Contract Legal - Jadwal Sidang"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Jadwal Sidang id",
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
     *                      property="tgl_waktu_sidang",
     *                      type="string",
     *                  ),
     *                  required={"tgl_waktu_sidang"},
     *                  example={
     *                      "tgl_waktu_sidang": "2025-01-01 00:00:00",
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function update(JadwalSidangRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = JadwalSidang::find($id);

            $data->update([
                'tgl_waktu_sidang'    => $request->tgl_waktu_sidang,
            ]);

            ActivityLogHelper::log('contract:court_schedule_update', 1, [
                'number'      => $data->no,
                'schedule'    => $data->tgl_waktu_sidang,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Jadwal Sidang Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:court_schedule_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *      path="/jadwal_sidang/cabut/{id}",
     *      summary="Cabut the jadwal_sidang",
     *      tags={"Contract Legal - Jadwal Sidang"},
     *      @OA\Parameter(
     *          name="id",
     *          description="Jadwal Sidang ID",
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
            $data = JadwalSidang::find($id);

            $data->update([
                'status' => 'cabut'
            ]);

            ActivityLogHelper::log('contract:court_schedule_cancel', 1, [
                'number' => $data->no,
            ]);

            return ApiResponseClass::sendResponse($data, 'Jadwal Sidang Cabut Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:court_schedule_cancel', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
