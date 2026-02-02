<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\contract_legal\Kontrak;
use App\Models\contract_legal\KontrakTahapan;
use App\Models\contract_legal\UploadKontrakTahapan;
use App\Http\Requests\contract_legal\KontrakTahapanRequest;
use App\Http\Resources\contract_legal\KontrakTahapanResource as Resource;

class PengajuanController extends Controller
{
    /**
     * @OA\Get(
     *  path="/pengajuan",
     *  summary="Get the list of contract legal pengajuan",
     *  tags={"Contract Legal - Proposal / Pengajuan"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = KontrakTahapan::where('id_tahapan_k', 1)
            ->where(function ($query) {
                $query->whereNull('status');
            })
            ->latest()
            ->get();

        return ApiResponseClass::sendResponse(Resource::collection($data), 'Contract Stages Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/pengajuan",
     *     summary="Store a newly created resource in storage",
     *     tags={"Contract Legal - Proposal / Pengajuan"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="tgl",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="judul",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     type="array",
     *                     @OA\Items(
     *                         type="file",
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="keterangan",
     *                     type="string",
     *                 ),
     *                 required={ "tgl", "judul", "file", "keterangan"},
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Return a list of resources"
     *     ),
     *     security={{ "bearerAuth": {} }}
     *
     * )
     */
    public function store(KontrakTahapanRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $kontrak = new Kontrak();
            $kontrak->nama_perusahaan = $request->nama_perusahaan;
            $kontrak->save();

            if ($kontrak) {
                $tahapan = new KontrakTahapan();
                $tahapan->id_tahapan_k = 1;
                $tahapan->id_kontrak   = $kontrak->id_kontrak;
                $tahapan->tgl          = $request->tgl;
                $tahapan->keterangan   = $request->keterangan;
                $tahapan->save();

                $attachment = add_file($request->file('files.File'), 'upload_kontrak_tahapan/');

                $file = new UploadKontrakTahapan();
                $file->id_kontrak_tahapan = $tahapan->id_kontrak_tahapan;
                $file->judul              = $request->judul;
                $file->file               = $attachment;
                $file->save();
            } else {
                DB::rollBack();
                return ApiResponseClass::sendResponse('Failed to create Kontrak instance', 422);
            }

            ActivityLogHelper::log('contract:proposal_create', 1, [
               'company'     => $request->nama_perusahaan,
               'date'        => $tahapan->tgl,
               'description' => $tahapan->keterangan,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($tahapan, 'Contract Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:proposal_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }


    /**
     * @OA\Get(
     *     path="/pengajuan/{id}",
     *     summary="Show a Pengajuan",
     *     tags={"Contract Legal - Proposal / Pengajuan"},
     *     @OA\Parameter(
     *         description="ID of Pengajuan to return",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = KontrakTahapan::find($id);

        return ApiResponseClass::sendResponse(Resource::collection($data), '... Retrieved Successfully');
    }

    public function update(KontrakTahapanRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);

            $data->update([
                'tgl'                => $request->tgl,
                'keterangan'         => $request->keterangan,
            ]);

            ActivityLogHelper::log('contract:proposal_update', 1, [
                'date'        => $data->tgl,
                'description' => $data->keterangan,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:proposal_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = KontrakTahapan::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:proposal_delete', 1, [
                'stages'      => $data->toTahapanK->name,
                'date'        => $data->tgl,
                'description' => $data->keterangan,
            ]);

            return ApiResponseClass::sendResponse($data, 'Contract Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
