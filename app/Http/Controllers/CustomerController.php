<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers for the current tenant with pagination, sorting, and filtering.
     */
    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to this tenant\'s customers.');
        }

        // Default sorting and pagination
        $sortBy = $request->input('sortBy', 'name');
        $sortDirection = $request->input('sortDirection', 'asc');
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $filterField = $request->input('filterField');

        $customersQuery = Customer::where('tenant_id', $tenantId);

        // Apply search filter
        if ($search) {
            $customersQuery->where(function ($query) use ($search, $filterField) {
                if ($filterField && in_array($filterField, ['name', 'email', 'phone', 'address'])) {
                    $query->where($filterField, 'ILIKE', '%' . $search . '%');
                } else {
                    // Default search across common fields
                    $query->where('name', 'ILIKE', '%' . $search . '%')
                        ->orWhere('email', 'ILIKE', '%' . $search . '%')
                        ->orWhere('phone', 'ILIKE', '%' . $search . '%')
                        ->orWhere('address', 'ILIKE', '%' . $search . '%');
                }
            });
        }

        // Apply sorting
        $customersQuery->orderBy($sortBy, $sortDirection);

        // Get paginated results
        $customers = $customersQuery->paginate($perPage)->withQueryString();

        return Inertia::render('customers/Index', [
            'customers' => $customers,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
                'filterField' => $filterField,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name, // Pass tenant name for ID card export
        ]);
    }

    /**
     * Store a newly created customer in storage.
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('customers')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        Customer::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return redirect()->route('customers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, string $tenantSlug, Customer $customer): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant AND the customer belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId || $customer->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('customers')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return redirect()->route('customers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Pelanggan berhasil diperbarui.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(string $tenantSlug, Customer $customer): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant AND the customer belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId || $customer->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $customer->delete();

        return redirect()->route('customers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Pelanggan berhasil dihapus.');
    }

    /**
     * Export a simple ID card for the specified customer.
     */
    public function exportIdCard(string $tenantSlug, Customer $customer): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant AND the customer belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId || $customer->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('customers/IdCard', [
            'customer' => $customer,
            'tenantName' => $tenant->name,
        ]);
    }
}

