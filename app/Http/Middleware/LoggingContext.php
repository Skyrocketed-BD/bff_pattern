<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class LoggingContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request_id = Str::uuid()->toString();
        
        Context::add('request_id', $request_id);
        
        return $next($request);
    }
}
