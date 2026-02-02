<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\ReportBodyRequest;
use App\Http\Resources\finance\ReportBodyResource;
use App\Models\finance\ReportBody;
use Illuminate\Support\Facades\DB;

class ReportBodyController extends Controller
{
    /**
     * @OA\Get(
     *  path="/report/bodies",
     *  summary="Get the list of report bodies",
     *  tags={"Finance - Report Body"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = ReportBody::with(['toCoa'])->orderBy('id_report_body', 'asc')->get();

        return ApiResponseClass::sendResponse(ReportBodyResource::collection($data), 'Report Body Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/report/bodies",
     *  summary="Add a new report body",
     *  tags={"Finance - Report Body"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_report_title",
     *                  type="integer",
     *                  description="Report title id"
     *              ),
     *              @OA\Property(
     *                  property="id_report_menu",
     *                  type="integer",
     *                  description="ID Report Menu"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              @OA\Property(
     *                  property="method",
     *                  type="string",
     *                  description="Method (default, range, report)"
     *              ),
     *              @OA\Property(
     *                  property="operation",
     *                  type="string",
     *                  description="Operation (+, -, *, /)"
     *              ),
     *              required={"id_report_title"},
     *              example={
     *                  "id_report_title": 1,
     *                  "id_report_menu": 1,
     *                  "id_coa": 1,
     *                  "method": "default",
     *                  "operation": "+"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ReportBodyRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $report_body                  = new ReportBody();
            $report_body->id_report_title = $request->id_report_title;
            $report_body->id_report_menu  = $request->id_report_menu;
            $report_body->id_coa_body     = $request->id_coa_body;
            $report_body->id_coa          = $request->id_coa;
            $report_body->method          = $request->method;
            $report_body->operation       = $request->operation;
            $report_body->save();

            ActivityLogHelper::log('finance:report_body_create', 1, $this->_getLogData($report_body));

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($report_body, 'Report Body Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_body_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/report/bodies/{id}",
     *  summary="Get report body by id",
     *  tags={"Finance - Report Body"},
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
        $data = ReportBody::find($id);

        return ApiResponseClass::sendResponse(ReportBodyResource::make($data), 'Report Body Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/report/bodies/{id}",
     *  summary="Update report body by id",
     *  tags={"Finance - Report Body"},
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
     *                  property="id_report_title",
     *                  type="integer",
     *                  description="Report title id"
     *              ),
     *              @OA\Property(
     *                  property="id_report_menu",
     *                  type="integer",
     *                  description="ID Report Menu"
     *              ),
     *              @OA\Property(
     *                  property="id_coa",
     *                  type="integer",
     *                  description="Coa id"
     *              ),
     *              @OA\Property(
     *                  property="method",
     *                  type="string",
     *                  description="Method (default, range, report)"
     *              ),
     *              @OA\Property(
     *                  property="operation",
     *                  type="string",
     *                  description="Operation (+, -, *, /)"
     *              ),
     *              required={"id_report_title"},
     *              example={
     *                  "id_report_title": 1,
     *                  "id_coa": 1,
     *                  "id_report_menu": 1,
     *                  "method": "default",
     *                  "operation": "+"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(ReportBodyRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = ReportBody::find($id);

            $data->update([
                'id_report_title' => $request->id_report_title,
                'id_report_menu'  => $request->id_report_menu,
                'id_coa'          => $request->id_coa,
                'id_coa_body'     => $request->id_coa_body,
                'method'          => $request->method,
                'operation'       => $request->operation,
            ]);

            ActivityLogHelper::log('finance:report_body_update', 1, $this->_getLogData($data));

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Report Body Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_body_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/report/bodies/{id}",
     *  summary="Delete report body by id",
     *  tags={"Finance - Report Body"},
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
            $data = ReportBody::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:report_body_delete', 1, $this->_getLogData($data));

            return ApiResponseClass::sendResponse($data, 'Report Body Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_body_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/report/bodies/details/{id}",
     *  summary="Get report body details by id",
     *  tags={"Finance - Report Body"},
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
        $data = ReportBody::whereIdReportTitle($id)->get();

        return ApiResponseClass::sendResponse(ReportBodyResource::collection($data), 'Report Body Retrieved Successfully');
    }

    /**
     * Prepare log data for activity logging
     *
     * @param ReportBody $data
     * @return array
     */
    private function _getLogData($data)
    {
        $log = [
            'finance:report_title' => $data->toReportTitle->name,
            'type'                 => $data->method === 'range' ? 'initial' : $data->method,
            'operation'            => $data->operation
        ];

        switch ($data->method) {
            case 'coa':
            case 'range':
                $log['finance:coa'] = $data->toCoa->name;
                break;
            case 'subcoa':
                $log['finance:coa_body'] = $data->toCoaBody->name;
                break;
            case 'report':
                $log['finance:report_menu'] = $data->toReportTitle->name;
                break;
        }

        return $log;
    }
}
