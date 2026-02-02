<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\PitRequest;
use App\Http\Resources\operation\PitResource;
use App\Models\operation\DomInPit;
use App\Models\operation\Pit;
use Illuminate\Support\Facades\DB;

class PitController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/pits",
     *  summary="Get the list of pits",
     *  tags={"Operation - Pit"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $pit = Pit::query();

        $pit->with(['toBlock']);

        if ($this->id_kontraktor != null) {
            $pit->whereKontraktor($this->id_kontraktor);
        }

        $data = $pit->get();

        return ApiResponseClass::sendResponse(PitResource::collection($data), 'Pit Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/pits",
     *  summary="Add a new pits",
     *  tags={"Operation - Pit"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_block",
     *                  type="integer",
     *                  description="Id block of pit"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of pit"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "PIT_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }} 
     * )
     */
    public function store(PitRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $pit = new Pit();
            $pit->id_kontraktor = $this->id_kontraktor;
            $pit->id_block      = $request->id_block;
            $pit->name          = $request->name;
            $pit->save();

            ActivityLogHelper::log('operation:pit_create', 1, [
                'operation:block_name' => $pit->toBlock->name,
                'operation:pit_name'   => $pit->name
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($pit, 'Pit Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:pit_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/pits/{id}",
     *  summary="Get the detail of  pits",
     *  tags={"Operation - Pit"},
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
        $data = Pit::find($id);

        $result = $data ? PitResource::make($data) : null;

        return ApiResponseClass::sendResponse($result, 'Pit Detail Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/pits/{id}",
     *  summary="Update a pits",
     *  tags={"Operation - Pit"},
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
     *                  property="id_block",
     *                  type="integer",
     *                  description="Id block of pit"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of pit"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "PIT_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(PitRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $data = Pit::find($id);

            $data->update([
                'id_block' => $request->id_block,
                'name'     => $request->name,
            ]);

            ActivityLogHelper::log('operation:pit_update', 1, [
                'operation:block_name' => $data->toBlock->name,
                'operation:pit_name'   => $data->name
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Dom Efo Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:pit_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/pits/{id}",
     *  summary="Delete a pits",
     *  tags={"Operation - Pit"},
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
            $data = Pit::find($id);

            $data->delete();

            ActivityLogHelper::log('operation:pit_delete', 1, [
                'operation:block_name' => $data->toBlock->name,
                'operation:pit_name'   => $data->name
            ]);

            return ApiResponseClass::sendResponse($data, 'Dom Efo Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:pit_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
