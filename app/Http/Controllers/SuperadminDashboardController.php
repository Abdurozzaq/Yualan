<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Category; // Import Category model
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuperadminDashboardController extends Controller
{
    /**
     * Display the superadmin dashboard with global statistics.
     */
    public function index(): Response
    {
        // Total Tenants
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $inactiveTenants = Tenant::where('is_active', false)->count();

        // Total Users
        $totalUsers = User::count();
        $superadmins = User::where('role', 'superadmin')->count();
        $admins = User::where('role', 'admin')->count();
        $cashiers = User::where('role', 'cashier')->count();

        // Total Products (across all tenants)
        $totalProducts = Product::count();
        $totalProductStock = Product::sum('stock');

        // Total Sales (across all tenants)
        $totalSalesAmount = Sale::where('status', 'completed')->sum('total_amount');
        $totalCompletedSales = Sale::where('status', 'completed')->count();
        $totalPendingSales = Sale::where('status', 'pending')->count();

        // Recent Tenants (e.g., last 5 registered tenants)
        $recentTenants = Tenant::orderBy('created_at', 'desc')->limit(5)->get();

        // Top 5 Tenants by Sales Amount (last 30 days)
        $last30Days = Carbon::now()->subDays(30);
        $topTenantsBySales = DB::table('sales')
            ->join('tenants', 'sales.tenant_id', '=', 'tenants.id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $last30Days)
            ->select(
                'tenants.name as tenant_name',
                'tenants.slug as tenant_slug',
                DB::raw('SUM(sales.total_amount) as total_sales_amount')
            )
            ->groupBy('tenants.id', 'tenants.name', 'tenants.slug')
            ->orderByDesc('total_sales_amount')
            ->limit(5)
            ->get();

        // New Global Analysis Stats
        // Total Sales Last 7 Days
        $last7Days = Carbon::now()->subDays(7);
        $totalSalesLast7Days = Sale::where('status', 'completed')
            ->where('created_at', '>=', $last7Days)
            ->sum('total_amount');

        // New Tenants Last 30 Days
        $newTenantsLast30Days = Tenant::where('created_at', '>=', $last30Days)->count();

        // Top Product Categories by Product Count
        $topProductCategoriesByProductCount = Category::select('name as category_name')
            ->withCount('products') // Assuming you have a 'products' relationship in Category model
            ->orderByDesc('products_count')
            ->limit(5)
            ->get();

        return Inertia::render('SuperadminDashboard', [
            'stats' => [
                'totalTenants' => $totalTenants,
                'activeTenants' => $activeTenants,
                'inactiveTenants' => $inactiveTenants,
                'totalUsers' => $totalUsers,
                'superadmins' => $superadmins,
                'admins' => $admins,
                'cashiers' => $cashiers,
                'totalProducts' => $totalProducts,
                'totalProductStock' => $totalProductStock,
                'totalSalesAmount' => $totalSalesAmount,
                'totalCompletedSales' => $totalCompletedSales,
                'totalPendingSales' => $totalPendingSales,
                // New global analysis stats
                'totalSalesLast7Days' => $totalSalesLast7Days,
                'newTenantsLast30Days' => $newTenantsLast30Days,
                'topProductCategoriesByProductCount' => $topProductCategoriesByProductCount,
            ],
            'recentTenants' => $recentTenants,
            'topTenantsBySales' => $topTenantsBySales,
        ]);
    }
}
