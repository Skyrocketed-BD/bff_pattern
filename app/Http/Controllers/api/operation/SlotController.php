<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\SlotRequest;
use App\Http\Resources\operation\SlotResource;
use App\Models\operation\Slot;
use Exception;
use Illuminate\Support\Facades\DB;

class SlotController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/slots",
     *  summary="Get the list of slots",
     *  tags={"Operation - Slot"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $slot = Slot::query();

        $slot->with(['toJetty']);

        $data = $slot->get();

        return ApiResponseClass::sendResponse(SlotResource::collection($data), 'Slot Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/slots",
     *  summary="Create a new slot",
     *  tags={"Operation - Slot"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_jetty",
     *                  type="integer",
     *                  description="Id jetty of slot"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of slot"
     *              ),
     *              required={"id_jetty", "name"},
     *              example={
     *                  "id_jetty": 1,
     *                  "name": "Slot 1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(SlotRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $slot           = new Slot();
            $slot->id_jetty = $request->id_jetty;
            $slot->name     = $request->name;
            $slot->save();

            ActivityLogHelper::log('operation:slot_create', 1, [
                'operation:jetty' => $slot->toJetty->name,
                'operation:slot'  => $slot->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse(SlotResource::make($slot), 'Slot Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:slot_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/slots/{id}",
     *  summary="Get the detail of  slots",
     *  tags={"Operation - Slot"},
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
        $data = Slot::find($id);

        $result = $data ? SlotResource::make($data) : null;

        return ApiResponseClass::sendResponse($result, 'Slot Detail Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/slots/{id}",
     *  summary="Update a slot",
     *  tags={"Operation - Slot"},
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
     *                  property="id_jetty",
     *                  type="integer",
     *                  description="Id jetty of slot"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of slot"
     *              ),
     *              required={"id_jetty", "name"},
     *              example={
     *                  "id_jetty": 1,
     *                  "name": "Slot 1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(SlotRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $data = Slot::find($id);

            $data->update([
                'id_jetty' => $request->id_jetty,
                'name'     => $request->name,
            ]);

            ActivityLogHelper::log('operation:slot_update', 1, [
                'operation:jetty' => $data->toJetty->name,
                'operation:slot'  => $data->name,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse(SlotResource::make($data), 'Slot Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:slot_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/slots/{id}",
     *  summary="Delete a slot",
     *  tags={"Operation - Slot"},
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
            $data = Slot::find($id);

            $data->delete();

            ActivityLogHelper::log('operation:slot_delete', 1, [
                'operation:jetty' => $data->toJetty->name,
                'operation:slot'  => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Slot Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:slot_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
