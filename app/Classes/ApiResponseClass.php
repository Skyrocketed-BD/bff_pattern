<?php

namespace App\Classes;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiResponseClass
{
    public static function rollback($e, $message = "Something went wrong!")
    {
        DB::rollBack();

        self::throw($message, 500, $e);
    }

    public static function throw($message = "Something went wrong!", $code = 500, $error = null)
    {
        $response['success'] = false;

        $response['message'] = $message;

        if (!empty($error)) {
            Log::info($error);

            $response['errors'] = $error;

            $response['request_id'] = Context::get('request_id');
        }

        throw new HttpResponseException(Response::json($response, $code));
    }

    public static function sendResponse($result, $message, $code = 200)
    {
        $response['success'] = true;

        if (!empty($message)) {
            $response['message'] = $message;
        }

        $response['data'] = $result;

        return Response::json($response, $code);
    }

    public static function respondWithToken($token, $response = [])
    {
        $response['access_token'] = $token;
        $response['token_type'] = 'bearer';
        $response['expires_in'] = JWTAuth::factory()->getTTL();

        return Response::json($response, 200);
    }
}
