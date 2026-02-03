<?php

namespace App\Http\Controllers\Api\Finance;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\finance\CoaGroupRequest;
use App\Models\finance\CoaGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class CoaGroupController extends Controller
{
    public function index()
    {
        $data = CoaGroup::orderBy('id_coa_group', 'asc')->get();

        return ApiResponseClass::sendResponse($data, 'Coa Group Retrieved Successfully');
    }

    public function store(CoaGroupRequest $request)
    {
        DB::connection('finance')->beginTransaction();

        try {
            $count = CoaGroup::count() + 1;
            $coa   = str_pad($count, get_arrangement('coa_digit'), '0', STR_PAD_RIGHT);

            $coa_group       = new CoaGroup();
            $coa_group->name = $request->name;
            $coa_group->coa  = $coa;
            $coa_group->save();

            if ($request->isSkip) {
                $coa_head               = new CoaHead();
                $coa_head->id_coa_group = $coa_group->id_coa_group;
                $coa_head->name         = $request->name;
                $coa_head->coa          = substr_replace($coa, '1', 1, 1);
                $coa_head->save();
            }

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($coa_group, 'Coa Group Created Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function show($id)
    {
        $data = CoaGroup::find($id);

        return ApiResponseClass::sendResponse($data, 'Coa Group Retrieved Successfully');
    }

    public function update(CoaGroupRequest $request, $id)
    {
        DB::connection('finance')->beginTransaction();

        try {
            $data = CoaGroup::find($id);

            if (!$data) {
                return Response::json([
                    'success' => false,
                    'message' => 'Data Not Found',
                ], 404);
            }

            $data->update([
                'name' => $request->name,
            ]);

            DB::connection('finance')->commit();

            return ApiResponseClass::sendResponse($data, 'Coa Group Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = CoaGroup::find($id);

            if (!$data) {
                return Response::json([
                    'success' => false,
                    'message' => 'Data Not Found',
                ], 404);
            }

            $data->delete();

            return ApiResponseClass::sendResponse($data, 'Coa Group Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
