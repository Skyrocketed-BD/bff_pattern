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

class ReviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/review",
     *     summary="Get a list of contract stages",
     *     tags={"Contract Legal - Review / Peninjauan"},
     *     @OA\Response(
     *         response=200,
     *         description="List of contract stages retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        // $data = KontrakTahapan::whereIn('id_tahapan_k', [3, 5])->latest()->get();
        $data = KontrakTahapan::with(['toUploadKontrakTahapan.toUploadRevisi'])
            ->whereIn('id_tahapan_k', [3, 5])
            ->where(function ($query) {
                $query->where('status', null)
                    ->orWhere('status', 'a');
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

                $file = new UploadKontrakTahapan();
                $file->id_kontrak_tahapan = $data->id_kontrak_tahapan;
                $file->judul              = $judul[$i];
                $file->file               = $attachment;
                $file->save();
            }

            ActivityLogHelper::log('contract:review_create', 1, [
                'description' => $data->keterangan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:review_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/review/{id}",
     *     summary="Show the specified resource",
     *     tags={"Contract Legal - Review / Peninjauan"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         description="ID of contract",
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
     *         description="Contract Stages Retrieved Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = KontrakTahapan::find($id);

        return ApiResponseClass::sendResponse(Resource::collection($data), '... Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/review/{id}",
     *     summary="Update the specified resource in storage",
     *     tags={"Contract Legal - Review / Peninjauan"},
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
     *                     property="keterangan",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="judul",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string"
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     type="array",
     *                     @OA\Items(
     *                         type="file"
     *                     ),
     *                 ),
     *                 required={"keterangan", "judul", "file"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract Updated Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
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

            $judul = $request->judul;
            $file  = $request->file;

            for ($i = 0; $i < count($judul); $i++) {
                $attachment = add_file($file[$i], 'upload_kontrak_tahapan/');

                $file = new UploadKontrakTahapan();
                $file->id_kontrak_tahapan = $data->id_kontrak_tahapan;
                $file->judul              = $judul[$i];
                $file->file               = $attachment;
                $file->save();
            }

            ActivityLogHelper::log('contract:review_update', 1, [
                'description' => $data->keterangan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:review_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = KontrakTahapan::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:review_delete', 1, [
                'stages'      => $data->toTahapanK->name,
                'description' => $data->keterangan
            ]);

            return ApiResponseClass::sendResponse($data, 'Contract Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:review_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
