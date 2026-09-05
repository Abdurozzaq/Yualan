<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckSuperadmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan pengguna sudah login
        if (!Auth::check()) {
            return redirect()->route('login'); // Arahkan ke halaman login jika belum login
        }

        // Periksa apakah peran pengguna adalah 'superadmin'
        if (Auth::user()->role !== 'superadmin') {
            abort(403, 'Akses Ditolak. Anda bukan Superadmin.'); // Tampilkan error 403 jika bukan superadmin
        }

        return $next($request);
    }
}

