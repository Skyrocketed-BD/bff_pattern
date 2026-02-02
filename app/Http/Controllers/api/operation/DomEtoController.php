<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\DomEtoRequest;
use App\Http\Resources\operation\DomEtoResource;
use App\Models\operation\DomEto;
use Illuminate\Support\Facades\DB;

class DomEtoController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/dom_etos",
     *  summary="Get the list of dom etos",
     *  tags={"Operation - Dom Eto"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $dom_eto = DomEto::query();

        if ($this->id_kontraktor != null) {
            $dom_eto->whereKontraktor($this->id_kontraktor);
        }

        $data = $dom_eto->get();

        return ApiResponseClass::sendResponse(DomEtoResource::collection($data), 'Dom Eto Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/dom_etos",
     *  summary="Add a new dom eto",
     *  tags={"Operation - Dom Eto"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of dom eto"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "DOM_ETO_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(DomEtoRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $dom_eto                = new DomEto();
            $dom_eto->id_kontraktor = $this->id_kontraktor;
            $dom_eto->name          = $request->name;
            $dom_eto->save();

            ActivityLogHelper::log('operation:dome_eto__create', 1, [
                'name'          => $request->name,
            ]);
            
            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($dom_eto, 'Dom Eto Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_eto__create', 0, ['error'=> $e->getMessage()]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/dom_etos/{id}",
     *  summary="Get the detail of dom eto",
     *  tags={"Operation - Dom Eto"},
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
        $dom_eto = DomEto::find($id);

        return ApiResponseClass::sendResponse(DomEtoResource::make($dom_eto), 'Dom Eto Detail Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/dom_etos/{id}",
     *  summary="Update a dom eto",
     *  tags={"Operation - Dom Eto"},
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
     *                  description="Name of dom eto"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "DOM_ETO_1",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(DomEtoRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $dom_eto = DomEto::find($id);

            $dom_eto->update([
                'name' => $request->name,
            ]);

            ActivityLogHelper::log('operation:dome_eto__update', 1, [
                'name'           => $request->name,
            ]);
            
            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($dom_eto, 'Dom Eto Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_eto__update', 0, ['error'=> $e->getMessage()]);
            
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/dom_etos/{id}",
     *  summary="Delete a dom eto",
     *  tags={"Operation - Dom Eto"},
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
            $data = DomEto::find($id);

            $data->delete();

            ActivityLogHelper::log('operation:dome_eto__delete', 1, [
                'name' => $data->name,
            ]);

            return ApiResponseClass::sendResponse($data, 'Dom Eto Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:dome_eto__delete', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }
}
