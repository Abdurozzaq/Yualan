<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promo;
use App\Models\Tenant;
use Inertia\Inertia;
use App\Models\Product;


class PromoController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $perPage = (int) $request->input('perPage', 10);
        $sortBy = $request->input('sortBy', 'expiry_date');
        $sortDirection = $request->input('sortDirection', 'desc');
        $search = $request->input('search');

    $query = Promo::query();
    $query->where('tenant_id', $tenant->id);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('type', 'LIKE', "%$search%") ;
            });
        }
        $query->orderBy($sortBy, $sortDirection);
        $promos = $query->paginate($perPage)->withQueryString();
        $products = Product::where('tenant_id', $tenant->id)->get(['id', 'name']);
        return Inertia::render('Promos/Index', [
            'promos' => $promos,
            'filters' => [
                'perPage' => $perPage,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'search' => $search,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
            'products' => $products,
        ]);
    }

    public function store(Request $request, $tenantSlug)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:buyxgetx,buyxgetanother',
            'buy_qty' => 'required|integer|min:1',
            'get_qty' => 'required|integer|min:1',
            'product_id' => 'nullable|string|exists:products,id',
            'another_product_id' => 'nullable|string|exists:products,id',
            'expiry_date' => 'required|date',
            'is_active' => 'required|boolean',
        ]);
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        Promo::create([
            'tenant_id' => $tenant->id,
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'buy_qty' => $request->buy_qty,
            'get_qty' => $request->get_qty,
            'product_id' => $request->product_id,
            'another_product_id' => $request->another_product_id,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active,
        ]);
    return redirect()->route('promos.index', ['tenantSlug' => $tenantSlug])->with('success', 'Promo berhasil ditambahkan.');
    }

    public function update(Request $request, $tenantSlug, $id)
    {
    $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
    $promo = Promo::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:buyxgetx,buyxgetanother',
            'buy_qty' => 'required|integer|min:1',
            'get_qty' => 'required|integer|min:1',
            'product_id' => 'nullable|string|exists:products,id',
            'another_product_id' => 'nullable|string|exists:products,id',
            'expiry_date' => 'required|date',
            'is_active' => 'required|boolean',
        ]);
        $promo->update([
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'buy_qty' => $request->buy_qty,
            'get_qty' => $request->get_qty,
            'product_id' => $request->product_id,
            'another_product_id' => $request->another_product_id,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active,
        ]);
    return redirect()->route('promos.index', ['tenantSlug' => $tenantSlug])->with('success', 'Promo berhasil diupdate.');
    }

    public function destroy($tenantSlug, $id)
    {
    $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
    $promo = Promo::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        $promo->delete();
    return redirect()->route('promos.index', ['tenantSlug' => $tenantSlug])->with('success', 'Promo berhasil dihapus.');
    }
}
