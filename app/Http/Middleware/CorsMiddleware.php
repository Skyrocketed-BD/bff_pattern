<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Config;


class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $cors = Config::get('app.cors_allow_origin');

        $response->headers->set('Access-Control-Allow-Origin', $cors);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');


        if ($request->getMethod() === 'OPTIONS') {
            $response->headers->set('Access-Control-Max-Age', '3600');
            $response->setStatusCode(204);
        }

        return $response;
    }
}
