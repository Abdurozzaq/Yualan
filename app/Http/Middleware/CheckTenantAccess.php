<?php

namespace App\Http\Middleware;

use App\Models\Tenant; // Import model Tenant
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Mendapatkan slug dari parameter rute.
        // Pastikan nama parameter rute di web.php adalah 'tenantSlug'.
        $tenantSlug = $request->route('tenantSlug');

        if (!$tenantSlug) {
            // Jika slug tidak ada di rute, mungkin ada kesalahan konfigurasi rute
            // atau pengguna mencoba mengakses tanpa slug.
            // Anda bisa mengarahkan ke halaman error atau dashboard default.
            return redirect()->route('dashboard.default'); // Contoh: Arahkan ke dashboard tanpa tenant
        }

        // Mencari tenant berdasarkan slug
        $tenant = Tenant::where('slug', $tenantSlug)->first();

        // Jika tenant tidak ditemukan atau tidak aktif
        if (!$tenant || !$tenant->is_active) {
            abort(404, 'Tenant tidak ditemukan atau tidak aktif.'); // Atau redirect ke halaman error
        }

        // Memeriksa apakah pengguna sudah login
        if (!auth()->check()) {
            // Jika belum login, arahkan ke halaman login
            return redirect()->route('login');
        }

        // Memeriksa apakah tenant_id pengguna yang login cocok dengan tenant_id dari slug
        if (auth()->user()->tenant_id !== $tenant->id) {
            // Jika tidak cocok, tolak akses (misalnya, 403 Forbidden)
            abort(403, 'Anda tidak memiliki akses ke tenant ini.'); // Atau redirect ke halaman error
        }

        // Jika semua pemeriksaan lolos, lanjutkan request
        return $next($request);
    }
}

