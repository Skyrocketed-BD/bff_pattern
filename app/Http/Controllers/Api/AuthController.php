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
    public function login(LoginRequest $request)
    {
        $login_type = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $login_type => $request->username,
            'password'  => $request->password,
            'is_active' => '1',
        ];

        if (!$token = JWTAuth::attempt($credentials)) {
            return ApiResponseClass::throw('Username or password is incorrect.', 400);
        }

        $response['user'] = UserResource::make(Auth::user());

        return ApiResponseClass::respondWithToken($token, $response);
    }

    public function me()
    {
        $response = auth('api')->user();

        return Response::json($response, 200);
    }

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
