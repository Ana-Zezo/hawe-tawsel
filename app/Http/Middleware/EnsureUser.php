<?php

namespace App\Http\Middleware;

use Closure;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (Auth::guard('user')->check()) {
        //     return $next($request);
        // }
        if (Auth::guard('user')->check()) {
            Auth::shouldUse('user'); // Manually tell Laravel to use 'user' guard
            return $next($request);
        }

        return ApiResponse::errorResponse(false, 'Unauthenticated');
        // return ApiResponse::errorResponse(false, 'Unauthenticated');
    }
}