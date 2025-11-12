<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckKantinPanelAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek jika user login DAN punya role "Kasir Kantin"
        if (auth()->check() && auth()->user()->hasRole('Kasir Kantin')) {
            // Jika ya, izinkan masuk
            return $next($request);
        }

        // Jika tidak, tolak akses
        abort(403, 'ANDA TIDAK MEMILIKI HAK AKSES.');
    }
}