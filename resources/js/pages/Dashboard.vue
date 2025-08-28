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
            <!-- Header with Welcome Message -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Selamat Datang, {{ page.props.auth.user.name }}!
            </h1>
            <p class="text-muted-foreground text-lg">
                Dashboard untuk {{ tenantName }}.
            </p>

            <!-- Quick Stats Section -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Card 1: Total Penjualan Hari Ini -->
                <Card class="p-6 flex flex-col items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <DollarSign class="h-6 w-6" />
                        <span class="text-lg font-semibold">Penjualan Hari Ini</span>
                    </div>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ formatCurrency(todaysSales) }}</p>
                    <p class="text-sm text-muted-foreground">Data per {{ currentDateTime }}</p>
                </Card>

                <!-- Card 2: Total Produk Tersedia -->
                <Card class="p-6 flex flex-col items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <Package class="h-6 w-6" />
                        <span class="text-lg font-semibold">Total Produk</span>
                    </div>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ totalProducts }}</p>
                    <p class="text-sm text-muted-foreground">Jumlah produk yang terdaftar</p>
                </Card>

                <!-- Card 3: Total Pelanggan -->
                <Card class="p-6 flex flex-col items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                        <Users class="h-6 w-6" />
                        <span class="text-lg font-semibold">Total Pelanggan</span>
                    </div>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ totalCustomers }}</p>
                    <p class="text-sm text-muted-foreground">Jumlah pelanggan terdaftar</p>
                </Card>
            </div>

            <!-- Main Content Area: Recent Sales & Quick Actions -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Recent Sales/Transactions -->
                <Card class="p-6 flex flex-col border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <h3 class="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        <ReceiptText class="h-5 w-5" /> Penjualan Terbaru
                    </h3>
                    <div v-if="recentSales.length === 0" class="text-muted-foreground text-center py-8">
                        Belum ada penjualan terbaru.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="sale in recentSales" :key="sale.id" class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ sale.invoice_number }}</p>
                                <p class="text-sm text-muted-foreground">{{ sale.customer?.name || 'Umum' }} - {{ formatTime(sale.created_at) }}</p>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ formatCurrency(sale.total_amount) }}</span>
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-semibold', getStatusColor(sale.status)]">
                                    {{ sale.status.toUpperCase() }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <Link :href="route('sales.history', { tenantSlug: tenantSlug })" class="text-blue-600 hover:underline text-sm">Lihat Semua Penjualan</Link>
                    </div>
                </Card>

                <!-- Quick Actions & Top Selling Products -->
                <div class="flex flex-col gap-4">
                    <Card class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <Zap class="h-5 w-5" /> Tindakan Cepat
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Button as-child class="w-full">
                                <Link :href="route('sales.order', { tenantSlug: tenantSlug })">
                                    <ShoppingCart class="h-4 w-4 mr-2" /> Buat Pesanan Baru
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="w-full">
                                <Link :href="route('products.index', { tenantSlug: tenantSlug })">
                                    <Package class="h-4 w-4 mr-2" /> Kelola Produk
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="w-full">
                                <Link :href="route('customers.index', { tenantSlug: tenantSlug })">
                                    <Users class="h-4 w-4 mr-2" /> Kelola Pelanggan
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="w-full">
                                <Link :href="route('categories.index', { tenantSlug: tenantSlug })">
                                    <Tag class="h-4 w-4 mr-2" /> Kelola Kategori
                                </Link>
                            </Button>
                        </div>
                    </Card>

                    <!-- Top Selling Products -->
                    <Card class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <Award class="h-5 w-5" /> Produk Terlaris
                        </h3>
                        <div v-if="topSellingProducts.length === 0" class="text-muted-foreground text-center py-8">
                            Belum ada data produk terlaris.
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="(product, index) in topSellingProducts" :key="index" class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">
                                <img
                                    v-if="product.product_image"
                                    :src="`/storage/${product.product_image}`"
                                    alt="Product Image"
                                    class="w-12 h-12 object-cover rounded-md flex-shrink-0"
                                />
                                <div v-else class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-md flex items-center justify-center text-gray-500 dark:text-gray-400 flex-shrink-0">
                                    <ImageIcon class="w-6 h-6" />
                                </div>
                                <div class="flex-grow">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ product.product_name }}</p>
                                    <p class="text-sm text-muted-foreground">Terjual: {{ product.total_quantity_sold }} unit</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
