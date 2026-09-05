<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Category; // Import Category model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // For file storage

class ProductController extends Controller
{
    /**
     * Display a listing of the products for the current tenant with pagination, sorting, and filtering.
     */
    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to this tenant\'s products.');
        }

        // Default sorting and pagination
        $sortBy = $request->input('sortBy', 'name');
        $sortDirection = $request->input('sortDirection', 'asc');
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $filterField = $request->input('filterField');

        $productsQuery = Product::where('tenant_id', $tenantId)
                                ->with('category'); // Eager load category relationship

        // Apply search filter
        if ($search) {
            $productsQuery->where(function ($query) use ($search, $filterField) {
                if ($filterField && in_array($filterField, ['name', 'sku', 'description', 'unit', 'ingredients'])) {
                    $query->where($filterField, 'ILIKE', '%' . $search . '%');
                } else {
                    // Default search across common fields
                    $query->where('name', 'ILIKE', '%' . $search . '%')
                          ->orWhere('sku', 'ILIKE', '%' . $search . '%')
                          ->orWhere('description', 'ILIKE', '%' . $search . '%')
                          ->orWhere('unit', 'ILIKE', '%' . $search . '%')
                          ->orWhere('ingredients', 'ILIKE', '%' . $search . '%');
                }
            });
        }

        // Apply sorting
        $productsQuery->orderBy($sortBy, $sortDirection);

        // Get paginated results
        $products = $productsQuery->paginate($perPage)->withQueryString();

        // Get categories for the dropdown in the frontend form
        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
                'filterField' => $filterField,
            ],
            'categories' => $categories, // Pass categories for dropdown
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Store a newly created product in storage.
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
            'category_id' => ['nullable', 'string', Rule::exists('categories', 'id')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'], // Added cost_price validation
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:2048'], // Max 2MB
            'is_food_item' => ['boolean'],
            'ingredients' => ['nullable', 'string', 'max:1000'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Store image in tenant-specific folder
            $imagePath = $request->file('image')->store('public/products/' . $tenantId);
            $imagePath = Str::replaceFirst('public/', '', $imagePath); // Remove 'public/' prefix for database storage
        }

        Product::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenantId,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'sku' => $request->sku,
            'description' => $request->description,
            'price' => $request->price,
            'cost_price' => $request->cost_price, // Save cost_price
            'stock' => $request->stock,
            'unit' => $request->unit,
            'image' => $imagePath,
            'is_food_item' => $request->boolean('is_food_item'),
            'ingredients' => $request->ingredients,
        ]);

        return redirect()->route('products.index', ['tenantSlug' => $tenantSlug])->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, string $tenantSlug, Product $product): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant AND the product belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId || $product->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'category_id' => ['nullable', 'string', Rule::exists('categories', 'id')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products')->where(function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })->ignore($product->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'], // Added cost_price validation
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:2048'], // Max 2MB
            'is_food_item' => ['boolean'],
            'ingredients' => ['nullable', 'string', 'max:1000'],
            'clear_image' => ['boolean'], // Added to handle explicit image removal
        ]);

        $imagePath = $product->image; // Keep existing image by default

        // Handle image update/deletion
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::delete('public/' . $product->image);
            }
            // Store new image
            $imagePath = $request->file('image')->store('public/products/' . $tenantId);
            $imagePath = Str::replaceFirst('public/', '', $imagePath);
        } elseif ($request->boolean('clear_image') && $product->image) { // Add a 'clear_image' flag from frontend
            Storage::delete('public/' . $product->image);
            $imagePath = null;
        }

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'sku' => $request->sku,
            'description' => $request->description,
            'price' => $request->price,
            'cost_price' => $request->cost_price, // Update cost_price
            'stock' => $request->stock,
            'unit' => $request->unit,
            'image' => $imagePath,
            'is_food_item' => $request->boolean('is_food_item'),
            'ingredients' => $request->ingredients,
        ]);

        return redirect()->route('products.index', ['tenantSlug' => $tenantSlug])->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(string $tenantSlug, Product $product): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ensure the authenticated user belongs to this tenant AND the product belongs to this tenant
        if (Auth::user()->tenant_id !== $tenantId || $product->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        // Delete associated image
        if ($product->image) {
            Storage::delete('public/' . $product->image);
        }

        $product->delete();

        return redirect()->route('products.index', ['tenantSlug' => $tenantSlug])->with('success', 'Produk berhasil dihapus.');
    }
}

