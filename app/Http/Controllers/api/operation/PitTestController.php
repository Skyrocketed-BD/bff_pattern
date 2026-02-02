<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\PitTestRequest;
use App\Models\operation\PitTest;
use Illuminate\Support\Facades\DB;

class PitTestController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/pit_tests",
     *  summary="Get the list of pit tests",
     *  tags={"Operation - Pit Test"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $pit_test = PitTest::query();

        if ($this->id_kontraktor != null) {
            $pit_test->whereKontraktor($this->id_kontraktor);
        }

        $data = $pit_test->get();

        return ApiResponseClass::sendResponse($data, 'Pit Test Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/pit_tests",
     *  summary="Add a new pit test",
     *  tags={"Operation - Pit Test"},
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
    public function store(PitTestRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $file = add_file($request->file, 'pit_test/');

            $pit_test = new PitTest();
            $pit_test->id_kontraktor = $this->id_kontraktor;
            $pit_test->name          = $request->name;
            $pit_test->file          = $file;
            $pit_test->save();

            ActivityLogHelper::log('operation:pit_test_create', 1, [
                'name' => $request->name
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($pit_test, 'Pit Test Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:pit_test_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/pit_tests/{id}",
     *  summary="Get a specific pit test",
     *  tags={"Operation - Pit Test"},
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
        $pit_test = PitTest::find($id);

        return ApiResponseClass::sendResponse($pit_test, 'Pit Test Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/pit_tests/{id}",
     *  summary="Update a pit test",
     *  tags={"Operation - Pit Test"},
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
    public function update(PitTestRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $pit_test = PitTest::find($id);

            $file = upd_file($request->file, $pit_test->file, 'pit_test/');

            $pit_test->update([
                'name' => $request->name,
                'file' => $file,
            ]);

            ActivityLogHelper::log('operation:pit_test_update', 1, [
                'name' => $request->name
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($pit_test, 'Pit Test Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:pit_test_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/pit_tests/{id}",
     *  summary="Delete a pit test",
     *  tags={"Operation - Pit Test"},
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
            $data = PitTest::find($id);

            del_file($data->file, 'pit_test/');

            $data->delete();

            ActivityLogHelper::log('operation:pit_test_delete', 1, [
                'name' => $data->name
            ]);

            return ApiResponseClass::sendResponse($data, 'Pit Test Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:pit_test_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/pit_tests/maps/{id_kontraktor}",
     *  summary="Get a specific pit test",
     *  tags={"Operation - Pit Test"},
     *  @OA\Parameter(
     *      name="id_kontraktor",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="integer",
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function maps($id_kontraktor)
    {
        $pit_test = PitTest::where('id_kontraktor', $id_kontraktor)->get();

        $data = [];

        foreach ($pit_test as $row) {
            $data[] = asset_upload('file/pit_test/' . $row->file);
        }

        return ApiResponseClass::sendResponse($data, 'Pit Test Retrieved Successfully');
    }
}
