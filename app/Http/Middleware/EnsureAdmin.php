<?php

namespace App\Http\Middleware;

use Closure;
use App\Trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (Auth::guard('admin')->check()) {
        //     return $next($request);
        // }

        // return response()->json(['message' => 'Unauthorized'], 401);
         if (Auth::guard('admin')->check()) {
            Auth::shouldUse('admin'); // Manually tell Laravel to use 'user' guard
            return $next($request);
        }

        return response()->json(['status' => false, 'message' => 'Unauthenticated']);
    }
}