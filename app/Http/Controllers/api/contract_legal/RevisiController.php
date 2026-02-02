<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\contract_legal\Revisi;
use App\Models\contract_legal\UploadRevisi;
use App\Http\Requests\contract_legal\RevisiRequest;
use App\Http\Resources\contract_legal\RevisiResource as Resource;

class RevisiController extends Controller
{

    /**
     * @OA\Get(
     *     path="/revisi",
     *     summary="Get a listing of the Revisi.",
     *     tags={"Contract Legal - Revisi Peninjauan"},
     *      @OA\Response(response=200, description="Return a list of resources"),
     *      security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = Revisi::with(['toUploadRevisi'])->latest()->get();

        return ApiResponseClass::sendResponse(Resource::collection($data), 'Contract Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/revisi",
     *     summary="Store a newly created resource in storage",
     *     tags={"Contract Legal - Revisi Peninjauan"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="id_kontrak_tahapan",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="revisi_ke",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="keterangan",
     *                     type="string",
     *                     example="keterangan"
     *                 ),
     *                 @OA\Property(
     *                     property="id_upload_kontrak_tahapan",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     type="file",
     *                 ),
     *                 required={"id_kontrak_tahapan", "revisi_ke", "keterangan", "id_upload_kontrak_tahapan", "file"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract created successfully"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function store(RevisiRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $revisi                     = new Revisi();
            $revisi->id_kontrak_tahapan = $request->id_kontrak_tahapan;
            $revisi->revisi_ke          = $request->revisi_ke;
            $revisi->keterangan         = $request->keterangan;
            $revisi->save();

            if ($revisi && $request->hasFile('files.File')) {
                $file       = $request->file('files.File');
                $attachment = add_file($file, 'upload_revisi/');

                $file                            = new UploadRevisi();
                $file->id_revisi                 = $revisi->id_revisi;
                $file->id_upload_kontrak_tahapan = $request->id_upload_kontrak_tahapan;
                $file->file                      = $attachment;
                $file->save();
            }

            ActivityLogHelper::log('contract:revision_create', 1, [
                'contract:revision' => $revisi->revisi_ke,
                'description'       => $revisi->keterangan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($revisi, 'Contract Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:revision_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function show($id)
    {
        $data = Revisi::find($id);

        return ApiResponseClass::sendResponse(Resource::collection($data), '... Retrieved Successfully');
    }

    public function update(RevisiRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = Revisi::find($id);

            $data->update([
                'nama'               => $request->nama,
                'keterangan'         => $request->keterangan,
            ]);

            ActivityLogHelper::log('contract:revision_update', 1, [
                'contract:revision' => $data->revisi_ke,
                'description'       => $data->keterangan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:revision_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = Revisi::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:revision_delete', 1, [
                'contractLrevision' => $data->revisi_ke,
                'description'       => $data->keterangan
            ]);

            return ApiResponseClass::sendResponse($data, 'Contract Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:revision_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
