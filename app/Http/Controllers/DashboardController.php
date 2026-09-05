<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon; // Import Carbon for date manipulation
use Illuminate\Support\Facades\DB; // Import DB facade for raw queries

class DashboardController extends Controller
{
    /**
     * Display the dashboard for a specific tenant with real-time data.
     */
    public function index(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        $tenantId = $tenant->id;

        // 1. Total Penjualan Hari Ini
        $todaysSales = Sale::where('tenant_id', $tenantId)
                            ->whereDate('created_at', Carbon::today())
                            ->where('status', 'completed') // Hanya hitung penjualan yang sudah selesai
                            ->sum('total_amount');

        // 2. Total Produk Tersedia
        $totalProducts = Product::where('tenant_id', $tenantId)->count();

        // 3. Total Pelanggan
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();

        // 4. Penjualan Terbaru (misalnya 5 transaksi terakhir yang sudah selesai atau pending)
        $recentSales = Sale::where('tenant_id', $tenantId)
                            ->whereIn('status', ['completed', 'pending']) // Ambil yang completed atau pending
                            ->with(['customer', 'user']) // Eager load customer and user details
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();

        // 5. Produk Terlaris (Top 5 berdasarkan kuantitas terjual)
        $topSellingProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'completed') // Hanya hitung dari penjualan yang selesai
            ->select(
                'products.name as product_name',
                'products.image as product_image', // Ambil gambar produk
                DB::raw('SUM(sale_items.quantity) as total_quantity_sold')
            )
            ->groupBy('products.id', 'products.name', 'products.image') // Group by product ID, name, and image
            ->orderByDesc('total_quantity_sold')
            ->limit(5)
            ->get();


        return Inertia::render('Dashboard', [
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
            'todaysSales' => $todaysSales,
            'totalProducts' => $totalProducts,
            'totalCustomers' => $totalCustomers,
            'recentSales' => $recentSales,
            'topSellingProducts' => $topSellingProducts,
            'currentDateTime' => Carbon::now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm'),
            'subscriptionSuccess' => session('subscription_success'),
            'error' => session('error'),
        ]);
    }
}
