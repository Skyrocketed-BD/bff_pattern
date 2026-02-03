<?php

namespace App\Http\Controllers\Bff\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        $response = Http::post('http://127.0.0.1:8080/api/auth/login', $credentials);

        if ($response->failed()) {
            return response()->json(['message' => $response->json()['message']], 401);
        }

        $data = $response->json();

        session([
            'access_token' => $data['access_token'],
        ]);

        return $data;
    }
    public function logout()
    {
        session()->forget(['access_token']);

        return response()->json([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }

    public function me()
    {
        return response()->json([
            'access_token' => session('access_token')
        ]);
    }

    public function csrf()
    {
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    }
}
