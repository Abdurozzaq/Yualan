<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\Tenant;
use Inertia\Inertia;


class VoucherController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $perPage = (int) $request->input('perPage', 10);
        $sortBy = $request->input('sortBy', 'expiry_date');
        $sortDirection = $request->input('sortDirection', 'desc');
        $search = $request->input('search');

        $query = Voucher::where('tenant_id', $tenant->id);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%$search%")
                  ->orWhere('name', 'LIKE', "%$search%")
                  ->orWhere('type', 'LIKE', "%$search%") ;
            });
        }
        $query->orderBy($sortBy, $sortDirection);
        $vouchers = $query->paginate($perPage)->withQueryString();
        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
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
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,percentage_max,nominal',
            'value' => 'required|numeric|min:0',
            'max_nominal' => 'nullable|numeric|min:0',
            'expiry_date' => 'required|date',
            // removed is_active validation
            'used' => 'sometimes|boolean',
        ]);
        Voucher::create([
            'tenant_id' => $tenant->id,
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'value' => $request->value,
            'max_nominal' => $request->max_nominal,
            'expiry_date' => $request->expiry_date,
            // removed is_active from data
            'used' => $request->used ?? false,
        ]);
        return redirect()->route('vouchers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function update(Request $request, $tenantSlug, $id)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $voucher = Voucher::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
        $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,percentage_max,nominal',
            'value' => 'required|numeric|min:0',
            'max_nominal' => 'nullable|numeric|min:0',
            'expiry_date' => 'required|date',
            // removed is_active validation
            'used' => 'sometimes|boolean',
        ]);
        $voucher->update([
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'value' => $request->value,
            'max_nominal' => $request->max_nominal,
            'expiry_date' => $request->expiry_date,
            // removed is_active from data
            'used' => $request->used ?? $voucher->used,
        ]);
        return redirect()->route('vouchers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Voucher berhasil diupdate.');
    }

    public function destroy($tenantSlug, $id)
    {
    $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
    $voucher = Voucher::where('id', $id)->where('tenant_id', $tenant->id)->firstOrFail();
    $voucher->delete();
    return redirect()->route('vouchers.index', ['tenantSlug' => $tenantSlug])->with('success', 'Voucher berhasil dihapus.');
    }
}
