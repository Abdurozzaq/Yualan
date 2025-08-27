<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promo;
use App\Models\Tenant;
use Inertia\Inertia;


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
        // If you want to scope by tenant, add tenant_id to promos table and filter here
        // $query->where('tenant_id', $tenant->id);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('type', 'LIKE', "%$search%") ;
            });
        }
        $query->orderBy($sortBy, $sortDirection);
        $promos = $query->paginate($perPage)->withQueryString();
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
        ]);
    }

    public function store(Request $request, $tenantSlug)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'value' => 'required|numeric|min:0',
            'expiry_date' => 'required|date',
            'is_active' => 'required|boolean',
        ]);
        Promo::create($request->only(['name','type','value','expiry_date','is_active']));
        return redirect()->route('promos.index', ['tenantSlug' => $tenantSlug])->with('success', 'Promo berhasil ditambahkan.');
    }

    public function update(Request $request, $tenantSlug, $id)
    {
        $promo = Promo::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'value' => 'required|numeric|min:0',
            'expiry_date' => 'required|date',
            'is_active' => 'required|boolean',
        ]);
        $promo->update($request->only(['name','type','value','expiry_date','is_active']));
        return redirect()->route('promos.index', ['tenantSlug' => $tenantSlug])->with('success', 'Promo berhasil diupdate.');
    }

    public function destroy($tenantSlug, $id)
    {
        $promo = Promo::findOrFail($id);
        $promo->delete();
        return redirect()->route('promos.index', ['tenantSlug' => $tenantSlug])->with('success', 'Promo berhasil dihapus.');
    }
}
