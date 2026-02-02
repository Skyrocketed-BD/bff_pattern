<?php

namespace App\Http\Controllers\api\finance;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\ReportTitleRequest;
use App\Http\Resources\finance\ReportTitleResource;
use App\Models\finance\ClosingEntry;
use App\Models\finance\Coa;
use App\Models\finance\CoaBody;
use App\Models\finance\ReportFormula;
use App\Models\finance\ReportMenu;
use App\Models\finance\ReportTitle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportTitleController extends Controller
{
    /**
     * @OA\Get(
     *  path="/report/titles",
     *  summary="Get the list of report titles",
     *  tags={"Finance - Report Title"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = ReportTitle::with(['toReportBody'])->orderBy('id_report_title', 'asc')->get();

        return ApiResponseClass::sendResponse(ReportTitleResource::collection($data), 'Report Title Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/report/titles",
     *  summary="Add a new report title",
     *  tags={"Finance - Report Title"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_report_menu",
     *                  type="integer",
     *                  description="Report menu id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Report title name"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  description="(default, formula, input)"
     *              ),
     *              @OA\Property(
     *                  property="value",
     *                  type="integer",
     *                  description="Value"
     *              ),
     *              @OA\Property(
     *                  property="id_report_title_select",
     *                  type="array",
     *                  @OA\Items(
     *                      type="integer",
     *                      example={1, 2, 3, 4},
     *                  ),
     *                  description="Report title select id"
     *              ),
     *              @OA\Property(
     *                  property="operation",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string",
     *                      example={"+", "-", "*", "/"},
     *                  ),
     *                  description="Operation"
     *              ),
     *              @OA\Property(
     *                  property="display_currency",
     *                  type="string",
     *                  description="Display currency",
     *                  enum={"on", "off"},
     *              ),
     *              required={"id_report_menu", "name", "type", "id_report_title_select", "operation"},
     *              example={
     *                  "id_report_menu": 1,
     *                  "name": "Income",
     *                  "type": "default",
     *                  "id_report_title_select": {1, 2, 3, 4},
     *                  "operation": {"+", "-", "*", "/"},
     *                  "display_currency": "on"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(ReportTitleRequest $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $report_title                   = new ReportTitle();
            $report_title->id_report_menu   = $request->id_report_menu;
            $report_title->name             = $request->name;
            $report_title->type             = $request->type;
            $report_title->value            = $request->value;
            $report_title->display_currency = $request->display_currency ?? 'off'; // default to 'off' if not provided
            $report_title->save();

            if ($request->type === 'formula') {
                foreach ($request->id_report_title_select as $key => $value) {
                    $report_formula                         = new ReportFormula();
                    $report_formula->id_report_title        = $report_title->id_report_title;
                    $report_formula->id_report_title_select = $request->id_report_title_select[$key];
                    $report_formula->operation              = $request->operation[$key];
                    $report_formula->save();
                }
            }

            ActivityLogHelper::log('finance:report_title_create', 1, [
                'finance:report_title' => $report_title->name,
                'finance:report_type'  => $report_title->type,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($report_title, 'Report Title Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_title_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/report/titles/{id}",
     *  summary="Get a specific report title",
     *  tags={"Finance - Report Title"},
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
        $data = ReportTitle::find($id);

        return ApiResponseClass::sendResponse(ReportTitleResource::make($data), 'Report Title Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/report/titles/{id}",
     *  summary="Update a specific report title",
     *  tags={"Finance - Report Title"},
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
     *                  property="id_report_menu",
     *                  type="integer",
     *                  description="Report menu id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Report title name"
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  description="(default, formula, input)"
     *              ),
     *              @OA\Property(
     *                  property="value",
     *                  type="integer",
     *                  description="Value"
     *              ),
     *              @OA\Property(
     *                  property="id_report_title_select",
     *                  type="array",
     *                  @OA\Items(
     *                      type="integer",
     *                      example={1, 2, 3, 4},
     *                  ),
     *                  description="Report title select id"
     *              ),
     *              @OA\Property(
     *                  property="operation",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string",
     *                      example={"+", "-", "*", "/"},
     *                  ),
     *                  description="Operation"
     *              ),
     *              @OA\Property(
     *                  property="display_currency",
     *                  type="string",
     *                  description="Display currency",
     *                  enum={"on", "off"},
     *              ),
     *              required={"id_report_menu", "name", "type", "id_report_title_select", "operation"},
     *              example={
     *                  "id_report_menu": 1,
     *                  "name": "Income",
     *                  "type": "default",
     *                  "id_report_title_select": {1, 2, 3, 4},
     *                  "operation": {"+", "-", "*", "/"},
     *                  "display_currency": "on"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(ReportTitleRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();
        try {
            $data = ReportTitle::find($id);

            $data->update([
                'id_report_menu'   => $request->id_report_menu,
                'name'             => $request->name,
                'type'             => $request->type,
                'value'            => $request->value,
                'display_currency' => $request->display_currency ?? 'off',
            ]);

            if ($request->type === 'formula') {
                ReportFormula::whereIdReportTitle($id)->delete();
                foreach ($request->id_report_title_select as $key => $value) {
                    $report_formula = new ReportFormula();
                    $report_formula->id_report_title        = $data->id_report_title;
                    $report_formula->id_report_title_select = $request->id_report_title_select[$key];
                    $report_formula->operation              = $request->operation[$key];
                    $report_formula->save();
                }
            }

            ActivityLogHelper::log('finance:report_title_update', 1, [
                'finance:report_title' => $data->name,
                'finance:report_type'  => $data->type
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Report Title Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_title_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    public function updateOrder(Request $request)
    {
        DB::connection('finance')->beginTransaction();
        try {
            // Validasi request
            $request->validate([
                'id' => 'required|integer',
                'orders' => 'required|array',
                'orders.*' => 'required|integer',
            ]);

            // Ambil data berdasarkan id dan orders
            $reportTitles = ReportTitle::where('id_report_menu', $request->id)
                ->whereIn('id_report_title', $request->orders)
                ->orderByRaw('FIELD(id_report_title, ' . implode(',', $request->orders) . ')') //ini untuk memastikan order berdasarkan whereIn
                ->get();

            // Pastikan data ditemukan
            if ($reportTitles->isEmpty()) {
                return ApiResponseClass::throw('No matching Report Titles found!', 404);
            }

            // loop data yang ditemukan lalu update urutan ordernya
            foreach ($reportTitles as $index => $reportTitle) {
                $reportTitle->update([
                    'order' => $index + 1,
                ]);
            }

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($reportTitles, 'Report Title Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_title_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/report/titles/{id}",
     *  summary="Delete a specific report title",
     *  tags={"Finance - Report Title"},
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
            $data = ReportTitle::find($id);

            $data->delete();

            ActivityLogHelper::log('finance:report_title_delete', 1, [
                'finance:report_title' => $data->name,
                'finance:report_type'  => $data->type
            ]);

            return ApiResponseClass::sendResponse($data, 'Report Title Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('finance:report_title_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/report/titles/details/{id}",
     *  summary="Get a specific report title details",
     *  tags={"Finance - Report Title"},
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
    public function details($id, Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $year       = Carbon::parse($end_date)->format('Y');

        $report_title = ReportTitle::whereIdReportMenu($id)->get();

        $data = [];
        $body = [];
        $formula = [];
        $coa_labarugi_berjalan = Coa::find(get_arrangement('equity_coa'))->coa ?? null;
        $cek_sudah_closing = ClosingEntry::where('year', $year)->first();

        foreach ($report_title as $key => $value) {
            if ($value->toReportBody) {
                foreach ($value->toReportBody as $key => $value2) {
                    $balance = 0;
                    // biar tidak lambat loading karena menghitung, cek saja request start_date & end_date
                    if ($start_date && $end_date) {
                        if ($value2->method === 'coa') {
                            // jika coanya laba/rugi tahun berjalan dan belum closing
                            if ($value2->toCoa->coa == $coa_labarugi_berjalan) {
                                $balance = _sum_pendapatan_beban($start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                            } else {
                                $balance = _sum_account_saldo($value2, $start_date, $end_date, ['opr', 'int', 'acm', 'tax']);
                            }
                        }

                        if ($value2->method === 'subcoa') {
                            $balance = _count_coa_body($value2->id_coa_body, $start_date, $end_date);
                        }

                        if ($value2->method === 'range') {
                            $balance = _sum_account_saldo($value2, $start_date, $start_date, ['opr', 'int', 'acm', 'tax']);
                        }

                        if ($value2->method === 'report') {
                            $balance = _count_report_menu_total($value2->id_report_menu, $start_date, $end_date);
                        }
                    }

                    if ($value2->method === 'report') {
                        $report_menu = ReportMenu::whereIdReportMenu($value2->id_report_menu)->first();

                        $body[$value->id_report_title][] = [
                            'id_report_body'  => $value2->id_report_body,
                            'id_report_title' => (int) $value2->id_report_title,
                            'id_report_menu'  => (int) $value2->id_report_menu,
                            'method'          => $value2->method,
                            'operation'       => $value2->operation,
                            'name'            => $report_menu->name,
                            'total'           => $balance
                        ];
                    } else if ($value2->method === 'subcoa') {
                        $coa_body = CoaBody::whereIdCoaBody($value2->id_coa_body)->first();

                        $body[$value->id_report_title][] = [
                            'id_report_body'  => $value2->id_report_body,
                            'id_report_title' => (int) $value2->id_report_title,
                            'id_coa'          => (int) $value2->id_coa,
                            'id_coa_body'     => (int) $value2->id_coa_body,
                            'method'          => $value2->method,
                            'operation'       => $value2->operation,
                            'name'            => ucwords(strtolower($coa_body->name)),
                            'coa'             => $coa_body->coa,
                            'total'           => $balance
                        ];
                    } else {
                        $body[$value->id_report_title][] = [
                            'id_report_body'  => $value2->id_report_body,
                            'id_report_title' => (int) $value2->id_report_title,
                            'id_coa'          => (int) $value2->id_coa,
                            'method'          => $value2->method,
                            'operation'       => $value2->operation,
                            'name'            => $value2->toCoa->name,
                            'coa'             => $value2->toCoa->coa,
                            'total'           => $balance
                        ];
                    }
                }
            }

            if ($value->toReportFormula) {
                foreach ($value->toReportFormula as $key => $value3) {
                    $formula[$value->id_report_title][] = [
                        'id_report_formula'      => $value3->id_report_formula,
                        'id_report_title'        => $value3->id_report_title,
                        'id_report_title_select' => $value3->id_report_title_select,
                        'operation'              => $value3->operation,
                    ];
                }
            }

            $report_body = $body[$value->id_report_title] ?? [];

            $report_formula = $formula[$value->id_report_title] ?? [];

            $total = 0;
            if ($value->type === 'formula') {
                $total = _count_report_title_formula($value->id_report_title, $start_date, $end_date);
            } else if ($value->type === 'input') {
                $total = $value->value;
            } else {
                // default
                $total = _count_report_title_total($value->id_report_title, $start_date, $end_date);
            }

            $data[] = [
                'id_report_title'  => $value->id_report_title,
                'id_report_menu'   => (int) $value->id_report_menu,
                'name'             => $value->name,
                'total'            => $total,
                'value'            => $total,
                'type'             => $value->type,
                'display_currency' => $value->display_currency,
                'report_body'      => $report_body,
                'report_formula'   => $report_formula
            ];
        }

        return ApiResponseClass::sendResponse($data, 'Report Title Retrieved Successfully');
    }
}
