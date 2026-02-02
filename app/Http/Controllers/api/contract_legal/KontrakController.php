<?php

namespace App\Http\Controllers\api\contract_legal;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\contract_legal\FinalizeRequest;
use App\Models\contract_legal\LogKontrak;
use App\Models\contract_legal\Kontrak;
use App\Models\contract_legal\KontrakTahapan;
use App\Models\contract_legal\UploadKontrakTahapan;
use App\Http\Requests\contract_legal\KontrakRequest;
use App\Http\Requests\contract_legal\KontrakTahapanRequest;
use App\Http\Requests\contract_legal\LampiranKontrakRequest;
use App\Http\Resources\contract_legal\KontrakResource as Resource;
use App\Models\contract_legal\LampiranKontrak;
use Illuminate\Http\Request;

class KontrakController extends Controller
{

    /**
     * @OA\Get(
     *     path="/kontrak",
     *     tags={"Contract Legal - Kontrak"},
     *     summary="Get all contract",
     *     description="Get all contract",
     *     @OA\Response(response=200, description="successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index()
    {
        $data = Kontrak::with([
            'toKontak',
            'toLampiranKontrak' => function ($q) {
                $q->orderBy('id_lampiran_kontrak', 'desc');
                        },
            'toKontrakTahapan' => function ($query) {
                $query->orderBy('id_kontrak_tahapan', 'desc')
                    ->with([
                        'toTahapanK',
                        'toUploadKontrakTahapan' => function ($q) {
                            $q->orderBy('id_upload_kontrak_tahapan', 'desc');
                        },
                        'toRevisi' => function ($q) {
                            $q->orderBy('id_revisi', 'desc')
                                ->with(['toUploadRevisi' => function ($q2) {
                                    $q2->orderBy('id_upload_revisi', 'desc');
                                }]);
                        }
                    ]);
            }
        ])
            ->whereNotNull('no_kontrak')
            ->latest()
            ->get();

        return ApiResponseClass::sendResponse(Resource::collection($data), 'Contract Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *     path="/kontrak",
     *     tags={"Contract Legal - Kontrak"},
     *     summary="Create new contract",
     *     description="Create new contract",
     *     @OA\RequestBody(
     *         description="Body of Create Contract",
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
     *         description="Contract Created Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function store(KontrakRequest $request)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $file = $request->file('files.File');
            $attachment = add_file($file, 'kontrak/');

            $kontrak                  = new Kontrak();
            $kontrak->no_kontrak      = $request->no_kontrak;
            $kontrak->nama_perusahaan = $request->nama_perusahaan;
            $kontrak->tgl_mulai       = $request->tgl_mulai;
            $kontrak->tgl_akhir       = $request->tgl_akhir;
            $kontrak->status          = 'final';
            $kontrak->attachment      = $attachment;
            $kontrak->save();

            ActivityLogHelper::log('contract:contract_create', 1, [
                'contract:contract_number' => $request->no_kontrak,
                'company'                  => $request->nama_perusahaan
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($kontrak, 'Contract Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_create', 0, [['error' => $e->getMessage()]]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function show($id)
    {
        $data = Kontrak::find($id);

        return ApiResponseClass::sendResponse(Resource::collection($data), '... Retrieved Successfully');
    }

    public function destroy($id)
    {
        try {
            $data = Kontrak::find($id);

            $data->delete();

            ActivityLogHelper::log('contract:contract_delete', 1, [
                'contract:contract_number' => $data->no_kontrak,
                'company'                  => $data->nama_perusahaan,
                'start_date'               => $data->tgl_mulai,
                'end_date'                 => $data->tgl_akhir
            ]);

            return ApiResponseClass::sendResponse($data, 'Contract Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/kontrak/renew/{id}",
     *     tags={"Contract Legal - Kontrak"},
     *     summary="Renew or amend a contract",
     *     description="Renews an existing contract with new details or creates an amendment (adendum)",
     *     @OA\Parameter(
     *         description="ID of the contract to renew",
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
     *         description="Contract renewal data",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"no_kontrak"},
     *                 @OA\Property(
     *                     property="no_kontrak",
     *                     type="string",
     *                     description="New contract number"
     *                 ),
     *                 @OA\Property(
     *                     property="tgl_mulai",
     *                     type="string",
     *                     format="date",
     *                     description="New start date (for renewal)"
     *                 ),
     *                 @OA\Property(
     *                     property="tgl_akhir",
     *                     type="string",
     *                     format="date",
     *                     description="New end date (for renewal)"
     *                 ),
     *                 @OA\Property(
     *                     property="files",
     *                     type="string",
     *                     format="binary",
     *                     description="Main contract file (for renewal)"
     *                 ),
     *                 @OA\Property(
     *                     property="file_adendum",
     *                     type="string",
     *                     format="binary",
     *                     description="Amendment file (for adendum)"
     *                 ),
     *                 @OA\Property(
     *                     property="judul_adendum",
     *                     type="string",
     *                     description="Title of the amendment (for adendum)"
     *                 ),
     *                 @OA\Property(
     *                     property="keterangan",
     *                     type="string",
     *                     description="Description for addendum"
     *                 ),
     *                 required={"no_kontrak", "files", "tgl_mulai", "tgl_akhir", "keterangan", "judul_adendum", "file_adendum"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract renewed successfully",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contract not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error message")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function renew(FinalizeRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = Kontrak::find($id);
            if (!$data) {
                return ApiResponseClass::throw('Contract not found', 404);
            }

            $log_kontrak = new LogKontrak();
            $log_kontrak->id_kontrak = $id;
            $log_kontrak->no_kontrak = $data->no_kontrak;
            $log_kontrak->save();

            if (empty($request->judul_adendum)) {
                $file = $request->file('files.File');
                $attachment = add_file($file, 'kontrak/');

                $data->update([
                    'no_kontrak'        => $request->no_kontrak,
                    'tgl_mulai'         => $request->tgl_mulai,
                    'tgl_akhir'         => $request->tgl_akhir,
                    'status'            => 'final',
                    'attachment'        => $attachment
                ]);
            } elseif (!empty($request->judul_adendum)) {
                $data->update([
                    'no_kontrak'    => $request->no_kontrak,
                    'status'        => 'adendum'
                ]);

                $tahapan = new KontrakTahapan();
                $tahapan->id_tahapan_k = 5;
                $tahapan->id_kontrak   = $id;
                $tahapan->tgl          = today();
                $tahapan->keterangan   = $request->keterangan;
                $tahapan->save();

                $judul = $request->judul_adendum;
                $file  = $request->file('files.File');

                $attachment = add_file($file, 'upload_kontrak_tahapan/');

                $upload_tahapan = new UploadKontrakTahapan();
                $upload_tahapan->id_kontrak_tahapan = $tahapan->id_kontrak_tahapan;
                $upload_tahapan->judul              = $judul;
                $upload_tahapan->file               = $attachment;
                $upload_tahapan->save();
            } else {
                return ApiResponseClass::sendResponse(null, 'Data cannot be empty', 400);
            }

            ActivityLogHelper::log('contract:contract_renewal', 1, [
                'contract:contract_number'      => $data->no_kontrak,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_renew', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/kontrak/addendum/{id}",
     *     tags={"Contract Legal - Kontrak"},
     *     summary="Addendum kontrak",
     *     description="Addendum kontrak",
     *     @OA\Parameter(
     *         description="ID Kontrak",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Body of Addendum Kontrak",
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="keterangan",
     *                 type="string"
     *             ),
     *             @OA\Property(
     *                 property="file",
     *                 type="string",
     *                 format="binary"
     *             ),
     *             @OA\Property(
     *                 property="judul",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Addendum Created Successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function addendum(KontrakTahapanRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $data = Kontrak::find($id);

            $data->update([
                'status'       => 'adendum',
            ]);

            if ($data) {
                $tahapn = new KontrakTahapan();
                $tahapn->id_tahapan_k = 5;
                $tahapn->id_kontrak   = $id;
                $tahapn->tgl          = today();
                $tahapn->keterangan   = $request->keterangan;
                $tahapn->status       = 'a';
                $tahapn->save();

                $attachment = add_file($request->file('files.File'), 'upload_kontrak_tahapan/');

                $file = new UploadKontrakTahapan();
                $file->id_kontrak_tahapan = $tahapn->id_kontrak_tahapan;
                $file->judul              = $request->judul;
                $file->file               = $attachment;
                $file->save();
            } else {
                return ApiResponseClass::throw('Invalid Data', 400);
            }

            ActivityLogHelper::log('contract:contract_addendum', 1, [
                'contract:contract_number'      => $data->no_kontrak,
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse($data, 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:contract_addendum', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/unassigned",
     *     tags={"Contract Legal - Kontrak"},
     *     summary="Get all contract",
     *     description="Get all contract",
     *     @OA\Response(response=200, description="successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function unassigned()
    {
        $query = Kontrak::query();

        $query->with(['toKontak']);

        $data = $query->get()->filter(function ($item) {
            return $item->toKontak === null;
        });

        return ApiResponseClass::sendResponse(Resource::collection($data), 'Contract Retrieved Successfully');
    }
    
    /**
     * @OA\Post(
     *     path="/kontrak/add_support_doc/{id}",
     *     tags={"Contract Legal - Kontrak"},
     *     summary="Add support file to the specified contract",
     *     description="Uploads and attaches a support file to the specified contract",
     *     @OA\Parameter(
     *         description="ID of the contract",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Body of Add Support Document",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
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
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support file added successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */

    public function addSupportDoc(LampiranKontrakRequest $request, $id)
    {
        DB::connection('contract_legal')->beginTransaction();
        try {
            $no_kontrak = Kontrak::where('id_kontrak', $id)->value('no_kontrak');
            $judul = $request->judul;
            $file  = $request->file;

            foreach ($judul as $i => $judulItem) {
                $attachment = add_file($file[$i], 'lampiran_kontrak/');

                $lampiran = new LampiranKontrak();
                $lampiran->id_kontrak = $id;
                $lampiran->judul      = $judulItem;
                $lampiran->file       = $attachment;
                $lampiran->save();
            }

            ActivityLogHelper::log('contract:supporting_document', 1, [
                'message' => 'Support file for contract ' . $no_kontrak . ' added successfully',
            ]);

            DB::connection('contract_legal')->commit();

            return ApiResponseClass::sendResponse([], 'Contract Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('contract:supporting_file', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
