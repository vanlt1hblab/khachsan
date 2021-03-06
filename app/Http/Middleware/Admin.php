<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            if (Auth::user()->roles && (strtolower(Auth::user()->roles->first()->name) == 'admin' || strtolower(Auth::user()->roles->first()->name) == 'employee')) {
                return $next($request);
            }
            return redirect()->route('login');
        }

        return redirect()->route('login');
    }
}
