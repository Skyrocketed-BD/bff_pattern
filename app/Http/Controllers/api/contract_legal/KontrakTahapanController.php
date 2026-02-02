<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\contract_legal\Kontrak;
use App\Models\contract_legal\UploadRevisi;
use App\Models\contract_legal\KontrakTahapan;
use App\Models\contract_legal\UploadKontrakTahapan;
use App\Http\Requests\contract_legal\KontrakRequest;
use App\Http\Requests\contract_legal\KontrakTahapanRequest;
use App\Http\Requests\contract_legal\EditKontrakTahapanDescRequest;
use App\Http\Requests\contract_legal\FinalizeRequest;
use App\Http\Resources\contract_legal\KontrakTahapanResource as Resource;

class KontrakTahapanController extends Controller
{

    /**
     * @OA\Get(
     *     path="/kontrak_tahapan",
     *     tags={"Contract Legal - Kontrak Tahapan"},
     *     summary="Get all Contract Stages",
     *     description="Get all Contract Stages",
     *     @OA\Response(response=200, description="Successful"),
     *     security={ {"bearerAuth": {} } }
     * )
     */
    public function index()
    {
        $data = KontrakTahapan::latest()->get();

        return ApiResponseClass::sendResponse(Resource::collection($data), 'Contract Stages Retrieved Successfully');
    }

    public function store(KontrakTahapanRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $tahapan = new KontrakTahapan();
            $tahapan->id_kontrak = $request->id_kontrak;
            $tahapan->tahapan    = $request->tahapan;
            $tahapan->tgl        = today();
            $tahapan->keterangan = $request->keterangan;
            $tahapan->save();

            $judul = $request->judul;
            $file  = $request->file;

            for ($i = 0; $i < count($judul); $i++) {
                $attachment = add_file($file[$i], 'upload_kontrak_tahapan/');

                $file = new UploadKontrakTahapan();
                $file->id_kontrak_tahapan = $tahapan->id_kontrak_tahapan;
                $file->judul              = $judul[$i];
                $file->file               = $attachment;
                $file->save();
            }

            ActivityLogHelper::log('contract:contract_stages_create', 1, [
                'date'        => $tahapan->tgl,
                'description' => $tahapan->keterangan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($tahapan, 'Contract Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_stages_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

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

            ActivityLogHelper::log('contract:contract_stages_update', 1, [
                'date'        => $data->tgl,
                'description' => $data->keterangan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_stages_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = KontrakTahapan::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:contract_stages_delete', 1, [
                'date'        => $data->tgl,
                'description' => $data->keterangan
            ]);

            return ApiResponseClass::sendResponse($data, 'Contract Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_stages_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/kontrak_tahapan/approve/{id}/{id_tahapan_k}",
     *     tags={"Contract Legal - Kontrak Tahapan"},
     *     summary="Approve the specified resource in storage",
     *     description="Approve the specified resource in storage",
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         description="ID of Contract",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="ID of Contract Stage",
     *         in="path",
     *         name="id_tahapan_k",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract Approved Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function approve($id, $id_tahapan_k)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);
            $file = UploadKontrakTahapan::where('id_kontrak_tahapan', $id)->first();


            $data->update([
                'status' => 'y'
            ]);

            if ($data) {
                switch ($id_tahapan_k) {
                    case 1:
                        $tahapan               = new KontrakTahapan();
                        $tahapan->id_kontrak   = $data->id_kontrak;
                        $tahapan->id_tahapan_k = 2;
                        $tahapan->tgl          = today();
                        $tahapan->keterangan   = '';
                        $tahapan->save();

                        ActivityLogHelper::log('contract:proposal_approved', 1, [
                            'date'        => $tahapan->tgl,
                        ]);

                        break;
                    case 2:
                        $tahapan = new KontrakTahapan();
                        $tahapan->id_kontrak   = $data->id_kontrak;
                        $tahapan->id_tahapan_k = 3;
                        $tahapan->tgl          = today();
                        $tahapan->keterangan   = $data->keterangan;
                        $tahapan->save();

                        if ($tahapan) {
                            $upload = new UploadKontrakTahapan();
                            $upload->id_kontrak_tahapan = $tahapan->id_kontrak_tahapan;
                            $upload->judul              = $file->judul;
                            $upload->file               = $file->file;
                            $upload->save();
                        }

                        ActivityLogHelper::log('contract:drafting_approved', 1, [
                            'date'        => $tahapan->tgl,
                        ]);

                        break;
                    case 3:
                        $revisi = UploadRevisi::where('id_upload_kontrak_tahapan', $file->id_upload_kontrak_tahapan)->latest()->first();

                        if ($revisi != null) {
                            $file_name = $revisi->file;
                            $filename = basename($file_name);
                            $filePath = public_path('uploads/file/upload_revisi/' . $filename);
                        } else {
                            $file_name = $file->file;
                            $filename = basename($file_name);
                            $filePath = public_path('uploads/file/upload_kontrak_tahapan/' . $filename);
                        }

                        if (!file_exists($filePath)) {
                            throw new \Exception("File not found: {$filename}");
                        }

                        // 3. Reconstruct an UploadedFile object
                        $tempPath = tempnam(sys_get_temp_dir(), 'tmp_'); // Create a temp file
                        copy($filePath, $tempPath); // Copy content to temp file

                        $fileContent = new UploadedFile(
                            $tempPath,
                            $filename,
                            mime_content_type($tempPath),
                            null,
                            true // Test mode (no actual HTTP upload)
                        );

                        $attachment = add_file($fileContent, 'upload_kontrak_tahapan/');

                        $tahapan = new KontrakTahapan();
                        $tahapan->id_kontrak   = $data->id_kontrak;
                        $tahapan->id_tahapan_k = 4;
                        $tahapan->tgl          = today();
                        $tahapan->keterangan   = $data->keterangan;
                        $tahapan->save();

                        if ($tahapan) {
                            $upload = new UploadKontrakTahapan();
                            $upload->id_kontrak_tahapan = $tahapan->id_kontrak_tahapan;
                            $upload->judul              = $file->judul;
                            $upload->file               = $attachment;
                            $upload->save();
                        }

                        ActivityLogHelper::log('contract:review_approved', 1, [
                            'date'        => $tahapan->tgl,
                        ]);

                        break;
                    default:
                        return ApiResponseClass::throw('Invalid Data', 400);
                }
            }

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Approved Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/kontrak_tahapan/reject/{id}",
     *     tags={"Contract Legal - Kontrak Tahapan"},
     *     summary="Reject the specified resource",
     *     description="Reject the specified resource",
     *     @OA\Parameter(
     *         description="id of contract stage",
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
     *         description="Contract Stage Rejected Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function reject($id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);
            if (!$data) {
                return ApiResponseClass::throw('Contract stage not found', 404);
            }

            if ($data->id_tahapan_k == 1) {
                $kontrak = Kontrak::find($data->id_kontrak);
                if (!$kontrak) {
                    return ApiResponseClass::throw('Contract not found', 404);
                }

                $kontrak->delete();

                ActivityLogHelper::log('contract:contract_stage_rejected', 1, [
                    'contract:stages' => $data->toTahapanK->name,
                    'date'            => $data->tgl,
                    'company'         => $kontrak->nama_perusahaan,
                ]);

                DB::connection('contract_legal')->commit();
                return ApiResponseClass::sendResponse($data, 'Contract Stage Rejected Successfully');
            }

            if ($data->id_tahapan_k == 5) {
                $kontrak = Kontrak::find($data->id_kontrak);

                if (!$kontrak) {
                    return ApiResponseClass::throw('Contract not found', 404);
                }

                $data->update(['status' => 'n']);
                $kontrak->update(['status' => 'final']);

                ActivityLogHelper::log('contract:contract_stage_rejected', 1, [
                    'contrcat:stages' => $data->toTahapanK->name,
                    'date'            => $data->tgl,
                    'company'         => $kontrak->nama_perusahaan,
                ]);

                DB::connection('contract_legal')->commit();
                return ApiResponseClass::sendResponse($data, 'Contract Stage Rejected Successfully');
            }

            $id_tahapan_k = $data->id_tahapan_k - 1;
            $tahapan = KontrakTahapan::where('id_kontrak', $data->id_kontrak)
                ->where('id_tahapan_k', '=', $id_tahapan_k)
                ->latest()
                ->first();

            if (!$tahapan) {
                return ApiResponseClass::throw('Previous contract stage not found', 404);
            }

            $data->update(['status' => 'n']);
            $tahapan->update(['status' => null]);

            ActivityLogHelper::log('contract:contract_stage_rejected', 1, [
                'contract:from_stage' => $data->toTahapanK->name,
                'contract:to_stage'   => $tahapan->toTahapanK->name,
                'date'                => $data->tgl,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Stage Rejected Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_stage_rejected', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/kontrak_tahapan/finalize/{id}",
     *     tags={"Contract Legal - Kontrak Tahapan"},
     *     summary="Finalize the specified resource",
     *     description="Finalize the specified resource",
     *     @OA\Parameter(
     *         description="id of contract stage",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Body of Finalize",
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="no_kontrak",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="tgl_mulai",
     *                 type="string",
     *                 format="date-time"
     *             ),
     *             @OA\Property(
     *                 property="tgl_akhir",
     *                 type="string",
     *                 format="date-time"
     *             ),
     *             @OA\Property(
     *                 property="attachment",
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract Finalized Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function finalize(KontrakRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);
            $data->update([
                'status' => 'y'
            ]);

            if ($data) {
                $kontrak = Kontrak::find($data->id_kontrak);
                if ($request->hasFile('files.File')) {

                    $file = $request->file('files.File');
                    $attachment = add_file($file, 'kontrak/');

                    $kontrak->update([
                        'no_kontrak'     => $request->no_kontrak,
                        'status'         => 'final',
                        'tgl_mulai'      => $request->tgl_mulai,
                        'tgl_akhir'      => $request->tgl_akhir,
                        'attachment'     => $attachment
                    ]);
                } else {
                    $kontrak->update([
                        'no_kontrak'     => $request->no_kontrak,
                        'status'         => 'final',
                        'tgl_mulai'      => $request->tgl_mulai,
                        'tgl_akhir'      => $request->tgl_akhir
                    ]);
                }
            } else {
                return ApiResponseClass::throw('Invalid Data', 400);
            }

            ActivityLogHelper::log('contract:contract_finalized', 1, [
                'contract:contract_number' => $kontrak->no_kontrak,
                'start_date'               => $kontrak->tgl_mulai,
                'end_date'                 => $kontrak->tgl_akhir,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Approved Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_finalized', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/kontrak_tahapan/approve_addendum/{id}",
     *     tags={"Contract Legal - Kontrak Tahapan"},
     *     summary="Approve addendum for the specified contract",
     *     description="Approve addendum for the specified contract",
     *     @OA\Parameter(
     *         description="ID of Contract Stage",
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
     *         description="Addendum Approved Successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Data"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contract not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function approve_addendum($id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);

            if (!$data) {
                return ApiResponseClass::throw('Invalid Data', 400);
            }

            $kontrak = Kontrak::find($data->id_kontrak);

            if (!$kontrak) {
                return ApiResponseClass::throw('Contract not found', 404);
            }

            $status = $kontrak->update([
                'status' => 'final'
            ]);

            if (!$status) {
                throw new \Exception('Failed to update contract status');
            }

            $data->update([
                'status' => 'y'
            ]);

            ActivityLogHelper::log('contract:addendum_approved', 1, [
                'contract:contract_number' => $kontrak->no_kontrak,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Approved Successfully');
        } catch (\Throwable $e) {
            ActivityLogHelper::log('contract:addendum_approved', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

        /**
     * @OA\PUT(
     *     path="/kontrak_tahapan/finalize_addendum/{id}",
     *     tags={"Contract Legal - Kontrak Tahapan"},
     *     summary="Finalize the specified resource",
     *     description="Finalize the specified resource",
     *     @OA\Parameter(
     *         description="id of contract stage",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Body of Finalize",
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="no_kontrak",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="tgl_mulai",
     *                 type="string",
     *                 format="date-time"
     *             ),
     *             @OA\Property(
     *                 property="tgl_akhir",
     *                 type="string",
     *                 format="date-time"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract Finalized Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function finalize_addendum(FinalizeRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);
            $data->update([
                'status' => 'y'
            ]);

            if ($data) {
                $kontrak = Kontrak::find($data->id_kontrak);
                
                $kontrak->update([
                    'status'         => 'final',
                    'tgl_mulai'      => $request->tgl_mulai,
                    'tgl_akhir'      => $request->tgl_akhir
                ]);
            } else {
                return ApiResponseClass::throw('Invalid Data', 400);
            }

            ActivityLogHelper::log('contract:contract_finalized', 1, [
                'contract:contract_number' => $kontrak->no_kontrak,
                'start_date'               => $kontrak->tgl_mulai,
                'end_date'                 => $kontrak->tgl_akhir,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Approved Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_finalized', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/kontrak_tahapan/edit_desc/{id}",
     *     tags={"Contract Legal - Kontrak Tahapan"},
     *     summary="Edit the specified resource in storage",
     *     description="Edit the specified resource in storage",
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
     *     @OA\RequestBody(
     *         description="Body of Edit Description",
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="keterangan",
     *                 type="string"
     *             ),
     *             required={"keterangan"},
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Description Updated Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function edit_desc(EditKontrakTahapanDescRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = KontrakTahapan::find($id);
            $data->update([
                'keterangan' => $request->keterangan,
            ]);

            ActivityLogHelper::log('contract:description_edit', 1, [
                'description' => $data->keterangan,
            ]);
            DB::connection('contract_legal')->commit();
            return ApiResponseClass::sendResponse($data, 'Description Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:description_edit', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
