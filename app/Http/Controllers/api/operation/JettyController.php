<?php

namespace App\Http\Controllers\api\operation;

use App\Models\operation\Jetty;
use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\JettyRequest;
use App\Http\Resources\operation\JettyResource;

class JettyController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/jetties",
     *  summary="Get the list of jetties",
     *  tags={"Operation - Jetty"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $jetty = Jetty::query();

        $data = $jetty->get();

        return ApiResponseClass::sendResponse(JettyResource::collection($data), 'Jetty Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/jetties",
     *  summary="Add a new drilling",
     *  tags={"Operation - Jetty"},
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
    public function store(JettyRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $file = add_file($request->file, 'jetty/');

            $jetty = new Jetty();
            $jetty->name = $request->name;
            $jetty->file = $file;
            $jetty->save();

            ActivityLogHelper::log('operation:jetty_create', 1, [
                'name' => $request->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($jetty, 'Jetty Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:jetty_create', 0, [['error' => $e->getMessage()]]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/jetties/{id}",
     *  summary="Get a specific jetty",
     *  tags={"Operation - Jetty"},
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
        $jetty = Jetty::find($id);

        return ApiResponseClass::sendResponse($jetty, 'Jetty Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/jetties/{id}",
     *  summary="Update a specific jetty",
     *  tags={"Operation - Jetty"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *          format="int64"
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
    public function update(JettyRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $jetty = Jetty::find($id);

            if ($request->hasFile('file')) {
                $file = upd_file($request->file, $jetty->file, 'jetty/');
            } else {
                $file = $jetty->file;
            }

            $jetty->update([
                'name' => $request->name,
                'file' => $file,
            ]);

            ActivityLogHelper::log('operation:jetty_update', 1, [
                'name' => $request->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($jetty, 'Jetty Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:jetty_update', 0, [['error' => $e->getMessage()]]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/jetties/{id}",
     *  summary="Delete a specific jetty",
     *  tags={"Operation - Jetty"},
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
            $data = Jetty::find($id);

            del_file($data->file, 'jetty/');

            $data->delete();

            ActivityLogHelper::log('operation:jetty_delete', 1, [
                'name' => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Jetty Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:jetty_delete', 0, [['error' => $e->getMessage()]]);
            return ApiResponseClass::rollback($e);
        }
    }
}
