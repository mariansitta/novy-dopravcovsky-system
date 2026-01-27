<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class APIKeyMiddleware
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
        $guess = $request->api_key;
        $api_key = config('api.key');

        if($api_key == null || $guess != $api_key) abort(401);

        return $next($request);
    }

}
