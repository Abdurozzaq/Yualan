<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB; // For database queries

class ReportController extends Controller
{
    /**
     * Display the Gross Profit Report.
     */
    public function grossProfitReport(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this tenant\'s reports.');
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // Calculate Gross Profit
        $salesData = Sale::where('tenant_id', $tenant->id)
            ->where('status', 'completed') // Only consider completed sales for gross profit
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with('saleItems') // Eager load sale items to get product details and cost_price_at_sale
            ->get();

        $totalRevenue = 0;
        $totalCogs = 0; // Cost of Goods Sold

        foreach ($salesData as $sale) {
            $totalRevenue += $sale->total_amount; // Total amount from sale
            foreach ($sale->saleItems as $item) {
                // COGS = quantity * cost_price_at_sale (harga pokok saat penjualan terjadi)
                $totalCogs += ($item->quantity * $item->cost_price_at_sale);
            }
        }

        $grossProfit = $totalRevenue - $totalCogs;

        return Inertia::render('Reports/GrossProfit', [ // Assuming you have this Vue component
            'totalRevenue' => round($totalRevenue, 2),
            'totalCogs' => round($totalCogs, 2),
            'grossProfit' => round($grossProfit, 2),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Display the current Stock Report.
     */
    public function stockReport(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this tenant\'s reports.');
        }

        // Get all products with their current stock and cost price for the tenant
        $products = Product::where('tenant_id', $tenant->id)
                           ->orderBy('name')
                           ->get(['name', 'sku', 'stock', 'cost_price', 'price', 'unit']); // Include unit for display

        // Calculate total stock value (based on cost price)
        $totalStockValue = $products->sum(function ($product) {
            return $product->stock * $product->cost_price;
        });

        return Inertia::render('Reports/StockReport', [ // Assuming you have this Vue component
            'products' => $products,
            'totalStockValue' => round($totalStockValue, 2),
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    public function productMargin(Request $request, $tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        $search = $request->input('search');
        $sort = $request->input('sort', 'sold_qty_desc');

        $products = \DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('products.tenant_id', $tenant->id)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereNull('sales.deleted_at')
            ->where('sales.status', 'completed')
            ->whereNull('products.deleted_at')
            ->selectRaw('
                products.id,
                products.name,
                products.sku,
                products.unit,
                products.price,
                products.cost_price,
                SUM(sale_items.quantity) as sold_qty,
                (products.price - products.cost_price) as margin,
                SUM((sale_items.price - sale_items.cost_price_at_sale) * sale_items.quantity) as total_profit
            ')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('products.name', 'ilike', "%$search%")
                       ->orWhere('products.sku', 'ilike', "%$search%")
                       ->orWhere('products.unit', 'ilike', "%$search%");
                });
            })
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.unit', 'products.price', 'products.cost_price')
            ->orderByRaw(
                $sort === 'sold_qty_desc' ? 'sold_qty DESC' :
                ($sort === 'sold_qty_asc' ? 'sold_qty ASC' :
                ($sort === 'margin_desc' ? 'margin DESC' :
                ($sort === 'margin_asc' ? 'margin ASC' :
                ($sort === 'profit_desc' ? 'total_profit DESC' :
                ($sort === 'profit_asc' ? 'total_profit ASC' : 'sold_qty DESC')))))
            )
            ->get();

        $totalProfit = $products->sum('total_profit');
        $products = $products->map(function ($p) use ($totalProfit) {
            $contribution = $totalProfit > 0 ? ($p->total_profit / $totalProfit) : 0;
            return [
                'name' => $p->name,
                'sku' => $p->sku,
                'sold_qty' => (int) $p->sold_qty,
                'price' => (float) $p->price,
                'cost_price' => (float) $p->cost_price,
                'margin' => (float) $p->margin,
                'total_profit' => (float) $p->total_profit,
                'contribution' => $contribution,
                'unit' => $p->unit,
            ];
        });

        return inertia('Reports/ProductMargin', [
            'products' => $products,
            'totalProfit' => $totalProfit,
            'tenantSlug' => $tenant->slug,
            'tenantName' => $tenant->name,
        ]);
    }
}
