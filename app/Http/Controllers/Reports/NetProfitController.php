<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class NetProfitController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
    // Set default date range to today if not provided
    $today = date('Y-m-d');
    $start_date = $request->input('start_date', $today);
    $end_date = $request->input('end_date', $today);
    // Sorting dilakukan di frontend, backend hanya filter tanggal

        // Ambil tenant_id dari slug
        $tenant = \Illuminate\Support\Facades\DB::table('tenants')->where('slug', $tenantSlug)->first();
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        $tenantId = $tenant->id;


        $salesQuery = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->with(['customer', 'items'])
            ->when($start_date, fn($q) => $q->whereDate('created_at', '>=', $start_date))
            ->when($end_date, fn($q) => $q->whereDate('created_at', '<=', $end_date));


    // Tidak perlu orderBy, sorting di frontend


        // Get all filtered sales (no pagination)
        $salesRaw = $salesQuery->get();

        // Get total sales for tenant (unfiltered)
        $totalSalesTenant = Sale::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->count();

        // Mapping
        $mappedSales = $salesRaw->map(function ($sale) {
            $total_cogs = $sale->items ? $sale->items->sum('cost_price_at_sale') : 0;
            $net_profit = $sale->total_amount - $total_cogs;
            return [
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer?->name ?? '-',
                'total_amount' => $sale->total_amount,
                'total_cogs' => $total_cogs,
                'net_profit' => $net_profit,
                'created_at' => $sale->created_at->format('Y-m-d'),
            ];
        });


        // Calculate totals from all filtered sales
        $totalRevenue = $salesRaw->sum('total_amount');
        $totalCogs = $salesRaw->reduce(function ($carry, $sale) {
            return $carry + ($sale->items ? $sale->items->sum('cost_price_at_sale') : 0);
        }, 0);
        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $salesRaw->reduce(function ($carry, $sale) {
            $total_cogs = $sale->items ? $sale->items->sum('cost_price_at_sale') : 0;
            return $carry + ($sale->total_amount - $total_cogs);
        }, 0);

        return inertia('Reports/NetProfit', [
            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'sales' => [
                'data' => $mappedSales,
                'total' => $totalSalesTenant,
            ],
            'filters' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                // Sorting di frontend
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name ?? '-',
        ]);
    }
}
