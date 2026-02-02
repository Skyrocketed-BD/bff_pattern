<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Models\operation\IupArea;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\OperationController;
use App\Http\Resources\operation\IupResource;
use App\Http\Requests\operation\IupAreaRequest;

class IupAreaController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/iup_areas",
     *  summary="Get the list of iup areas",
     *  tags={"Operation - Iup Area"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $iup_area = IupArea::query();

        $data = $iup_area->get();

        return ApiResponseClass::sendResponse(IupResource::collection($data), 'Iup Area Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/iup_areas",
     *  summary="Add a new iup area",
     *  tags={"Operation - Iup Area"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of pit test"
     *              ),
     *              @OA\Property(
     *                  property="file",
     *                  type="file",
     *                  description="File of pit test"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(IupAreaRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $file = add_file($request->file, 'area_iup/');

            $iup       = new IupArea();
            $iup->name = $request->name;
            $iup->file = $file;
            $iup->save();

            ActivityLogHelper::log('operation:iup_area_create', 1, [
                'name' => $request->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($iup, 'Drilling Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:iup_area_create', 0, [['error' => $e->getMessage()]]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/iup_areas/{id}",
     *  summary="Get a specific iup area",
     *  tags={"Operation - Iup Area"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = IupArea::find($id);

        return ApiResponseClass::sendResponse($data, 'Iup Area Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/iup_areas/{id}",
     *  summary="Update a iup area",
     *  tags={"Operation - Iup Area"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="_method",
     *      in="query",
     *      description="HTTP Method",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *          default="PUT"
     *      )
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of pit test"
     *              ),
     *              @OA\Property(
     *                  property="file",
     *                  type="file",
     *                  description="File of pit test"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(IupAreaRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $data = IupArea::find($id);

            if ($request->hasFile('file')) {
                $file = upd_file($request->file, $data->file, 'area_iup/');
            } else {
                $file = $data->file;
            }

            $data->update([
                'name' => $request->name,
                'file' => $file,
            ]);

            ActivityLogHelper::log('operation:iup_area_update', 1, [
                'name' => $request->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Iup Area Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:iup_area_update', 0, [['error' => $e->getMessage()]]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/iup_areas/{id}",
     *  summary="Delete a iup area",
     *  tags={"Operation - Iup Area"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = IupArea::find($id);

            del_file($data->file, 'area_iup/');

            $data->delete();

            ActivityLogHelper::log('operation:iup_area_delete', 1, [
                'name' => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Iup Area Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:iup_area_delete', 0, [['error' => $e->getMessage()]]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/iup_areas/maps",
     *  summary="Get a specific iup area",
     *  tags={"Operation - Iup Area"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function maps()
    {
        $query = IupArea::query();

        $iup_area = $query->get();

        $data = [];

        foreach ($iup_area as $item) {
            $data[] = asset_upload('file/area_iup/' . $item->file);
        }

        return ApiResponseClass::sendResponse($data, 'Iup Area Maps Successfully');
    }
}
