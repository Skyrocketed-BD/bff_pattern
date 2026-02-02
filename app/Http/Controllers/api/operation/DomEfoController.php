<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\DomEfoRequest;
use App\Http\Resources\operation\DomEfoResource;
use App\Models\operation\DomEfo;
use Illuminate\Support\Facades\DB;

class DomEfoController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/dom_efos",
     *  summary="Get the list of dom efos",
     *  tags={"Operation - Dom Efo"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $dom_efo = DomEfo::query();

        if ($this->id_kontraktor != null) {
            $dom_efo->whereKontraktor($this->id_kontraktor);
        }

        $data = $dom_efo->get();

        return ApiResponseClass::sendResponse(DomEfoResource::collection($data), 'Dom Efo Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/dom_efos",
     *  summary="Add a new dom efos",
     *  tags={"Operation - Dom Efo"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of dom efo"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "DOM_EFO_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(DomEfoRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $dom_efo                = new DomEfo();
            $dom_efo->id_kontraktor = $this->id_kontraktor;
            $dom_efo->name          = $request->name;
            $dom_efo->save();

            ActivityLogHelper::log('operation:dome_efo_create', 1, [
                'name'          => $request->name,
            ]);
            
            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($dom_efo, 'Dom Efo Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_efo_create', 0, ['error'=> $e->getMessage()]);
            
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/dom_efos/{id}",
     *  summary="Get the detail of dom efos",
     *  tags={"Operation - Dom Efo"},
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
        $dom_efo = DomEfo::find($id);

        return ApiResponseClass::sendResponse(DomEfoResource::make($dom_efo), 'Dom Efo Detail Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/dom_efos/{id}",
     *  summary="Update a dom efos",
     *  tags={"Operation - Dom Efo"},
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
     *                  property="name",
     *                  type="string",
     *                  description="Name of dom efo"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "DOM_EFO_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(DomEfoRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $dom_efo = DomEfo::find($id);

            $dom_efo->update([
                'name' => $request->name,
            ]);

            DB::connection('operation')->commit();

            ActivityLogHelper::log('operation:dome_efo_update', 1, [
                'name'           => $request->name,
            ]);

            return ApiResponseClass::sendResponse($dom_efo, 'Dom Efo Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_efo_update', 0, ['error'=> $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/dom_efos/{id}",
     *  summary="Delete a dom efos",
     *  tags={"Operation - Dom Efo"},
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
            $data = DomEfo::find($id);

            $data->delete();

            ActivityLogHelper::log('operation:dome_efo_delete', 1, [
                'name' => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Dom Efo Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_efo_delete', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
