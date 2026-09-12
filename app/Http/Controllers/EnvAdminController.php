<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EnvAdminController extends Controller
{
    /**
     * Show the login form for the env-based admin panel.
     */
    public function login()
    {
        if (session('env_admin_authenticated')) {
            return redirect()->route('env_admin.dashboard');
        }

        return Inertia::render('EnvAdmin/Login');
    }

    /**
     * Authenticate the admin using the password from .env.
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $adminPassword = config('app.admin_password');

        if ($request->password !== $adminPassword) {
            throw ValidationException::withMessages([
                'password' => 'Password salah.',
            ]);
        }

        session(['env_admin_authenticated' => true]);

        return redirect()->route('env_admin.dashboard');
    }

    /**
     * Show the statistics dashboard.
     */
    public function dashboard()
    {
        // Gather Statistics
        $totalUsers = User::count();
        $totalTenants = Tenant::count();
        $totalProducts = Product::count();
        $totalSalesCount = Sale::count();

        // Version info from config
        $appVersion = 'v' . config('app.version', '0.0.0') . ' System Version';

        return Inertia::render('EnvAdmin/Dashboard', [
            'stats' => [
                'users' => $totalUsers,
                'tenants' => $totalTenants,
                'products' => $totalProducts,
                'salesCount' => $totalSalesCount,
            ],
            'version' => $appVersion,
        ]);
    }

    /**
     * Show the user management page.
     */
    public function users(Request $request)
    {
        $search = $request->input('search');

        $users = User::with('tenant')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('EnvAdmin/Users', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Force update a user's password.
     */
    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'Password user berhasil diubah.');
    }

    /**
     * Delete a user and cascade soft delete to their tenant and data.
     */
    public function destroy(User $user)
    {
        if ($user->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            if ($tenant) {
                // Cascade soft delete to tenant data
                \App\Models\Product::where('tenant_id', $tenant->id)->delete();
                \App\Models\Category::where('tenant_id', $tenant->id)->delete();
                \App\Models\Customer::where('tenant_id', $tenant->id)->delete();
                \App\Models\Sale::where('tenant_id', $tenant->id)->delete();
                
                // Soft delete the tenant itself
                $tenant->delete();
            }
        }

        // Soft delete the user
        $user->delete();

        return redirect()->back()->with('success', 'User dan seluruh data tenant berhasil di-soft delete.');
    }

    /**
     * Logout the admin.
     */
    public function logout()
    {
        session()->forget('env_admin_authenticated');
        return redirect()->route('env_admin.login');
    }
}
