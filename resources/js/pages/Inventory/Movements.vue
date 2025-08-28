<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ChevronUp, ChevronDown, Search, ArrowDownCircle, ArrowUpCircle, RefreshCcw, MinusCircle } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/utils/formatters'; // Import the formatter

// Props from controller
interface Movement {
    id: string;
    tenant_id: string;
    product_id: string;
    quantity_change: number;
    cost_per_unit: number;
    type: string; // e.g., 'in', 'out', 'adjustment', 'sale', 'return'
    reason: string | null;
    source_id: string | null;
    source_type: string | null;
    created_at: string;
    product: {
        name: string;
        sku: string | null;
        unit: string | null;
    };
}

interface PaginatedMovements {
    data: Movement[];
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
    typeFilter: string | null;
}

const props = defineProps<{
    movements: PaginatedMovements;
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
        title: 'Riwayat Pergerakan',
        href: route('inventory.movements', { tenantSlug: props.tenantSlug }),
    },
];

// Reactive state for filters and sorting
const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');
const currentTypeFilter = ref(props.filters.typeFilter || 'all');

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
watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch, currentTypeFilter], () => {
    router.get(route('inventory.movements', { tenantSlug: props.tenantSlug }), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
        typeFilter: currentTypeFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['movements', 'filters'],
    });
}, { deep: true });

// Function to apply search filter on input change (e.g., debounce)
let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('inventory.movements', { tenantSlug: props.tenantSlug }), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
            typeFilter: currentTypeFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['movements', 'filters'],
        });
    }, 300); // Debounce for 300ms
};

const getMovementTypeDisplay = (type: string) => {
    switch (type) {
        case 'in': return { text: 'Penerimaan', icon: ArrowDownCircle, class: 'text-green-600' };
        case 'out': return { text: 'Pengeluaran', icon: ArrowUpCircle, class: 'text-red-600' };
        case 'adjustment': return { text: 'Penyesuaian', icon: RefreshCcw, class: 'text-blue-600' };
        case 'sale': return { text: 'Penjualan', icon: ArrowUpCircle, class: 'text-red-600' }; // Sales are 'out'
        case 'return': return { text: 'Pengembalian', icon: ArrowDownCircle, class: 'text-green-600' }; // Returns are 'in'
        default: return { text: type, icon: null, class: 'text-gray-600' };
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
    <Head title="Riwayat Pergerakan Inventaris" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Riwayat Pergerakan Inventaris {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>

            <!-- Filter and Search Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative flex-grow">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari berdasarkan alasan atau nama produk..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model="currentTypeFilter">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filter Tipe Pergerakan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Tipe</SelectItem>
                            <SelectItem value="in">Penerimaan</SelectItem>
                            <SelectItem value="out">Pengeluaran</SelectItem>
                            <SelectItem value="adjustment">Penyesuaian</SelectItem>
                            <SelectItem value="sale">Penjualan</SelectItem>
                            <SelectItem value="return">Pengembalian</SelectItem>
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

            <!-- Movement List Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('created_at')"
                            >
                                <div class="flex items-center gap-1">
                                    Tanggal & Waktu
                                    <template v-if="currentSortBy === 'created_at'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Produk</TableHead>
                            <TableHead>Tipe</TableHead>
                            <TableHead>Perubahan Kuantitas</TableHead>
                            <TableHead>Harga Pokok per Unit</TableHead>
                            <TableHead>Total Biaya Pergerakan</TableHead>
                            <TableHead>Alasan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.movements.data.length === 0">
                            <TableCell colspan="8" class="text-center text-muted-foreground py-8">
                                Belum ada pergerakan inventaris yang tercatat atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(movement, index) in props.movements.data" :key="movement.id">
                            <TableCell>{{ props.movements.from + index }}</TableCell>
                            <TableCell>{{ formatDateTime(movement.created_at) }}</TableCell>
                            <TableCell class="font-medium">
                                {{ movement.product.name }}
                                <span v-if="movement.product.sku" class="text-xs text-muted-foreground">({{ movement.product.sku }})</span>
                            </TableCell>
                            <TableCell>
                                <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold', getMovementTypeDisplay(movement.type).class]">
                                    <component :is="getMovementTypeDisplay(movement.type).icon" class="h-3 w-3" />
                                    {{ getMovementTypeDisplay(movement.type).text }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span :class="movement.quantity_change > 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ movement.quantity_change > 0 ? '+' : '' }}{{ movement.quantity_change }} {{ movement.product.unit || 'pcs' }}
                                </span>
                            </TableCell>
                            <TableCell>{{ formatCurrency(movement.cost_per_unit) }}</TableCell>
                            <TableCell class="font-semibold">{{ formatCurrency(Math.abs(movement.quantity_change * movement.cost_per_unit)) }}</TableCell>
                            <TableCell>{{ movement.reason || '-' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Controls -->
            <div v-if="props.movements.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.movements.from }} hingga {{ props.movements.to }} dari {{ props.movements.total }} pergerakan
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.movements.links"
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
