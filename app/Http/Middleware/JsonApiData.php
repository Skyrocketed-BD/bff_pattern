<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonApiData
{
    const PARSED_METHODS = [
        'POST', 'PUT', 'PATCH'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getMethod(), self::PARSED_METHODS)) {
            $request->merge((array)json_decode($request->getContent()));
        }
        
        return $next($request);
    }
}
