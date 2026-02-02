<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\DomInPitRequest;
use App\Http\Resources\operation\DomInPitResource;
use App\Models\operation\DomInPit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DomInPitController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/dom_in_pits",
     *  summary="Get the list of dom in pits",
     *  tags={"Operation - Dom In Pit"},
     *  @OA\Parameter(
     *      name="id_pit",
     *      in="query",
     *      required=false,
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        $dom_in_pit = DomInPit::query();

        $dom_in_pit->with(['toPit']);

        if ($this->id_kontraktor != null) {
            $dom_in_pit->whereKontraktor($this->id_kontraktor);
        }

        if (isset($request->id_pit)) {
            $dom_in_pit->where('id_pit', $request->id_pit);
        }

        $data = $dom_in_pit->get();

        return ApiResponseClass::sendResponse(DomInPitResource::collection($data), 'Dom Efo Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/dom_in_pits",
     *  summary="Add a new dom in pits",
     *  tags={"Operation - Dom In Pit"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_pit",
     *                  type="integer",
     *                  description="Id pit"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "DOM_IN_PIT_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(DomInPitRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $dom_in_pit                = new DomInPit();
            $dom_in_pit->id_kontraktor = $this->id_kontraktor;
            $dom_in_pit->id_pit        = $request->id_pit;
            $dom_in_pit->name          = $request->name;
            $dom_in_pit->save();

            ActivityLogHelper::log('operation:dome_in_pit_create', 1, [
                'operation:pit_name'  => $dom_in_pit->toPit->name,
                'operation:dome_name' => $dom_in_pit->name,
            ]);
            
            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($dom_in_pit, 'Dom Efo Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_in_pit_create', 0, ['error'=> $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/dom_in_pits/{id}",
     *  summary="Get the detail of dom in pits",
     *  tags={"Operation - Dom In Pit"},
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
        $dom_in_pit = DomInPit::find($id);

        return ApiResponseClass::sendResponse(DomInPitResource::make($dom_in_pit), 'Dom Efo Detail Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/dom_in_pits/{id}",
     *  summary="Update a dom in pits",
     *  tags={"Operation - Dom In Pit"},
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
     *                  property="id_pit",
     *                  type="integer",
     *                  description="Id pit"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "DOM_IN_PIT_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(DomInPitRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $dom_in_pit = DomInPit::find($id);

            $dom_in_pit->update([
                'id_pit' => $request->id_pit,
                'name'   => $request->name,
            ]);

            ActivityLogHelper::log('operation:dome_in_pit_update', 1, [
                'operation:pit_name'  => $dom_in_pit->toPit->name,
                'operation:dome_name' => $dom_in_pit->name,
            ]);
            
            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($dom_in_pit, 'Dom Efo Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_in_pit_update', 0, ['error'=> $e->getMessage()]);
            
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/dom_in_pits/{id}",
     *  summary="Delete a dom in pits",
     *  tags={"Operation - Dom In Pit"},
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
            $data = DomInPit::find($id);

            $data->delete();

            ActivityLogHelper::log('operation:dome_in_pit_delete', 1, [
                'operation:pit_name'  => $data->toPit->name,
                'operation:dome_name' => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Dom Efo Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_in_pit_delete', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
