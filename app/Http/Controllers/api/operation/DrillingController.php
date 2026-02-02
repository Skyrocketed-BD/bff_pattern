<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\DrillingRequest;
use App\Models\operation\Drilling;
use Illuminate\Support\Facades\DB;

class DrillingController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/drillings",
     *  summary="Get the list of drillings",
     *  tags={"Operation - Drilling"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $drilling = Drilling::query();

        if ($this->id_kontraktor != null) {
            $drilling->whereKontraktor($this->id_kontraktor);
        }

        $data = $drilling->get();

        return ApiResponseClass::sendResponse($data, 'Drilling Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/drillings",
     *  summary="Add a new drilling",
     *  tags={"Operation - Drilling"},
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
     *              required={"name", "file"},
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(DrillingRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $file = add_file($request->file, 'drilling/');

            $drilling                = new Drilling();
            $drilling->id_kontraktor = $this->id_kontraktor;
            $drilling->name          = $request->name;
            $drilling->file          = $file;
            $drilling->save();

            DB::connection('operation')->commit();

            ActivityLogHelper::log('operation:drilling_create', 1, [
                'name'          => $request->name,
            ]);

            return ApiResponseClass::sendResponse($drilling, 'Drilling Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:drilling_create', 0, ['error'=> $e->getMessage()]);
            
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/drillings/{id}",
     *  summary="Get a specific drilling",
     *  tags={"Operation - Drilling"},
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
        $drilling = Drilling::find($id);

        return ApiResponseClass::sendResponse($drilling, 'Drilling Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/drillings/{id}",
     *  summary="Update a drilling",
     *  tags={"Operation - Drilling"},
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
     *      ),
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
     *              required={"name", "file"},
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(DrillingRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $drilling = Drilling::find($id);

            $file = upd_file($request->file, $drilling->file, 'drilling/');

            $drilling->update([
                'name' => $request->name,
                'file' => $file,
            ]);

            ActivityLogHelper::log('operation:drilling_update', 1, [
                'name'          => $request->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($drilling, 'Drilling Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:drilling_update', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/drillings/{id}",
     *  summary="Delete a drilling",
     *  tags={"Operation - Drilling"},
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
            $data = Drilling::find($id);

            del_file($data->file, 'drilling/');

            $data->delete();

            ActivityLogHelper::log('operation:drilling_delete', 1, [
                'name' => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Drilling Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:drilling_delete', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/drillings/maps/{id_kontraktor}",
     *  summary="Get a specific drilling",
     *  tags={"Operation - Drilling"},
     *  @OA\Parameter(
     *      name="id_kontraktor",
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
    public function maps($id_kontraktor)
    {
        $drilling = Drilling::where('id_kontraktor', $id_kontraktor)->get();

        $data = [];

        foreach ($drilling as $item) {
            $data[] = asset_upload('file/drilling/' . $item->file);
        }

        return ApiResponseClass::sendResponse($data, 'Drilling Retrieved Successfully');
    }
}
