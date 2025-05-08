<?php

namespace App\Http\Middleware;

use App\Trait\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriver
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (Auth::guard('driver')->check()) {
        //     return $next($request);
        // }
        if (Auth::guard('driver')->check()) {
            Auth::shouldUse('driver'); // Manually tell Laravel to use 'user' guard
            return $next($request);
        }
        return ApiResponse::errorResponse(false, 'unauthentecation');
    }
}