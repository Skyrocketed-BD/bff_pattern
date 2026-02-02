<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\CogRequest;
use App\Http\Resources\operation\CogResource;
use App\Models\operation\Cog;
use App\Models\operation\Kontraktor;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogHelper;

class CogController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/cogs",
     *  summary="Get the list of cogs",
     *  tags={"Operation - COG"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $cogs = Cog::query();

        if ($this->id_kontraktor != null) {
            $cogs->whereKontraktor($this->id_kontraktor);
        }

        $data = $cogs->get();

        return ApiResponseClass::sendResponse(CogResource::collection($data), 'COG Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/cogs",
     *  summary="Add a new cog",
     *  tags={"Operation - COG"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  description="Type of cog"
     *              ),
     *              @OA\Property(
     *                  property="min",
     *                  type="integer",
     *                  description="Minimum value of cog"
     *              ),
     *              @OA\Property(
     *                  property="max",
     *                  type="integer",
     *                  description="Maximum value of cog"
     *              ),
     *              required={"type", "min", "max"},
     *              example={
     *                  "type": "Low",
     *                  "min": 1.40,
     *                  "max": 1.60
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(CogRequest $request)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $kontraktor = Kontraktor::whereIdUsers($this->user->id_users)->first();

            $cog                = new Cog();
            $cog->id_kontraktor = $kontraktor->id_kontraktor;
            $cog->type          = $request->type;
            $cog->min           = $request->min;
            $cog->max           = $request->max;
            $cog->save();

            ActivityLogHelper::log('operation:cog_create', 1, [
                'type'          => $request->type,
                'min'           => $request->min,
                'max'           => $request->max,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($cog, 'Cog Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:cog_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/cogs/{id}",
     *  summary="Get a single cog",
     *  tags={"Operation - COG"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function show($id)
    {
        $data = Cog::find($id);

        return ApiResponseClass::sendResponse($data, 'Cog Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/cogs/{id}",
     *  summary="Update a cog",
     *  tags={"Operation - COG"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  description="Type of cog"
     *              ),
     *              @OA\Property(
     *                  property="min",
     *                  type="integer",
     *                  description="Minimum value of cog"
     *              ),
     *              @OA\Property(
     *                  property="max",
     *                  type="integer",
     *                  description="Maximum value of cog"
     *              ),
     *              required={"type", "min", "max"},
     *              example={
     *                  "type": "Low",
     *                  "min": 1.40,
     *                  "max": 1.60
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(CogRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();
        try {
            $data = Cog::find($id);

            $data->update([
                'type' => $request->type,
                'min'  => $request->min,
                'max'  => $request->max
            ]);

            if ($data->wasChanged('min')) {
                $id_cog_previous = Cog::where('id_cog', '<', $data->id_cog)->max('id_cog');

                if ($id_cog_previous != null) {
                    $previous = Cog::find($id_cog_previous);

                    $previous->update([
                        'max' => ($request->min - 0.01)
                    ]);
                }
            }

            if ($data->wasChanged('max')) {
                $id_cog_next = Cog::where('id_cog', '>', $data->id_cog)->min('id_cog');

                if ($id_cog_next != null) {
                    $next = Cog::find($id_cog_next);

                    $next->update([
                        'min' => ($request->max + 0.01)
                    ]);
                }
            }

            DB::connection('operation')->commit();

            ActivityLogHelper::log('operation:cog_update', 1, [
                'type' => $request->type,
                'min'  => $request->min,
                'max'  => $request->max
            ]);

            return ApiResponseClass::sendResponse($data, 'Cog Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:cog_update', 0, ['error'=> $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/cogs/{id}",
     *  summary="Delete a cog",
     *  tags={"Operation - COG"},
     *  @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OA\Schema(type="integer")
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = Cog::find($id);

            $data->delete();

            ActivityLogHelper::log('operation:cog_delete', 1, [
                'type' => $data->type,
                'min'  => $data->min,
                'max'  => $data->max
            ]);

            return ApiResponseClass::sendResponse($data, 'Cog Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:cog_delete', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
