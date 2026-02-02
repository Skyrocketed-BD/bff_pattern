<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\OperationController;
use App\Http\Requests\operation\KontraktorUsersRequest;
use App\Http\Resources\main\UserResource;
use App\Models\main\User;
use App\Repositories\main\UsersRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class KontraktorUsersController extends OperationController
{
    /**
     * @OA\Get(
     *  path="/kontraktor_users",
     *  summary="Get the list of kontraktor users",
     *  tags={"Operation - Kontraktor Users"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index(UsersRepository $users_repository)
    {
        $data = $users_repository->getAll($this->id_kontraktor);

        return ApiResponseClass::sendResponse($data, 'User Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/kontraktor_users",
     *  summary="Add a new kontraktor user",
     *  tags={"Operation - Kontraktor Users"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email"
     *              ),
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  description="Username"
     *              ),
     *              @OA\Property(
     *                  property="gender",
     *                  type="string",
     *                  description="Gender"
     *              ),
     *              @OA\Property(
     *                  property="birth_date",
     *                  type="string",
     *                  description="Birth Date"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone"
     *              ),
     *              @OA\Property(
     *                  property="address",
     *                  type="string",
     *                  description="Address"
     *              ),
     *              required={"name", "email", "username", "gender", "birth_date", "phone", "address"},
     *              example={
     *                  "name": "John Doe",
     *                  "email": "johndoe@ex.com",
     *                  "username": "johndoe",
     *                  "gender": "M",
     *                  "birth_date": "2000-01-01",
     *                  "phone": "08123456789",
     *                  "address": "Jl. Raya No. 1"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store(KontraktorUsersRequest $request)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            // cek jumlah user
            $count_id_kontraktor = User::where('id_role', '5')->where('id_kontraktor', $this->id_kontraktor)->count();

            $initial  = generateUniqueInitials('operation', $this->kontraktor->toKontraktor->company, 'kontraktor', 'initial');
            $username = $initial . '-' . Str::password(8, true, true, false, false);

            if ($count_id_kontraktor < 2) {
                $data = User::create([
                    'id_kontraktor' => $this->id_kontraktor,
                    'id_role'       => 5, // data entry
                    'name'          => $request->name,
                    'email'         => $request->email,
                    'username'      => $username,
                    'password'      => Hash::make($username . '1234'),
                    'gender'        => $request->gender,
                    'birth_date'    => $request->birth_date,
                    'phone'         => $request->phone,
                    'address'       => $request->address
                ]);

                ActivityLogHelper::log('operation:contractor_user_create', 1, [
                    'name'          => $request->name,
                    'email'         => $request->email,
                    'username'      => $username
                ]);

                DB::connection('operation')->commit();

                return ApiResponseClass::sendResponse($data, 'User Created Successfully');
            }

            return response()->json(['status'  => false, 'message' => 'The user creation limit has been exceeded'], 422);
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:contractor_user_create', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Get(
     *  path="/kontraktor_users/{id}",
     *  summary="Get a kontraktor user",
     *  tags={"Operation - Kontraktor Users"},
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
     *  path="/kontraktor_users/{id}",
     *  summary="Update a kontraktor user",
     *  tags={"Operation - Kontraktor Users"},
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
     *                  property="name",
     *                  type="string",
     *                  description="Name"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email"
     *              ),
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  description="Username"
     *              ),
     *              @OA\Property(
     *                  property="gender",
     *                  type="string",
     *                  description="Gender"
     *              ),
     *              @OA\Property(
     *                  property="birth_date",
     *                  type="string",
     *                  description="Birth Date"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone"
     *              ),
     *              @OA\Property(
     *                  property="address",
     *                  type="string",
     *                  description="Address"
     *              ),
     *              required={"name", "email", "username", "gender", "birth_date", "phone", "address"},
     *              example={
     *                  "name": "John Doe",
     *                  "email": "johndoe@ex.com",
     *                  "username": "johndoe",
     *                  "gender": "M",
     *                  "birth_date": "2000-01-01",
     *                  "phone": "08123456789",
     *                  "address": "Jl. Raya No. 1"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update(KontraktorUsersRequest $request, $id)
    {
        DB::connection('operation')->beginTransaction();;
        try {
            $data = User::find($id);

            $data->update([
                'name'          => $request->name,
                'email'         => $request->email,
                // 'username'      => $request->username,
                'gender'        => $request->gender,
                'birth_date'    => $request->birth_date,
                'phone'         => $request->phone,
                'address'       => $request->address
            ]);

            ActivityLogHelper::log('operation:contractor_user_update', 1, [
                'name'  => $data->name,
                'email' => $data->email
            ]);

            DB::connection('operation')->commit();

            return ApiResponseClass::sendResponse(UserResource::make($data), 'User Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:contractor_user_update', 0, ['error' => $e->getMessage()]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/kontraktor_users/{id}",
     *  summary="Delete a kontraktor user",
     *  tags={"Operation - Kontraktor Users"},
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

            ActivityLogHelper::log('operation:contractor_user_delete', 1, [
                'name'  => $data->name,
                'email' => $data->email
            ]);

            return ApiResponseClass::sendResponse(UserResource::make($data), 'User Deleted Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('operation:contractor_user_delete', 0, ['error' => $e->getMessage()]);

            return ApiResponseClass::throw($e, 500);
        }
    }
}
