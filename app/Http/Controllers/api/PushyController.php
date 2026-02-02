<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PushyRequest;
use App\Models\main\UserTokens;
use Illuminate\Support\Facades\Response;

class PushyController extends Controller
{
    public function store(PushyRequest $request)
    {
        $id_user = auth('api')->user()->id_users;

        $user_token = UserTokens::where('id_users', $id_user)->where('token', $request->token)->first();

        if (!$user_token) {
            $user_token           = new UserTokens();
            $user_token->id_users = $id_user;
            $user_token->token    = $request->token;
            $user_token->save();

            return Response::json(['message' => 'Token saved']);
        } else {
            return Response::json(['message' => 'Token already exists']);
        }
    }
}
