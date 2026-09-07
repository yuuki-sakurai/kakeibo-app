<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivateApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
