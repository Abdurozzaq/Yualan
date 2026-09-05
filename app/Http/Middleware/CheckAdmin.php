<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckAdmin
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

        // Periksa apakah peran pengguna adalah 'admin' atau 'superadmin'
        // Karena superadmin juga harus bisa mengakses pengaturan tenant
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'superadmin') {
            abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.'); // Tampilkan error 403 jika bukan admin/superadmin
        }

        return $next($request);
    }
}

