<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\main\MenuBodyResource;
use App\Models\main\MenuBody;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuBodyController extends Controller
{
    /**
     * @OA\Get(
     *  path="/menu/bodies/{id_menu_category}",
     *  summary="Get a list of menu body",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
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
    public function index($id_menu_category)
    {
        $data = MenuBody::with(['toMenuChild'])->whereIdMenuCategory($id_menu_category)->orderBy('position')->get();

        return ApiResponseClass::sendResponse(MenuBodyResource::collection($data), 'Menu Body Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/menu/bodies",
     *  summary="Add a new menu body",
     *  tags={"Main - Menu Management"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_menu_category",
     *                  type="integer",
     *                  description="Menu Category id"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Menu Body name"
     *              ),
     *              @OA\Property(
     *                  property="icon",
     *                  type="string",
     *                  description="Menu Body icon"
     *              ),
     *              @OA\Property(
     *                  property="url",
     *                  type="string",
     *                  description="Menu Body url"
     *              ),
     *              @OA\Property(
     *                  property="position",
     *                  type="integer",
     *                  description="Menu Body position"
     *              ),
     *              required={"id_menu_category", "name", "icon", "url", "position"},
     *              example={
     *                  "id_menu_category": 1,
     *                  "name": "Cash",
     *                  "icon": "fas fa-coins",
     *                  "url": "/cash",
     *                  "position": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $body = new MenuBody();
            $body->id_menu_category = $request->id_menu_category;
            $body->name             = $request->name;
            $body->icon             = $request->icon;
            $body->url              = $request->url;
            $body->position         = $request->position;
            $body->save();

            ActivityLogHelper::log('admin:menu_item_create', 1, [
                'name'     => $request->name,
            ]);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($body, 'Role Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:menu_item_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/menu/bodies/{id_menu_category}/{id_menu_body}",
     *  summary="Get a menu body",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
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
    public function show($id_menu_category, $id_menu_body)
    {
        $data = MenuBody::whereIdMenuCategory($id_menu_category)->whereIdMenuBody($id_menu_body)->get();

        return ApiResponseClass::sendResponse(MenuBodyResource::collection($data), 'Menu Body Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/menu/bodies/{id_menu_category}/{id_menu_body}",
     *  summary="Update a menu body",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
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
     *                  description="Menu Body name"
     *              ),
     *              @OA\Property(
     *                  property="icon",
     *                  type="string",
     *                  description="Menu Body icon"
     *              ),
     *              @OA\Property(
     *                  property="url",
     *                  type="string",
     *                  description="Menu Body url"
     *              ),
     *              @OA\Property(
     *                  property="position",
     *                  type="integer",
     *                  description="Menu Body position"
     *              ),
     *              required={"name", "icon", "url", "position"},
     *              example={
     *                  "name": "Cash",
     *                  "icon": "fas fa-coins",
     *                  "url": "cash",
     *                  "position": 1
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(Request $request, $id_menu_category, $id_menu_body)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $data = MenuBody::find($id_menu_body);

            $data->update([
                'name'     => $request->name,
                'icon'     => $request->icon,
                'url'      => $request->url,
                'position' => $request->position
            ]);

            ActivityLogHelper::log('admin:menu_item_update', 1, [
                'name'     => $request->name,
            ]);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($data, 'Menu Body Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:menu_item_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/menu/bodies/{id_menu_category}/{id_menu_body}",
     *  summary="Delete a menu body",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
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
    public function destroy($id_menu_category, $id_menu_body)
    {
        try {
            $data = MenuBody::find($id_menu_body);

            $data->delete();

            ActivityLogHelper::log('admin:menu_item_delete', 1, [
                'name' => $data->name
            ]);

            return ApiResponseClass::sendResponse($data, 'Menu Body Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:menu_item_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *  path="/menu/bodies/active/{id_menu_category}/{id_menu_body}",
     *  summary="Active a menu body",
     *  tags={"Main - Menu Management"},
     *  @OA\Parameter(
     *      name="id_menu_category",
     *      description="Menu Category id",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_body",
     *      description="Menu Body id",
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
    public function active($id_menu_category, $id_menu_body)
    {
        $data = MenuBody::find($id_menu_body);

        $data->is_enabled = $data->is_enabled == '0' ? '1' : '0';

        $data->save();

        return ApiResponseClass::sendResponse($data, 'Menu Body Updated Successfully');
    }
}
