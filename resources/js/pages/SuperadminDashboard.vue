<script setup lang="ts">
import SuperadminLayout from '@/layouts/app/SuperadminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3'; // Import Link and usePage
import { Card } from '@/components/ui/card'; // Import Card component
import { DollarSign, Package, Users, Store, CheckCircle, XCircle, Clock, TrendingUp, BarChart } from 'lucide-vue-next'; // Import icons
import { formatCurrency } from '@/utils/formatters'; // Import formatCurrency

// Define props to receive data from the controller
interface SuperadminStats {
    totalTenants: number;
    activeTenants: number;
    inactiveTenants: number;
    totalUsers: number;
    superadmins: number;
    admins: number;
    cashiers: number;
    totalProducts: number;
    totalProductStock: number;
    totalSalesAmount: number;
    totalCompletedSales: number;
    totalPendingSales: number;
    // New global analysis stats
    totalSalesLast7Days: number;
    newTenantsLast30Days: number;
    topProductCategoriesByProductCount: Array<{ category_name: string; product_count: number }>;
}

interface RecentTenant {
    id: string;
    name: string;
    slug: string;
    is_active: boolean;
    created_at: string;
}

interface TopTenantBySales {
    tenant_name: string;
    tenant_slug: string;
    total_sales_amount: number;
}


const page = usePage(); // To access auth.user for welcome message

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Superadmin Dashboard',
        href: route('superadmin.dashboard'), // Rute khusus superadmin
    },
];

// Helper function for tenant status badge colors
const getTenantStatusColor = (isActive: boolean) => {
    return isActive ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200';
};

// Helper function to format date for recent tenants
const formatDate = (dateTimeString: string) => {
    return new Date(dateTimeString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
// ...existing script code...
</script>

<template>
    <SuperadminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Selamat Datang, {{ page.props.auth.user.name }}!</h1>
            <p class="text-gray-600 dark:text-gray-400 text-lg">Ini adalah gambaran umum sistem Yualan POS Anda.</p>

            <!-- Global Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Tenants -->
                <Card class="p-6 flex flex-col items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <Store class="h-6 w-6" />
                        <span class="text-lg font-semibold">Total Tenant</span>
                    </div>
                    <!-- Updated font sizes for responsiveness -->
                    <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ stats.totalTenants }}</p>
                    <div class="text-sm text-muted-foreground">
                        <span class="text-green-600 dark:text-green-400 font-medium">{{ stats.activeTenants }} Aktif</span> |
                        <span class="text-red-600 dark:text-red-400 font-medium">{{ stats.inactiveTenants }} Nonaktif</span>
                    </div>
                </Card>

                <!-- Total Users -->
                <Card class="p-6 flex flex-col items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                        <Users class="h-6 w-6" />
                        <span class="text-lg font-semibold">Total Pengguna</span>
                    </div>
                    <!-- Updated font sizes for responsiveness -->
                    <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ stats.totalUsers }}</p>
                    <div class="text-sm text-muted-foreground">
                        <span class="font-medium">S: {{ stats.superadmins }}</span> |
                        <span class="font-medium">A: {{ stats.admins }}</span> |
                        <span class="font-medium">K: {{ stats.cashiers }}</span>
                    </div>
                </Card>

                <!-- Total Products -->
                <Card class="p-6 flex flex-col items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 text-orange-600 dark:text-orange-400">
                        <Package class="h-6 w-6" />
                        <span class="text-lg font-semibold">Total Produk</span>
                    </div>
                    <!-- Updated font sizes for responsiveness -->
                    <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ stats.totalProducts }}</p>
                    <p class="text-sm text-muted-foreground">Total Stok: {{ stats.totalProductStock }} unit</p>
                </Card>

                <!-- Total Sales Amount -->
                <Card class="p-6 flex flex-col items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <DollarSign class="h-6 w-6" />
                        <span class="text-lg font-semibold">Total Penjualan Selesai</span>
                    </div>
                    <!-- Updated font sizes for responsiveness -->
                    <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ formatCurrency(stats.totalSalesAmount) }}</p>
                    <div class="text-sm text-muted-foreground">
                        <span class="font-medium">{{ stats.totalCompletedSales }} Transaksi Selesai</span> |
                        <span class="font-medium">{{ stats.totalPendingSales }} Transaksi Pending</span>
                    </div>
                </Card>
            </div>

            <!-- Recent Tenants & Top Tenants by Sales -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Recent Tenants -->
                <Card class="p-6 flex flex-col border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <h3 class="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        <Clock class="h-5 w-5" /> Tenant Terbaru
                    </h3>
                    <div v-if="recentTenants.length === 0" class="text-muted-foreground text-center py-8">
                        Belum ada tenant yang terdaftar.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="tenant in recentTenants" :key="tenant.id" class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">
                            <div>
                                <Link :href="route('tenant.dashboard', { tenantSlug: tenant.slug })" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    {{ tenant.name }}
                                </Link>
                                <p class="text-sm text-muted-foreground">{{ tenant.slug }}</p>
                            </div>
                            <div class="flex flex-col items-end">
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-semibold', getTenantStatusColor(tenant.is_active)]">
                                    {{ tenant.is_active ? 'AKTIF' : 'NONAKTIF' }}
                                </span>
                                <p class="text-sm text-muted-foreground mt-1">{{ formatDate(tenant.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Top Tenants by Sales (last 30 days) -->
                <Card class="p-6 flex flex-col border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <h3 class="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        <TrendingUp class="h-5 w-5" /> Tenant Terlaris (30 Hari Terakhir)
                    </h3>
                    <div v-if="topTenantsBySales.length === 0" class="text-muted-foreground text-center py-8">
                        Belum ada data penjualan untuk 30 hari terakhir.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="(tenant, index) in topTenantsBySales" :key="tenant.tenant_slug" class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-lg text-gray-700 dark:text-gray-300">{{ index + 1 }}.</span>
                                <Link :href="route('tenant.dashboard', { tenantSlug: tenant.tenant_slug })" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    {{ tenant.tenant_name }}
                                </Link>
                            </div>
                            <span class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ formatCurrency(tenant.total_sales_amount) }}</span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Global System Overview (Dynamic Data) -->
            <Card class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                    <BarChart class="h-5 w-5" /> Analisis Sistem Global
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Total Sales Last 7 Days -->
                    <div class="flex flex-col gap-1">
                        <p class="text-sm text-muted-foreground">Total Penjualan (7 Hari Terakhir):</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(stats.totalSalesLast7Days) }}</p>
                    </div>

                    <!-- New Tenants Last 30 Days -->
                    <div class="flex flex-col gap-1">
                        <p class="text-sm text-muted-foreground">Tenant Baru (30 Hari Terakhir):</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.newTenantsLast30Days }}</p>
                    </div>

                    <!-- Top Product Categories by Product Count -->
                    <div class="col-span-1 md:col-span-2">
                        <p class="text-sm text-muted-foreground mb-2">Kategori Produk Teratas (Berdasarkan Jumlah Produk):</p>
                        <div v-if="stats.topProductCategoriesByProductCount.length === 0" class="text-muted-foreground">
                            Tidak ada kategori produk.
                        </div>
                        <ul v-else class="space-y-1">
                            <li v-for="(category, index) in stats.topProductCategoriesByProductCount" :key="index" class="flex justify-between items-center text-gray-800 dark:text-gray-200">
                                <span class="font-medium">{{ category.category_name }}</span>
                                <span class="text-sm text-muted-foreground">{{ category.product_count }} produk</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </Card>
        </div>
    </SuperadminLayout>
</template>
