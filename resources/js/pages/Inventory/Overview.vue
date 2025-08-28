<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ChevronUp, ChevronDown, Search, AlertTriangle, CheckCircle } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/utils/formatters'; // Import the formatter

// Props from controller
interface Product {
    id: string;
    name: string;
    sku: string | null;
    stock: number;
    cost_price: number;
    price: number;
    unit: string | null;
    category?: { name: string } | null;
}

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    sortBy: string;
    sortDirection: 'asc' | 'desc';
    perPage: number;
    search: string | null;
}

const props = defineProps<{
    products: PaginatedProducts;
    filters: Filters;
    tenantSlug: string;
    tenantName: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Inventaris',
        href: '#', // Placeholder for Inventory main page if any
    },
    {
        title: 'Ringkasan Inventaris',
        href: route('inventory.overview', { tenantSlug: props.tenantSlug }),
    },
];

// Reactive state for filters and sorting
const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');

// Function to handle sorting
const handleSort = (field: string) => {
    if (currentSortBy.value === field) {
        currentSortDirection.value = currentSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortBy.value = field;
        currentSortDirection.value = 'asc';
    }
};

// Watch for changes in filters and trigger Inertia visit
watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch], () => {
    router.get(route('inventory.overview', { tenantSlug: props.tenantSlug }), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['products', 'filters'],
    });
}, { deep: true });

// Function to apply search filter on input change (e.g., debounce)
let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('inventory.overview', { tenantSlug: props.tenantSlug }), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['products', 'filters'],
        });
    }, 300); // Debounce for 300ms
};

// Function to determine stock status color/icon
const getStockStatus = (stock: number) => {
    if (stock <= 0) {
        return { text: 'Stok Habis', class: 'text-red-600 dark:text-red-400', icon: AlertTriangle };
    } else if (stock <= 5) { // Assuming 5 is a low stock threshold
        return { text: 'Stok Rendah', class: 'text-orange-600 dark:text-orange-400', icon: AlertTriangle };
    } else {
        return { text: 'Tersedia', class: 'text-green-600 dark:text-green-400', icon: CheckCircle };
    }
};
</script>

<template>
    <Head title="Ringkasan Inventaris" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Ringkasan Inventaris {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>

            <!-- Filter and Search Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari produk berdasarkan nama atau SKU..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model.number="currentPerPage">
                        <SelectTrigger class="w-full sm:w-[100px]">
                            <SelectValue placeholder="Per Halaman" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="5">5</SelectItem>
                            <SelectItem :value="10">10</SelectItem>
                            <SelectItem :value="25">25</SelectItem>
                            <SelectItem :value="50">50</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Product List Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('name')"
                            >
                                <div class="flex items-center gap-1">
                                    Nama Produk
                                    <template v-if="currentSortBy === 'name'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Kategori</TableHead>
                            <TableHead>Stok Saat Ini</TableHead>
                            <TableHead>Unit</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('cost_price')"
                            >
                                <div class="flex items-center gap-1">
                                    Harga Pokok Rata-rata
                                    <template v-if="currentSortBy === 'cost_price'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('price')"
                            >
                                <div class="flex items-center gap-1">
                                    Harga Jual
                                    <template v-if="currentSortBy === 'price'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Status Stok</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.products.data.length === 0">
                            <TableCell colspan="9" class="text-center text-muted-foreground py-8">
                                Belum ada produk yang ditambahkan atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(product, index) in props.products.data" :key="product.id">
                            <TableCell>{{ props.products.from + index }}</TableCell>
                            <TableCell class="font-medium">{{ product.name }}</TableCell>
                            <TableCell>{{ product.sku || '-' }}</TableCell>
                            <TableCell>{{ product.category?.name || 'Uncategorized' }}</TableCell>
                            <TableCell>{{ product.stock }}</TableCell>
                            <TableCell>{{ product.unit || '-' }}</TableCell>
                            <TableCell>{{ formatCurrency(product.cost_price) }}</TableCell>
                            <TableCell>{{ formatCurrency(product.price) }}</TableCell>
                            <TableCell>
                                <span :class="['px-2 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1', getStockStatus(product.stock).class]">
                                    <component :is="getStockStatus(product.stock).icon" class="h-3 w-3" />
                                    {{ getStockStatus(product.stock).text }}
                                </span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Controls -->
            <div v-if="props.products.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.products.from }} hingga {{ props.products.to }} dari {{ props.products.total }} produk
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.products.links"
                        :key="index"
                        :as="Link"
                        :href="link.url || '#'"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        class="px-3 py-1 rounded-md text-sm"
                        >
                            <span v-html="link.label"></span>
                 </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
