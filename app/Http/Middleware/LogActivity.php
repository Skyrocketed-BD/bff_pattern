<?php

namespace App\Http\Middleware;

use App\Models\main\LogActivity as MainLogActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $log = [
            'url'      => $request->fullUrl(),
            'method'   => $request->method(),
            'ip'       => $request->ip(),
            'agent'    => $request->header('user-agent'),
            'id_users' => auth('api')->user()->id_users,
        ];

        MainLogActivity::create($log);

        return $next($request);
    }
}
