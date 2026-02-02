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
    public function logout(Request $request)
    {
        // Hapus session
        session()->forget(['access_token']);

        return response()->json([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }
    public function verify(Request $request)
    {
        dd('verify bff web');
    }

    public function me(Request $request)
    {
        $access_token = session('access_token');

        dd($access_token);
    }
}
