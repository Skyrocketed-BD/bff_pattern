<?php

namespace App\Http\Controllers\Bff\Mobile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        dd('login bff mobile');

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

    public function logout(Request $request)
    {
        dd('logout bff mobile');
    }
    public function verify(Request $request)
    {
        dd('verify bff mobile');
    }
}
