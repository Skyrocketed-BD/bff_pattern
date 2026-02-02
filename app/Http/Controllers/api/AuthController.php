<?php

namespace App\Http\Controllers\api;

use App\Classes\ApiResponseClass;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginMobileRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\main\UserResource;
use App\Models\main\UserTokens;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *  path = "/auth/login",
     *  summary = "Login",
     *  tags = {"Authentication"},
     *  @OA\RequestBody(
     *      @OA\MediaType(
     *          mediaType = "application/json",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property = "username",
     *                  type = "string",
     *                  description = "User name or email"
     *              ),
     *              @OA\Property(
     *                  property = "password",
     *                  type = "string",
     *                  description = "User password"
     *              ),
     *              required = {"username", "password"},
     *              example = {
     *                  "username": "admin",
     *                  "password": "admin123"
     *              }
     *          )
     *      )
     *  ),
     *  @OA\Response(response="200", description="Login successful"),
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $login_type = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            $credentials = [
                $login_type => $request->username,
                'password'  => $request->password,
                'is_active' => '1',
            ];

            if (!$token = JWTAuth::attempt($credentials)) {
                ActivityLogHelper::log('login', 0, ['username' => $request->username, 'error' => 'Username or password is incorrect.']);

                return ApiResponseClass::throw('Username or password is incorrect.', 400);
            }
        } catch (JWTException $e) {
            return ApiResponseClass::throw('Could not create token.', 500, $e);
        }

        // if (Auth::user()->count_logged_in >= 3) {
        //     return ApiResponseClass::throw('You have reached the maximum number of login attempts. Please try again later.', 400);
        // }

        $this->saveRememberToken($token);

        $user = Auth::user();

        if ($user->toRole->name === 'Kontraktor') {
            $user->is_logged_in = true;
            $user->count_logged_in = $user->count_logged_in + 1;
            $user->save();
        }

        $response['user'] = UserResource::make(Auth::user());

        ActivityLogHelper::log('login', 1, ['info' => 'Login successful.']);

        return ApiResponseClass::respondWithToken($token, $response);
    }

    public function mobile_login(LoginMobileRequest $request)
    {
        try {
            $login_type = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            $credentials = [
                $login_type => $request->username,
                'password'  => $request->password,
                'is_active' => '1',
            ];

            // kalau sukses login,
            // updatekan token notifikasi ke user terkait

            if (!$token = JWTAuth::attempt($credentials)) {
                ActivityLogHelper::log('login', 0, ['username' => $request->username, 'error' => 'Username or password is incorrect.']);

                return ApiResponseClass::throw('Username or password is incorrect.', 400);
            }
        } catch (JWTException $e) {
            return ApiResponseClass::throw('Could not create token.', 500, $e);
        }

        $user = Auth::user();
        $id_user = $user->id_users;

        $user_notification = UserTokens::where('id_users', $id_user)->where('token', $request->device_token)->first();
        $response['user'] = UserResource::make(Auth::user());

        if (!$user_notification) {
            $user_notification           = new UserTokens();
            $user_notification->id_users = $id_user;
            $user_notification->token    = $request->device_token;
            $user_notification->save();

            return ApiResponseClass::respondWithToken($token, $response);
        } else {
            return ApiResponseClass::respondWithToken($token, $response);
        }

    }

    /**
     * @OA\Post(
     *     path="/auth/me",
     *     summary="User detail",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(response=200, description="Return a list of resources"),
     * )
     */
    public function me()
    {
        $response = auth('api')->user();

        return Response::json($response, 200);
    }

    /**
     * @OA\Post(
     *     path = "/auth/refresh",
     *     summary = "Refresh token",
     *     tags = {"Authentication"},
     *     security = {{ "bearerAuth": {} }},
     *     @OA\Response(response=200, description="Return a list of resources"),
     * )
     */
    public function refresh()
    {
        $token = JWTAuth::refresh();

        $this->saveRememberToken($token);

        return ApiResponseClass::respondWithToken($token);
    }

    public function logout()
    {
        ActivityLogHelper::log('logout', 1, ['info' => 'Successfully logged out']);

        $user = auth('api')->user();
        $user->count_logged_in = $user->count_logged_in - 1;
        if ($user->count_logged_in == 0) {
            $user->is_logged_in = false;
        }
        $user->save();

        auth('api')->logout();

        $response = [
            'message' => 'Successfully logged out'
        ];

        return Response::json($response, 200);
    }

    protected function saveRememberToken($token)
    {
        $user = Auth::user();
        $user->setRememberToken($token);
        $user->save();
    }
}
