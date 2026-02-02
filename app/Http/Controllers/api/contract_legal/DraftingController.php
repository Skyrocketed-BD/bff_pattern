<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\contract_legal\KontrakTahapan;
use App\Models\contract_legal\UploadKontrakTahapan;
use App\Http\Requests\contract_legal\KontrakTahapanRequest;
use App\Http\Resources\contract_legal\KontrakTahapanResource as Resource;

class DraftingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/drafting",
     *     summary="Get the list of drafting / penyusunan",
     *     tags={"Contract Legal - Drafting / Penyusunan"},
     *     @OA\Response(
     *         response=200,
     *         description="Return a list of resources"
     *     ), 
     *     security={{ "bearerAuth": {} }}
     * 
     * )
     */
    public function index()
    {
        $data = KontrakTahapan::where('id_tahapan_k', 2)
            ->where(function ($query) {
                $query->whereNull('status');
            })
            ->latest()
            ->get();

        return ApiResponseClass::sendResponse(Resource::collection($data), 'Contract Stages Retrieved Successfully');
    }

    public function store(KontrakTahapanRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($request->id_kontrak_tahapan);

            $data->update([
                'keterangan'         => $request->keterangan,
            ]);

            $judul = $request->judul;
            $file  = $request->file;

            for ($i = 0; $i < count($judul); $i++) {
                $attachment = add_file($file[$i], 'upload_kontrak_tahapan/');

                $file                     = new UploadKontrakTahapan();
                $file->id_kontrak_tahapan = $data->id_kontrak_tahapan;
                $file->judul              = $judul[$i];
                $file->file               = $attachment;
                $file->save();
            }

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function show($id)
    {
        $data = KontrakTahapan::find($id);

        return ApiResponseClass::sendResponse(Resource::collection($data), '... Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/drafting/{id}",
     *     summary="Update the specified resource in storage",
     *     tags={"Contract Legal - Drafting / Penyusunan"},
     *     @OA\Parameter(
     *         description="ID of contract stage",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Parameter(
     *          name="_method",
     *          in="query",
     *          description="HTTP Method",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              default="PUT"
     *          ),
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
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
     *                 required={"judul", "file", "keterangan"},
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Return a list of resources"
     *     ), 
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * 
     * )
     */
    public function update(KontrakTahapanRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);

            $data->update([
                'keterangan'         => $request->keterangan,
            ]);

            $attachment = add_file($request->file('files.File'), 'upload_kontrak_tahapan/');

            $file = new UploadKontrakTahapan();
            $file->id_kontrak_tahapan = $data->id_kontrak_tahapan;
            $file->judul              = $request->judul;
            $file->file               = $attachment;
            $file->save();

            ActivityLogHelper::log('contract:upload_drafting_document', 1, [
                'title'       => $file->judul,
                'description' => $data->keterangan,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:upload_drafting_document', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = KontrakTahapan::find($id);

            $data->delete();

            return ApiResponseClass::sendResponse($data, 'Contract Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
