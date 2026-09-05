<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str; // For UUID

class SupplierController extends Controller
{
    /**
     * Display a listing of the suppliers for the current tenant with pagination, sorting, and filtering.
     */
    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to this tenant\'s suppliers.');
        }

        $sortBy = $request->input('sortBy', 'name');
        $sortDirection = $request->input('sortDirection', 'asc');
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');

        $suppliersQuery = Supplier::where('tenant_id', $tenantId);

        if ($search) {
            $suppliersQuery->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', '%' . $search . '%')
                      ->orWhere('contact_person', 'ILIKE', '%' . $search . '%')
                      ->orWhere('email', 'ILIKE', '%' . $search . '%')
                      ->orWhere('phone', 'ILIKE', '%' . $search . '%');
            });
        }

        $suppliers = $suppliersQuery->orderBy($sortBy, $sortDirection)->paginate($perPage)->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Supplier::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'notes' => $request->notes,
        ]);

        return redirect()->route('suppliers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Supplier berhasil ditambahkan.');
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, string $tenantSlug, Supplier $supplier): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant AND the supplier belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId || $supplier->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($supplier->id),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $supplier->update([
            'name' => $request->name,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'notes' => $request->notes,
        ]);

        return redirect()->route('suppliers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Supplier berhasil diperbarui.');
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(string $tenantSlug, Supplier $supplier): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant AND the supplier belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId || $supplier->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Supplier berhasil dihapus.');
    }
}
