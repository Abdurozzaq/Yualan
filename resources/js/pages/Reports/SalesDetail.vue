<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/utils/formatters';
import { FileText } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

interface SaleDetail {
    id: number;
    date: string;
    transaction_number: string;
    items_summary: string;
    payment_method: string;
    discount: number;
    tax: number;
    cashier: string;
    total: number;
}

const props = defineProps<{
    sales: SaleDetail[];
    tenantSlug: string;
    tenantName: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Laporan',
        href: '#',
    },
    {
        title: 'Penjualan Detail',
        href: route('reports.salesDetail', { tenantSlug: props.tenantSlug }),
    },
];

const sortedSales = computed(() => props.sales);

const exportToExcel = async () => {
    // Pastikan XLSX tersedia di window
    let XLSX = window.XLSX;
    if (!XLSX) {
        XLSX = await import('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js').then(mod => mod.default || window.XLSX);
        window.XLSX = XLSX;
    }

    // Prepare worksheet data
    const sheetData = [
        [
            'No.',
            'Tanggal',
            'No. Transaksi',
            'Item',
            'Metode Pembayaran',
            'Diskon',
            'Pajak',
            'Kasir',
            'Total'
        ],
        ...sortedSales.value.map((sale, idx) => [
            idx + 1,
            sale.date,
            sale.transaction_number,
            sale.items_summary,
            sale.payment_method,
            sale.discount,
            sale.tax,
            sale.cashier,
            sale.total
        ])
    ];

    // Tambahkan summary row di akhir
    if (sortedSales.value.length > 0) {
        sheetData.push([
            '', '', '', '', 'Total',
            summary.value.totalDiscount,
            summary.value.totalTax,
            '',
            summary.value.totalSales
        ]);
    }

    const ws = XLSX.utils.aoa_to_sheet(sheetData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Laporan Penjualan Detail');
    XLSX.writeFile(wb, `Laporan_Penjualan_Detail_${props.tenantName || 'Toko'}.xlsx`);
};

const filterType = ref<'day' | 'week' | 'month'>('day');
const filterDate = ref<string>(new Date().toISOString().slice(0, 10)); // default hari ini
const sortBy = ref<string>('date');
const sortDirection = ref<'asc' | 'desc'>('desc');

function updateFilter() {
    router.get(
        route('reports.salesDetail', { tenantSlug: props.tenantSlug }),
        {
            filterType: filterType.value,
            filterDate: filterDate.value,
            sortBy: sortBy.value,
            sortDirection: sortDirection.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

// Watch filter and sort changes
watch([filterType, filterDate, sortBy, sortDirection], updateFilter);

// filteredSales tidak perlu filter di frontend, gunakan props.sales langsung
const filteredSales = computed(() => props.sales);

// Summary computed
const summary = computed(() => {
    let totalDiscount = 0;
    let totalTax = 0;
    let totalSales = 0;
    for (const sale of filteredSales.value) {
        totalDiscount += sale.discount;
        totalTax += sale.tax;
        totalSales += sale.total;
    }
    return {
        totalDiscount,
        totalTax,
        totalSales,
    };
});

function handleSort(column: string) {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
}
</script>

<template>
    <Head title="Laporan Penjualan Detail" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Laporan Penjualan Detail {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <button @click="exportToExcel" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
                    <FileText class="h-5 w-5" />
                    Export Excel
                </button>
            </div>
            <!-- Filter Section -->
            <div class="flex items-center gap-4 mb-4">
                <label class="font-semibold">Periode:</label>
                <select v-model="filterType" class="border rounded px-2 py-1 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
                    <option value="day">Hari</option>
                    <option value="week">Minggu</option>
                    <option value="month">Bulan</option>
                </select>
                <input
                    v-if="filterType === 'day'"
                    type="date"
                    v-model="filterDate"
                    class="border rounded px-2 py-1"
                />
                <input
                    v-if="filterType === 'week'"
                    type="date"
                    v-model="filterDate"
                    class="border rounded px-2 py-1"
                />
                <input
                    v-if="filterType === 'month'"
                    type="month"
                    v-model="filterDate"
                    class="border rounded px-2 py-1"
                />
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Deskripsi Laporan</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Export transaksi penjualan lengkap (per hari/bulan/tahun), termasuk item, metode pembayaran, diskon, pajak, dan kasir. Cocok untuk audit dan rekap keuangan.
                </p>
            </div>
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead @click="handleSort('date')" class="cursor-pointer">
                                Tanggal
                                <span v-if="sortBy === 'date'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('transaction_number')" class="cursor-pointer">
                                No. Transaksi
                                <span v-if="sortBy === 'transaction_number'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('items_summary')" class="cursor-pointer">
                                Item
                                <span v-if="sortBy === 'items_summary'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('payment_method')" class="cursor-pointer">
                                Metode Pembayaran
                                <span v-if="sortBy === 'payment_method'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('discount')" class="cursor-pointer">
                                Diskon
                                <span v-if="sortBy === 'discount'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('tax')" class="cursor-pointer">
                                Pajak
                                <span v-if="sortBy === 'tax'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('cashier')" class="cursor-pointer">
                                Kasir
                                <span v-if="sortBy === 'cashier'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('total')" class="cursor-pointer">
                                Total
                                <span v-if="sortBy === 'total'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="filteredSales.length === 0">
                            <TableCell colspan="9" class="text-center text-muted-foreground py-8">
                                Belum ada data penjualan.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(sale, index) in filteredSales" :key="sale.id">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell>{{ sale.date }}</TableCell>
                            <TableCell>{{ sale.transaction_number }}</TableCell>
                            <TableCell>{{ sale.items_summary }}</TableCell>
                            <TableCell>{{ sale.payment_method }}</TableCell>
                            <TableCell>{{ formatCurrency(sale.discount) }}</TableCell>
                            <TableCell>{{ formatCurrency(sale.tax) }}</TableCell>
                            <TableCell>{{ sale.cashier }}</TableCell>
                            <TableCell class="font-semibold">{{ formatCurrency(sale.total) }}</TableCell>
                        </TableRow>
                        <!-- Summary Row -->
                        <TableRow v-if="filteredSales.length > 0" class="bg-gray-100 dark:bg-gray-900 font-bold">
                            <TableCell colspan="5" class="text-right">Total</TableCell>
                            <TableCell>{{ formatCurrency(summary.totalDiscount) }}</TableCell>
                            <TableCell>{{ formatCurrency(summary.totalTax) }}</TableCell>
                            <TableCell></TableCell>
                            <TableCell class="font-semibold">{{ formatCurrency(summary.totalSales) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mt-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Informasi Laporan Penjualan Detail</h3>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-4">
                    <li>Menampilkan seluruh transaksi penjualan beserta detail item, diskon, pajak, dan kasir.</li>
                    <li>Dapat diexport ke Excel untuk kebutuhan audit dan rekap keuangan.</li>
                    <li>Filter dan pencarian dapat ditambahkan sesuai kebutuhan.</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>