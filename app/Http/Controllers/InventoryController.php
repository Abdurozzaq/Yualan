<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Supplier; // Import Supplier model if you created it
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule; // Import Rule for validation

class InventoryController extends Controller
{
    /**
     * Display a listing of products with current stock and cost price (Inventory Overview).
     */
    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this tenant\'s inventory.');
        }

        $sortBy = $request->input('sortBy', 'name');
        $sortDirection = $request->input('sortDirection', 'asc');
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');

        $productsQuery = Product::where('tenant_id', $tenant->id)
                                ->with('category'); // Eager load category

        if ($search) {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', '%' . $search . '%')
                      ->orWhere('sku', 'ILIKE', '%' . $search . '%');
            });
        }

        $products = $productsQuery->orderBy($sortBy, $sortDirection)->paginate($perPage)->withQueryString();

        return Inertia::render('Inventory/Overview', [
            'products' => $products,
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
     * API endpoint for product search (autocomplete).
     */
    public function searchProducts(Request $request, string $tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $q = $request->input('q');
        $limit = (int) $request->input('limit', 10);

        $productsQuery = Product::where('tenant_id', $tenant->id);
        if ($q) {
            $productsQuery->where(function ($query) use ($q) {
                $query->where('name', 'ILIKE', "%" . $q . "%")
                      ->orWhere('sku', 'ILIKE', "%" . $q . "%");
            });
        }
        $products = $productsQuery->orderBy('name')->limit($limit)->get(['id', 'name', 'stock', 'cost_price']);

        return response()->json(['products' => $products]);
    }

    /**
     * Display a listing of inventory movements.
     */
    public function movements(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this tenant\'s inventory movements.');
        }

        $sortBy = $request->input('sortBy', 'created_at');
        $sortDirection = $request->input('sortDirection', 'desc');
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $typeFilter = $request->input('typeFilter', 'all');

        $movementsQuery = Inventory::where('tenant_id', $tenant->id)
                                   ->with('product'); // Eager load product details

        if ($typeFilter !== 'all') {
            $movementsQuery->where('type', $typeFilter);
        }

        if ($search) {
            $movementsQuery->where(function ($query) use ($search) {
                $query->where('reason', 'ILIKE', '%' . $search . '%')
                      ->orWhereHas('product', function ($q) use ($search) {
                          $q->where('name', 'ILIKE', '%' . $search . '%')
                            ->orWhere('sku', 'ILIKE', '%' . $search . '%');
                      });
            });
        }

        $movements = $movementsQuery->orderBy($sortBy, $sortDirection)->paginate($perPage)->withQueryString();

        return Inertia::render('Inventory/Movements', [
            'movements' => $movements,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
                'typeFilter' => $typeFilter,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Show the form for receiving new goods.
     */
    public function receiveGoodsForm(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }

        $products = Product::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'stock', 'cost_price']);
        $suppliers = Supplier::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']); // If using suppliers

        return Inertia::render('Inventory/ReceiveGoods', [
            'products' => $products,
            'suppliers' => $suppliers, // Pass suppliers
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Store a new goods receipt.
     */
    public function receiveGoods(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id', Rule::exists('products', 'id')->where(function ($query) use ($tenant) {
                return $query->where('tenant_id', $tenant->id);
            })],
            'quantity' => ['required', 'integer', 'min:1'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'string', 'exists:suppliers,id', Rule::exists('suppliers', 'id')->where(function ($query) use ($tenant) {
                return $query->where('tenant_id', $tenant->id);
            })],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::where('tenant_id', $tenant->id)->findOrFail($request->product_id);
        $oldStock = $product->stock;
        $oldCostPrice = $product->cost_price;
        $incomingQuantity = $request->quantity;
        $incomingCost = $request->cost_per_unit;

        // Calculate new weighted average cost price
        $newStock = $oldStock + $incomingQuantity;
        $newTotalCost = ($oldStock * $oldCostPrice) + ($incomingQuantity * $incomingCost);
        $newCostPrice = ($newStock > 0) ? ($newTotalCost / $newStock) : 0.00;

        // Update product stock and cost price
        $product->update([
            'stock' => $newStock,
            'cost_price' => round($newCostPrice, 2), // Round to 2 decimal places
        ]);

        // Create inventory movement record
        Inventory::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'quantity_change' => $incomingQuantity,
            'cost_per_unit' => round($incomingCost, 2), // Record the actual incoming cost
            'type' => 'in',
            'reason' => $request->reason ?? 'Penerimaan barang dari supplier' . ($request->supplier_id ? ' (' . Supplier::find($request->supplier_id)->name . ')' : ''),
            // source_id and source_type can be null or link to a PurchaseOrder if you implement one
        ]);

        return redirect()->route('inventory.overview', ['tenantSlug' => $tenantSlug])->with('success', 'Penerimaan barang berhasil dicatat.');
    }

    /**
     * Show the form for adjusting stock.
     */
    public function adjustStockForm(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }

        $products = Product::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'stock', 'cost_price']);

        return Inertia::render('Inventory/AdjustStock', [
            'products' => $products,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Store a new stock adjustment.
     */
    public function adjustStock(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id', Rule::exists('products', 'id')->where(function ($query) use ($tenant) {
                return $query->where('tenant_id', $tenant->id);
            })],
            'quantity_change' => ['required', 'integer', 'min:-999999', 'max:999999'], // Allow negative for reduction
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $product = Product::where('tenant_id', $tenant->id)->findOrFail($request->product_id);
        $change = $request->quantity_change;

        // Update product stock
        $product->stock += $change;
        $product->save();

        // Determine cost_per_unit for adjustment (use current average cost)
        $costPerUnit = $product->cost_price;

        // Create inventory movement record
        Inventory::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'quantity_change' => $change,
            'cost_per_unit' => round($costPerUnit, 2),
            'type' => 'adjustment',
            'reason' => $request->reason,
        ]);

        return redirect()->route('inventory.overview', ['tenantSlug' => $tenantSlug])->with('success', 'Penyesuaian stok berhasil dicatat.');
    }

    /**
     * (Optional) Show the form for returning goods.
     */
    public function returnGoodsForm(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }

        $products = Product::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'stock', 'cost_price']);

        return Inertia::render('Inventory/ReturnGoods', [
            'products' => $products,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * (Optional) Store a new goods return.
     */
    public function returnGoods(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id', Rule::exists('products', 'id')->where(function ($query) use ($tenant) {
                return $query->where('tenant_id', $tenant->id);
            })],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
            'sale_item_id' => ['nullable', 'string', 'exists:sale_items,id'], // Link to original sale item
        ]);

        $product = Product::where('tenant_id', $tenant->id)->findOrFail($request->product_id);
        $returnedQuantity = $request->quantity;

        // Update product stock (increase for returns)
        $product->stock += $returnedQuantity;
        $product->save();

        // Determine cost_per_unit for return (use current average cost)
        $costPerUnit = $product->cost_price;

        // Create inventory movement record
        Inventory::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'quantity_change' => $returnedQuantity, // Positive change for stock increase
            'cost_per_unit' => round($costPerUnit, 2),
            'type' => 'return',
            'reason' => $request->reason,
            'source_id' => $request->sale_item_id,
            'source_type' => $request->sale_item_id ? 'App\\Models\\SaleItem' : null,
        ]);

        return redirect()->route('inventory.overview', ['tenantSlug' => $tenantSlug])->with('success', 'Pengembalian barang berhasil dicatat.');
    }
}
