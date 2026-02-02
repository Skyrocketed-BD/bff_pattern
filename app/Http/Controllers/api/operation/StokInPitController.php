<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\StokInPitRequest;
use App\Http\Requests\operation\StokInPitUploadRequest;
use App\Imports\operation\StokInPitImport;
use App\Models\operation\Kontraktor;
use App\Models\operation\StokInPit;
use App\Repositories\operation\StokInPitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use File;
use Maatwebsite\Excel\Validators\ValidationException;

class StokInPitController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/stok_in_pits",
     *  summary="Get the list of stok in pits",
     *  tags={"Operation - Stok In Pit"},
     *  @OA\Parameter(
     *      name="start_date",
     *      in="query",
     *      description="Start date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="end_date",
     *      in="query",
     *      description="End date",
     *      required=false,
     *      @OA\Schema(
     *          type="string",
     *          format="date"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="id_kontraktor",
     *      in="query",
     *      description="Id Kontraktor",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="id_pit",
     *      in="query",
     *      description="Id Pit",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="id_block",
     *      in="query",
     *      description="Id Block",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="id_dom",
     *      in="query",
     *      description="Id Dom",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OA\Parameter(
     *      name="grade",
     *      in="query",
     *      description="Grade",
     *      required=false,
     *      @OA\Schema(
     *          type="string"
     *      ),
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request, StokInPitRepository $stok_in_pit_repository)
    {
        $data = $stok_in_pit_repository->getAll($request, $this->id_kontraktor);

        return ApiResponseClass::sendResponse($data, 'Stok In Pit Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/stok_in_pits",
     *  summary="Add a new stok in pits",
     *  tags={"Operation - Stok In Pit"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_block",
     *                  type="integer",
     *                  description="Id Block of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="id_pit",
     *                  type="integer",
     *                  description="Id Pit of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="id_dom_in_pit",
     *                  type="integer",
     *                  description="Id Pit of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="sample_id",
     *                  type="string",
     *                  description="Sample Id of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="float",
     *                  description="Ni of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="fe",
     *                  type="float",
     *                  description="Fe of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="co",
     *                  type="float",
     *                  description="Co of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="sio2",
     *                  type="float",
     *                  description="Sio2 of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="mgo2",
     *                  type="float",
     *                  description="Mgo2 of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="float",
     *                  description="Tonage of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="ritasi",
     *                  type="float",
     *                  description="Ritasi of stok_in_pit"
     *              ),
     *              required={"id_block", "id_pit", "id_dom_in_pit", "sample_id", "date", "ni", "fe", "co", "sio2", "mgo2", "tonage", "ritasi"},
     *              example={
     *                  "id_block": 1,
     *                  "id_pit": 1,
     *                  "id_dom_in_pit": 1,
     *                  "sample_id": "SM-0001",
     *                  "date": "2021-01-01",
     *                  "ni": 1,
     *                  "fe": 1,
     *                  "co": 1,
     *                  "sio2": 1,
     *                  "mgo2": 1,
     *                  "tonage": 1,
     *                  "ritasi": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(StokInPitRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $stok_in_pit                = new StokInPit();
            $stok_in_pit->id_kontraktor = $this->id_kontraktor;
            $stok_in_pit->id_block      = $request->id_block;
            $stok_in_pit->id_pit        = $request->id_pit;
            $stok_in_pit->id_dom_in_pit = $request->id_dom_in_pit;
            $stok_in_pit->sample_id     = $request->sample_id;
            $stok_in_pit->date          = $request->date;
            $stok_in_pit->ni            = $request->ni;
            $stok_in_pit->fe            = $request->fe;
            $stok_in_pit->co            = $request->co;
            $stok_in_pit->sio2          = $request->sio2;
            $stok_in_pit->mgo2          = $request->mgo2;
            $stok_in_pit->tonage        = $request->tonage;
            $stok_in_pit->ritasi        = $request->ritasi;
            $stok_in_pit->save();

            ActivityLogHelper::log('operation:stock_in_pit_create', 1, [
                'operation:contractor' => Kontraktor::find($stok_in_pit->id_kontraktor)->company,
                'date'                 => $stok_in_pit->date,
                'operation:tonnage'     => $stok_in_pit->tonage,
                'operation:ritasion'   => $stok_in_pit->ritasi
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_in_pit, 'Pit Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_in_pit_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Post(
     *  path="/stok_in_pits/upload",
     *  summary="Upload a stok in pit",
     *  tags={"Operation - Stok In Pit"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_block",
     *                  type="integer",
     *                  description="Id Block of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="id_pit",
     *                  type="integer",
     *                  description="Id Pit of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="id_dom_in_pit",
     *                  type="integer",
     *                  description="Id Pit of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="file",
     *                  type="file",
     *                  description="File of stok_in_pit"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function upload(StokInPitUploadRequest $request)
    {
        $file_name = add_file($request->file, 'stok_in_pit/');

        $import = new StokInPitImport($request, $this->id_kontraktor);

        try {
            Excel::import($import, upload_path('file/stok_in_pit/' . $file_name));

            $file = upload_path('file/stok_in_pit/' . $file_name);

            // hapus file
            if (File::exists($file)) {
                File::delete($file);
            }

            $response = [
                'status'  => true,
                'message' => 'Pit Created Successfully'
            ];

            return Response::json($response, 200);
        } catch (ValidationException $e) {
            $failures = $e->failures();

            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }

            del_file($file_name, 'stok_in_pit/');

            $response = [
                'status'  => false,
                'message' => 'Validation errors',
                'errors'  => $errorMessages
            ];

            return Response::json($response, 422);
        }
    }

    /**
     * @OA\Put(
     *  path="/stok_in_pits/{id}",
     *  summary="Update a stok in pit",
     *  tags={"Operation - Stok In Pit"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_block",
     *                  type="integer",
     *                  description="Id block of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="id_pit",
     *                  type="integer",
     *                  description="Id pit of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="id_dom_in_pit",
     *                  type="integer",
     *                  description="Id dom_in_pit of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="sample_id",
     *                  type="string",
     *                  description="Sample Id of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="date",
     *                  type="date",
     *                  description="Date of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="ni",
     *                  type="float",
     *                  description="Ni of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="fe",
     *                  type="float",
     *                  description="Fe of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="co",
     *                  type="float",
     *                  description="Co of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="sio2",
     *                  type="float",
     *                  description="Sio2 of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="mgo2",
     *                  type="float",
     *                  description="Mgo2 of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="tonage",
     *                  type="float",
     *                  description="Tonage of stok_in_pit"
     *              ),
     *              @OA\Property(
     *                  property="ritasi",
     *                  type="float",
     *                  description="Ritasi of stok_in_pit"
     *              ),
     *              required={"id_block", "id_pit", "id_dom_in_pit", "sample_id", "date", "ni", "fe", "co", "sio2", "mgo2", "tonage", "ritasi"},
     *              example={
     *                  "id_block": 1,
     *                  "id_pit": 1,
     *                  "id_dom_in_pit": 1,
     *                  "sample_id": "sample_id",
     *                  "date": "2021-01-01",
     *                  "ni": 1,
     *                  "fe": 1,
     *                  "co": 1,
     *                  "sio2": 1,
     *                  "mgo2": 1,
     *                  "tonage": 1,
     *                  "ritasi": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(StokInPitRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $stok_in_pit = StokInPit::find($id);

            $stok_in_pit->update([
                'id_kontraktor' => $this->id_kontraktor,
                'id_block'      => $request->id_block,
                'id_pit'        => $request->id_pit,
                'id_dom_in_pit' => $request->id_dom_in_pit,
                'date'          => $request->date,
                'sample_id'     => $request->sample_id,
                'ni'            => $request->ni,
                'fe'            => $request->fe,
                'co'            => $request->co,
                'sio2'          => $request->sio2,
                'mgo2'          => $request->mgo2,
                'tonage'        => $request->tonage,
                'ritasi'        => $request->ritasi,
            ]);

            ActivityLogHelper::log('operation:stock_in_pit_update', 1, [
                'operation:contractor' => Kontraktor::find($stok_in_pit->id_kontraktor)->company,
                'date'                 => $stok_in_pit->date,
                'operation:tonnage'     => $stok_in_pit->tonage,
                'operation:ritasion'   => $stok_in_pit->ritasi
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_in_pit, 'Pit Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_in_pit_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/stok_in_pits/{id}",
     *  summary="Delete a stok_in_pits",
     *  tags={"Operation - Stok In Pit"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          description="ID of stok_in_pits"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = StokInPit::find($id);

            $data->delete();

            ActivityLogHelper::log('operation:stock_in_pit_delete', 1, [
                'operation:contractor'  => Kontraktor::find($data->id_kontraktor)->company,
                'date'                  => $data->date,
                'operation:block'       => $data->toBlock->name,
                'operation:pit'         => $data->toPit->name,
                'operation:dome_in_pit' => $data->toDomInPit->name,
                'operation:tonnage'      => $data->tonage,
                'operation:ritasion'    => $data->ritasi
            ]);

            return ApiResponseClass::sendResponse($data, 'Pit Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_in_pit_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/stok_in_pits/filter",
     *  summary="Get the list of stok in pits filter",
     *  tags={"Operation - Stok In Pit"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      @OA\Schema(
     *          type="integer",
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function filter()
    {
        $kontraktor = Kontraktor::query();

        $kontraktor->with(['toBlock', 'toPit', 'toDomInPit']);

        if ($this->id_kontraktor != null) {
            $kontraktor->whereKontraktor($this->id_kontraktor);
        }

        $data = $kontraktor->get();

        $kontraktor = [];
        $block      = [];
        $pit        = [];
        $dom_in_pit = [];

        if (!empty($data)) {
            foreach ($data as $row) {
                $kontraktor[] = [
                    'id_kontraktor' => $row->id_kontraktor,
                    'name'          => $row->company,
                    'initial'       => $row->initial,
                ];

                if ($row->toBlock) {
                    foreach ($row->toBlock as $val1) {
                        $block[] = [
                            'id_kontraktor' => $row->id_kontraktor,
                            'id_block'      => $val1->id_block,
                            'name'          => $val1->name,
                        ];
                    }
                }

                if ($row->toPit) {
                    foreach ($row->toPit as $val2) {
                        $pit[] = [
                            'id_kontraktor' => $row->id_kontraktor,
                            'id_block'      => $val2->id_block,
                            'id_pit'        => $val2->id_pit,
                            'name'          => $val2->name,
                        ];
                    }
                }

                if ($row->toDomInPit) {
                    foreach ($row->toDomInPit as $val3) {
                        $dom_in_pit[] = [
                            'id_kontraktor' => $row->id_kontraktor,
                            'id_dom_in_pit' => $val3->id_dom_in_pit,
                            'id_pit'        => $val3->id_pit,
                            'name'          => $val3->name,
                        ];
                    }
                }
            }
        }

        $response = [
            'kontraktor' => $kontraktor,
            'block'      => $block,
            'pit'        => $pit,
            'dom'        => $dom_in_pit,
        ];

        return ApiResponseClass::sendResponse($response, 'Stok In Pit Filtered Successfully');
    }

    /**
     * @OA\Post(
     *  path="/stok_in_pits/transfer",
     *  summary="Transfer a stok in pit",
     *  tags={"Operation - Stok In Pit"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_stok_in_pit",
     *                  type="array",
     *                  description="Id of stok_in_pit",
     *                  @OA\Items(type="integer")
     *              ),
     *              @OA\Property(
     *                  property="id_kontraktor_sender",
     *                  type="integer",
     *                  description="Id Kontraktor Sender"
     *              ),
     *              @OA\Property(
     *                  property="id_kontraktor_receipt",
     *                  type="integer",
     *                  description="Id Kontraktor Receipt"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function transfer(Request $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $stok_in_pit           = $request->id_stok_in_pit;
            $id_kontraktor_sender  = $request->id_kontraktor_sender;
            $id_kontraktor_receipt = $request->id_kontraktor_receipt;

            foreach ($stok_in_pit as $key => $value) {
                $stok_in_pit = StokInPit::where('id_stok_in_pit', $value)->where('id_kontraktor', $id_kontraktor_sender)->first();  

                if ($stok_in_pit) {
                    $stok_in_pit->update([
                        'id_kontraktor' => $id_kontraktor_receipt,
                    ]);
                }
            }

            ActivityLogHelper::log('operation:stock_in_pit_transfer', 1, [
                'operation:contractor_sender'  => Kontraktor::find($id_kontraktor_sender)->company,
                'operation:contractor_receipt' => Kontraktor::find($id_kontraktor_receipt)->company
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($stok_in_pit, 'Stok In Pit Transfered Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:stok_in_pit_transfer', 0, ['error' => $e->getMessage()]);
            DB::connection('operation')->rollBack();
            return ApiResponseClass::rollback($e);
        }
    }

    public function download()
    {
        $file_path = format_path('StokInPitFormat.csv');
        if (File::exists($file_path)) {
            return response()->download($file_path);
        } else {
            return response()->json(['error' => 'File not found'], 404);
        }
    }
}
