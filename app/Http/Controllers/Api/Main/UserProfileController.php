<?php

namespace App\Http\Controllers\api\main;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\main\UserProfileResource;
use App\Models\main\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserProfileController extends Controller
{
    /**
     * @OA\Get(
     *  path="/user-profile",
     *  summary="Get the list of users",
     *  tags={"Main - User Profile"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function index()
    {
        $data = $this->user;

        return ApiResponseClass::sendResponse(UserProfileResource::make($data), 'User Retrieved Successfully');
    }

    /**
     * @OA\Post(
     *  path="/user-profile",
     *  summary="Add a new user",
     *  tags={"Main - User Profile"},
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="picture",
     *                  type="file",
     *                  format="binary",
     *                  description="File Image"
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function store_avatar(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $avatar = upd_file($request->picture, $this->user->avatar, 'profile/');

            $this->user->update([
                'avatar' => $avatar
            ]);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($this->user, 'Image Updated Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Put(
     *  path="/user-profile",
     *  summary="Update a user",
     *  tags={"Main - User Profile"},
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="payload",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="key",
     *                          type="string",
     *                          description="Key of value"
     *                      ),
     *                      @OA\Property(
     *                          property="value",
     *                          type="string",
     *                          description="Value of value"
     *                      ),
     *                  ),
     *              ),
     *          )
     *      )
     *  ),
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function update_avatar(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            // Ambil payload dari request, berupa array key-value
            $payload = $request->payload;

            // tampung ke array untuk update
            $updated_data = [];

            foreach ($payload as $item) {
                $updated_data[$item->key] = $item->value;
            }

            $this->user->update($updated_data);

            DB::connection('mysql')->commit();

            ActivityLogHelper::log('admin:update_user', 1, $updated_data);

            return ApiResponseClass::sendResponse(UserProfileResource::make($this->user), 'User Updated Successfully');
        } catch (\Exception $e) {
            ActivityLogHelper::log('admin:update_user', 0, ['error' => $e]);
            return ApiResponseClass::rollback($e);
        }
    }

    /**
     * @OA\Delete(
     *  path="/user-profile",
     *  summary="Delete a user",
     *  tags={"Main - User Profile"},
     *  @OA\Response(response=200, description="Return a list of resources"),
     *  security={{ "bearerAuth": {} }}
     * )
     */
    public function destroy_avatar()
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $avatar = $this->user->avatar;

            del_file($avatar, 'profile/');

            $this->user->update([
                'avatar' => null
            ]);

            DB::connection('mysql')->commit();

            return ApiResponseClass::sendResponse($this->user, 'Image Deleted Successfully');
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e);
        }
    }
}
