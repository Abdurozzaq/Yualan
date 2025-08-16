<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Product;

class ReportSalesDetailController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        $filterType = $request->input('filterType', 'day');
        $filterDate = $request->input('filterDate', date('Y-m-d'));

        // Ambil tenant berdasarkan slug
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Filter tanggal
        $query = Sale::where('tenant_id', $tenant->id);

        $date = date('Y-m-d', strtotime($filterDate));
        if ($filterType === 'day') {
            $query->whereDate('created_at', $date);
        } elseif ($filterType === 'week') {
            $week = date('W', strtotime($filterDate));
            $year = date('Y', strtotime($filterDate));
            $query->whereRaw("EXTRACT('week' FROM created_at) = ?", [$week])
                  ->whereRaw("EXTRACT('year' FROM created_at) = ?", [$year]);
        } elseif ($filterType === 'month') {
            $month = date('m', strtotime($filterDate));
            $year = date('Y', strtotime($filterDate));
            $query->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
        }

        $sales = $query->with(['user', 'saleItems.product'])->orderBy('created_at', 'desc')->get();

        // Format data untuk frontend
        $salesData = $sales->map(function ($sale) {
            $items_summary = $sale->saleItems->map(function ($item) {
                return $item->product->name . ' x' . $item->quantity;
            })->implode(', ');

            return [
                'id' => $sale->id,
                'date' => $sale->created_at ? $sale->created_at->format('Y-m-d') : null,
                'transaction_number' => $sale->invoice_number,
                'items_summary' => $items_summary,
                'payment_method' => $sale->payment_method,
                'discount' => $sale->discount_amount,
                'tax' => $sale->tax_amount,
                'cashier' => $sale->user ? $sale->user->name : null,
                'total' => $sale->total_amount,
            ];
        });

        return Inertia::render('Reports/SalesDetail', [
            'sales' => $salesData,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }
}
