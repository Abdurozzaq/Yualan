<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str; // For UUID
use App\Models\Tenant; // Import model Tenant

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories for the current tenant with pagination, sorting, and filtering.
     */
    public function index(Request $request, string $tenantSlug): Response // Add $tenantSlug parameter
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail(); // Find the tenant by slug
        $tenantId = $tenant->id; // Use tenant ID from the slug

        // Default sorting and pagination
        $sortBy = $request->input('sortBy', 'name'); // Default sort by name
        $sortDirection = $request->input('sortDirection', 'asc'); // Default sort ascending
        $perPage = $request->input('perPage', 10); // Default items per page
        $search = $request->input('search');
        $filterField = $request->input('filterField'); // Field to filter by (e.g., 'name', 'description')

        $categoriesQuery = Category::where('tenant_id', $tenantId);

        // Apply search filter
        if ($search) {
            $categoriesQuery->where(function ($query) use ($search, $filterField) {
                if ($filterField && in_array($filterField, ['name', 'description'])) {
                    // Apply filter to a specific field if provided
                    $query->where($filterField, 'ILIKE', '%' . $search . '%'); // Use ILIKE for case-insensitive search in PostgreSQL
                } else {
                    // Default to searching both name and description
                    $query->where('name', 'ILIKE', '%' . $search . '%')
                        ->orWhere('description', 'ILIKE', '%' . $search . '%');
                }
            });
        }

        // Apply sorting
        $categoriesQuery->orderBy($sortBy, $sortDirection);

        // Get paginated results
        $categories = $categoriesQuery->paginate($perPage)->withQueryString();

        return Inertia::render('categories/Index', [
            'categories' => $categories,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
                'filterField' => $filterField,
            ],
            'tenantSlug' => $tenantSlug, // Pass tenantSlug explicitly
            'tenantName' => $tenant->name, // Pass tenantName explicitly
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request, string $tenantSlug): RedirectResponse // Add $tenantSlug parameter
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
                Rule::unique('categories')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Category::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('categories.index', ['tenantSlug' => $tenantSlug])->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, string $tenantSlug, string $category): RedirectResponse // Add $tenantSlug parameter
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $categoryModel = Category::where('id', $category)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($categoryModel->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $categoryModel->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('categories.index', ['tenantSlug' => $tenantSlug])->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(string $tenantSlug, string $category): RedirectResponse // Add $tenantSlug parameter
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $categoryModel = Category::where('id', $category)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $categoryModel->delete();

        return redirect()->route('categories.index', ['tenantSlug' => $tenantSlug])->with('success', 'Kategori berhasil dihapus.');
    }
}

