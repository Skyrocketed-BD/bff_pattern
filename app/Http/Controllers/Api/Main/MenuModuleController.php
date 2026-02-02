<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\main\MenuModuleRequest;
use App\Http\Resources\main\MenuModuleResource;
use App\Models\main\MenuModule;
use Illuminate\Support\Facades\DB;

class MenuModuleController extends Controller
{
    /**
     * @OA\Get(
     *  path="/menu-modules",
     *  summary="Get the list of menu modules",
     *  tags={"Main - Menu Module"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = MenuModule::orderBy('id_menu_module', 'asc')->get();

        return ApiResponseClass::sendResponse(MenuModuleResource::collection($data), 'Menu Module Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/menu-modules",
     *  summary="Add a new menu module",
     *  tags={"Main - Menu Module"},
     *
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Menu Module name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "Cash",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(MenuModuleRequest $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $module       = New MenuModule();
            $module->name = $request->name;
            $module->save();

            DB::connection('mysql')->commit();
            ActivityLogHelper::log('admin:module_menu_create', 1, ['name' => $request->name]);

            return ApiResponseClass::sendResponse($module, 'Menu Module Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:module_menu_create', 0, ['error' => $e]);

            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/menu-modules/{id}",
     *  summary="Get a single menu module",
     *  tags={"Main - Menu Module"},
     *  @OA\Parameter(
     *      name="id",
     *      description="Menu Module id",
     *      required=true,
     *      in="path",
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
        $data = MenuModule::find($id);

        return ApiResponseClass::sendResponse(MenuModuleResource::make($data), 'Menu Module Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/menu-modules/{id}",
     *  summary="Update a menu module",
     *  tags={"Main - Menu Module"},
     *  @OA\Parameter(
     *      name="id",
     *      description="Menu Module id",
     *      required=true,
     *      in="path",
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
     *                  description="Menu Module name"
     *              ),
     *              required={"name"},
     *              example={
     *                  "name": "Cash",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(MenuModuleRequest $request, $id)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $data = MenuModule::find($id);

            $data->update([
                'name' => $request->name
            ]);

            DB::connection('mysql')->commit();
            ActivityLogHelper::log('admin:module_menu_update', 1, ['name' => $request->name]);

            return ApiResponseClass::sendResponse($data, 'Menu Module Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:module_menu_update', 0, ['error' => $e]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/menu-modules/{id}",
     *  summary="Delete a menu module",
     *  tags={"Main - Menu Module"},
     *  @OA\Parameter(
     *      name="id",
     *      description="Menu Module id",
     *      required=true,
     *      in="path",
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
            $data = MenuModule::find($id);
            $data->delete();
            ActivityLogHelper::log('admin:module_menu_delete', 1, ['name' => $data->name]);

            return ApiResponseClass::sendResponse($data, 'Menu Module Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:module_menu_delete', 0, ['error' => $e]);

            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }
}
