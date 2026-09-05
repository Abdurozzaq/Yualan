<?php
namespace App\Http\Controllers;

use App\Models\SaasInvoice;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Http\Response;

class SaasInvoiceHistoryController extends Controller
{

    public function index(Request $request, string $tenantSlug): \Inertia\Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $tenantId = $tenant->id;

        // Ambil filter dari request
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDirection = $request->input('sortDirection', 'desc');
        $perPage = (int) $request->input('perPage', 10);
        $search = $request->input('search');
        $filterField = $request->input('filterField', 'plan_name');

        $query = SaasInvoice::where('tenant_id', $tenantId);

        // Search & filter
        if ($search) {
            $query->where(function ($q) use ($search, $filterField) {
                if (in_array($filterField, ['plan_name', 'created_at', 'expired_at'])) {
                    $q->where($filterField, 'like', "%$search%");
                } else {
                    $q->where('plan_name', 'like', "%$search%")
                      ->orWhere('created_at', 'like', "%$search%")
                      ->orWhere('expired_at', 'like', "%$search%");
                }
            });
        }

        // Sorting
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $invoices = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Subscription/InvoiceHistory', [
            'invoices' => $invoices,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => $perPage,
                'search' => $search,
                'filterField' => $filterField,
            ],
            'tenantSlug' => $tenantSlug,
        ]);
    }
}
