<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\main\RoleAccessResource;
use App\Models\main\MenuModule;
use App\Models\main\Role;
use App\Models\main\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleAccessController extends Controller
{
    /**
     * @OA\Get(
     *  path="/role-accesses/{id_role}",
     *  summary="Get the list of role access",
     *  tags={"Main - Role Access"},
     *  @OA\Parameter(
     *      name="id_role",
     *      description="Role ID",
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
    public function index($id_role)
    {
        $data = RoleAccess::with(['toMenuBody'])->whereIdRole($id_role)->get();

        return ApiResponseClass::sendResponse(RoleAccessResource::collection($data), 'Role Access Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/role-accesses",
     *  summary="Add a new role access",
     *  tags={"Main - Role Access"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_role",
     *                  type="integer",
     *                  description="Role ID"
     *              ),
     *              @OA\Property(
     *                  property="id_menu_module",
     *                  type="integer",
     *                  description="Menu Module ID"
     *              ),
     *              @OA\Property(
     *                  property="id_menu_body",
     *                  type="array",
     *                  @OA\Items(type="integer", example=1),
     *                  description="Menu Body ID"
     *              ),
     *              example={
     *                  "id_role": 1,
     *                  "id_menu_module": 1,
     *                  "id_menu_body": {1, 2, 3},
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
            if (RoleAccess::whereIdRole($request->id_role)->whereIdMenuModule($request->id_menu_module)->count() > 0) {
                RoleAccess::whereIdRole($request->id_role)->whereIdMenuModule($request->id_menu_module)->delete();
            }

            $data = [];

            foreach ($request->id_menu_body as $key => $value) {
                $data[] = [
                    'id_menu_module' => $request->id_menu_module,
                    'id_menu_body'   => $value,
                    'id_role'        => $request->id_role,
                ];
            }

            RoleAccess::insert($data);
            DB::connection('mysql')->commit();

            ActivityLogHelper::log('admin:role_access_update', 1, ['id_menu_module' => $request->id_menu_module]);

            return ApiResponseClass::sendResponse($data, 'Role Access Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:role_access_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/role-accesses/{id_role_access}",
     *  summary="Delete a role access",
     *  tags={"Main - Role Access"},
     *  @OA\Parameter(
     *      name="id_role_access",
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
    public function destroy($id_role_access)
    {
        try {
            $data = RoleAccess::find($id_role_access);

            $data->delete();

            ActivityLogHelper::log('admin:role_access_delete', 1, [
                'id_role_access' => $id_role_access,
            ]);

            return ApiResponseClass::sendResponse($data, 'Role Access Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:role_access_delete', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::throw('Cannot delete data or it is being used', 409, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *  path="/role-accesses/action/{id_role_access}",
     *  summary="Update a role access",
     *  tags={"Main - Role Access"},
     *  @OA\Parameter(
     *      name="id_role_access",
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
    public function action($id_role_access)
    {
        $data = RoleAccess::find($id_role_access);

        $data->action = $data->action == 'view' ? 'crud' : 'view';

        $data->save();

        ActivityLogHelper::log('admin:role_access_type_update', 1, [
            'id_role_access'    => $id_role_access,
            'admin:access_type' => $data->action
        ]);

        return ApiResponseClass::sendResponse($data, 'Role Access Updated Successfully');
    }

    /**
     * @OA\Get(
     *  path="/role-accesses/trees/{id_role}/{id_menu_module}",
     *  summary="Get the list of role access trees",
     *  tags={"Main - Role Access"},
     *  @OA\Parameter(
     *      name="id_role",
     *      description="Role ID",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="integer"
     *      )
     *  ),
     *  @OA\Parameter(
     *      name="id_menu_module",
     *      description="Menu Module ID",
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
    public function trees($id_role, $id_menu_module)
    {
        $menu_module = MenuModule::with(['toMenuCategory.toMenuBody.toMenuChild'])->find($id_menu_module);

        $menu_category = $menu_module->toMenuCategory;
        $menu_body     = [];
        $menu_child    = [];
        $tree          = [];

        foreach ($menu_category as $key => $value) {
            if ($value->toMenuBody->count() > 0) {
                foreach ($value->toMenuBody as $key => $value1) {
                    if ($value1->toMenuChild->count() > 0) {
                        foreach ($value1->toMenuChild as $key => $value2) {
                            $menu_child[$value2->id_menu_body][] = [
                                'title'     => $value2->name,
                                'checkable' => false,
                                'key'       => $value1->id_menu_category . '-' . $value1->id_menu_body . '-' . $value2->id_menu_child,
                            ];
                        }

                        $menu_body[$value1->id_menu_category][] = [
                            'title'    => $value1->name,
                            'key'      => $value1->id_menu_body,
                            'children' => $menu_child[$value1->id_menu_body] ?? [],
                        ];
                    } else {
                        $menu_body[$value1->id_menu_category][] = [
                            'title' => $value1->name,
                            'key'   => $value1->id_menu_body,
                        ];
                    }
                }

                $tree[] = [
                    'title'    => $value->name,
                    'key'      => $key . '-' . $value->id_menu_category,
                    'children' => $menu_body[$value->id_menu_category] ?? [],
                ];
            } else {
                $tree[] = [
                    'title' => $value->name,
                    'key'   => $key . '-' . $value->id_menu_category,
                ];
            }
        }

        $role        = Role::find($id_role);
        $role_access = $role->toRoleAccess;
        $active      = [];

        foreach ($role_access as $key => $value) {
            if ($value->toMenuBody->toMenuCategory->toMenuModule->id_menu_module == $id_menu_module) {
                $active[] = $value->toMenuBody->id_menu_body;
            }
        }

        $response = [
            'tree'   => $tree,
            'active' => $active,
        ];

        return ApiResponseClass::sendResponse($response, 'Role Access Updated Successfully');
    }
}
