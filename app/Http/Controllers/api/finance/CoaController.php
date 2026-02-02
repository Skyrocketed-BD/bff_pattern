<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\CoaRequest;
use App\Http\Requests\finance\CoaMultipleRequest;
use App\Http\Resources\finance\CoaResource;
use App\Models\finance\ClosingEntryDetail;
use App\Models\finance\Coa;
use App\Models\finance\GeneralLedger;
use App\Models\finance\GeneralLedgerLog;
use Illuminate\Support\Facades\DB;


class CoaController extends Controller
{
    /**
     * @OA\Get(
     *  path="/coa/coas",
     *  summary="Get the list of coa",
     *  tags={"Finance - Coa"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = Coa::with(['toTaxCoa', 'toCoaBody.toCoaClasification'])->orderBy('id_coa', 'asc')->get();

        return ApiResponseClass::sendResponse(CoaResource::collection($data), 'Coa Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/coa/coas",
     *  summary="Add a new coa",
     *  tags={"Finance - Coa"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa_body",
     *                  type="integer",
     *                  description="Coa body id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Coa name"
     *              ),
     *              @OA\Property(
     *                  property="coa",
     *                  type="integer",
     *                  description="Coa code"
     *              ),
     *              required={"id_coa_body", "name", "coa"},
     *              example={
     *                  "id_coa_body": 1,
     *                  "name": "Kas & Setara Kas",
     *                  "coa": 10
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(CoaMultipleRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $coaData     = $request->all();
            $id_coa_body = $coaData['id_coa_body'];
            $coaNames    = $coaData['coaName'];
            $coaNumbers  = $coaData['coaNumber'];

            $dataInsert = array_map(function ($name, $number) use ($id_coa_body) {
                return [
                    'id_coa_body' => $id_coa_body,
                    'name'        => $name,
                    'coa'         => $number,
                    'created_by'  => auth('api')->user()->id_users
                ];
            }, $coaNames, $coaNumbers);

            Coa::insert($dataInsert);

            ActivityLogHelper::log('finance:coa_create', 1, [
                'finance:coa_added' => implode(', ', $coaNames)
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($coaData, 'Coa Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/coa/coas/{id}",
     *  summary="Get the detail of coa",
     *  tags={"Finance - Coa"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = Coa::find($id);

        return ApiResponseClass::sendResponse(CoaResource::make($data), 'Coa Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/coa/coas/{id}",
     *  summary="Update the detail of coa",
     *  tags={"Finance - Coa"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_coa_body",
     *                  type="integer",
     *                  description="Coa body id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Coa name"
     *              ),
     *              @OA\Property(
     *                  property="coa",
     *                  type="integer",
     *                  description="Coa code"
     *              ),
     *              required={"id_coa_body", "name", "coa"},
     *              example={
     *                  "id_coa_body": 1,
     *                  "name": "Kas & Setara Kas",
     *                  "coa": 10
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(CoaRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = Coa::find($id);

            $data->update([
                'id_coa_body' => $request->id_coa_body,
                'name'        => $request->name,
                'coa'         => $request->coa,
                'updated_by'  => auth('api')->user()->id_users
            ]);

            ActivityLogHelper::log('finance:coa_update', 1, [
                'finance:coa_name' => $data->name,
                'finance:coa'      => $data->coa
            ]);

            $this->updateCoa($request->coa_old, $request->coa);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Coa Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/coa/coas/{id}",
     *  summary="Delete a coa",
     *  tags={"Finance - Coa"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = Coa::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:coa_delete', 1, [
                'finance:coa_name' => $data->name,
                'finance:coa'      => $data->coa
            ]);

            return ApiResponseClass::sendResponse($data, 'Coa Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:coa_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *  path="/coa/coas/details/{id}",
     *  summary="Get the detail of coa",
     *  tags={"Finance - Coa"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function details($id)
    {
        $data = Coa::whereIdCoaBody($id)->orderBy('coa', 'asc')->get();

        return ApiResponseClass::sendResponse(CoaResource::collection($data), 'Coa Retrieved Successfully');
    }

    private function updateCoa($coa_old, $coa_new)
    {
        // general ledger
        $general_ledger = GeneralLedger::withTrashed()->whereCoa($coa_old)->get();

        foreach ($general_ledger as $key => $value) {
            $value->coa = $coa_new;
            $value->save();
        }

        // general ledger log
        $general_ledger_log = GeneralLedgerLog::whereCoa($coa_old)->get();

        foreach ($general_ledger_log as $key => $value) {
            $value->coa = $coa_new;
            $value->save();
        }

        // closing entry detail
        $closing_entry_detail = ClosingEntryDetail::whereCoa($coa_old)->get();

        foreach ($closing_entry_detail as $key => $value) {
            $value->coa = $coa_new;
            $value->save();
        }
    }
}
