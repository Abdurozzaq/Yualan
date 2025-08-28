<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle, Eye, ChevronUp, ChevronDown, Search, ReceiptText, CheckCircle, XCircle, Clock } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Props from controller
interface Sale {
    id: string;
    invoice_number: string;
    total_amount: number;
    payment_method: string;
    status: string;
    created_at: string;
    user: { name: string }; // Cashier
    customer: { name: string } | null;
}

interface PaginatedSales {
    data: Sale[];
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
    filterField: string | null;
    statusFilter: string | null;
}

const props = defineProps<{
    sales: PaginatedSales;
    filters: Filters;
    tenantSlug: string;
    tenantName: string;
}>();

// Inertia page props

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Riwayat Penjualan',
        href: route('sales.history', { tenantSlug: props.tenantSlug }),
    },
];

// Reactive state for filters and sorting
const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');
const currentFilterField = ref(props.filters.filterField || 'invoice_number'); // Default filter field
const currentStatusFilter = ref(props.filters.statusFilter || 'all');

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
watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch, currentFilterField, currentStatusFilter], () => {
    router.get(route('sales.history', { tenantSlug: props.tenantSlug }), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
        filterField: currentFilterField.value,
        statusFilter: currentStatusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['sales', 'filters'],
    });
}, { deep: true });

// Function to apply search filter on input change (e.g., debounce)
let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('sales.history', { tenantSlug: props.tenantSlug }), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
            filterField: currentFilterField.value,
            statusFilter: currentStatusFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['sales', 'filters'],
        });
    }, 300); // Debounce for 300ms
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed': return 'text-green-600 bg-green-100 dark:bg-green-800 dark:text-green-200';
        case 'pending': return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-800 dark:text-yellow-200';
        case 'cancelled':
        case 'refunded': return 'text-red-600 bg-red-100 dark:bg-red-800 dark:text-red-200';
        default: return 'text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed': return CheckCircle;
        case 'pending': return Clock;
        case 'cancelled':
        case 'refunded': return XCircle;
        default: return null;
    }
};

const formatDateTime = (dateTimeString: string) => {
    return new Date(dateTimeString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Riwayat Penjualan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Riwayat Penjualan {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>

            <!-- Filter and Search Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative flex-grow">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari transaksi..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model="currentFilterField">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filter Berdasarkan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="invoice_number">Nomor Invoice</SelectItem>
                            <SelectItem value="payment_method">Metode Pembayaran</SelectItem>
                            <SelectItem value="status">Status</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model="currentStatusFilter">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filter Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                            <SelectItem value="refunded">Refunded</SelectItem>
                        </SelectContent>
                    </Select>
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

            <!-- Sales List Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('invoice_number')"
                            >
                                <div class="flex items-center gap-1">
                                    Invoice
                                    <template v-if="currentSortBy === 'invoice_number'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Kasir</TableHead>
                            <TableHead>Pelanggan</TableHead>
                            <TableHead>Metode Pembayaran</TableHead>
                            <TableHead>Jumlah Total</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('status')"
                            >
                                <div class="flex items-center gap-1">
                                    Status
                                    <template v-if="currentSortBy === 'status'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead class="w-[80px] text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.sales.data.length === 0">
                            <TableCell colspan="9" class="text-center text-muted-foreground py-8">
                                Belum ada penjualan yang tercatat atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(sale, index) in props.sales.data" :key="sale.id">
                            <TableCell>{{ props.sales.from + index }}</TableCell>
                            <TableCell class="font-medium">{{ sale.invoice_number }}</TableCell>
                            <TableCell>{{ formatDateTime(sale.created_at) }}</TableCell>
                            <TableCell>{{ sale.user.name }}</TableCell>
                            <TableCell>{{ sale.customer?.name || 'Umum' }}</TableCell>
                            <TableCell>{{ sale.payment_method.toUpperCase() }}</TableCell>
                            <TableCell>Rp{{ sale.total_amount.toLocaleString('id-ID') }}</TableCell>
                            <TableCell>
                                <span :class="['px-2 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1', getStatusColor(sale.status)]">
                                    <component :is="getStatusIcon(sale.status)" class="h-3 w-3" />
                                    {{ sale.status.toUpperCase() }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="route('sales.receipt', { tenantSlug: tenantSlug, sale: sale.id })">
                                    <Button variant="ghost" size="icon">
                                        <Eye class="h-4 w-4" />
                                        <span class="sr-only">Lihat Resi</span>
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Controls -->
            <div v-if="props.sales.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.sales.from }} hingga {{ props.sales.to }} dari {{ props.sales.total }} penjualan
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.sales.links"
                        :key="index"
                        :as="Link"
                        :href="link.url || '#'"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        class="px-3 py-1 rounded-md text-sm"
                        >{{ link.label }}
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

