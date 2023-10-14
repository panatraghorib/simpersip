<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\ResponseApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppstraAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(Auth::check()) {
            return $next($request);
        }

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return ResponseApi::unauthorized();
    }
}
