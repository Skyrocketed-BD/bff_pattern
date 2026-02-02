<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckPreferenceKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$requiredKeys): Response
    {
        $missing = [];
        foreach ($requiredKeys as $key) {
            if (!get_arrangement($key)) {
                $missing[] = str_replace('_', ' ', $key);
            }
        }

        $key = implode(', ', $missing);

        if (!empty($missing)) {
            return FacadesResponse::json([
                'success' => false,
                'message' => "Please, setup {$key} first in preference!",
            ], 400);
        }

        return $next($request);
    }
}
