<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Use User model instead of Employee
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        $tenant = $request->user()->tenant; // Ambil tenant dari user login

        // Use User::withTrashed() and filter by tenant_slug or tenant_id
        $query = User::withTrashed()->whereHas('tenant', function ($q) use ($tenantSlug) {
            $q->where('slug', $tenantSlug);
        });

        // Filter & search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        if ($request->sortBy) {
            $query->orderBy($request->sortBy, $request->sortDirection ?? 'asc');
        }

        $employees = $query->paginate($request->perPage ?? 10)->appends($request->except('page'));

        return Inertia::render('Employee/Index', [
            'employees' => $employees,
            'filters' => [
                'sortBy' => $request->sortBy ?? 'name',
                'sortDirection' => $request->sortDirection ?? 'asc',
                'perPage' => $request->perPage ?? 10,
                'search' => $request->search ?? '',
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    public function store(Request $request, $tenantSlug)
    {
        $tenant = $request->user()->tenant;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:admin,cashier',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'id' => (string) Str::uuid(), // Set UUID secara manual
            'tenant_id' => (string) $tenant->id, // Pastikan string
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('employees.index', $tenantSlug)->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, $tenantSlug, User $employee)
    {
        $tenant = $request->user()->tenant;

        if ($employee->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$employee->id}",
            'role' => 'required|in:admin,cashier',
            'password' => 'nullable|string|min:6',
        ]);

        $employee->name = $request->name;
        $employee->email = $request->email;
        $employee->role = $request->role;
        if ($request->password) {
            $employee->password = Hash::make($request->password);
        }
        $employee->save();

        return redirect()->route('employees.index', $tenantSlug)->with('success', 'Karyawan berhasil diupdate.');
    }

    public function destroy($tenantSlug, User $employee)
    {
        $employee->delete();
        return redirect()->back()->with('success', 'Karyawan berhasil dihapus (soft delete).');
    }

    public function restore($tenantSlug, $id)
    {
        $employee = User::withTrashed()->findOrFail($id);
        $employee->restore();
        return redirect()->back()->with('success', 'Karyawan berhasil direstore.');
    }

    public function changePassword(Request $request, $tenantSlug, User $employee)
    {
        // Only admin can change password
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $employee->password = Hash::make($request->password);
        $employee->save();

        return redirect()->back()->with('success', 'Password karyawan berhasil diubah.');
    }
}