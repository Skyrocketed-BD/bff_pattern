<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\ReportMenuRequest;
use App\Models\finance\ReportMenu;
use Illuminate\Support\Facades\DB;

class ReportMenuController extends Controller
{
    /**
     * @OA\Get(
     *  path="/report/menus",
     *  summary="Get the list of report menus",
     *  tags={"Finance - Report Menu"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = ReportMenu::orderBy('id_report_menu', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Report Menu Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/report/menus",
     *  summary="Add a new report menu",
     *  tags={"Finance - Report Menu"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Report menu name"
     *              ),
     *              @OA\Property(
     *                  property="is_annual",
     *                  type="string",
     *                  description="Is annual"
     *              ),
     *              required={"name", "is_annual"},
     *              example={
     *                  "name": "Income",
     *                  "is_annual": "1"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ReportMenuRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $report_menu            = new ReportMenu();
            $report_menu->name      = $request->name;
            $report_menu->is_annual = $request->is_annual;
            $report_menu->save();

            ActivityLogHelper::log('finance:report_menu_create', 1, [
                'finance:report_title'  => $report_menu->name,
                'finance:report_period' => $report_menu->is_annual ? 'Annual' : 'Monthly'
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($report_menu, 'Report Menu Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_menu_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/report/menus/{id}",
     *  summary="Get report menu by ID",
     *  tags={"Finance - Report Menu"},
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
        $data = ReportMenu::find($id);

        return ApiResponseClass::sendResponse($data, 'Report Menu Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/report/menus/{id}",
     *  summary="Update report menu by ID",
     *  tags={"Finance - Report Menu"},
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
     *                  property="name",
     *                  type="string",
     *                  description="Report menu name"
     *              ),
     *              @OA\Property(
     *                  property="is_annual",
     *                  type="string",
     *                  description="Is annual"
     *              ),
     *              required={"name", "is_annual"},
     *              example={
     *                  "name": "Income",
     *                  "is_annual": "1"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(ReportMenuRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = ReportMenu::find($id);

            $data->update([
                'name'      => $request->name,
                'is_annual' => $request->is_annual,
            ]);

            ActivityLogHelper::log('finance:report_menu_update', 1, [
                'finance:report_title'  => $data->name,
                'finance:report_period' => $data->is_annual ? 'Annual' : 'Monthly'
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Report Menu Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_menu_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/report/menus/{id}",
     *  summary="Delete report menu by ID",
     *  tags={"Finance - Report Menu"},
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
            $data = ReportMenu::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:report_menu_delete', 1, [
                'finance:report_title'  => $data->name,
                'finance:report_period' => $data->is_annual ? 'Annual' : 'Monthly'
            ]);

            return ApiResponseClass::sendResponse($data, 'Report Menu Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_menu_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
