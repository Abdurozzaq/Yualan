<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DollarSign, Package, Users, ReceiptText, Zap, Award, ShoppingCart, Tag, Image as ImageIcon } from 'lucide-vue-next';
import { formatCurrency } from '@/utils/formatters';

// Define props to receive data from the controller
interface Sale {
    id: string;
    invoice_number: string;
    total_amount: number;
    status: string;
    customer?: { name: string } | null; // Customer might be null for general sales
    created_at: string;
}

interface TopProduct {
    product_name: string;
    product_image: string | null;
    total_quantity_sold: number;
}

const props = defineProps<{
    tenantSlug: string;
    tenantName: string;
    todaysSales: number;
    totalProducts: number;
    totalCustomers: number;
    recentSales: Sale[];
    topSellingProducts: TopProduct[]; // New prop for top selling products
    currentDateTime: string; // Formatted date-time string from backend
}>();

// Inertia page props
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
];

// Helper function for status badge colors
const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200';
        case 'cancelled': return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

// Helper function to format date and time for recent sales (only time needed as date is implied today)
const formatTime = (dateTimeString: string) => {
    return new Date(dateTimeString).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

</script>

<template>
    <Head :title="tenantName ? `Dashboard - ${tenantName}` : 'Dashboard'" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex flex-col gap-1 mb-2">
                <h1 class="text-2xl md:text-4xl font-black text-gray-900 dark:text-gray-100 tracking-tight">
                    Selamat Datang, {{ page.props.auth.user.name }}!
                </h1>
                <p class="text-muted-foreground text-base md:text-lg font-medium">
                    Dashboard untuk <span class="text-blue-600 dark:text-blue-400 font-bold">{{ tenantName }}</span>.
                </p>
            </div>

            <!-- Quick Stats Section -->
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Card 1: Total Penjualan Hari Ini -->
                <Card class="p-6 flex flex-col items-start gap-2 border-none rounded-2xl shadow-xl shadow-blue-100 dark:shadow-none bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
                    <div class="flex items-center gap-2 opacity-90">
                        <DollarSign class="h-5 w-5" />
                        <span class="text-sm font-bold uppercase tracking-wider">Penjualan Hari Ini</span>
                    </div>
                    <p class="text-3xl md:text-4xl font-black">{{ formatCurrency(todaysSales) }}</p>
                    <p class="text-xs opacity-75 font-medium mt-1">Data per {{ currentDateTime }}</p>
                </Card>
 
                <!-- Card 2: Total Produk Tersedia -->
                <Card class="p-6 flex flex-col items-start gap-2 border-none rounded-2xl shadow-xl shadow-emerald-100 dark:shadow-none bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
                    <div class="flex items-center gap-2 opacity-90">
                        <Package class="h-5 w-5" />
                        <span class="text-sm font-bold uppercase tracking-wider">Total Produk</span>
                    </div>
                    <p class="text-3xl md:text-4xl font-black">{{ totalProducts }}</p>
                    <p class="text-xs opacity-75 font-medium mt-1">Jumlah produk aktif</p>
                </Card>
 
                <!-- Card 3: Total Pelanggan -->
                <Card class="p-6 flex flex-col items-start gap-2 border-none rounded-2xl shadow-xl shadow-purple-100 dark:shadow-none bg-gradient-to-br from-purple-500 to-fuchsia-600 text-white">
                    <div class="flex items-center gap-2 opacity-90">
                        <Users class="h-5 w-5" />
                        <span class="text-sm font-bold uppercase tracking-wider">Total Pelanggan</span>
                    </div>
                    <p class="text-3xl md:text-4xl font-black">{{ totalCustomers }}</p>
                    <p class="text-xs opacity-75 font-medium mt-1">Pelanggan terdaftar</p>
                </Card>
            </div>

            <!-- Main Content Area: Recent Sales & Quick Actions -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Recent Sales/Transactions -->
                <Card class="p-6 flex flex-col border-none rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none bg-white dark:bg-gray-800">
                    <h3 class="text-xl font-black mb-6 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        <ReceiptText class="h-5 w-5 text-blue-600" /> Penjualan Terbaru
                    </h3>
                    <div v-if="recentSales.length === 0" class="text-muted-foreground text-center py-12">
                        <p class="text-lg font-medium">Belum ada penjualan terbaru.</p>
                        <p class="text-sm">Transaksi Anda akan muncul di sini.</p>
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="sale in recentSales" :key="sale.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-transparent hover:border-blue-100 dark:hover:border-blue-900/30 transition-all">
                            <div class="flex flex-col gap-0.5">
                                <p class="font-bold text-gray-900 dark:text-gray-100">{{ sale.invoice_number }}</p>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-tight">{{ sale.customer?.name || 'Umum' }} • {{ formatTime(sale.created_at) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="font-black text-lg text-gray-900 dark:text-gray-100">{{ formatCurrency(sale.total_amount) }}</span>
                                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest', getStatusColor(sale.status)]">
                                    {{ sale.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <Button variant="ghost" as-child class="w-full text-blue-600 dark:text-blue-400 font-bold hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl">
                            <Link :href="route('sales.history', { tenantSlug: tenantSlug })">Lihat Semua Penjualan</Link>
                        </Button>
                    </div>
                </Card>

                <!-- Quick Actions & Top Selling Products -->
                <div class="flex flex-col gap-4">
                    <Card class="p-6 border-none rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none bg-white dark:bg-gray-800">
                        <h3 class="text-xl font-black mb-6 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <Zap class="h-5 w-5 text-amber-500" /> Tindakan Cepat
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <Button as-child class="h-14 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-lg shadow-blue-200 dark:shadow-none transition-all active:scale-95">
                                <Link :href="route('sales.order', { tenantSlug: tenantSlug })">
                                    <ShoppingCart class="h-5 w-5 mr-2" /> POS Kasir
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="h-14 rounded-xl font-bold border-gray-200 dark:border-gray-700 transition-all active:scale-95">
                                <Link :href="route('products.index', { tenantSlug: tenantSlug })">
                                    <Package class="h-5 w-5 mr-2 text-emerald-500" /> Produk
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="h-14 rounded-xl font-bold border-gray-200 dark:border-gray-700 transition-all active:scale-95">
                                <Link :href="route('customers.index', { tenantSlug: tenantSlug })">
                                    <Users class="h-5 w-5 mr-2 text-purple-500" /> Pelanggan
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="h-14 rounded-xl font-bold border-gray-200 dark:border-gray-700 transition-all active:scale-95">
                                <Link :href="route('categories.index', { tenantSlug: tenantSlug })">
                                    <Tag class="h-5 w-5 mr-2 text-fuchsia-500" /> Kategori
                                </Link>
                            </Button>
                        </div>
                    </Card>

                    <!-- Top Selling Products -->
                    <Card class="p-6 border-none rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none bg-white dark:bg-gray-800">
                        <h3 class="text-xl font-black mb-6 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <Award class="h-5 w-5 text-rose-500" /> Produk Terlaris
                        </h3>
                        <div v-if="topSellingProducts.length === 0" class="text-muted-foreground text-center py-12">
                            <p class="text-lg font-medium">Belum ada data produk terlaris.</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="(product, index) in topSellingProducts" :key="index" class="flex items-center gap-4 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-transparent transition-all">
                                <div class="relative">
                                    <img
                                        v-if="product.product_image"
                                        :src="`/storage/${product.product_image}`"
                                        alt="Product Image"
                                        class="w-14 h-14 object-cover rounded-xl shadow-sm"
                                    />
                                    <div v-else class="w-14 h-14 bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center text-gray-400">
                                        <ImageIcon class="w-6 h-6" />
                                    </div>
                                    <div class="absolute -top-2 -left-2 bg-rose-500 text-white w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black border-2 border-white dark:border-gray-800 shadow-md">
                                        {{ index + 1 }}
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <p class="font-black text-gray-900 dark:text-gray-100 leading-tight">{{ product.product_name }}</p>
                                    <p class="text-sm font-bold text-rose-600 dark:text-rose-400 mt-0.5">Terjual: {{ product.total_quantity_sold }} unit</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
