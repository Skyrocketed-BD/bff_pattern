<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\main\UserRequest;
use App\Http\Resources\main\UserResource;
use App\Models\main\User;
use App\Repositories\main\UsersRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *  path="/users",
     *  summary="Get the list of users",
     *  tags={"Main - User"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(UsersRepository $users_repository)
    {
        $data = $users_repository->getAll();

        return ApiResponseClass::sendResponse($data, 'User Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/users",
     *  summary="Add a new user",
     *  tags={"Main - User"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="id_role",
     *                  type="integer",
     *                  description="ID Role"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of user"
     *              ),
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  description="Username of user"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email of user"
     *              ),
     *              required={"id_role", "name", "username", "email"},
     *              example={
     *                  "id_role": 1,
     *                  "name": "Cash",
     *                  "username": "cash",
     *                  "email": "S9fQ8@example.com",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(UserRequest $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $user           = new User();
            $user->id_role  = $request->id_role;
            $user->name     = $request->name;
            $user->username = $request->username;
            $user->email    = $request->email;
            $user->password = Hash::make($request->username . '1234');
            $user->save();

            ActivityLogHelper::log('admin:user_create', 1, ['username' => $request->username]);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($user, 'User Created Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:user_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/users/{id}",
     *  summary="Get a single user",
     *  tags={"Main - User"},
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
        $data = User::find($id);

        return ApiResponseClass::sendResponse(UserResource::make($data), 'User Retrieved Successfully');
    }

    /**
     * @OA\Put(
     *  path="/users/{id}",
     *  summary="Update a user",
     *  tags={"Main - User"},
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
     *                  property="id_role",
     *                  type="integer",
     *                  description="ID Role"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of user"
     *              ),
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  description="Username of user"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email of user"
     *              ),
     *              required={"id_role", "name", "username", "email"},
     *              example={
     *                  "id_role": 1,
     *                  "name": "Cash",
     *                  "username": "cash",
     *                  "email": "S9fQ8@example.com",
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     * */
    public function update(UserRequest $request, $id)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $data = User::find($id);
            $updated_data = [
                'id_role'  => $request->id_role,
                'name'     => $request->name,
                'username' => $request->username,
                'email'    => $request->email,
            ];
            $data->update($updated_data);

            ActivityLogHelper::log('admin:user_update', 1, [
                'username' => $data->username,
            ]);
            
            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($data, 'User Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:user_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/users/{id}",
     *  summary="Delete a user",
     *  tags={"Main - User"},
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
            $data = User::find($id);
            $data->delete();
            ActivityLogHelper::log('admin:user_delete', 1, ['username' => $data->username]);

            return ApiResponseClass::sendResponse($data, 'User Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:user_delete', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::throw($e, 500);
        }
    }

    /**
     * @OA\Post(
     *  path="/users/active/{id}",
     *  summary="Update a user active",
     *  tags={"Main - User"},
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
    public function active($id)
    {
        $data = User::find($id);

        $data->is_active = $data->is_active == "1" ? "0" : "1";

        $data->save();

        return ApiResponseClass::sendResponse($data, 'User Updated Successfully');
    }

    /**
     * @OA\Post(
     *  path="/users/reset/{id}",
     *  summary="Reset a user password",
     *  tags={"Main - User"},
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
    public function reset($id)
    {
        $data = User::find($id);

        if ($data->id_kontraktor == null) {
            $data->password = Hash::make($data->username . '1234');
        } else {
            $data->password = Hash::make($data->username);
        }

        $data->save();

        ActivityLogHelper::log('admin:user_password_reset', 1, ['username' => $data->username]);

        return ApiResponseClass::sendResponse($data, 'User Updated Successfully');
    }
}
