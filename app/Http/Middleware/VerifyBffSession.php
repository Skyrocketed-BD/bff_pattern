<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBffSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasToken = session()->has('access_token');

        if (!$hasToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - No session'
            ], 401);
        }

        return $next($request);
    }
}
