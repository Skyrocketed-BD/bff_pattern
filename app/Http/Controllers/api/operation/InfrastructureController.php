<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\InfrastructureRequest;
use App\Http\Resources\operation\InfrastructureResource;
use App\Models\operation\Infrastructure;
use Illuminate\Support\Facades\DB;

class InfrastructureController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/infrastructures",
     *  summary="Get the list of infrastructures",
     *  tags={"Operation - Infrastructure"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $infrastructure = Infrastructure::query();

        $infrastructure->with(['toKontraktor']);

        if ($this->id_kontraktor != null) {
            $infrastructure->whereKontraktor($this->id_kontraktor);

            $infrastructure->orWhereNull('id_kontraktor');
        }

        $data = $infrastructure->get();

        return ApiResponseClass::sendResponse(InfrastructureResource::collection($data), 'Infrastructure Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/infrastructures",
     *  summary="Add a new infrastructure",
     *  tags={"Operation - Infrastructure"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of infrastructure"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Category of infrastructure",
     *                  enum={"sedimen_pond", "nursery", "lab", "mess", "office", "workshop", "fuel_storage", "stockpile_eto_efo", "dome", "water_channel"}
     *              ),
     *              @OA\Property(
     *                  property="file",
     *                  type="file",
     *                  description="File of infrastructure"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(InfrastructureRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $file = add_file($request->file, 'infrastructure/');

            $infrastructure                = new Infrastructure();
            $infrastructure->id_kontraktor = $this->id_kontraktor;
            $infrastructure->name          = $request->name;
            $infrastructure->file          = $file;
            $infrastructure->category      = $request->category;
            $infrastructure->save();

            ActivityLogHelper::log('operation:infrastructure_create', 1, [
                'name'          => $request->name,
                'category'      => $request->category,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($infrastructure, 'Infrastructure Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:infrastructure_create', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/infrastructures/{id}",
     *  summary="Get a specific infrastructure",
     *  tags={"Operation - Infrastructure"},
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
        $data = Infrastructure::find($id);

        return ApiResponseClass::sendResponse(InfrastructureResource::make($data), 'Infrastructure Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/infrastructures/{id}",
     *  summary="Update a infrastructure",
     *  tags={"Operation - Infrastructure"},
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
     *                  description="Name of infrastructure"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="Category of infrastructure",
     *                  enum={"sedimen_pond", "nursery", "lab", "mess", "office", "workshop", "fuel_storage", "stockpile_eto_efo", "dome", "water_channel"}
     *              ),
     *              @OA\Property(
     *                  property="file",
     *                  type="file",
     *                  description="File of infrastructure"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(InfrastructureRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $infrastructure = Infrastructure::find($id);

            if ($request->hasFile('file')) {
                $file = upd_file($request->file, $infrastructure->file, 'infrastructure/');
            } else {
                $file = $infrastructure->file;
            }

            $data = $infrastructure->update([
                'name'     => $request->name,
                'file'     => $file,
                'category' => $request->category,
            ]);

            ActivityLogHelper::log('operation:infrastructure_update', 1, [
                'name'          => $request->name,
                'category'      => $request->category,
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse($data, 'Infrastructure Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:infrastructure_update', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     * path="/infrastructures/{id}",
     * summary="Delete a infrastructure",
     * tags={"Operation - Infrastructure"},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64"
     *     )
     * ),
     * @OA\Response(response=200, description="Return a list of resources"),
     * security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try {
            $data = Infrastructure::find($id);

            del_file($data->file, 'infrastructure/');

            $data->delete();

            ActivityLogHelper::log('operation:infrastructure_delete', 1, [
                'name'     => $data->name,
                'category' => $data->category
            ]);

            return ApiResponseClass::sendResponse($data, 'Infrastructure Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:infrastructure_delete', 0, ['error'=> $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *  path="/infrastructures/maps/{id_kontraktor}",
     *  summary="Get a specific infrastructure",
     *  tags={"Operation - Infrastructure"},
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
        $infrastructure = Infrastructure::where('id_kontraktor', $id_kontraktor)->get();

        $data = [];

        foreach ($infrastructure as $item) {
            $data[] = asset_upload('file/infrastructure/' . $item->file);
        }

        return ApiResponseClass::sendResponse($data, 'Infrastructure Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/infrastructures/filter/{category}",
     *  summary="Get a specific infrastructure",
     *  tags={"Operation - Infrastructure"},
     *  @OA\Parameter(
     *      name="category",
     *      in="path",
     *      required=true,
     *      @OA\Schema(
     *          type="string",
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function filter($category)
    {
        $arr_category = explode(',', $category);

        $infrastructure = Infrastructure::whereIn('category', $arr_category);

        if ($this->id_kontraktor != null) {
            $infrastructure->whereKontraktor($this->id_kontraktor);

            $infrastructure->orWhereNull('id_kontraktor');
        }

        $data = $infrastructure->get();

        return ApiResponseClass::sendResponse(InfrastructureResource::collection($data), 'Infrastructure Retrieved Successfully');
    }

    /**
     * @OA\Get(
     *  path="/infrastructures/self",
     *  summary="Get a specific infrastructure",
     *  tags={"Operation - Infrastructure"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function self()
    {
        $infrastructure = Infrastructure::query();

        $infrastructure->with(['toKontraktor']);

        if ($this->id_kontraktor != null) {
            $infrastructure->whereKontraktor($this->id_kontraktor);
        } else {
            $infrastructure->orWhereNull('id_kontraktor');
        }

        $data = $infrastructure->get();

        return ApiResponseClass::sendResponse(InfrastructureResource::collection($data), 'Infrastructure Retrieved Successfully');
    }
}
