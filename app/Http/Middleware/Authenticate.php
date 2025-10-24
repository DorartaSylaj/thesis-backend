<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // If the request expects JSON, return nothing — Sanctum/JWT will return 401 instead of redirecting
        if (! $request->expectsJson()) {
            return null;
        }
    }
}
