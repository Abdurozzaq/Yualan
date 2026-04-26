<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/utils/formatters';
import { FileText } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import * as XLSX from 'xlsx'; // <--- Tambahkan import package xlsx

// Struktur data untuk pembayaran dan piutang
interface PaymentReportRow {
    id: string;
    date: string;
    invoice_number: string;
    customer_name: string;
    payment_method: string;
    status: string;
    total_amount: number;
    paid_amount: number;
    outstanding_amount: number;
    notes?: string;
}

const props = defineProps<{
    payments: PaymentReportRow[]; // gabungan dari payments dan sales
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
        title: 'Pembayaran & Piutang',
        href: route('reports.payments', { tenantSlug: props.tenantSlug }),
    },
];

// Sorting
const sortBy = ref<string>('date');
const sortDirection = ref<'asc' | 'desc'>('desc');
function handleSort(column: string) {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
}

// Filter
const filterType = ref<'all' | 'paid' | 'outstanding'>('all');
const filterDate = ref<string>('');

function updateFilter() {
    router.get(
        route('reports.payments', { tenantSlug: props.tenantSlug }),
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
watch([filterType, filterDate, sortBy, sortDirection], updateFilter);

// Filtered & sorted data
const filteredPayments = computed(() => {
    let rows = props.payments;
    if (filterType.value === 'paid') {
        rows = rows.filter(r => r.outstanding_amount === 0);
    } else if (filterType.value === 'outstanding') {
        rows = rows.filter(r => r.outstanding_amount > 0);
    }
    // Sorting
    rows = [...rows].sort((a, b) => {
        const valA = a[sortBy.value as keyof PaymentReportRow];
        const valB = b[sortBy.value as keyof PaymentReportRow];
        if (typeof valA === 'string' && typeof valB === 'string') {
            return sortDirection.value === 'asc'
                ? valA.localeCompare(valB)
                : valB.localeCompare(valA);
        }
        if (typeof valA === 'number' && typeof valB === 'number') {
            return sortDirection.value === 'asc'
                ? valA - valB
                : valB - valA;
        }
        return 0;
    });
    return rows;
});

// Summary
const summary = computed(() => {
    let totalPaid = 0;
    let totalOutstanding = 0;
    let totalAmount = 0;
    for (const row of filteredPayments.value) {
        totalPaid += row.paid_amount;
        totalOutstanding += row.outstanding_amount;
        totalAmount += row.total_amount;
    }
    return {
        totalPaid,
        totalOutstanding,
        totalAmount,
    };
});

// Export Excel
const exportToExcel = async () => {
    // gunakan package xlsx langsung
    const sheetData = [
        [
            'No.',
            'Tanggal',
            'Invoice',
            'Customer',
            'Metode Pembayaran',
            'Status',
            'Total',
            'Terbayar',
            'Outstanding',
            'Catatan'
        ],
        ...filteredPayments.value.map((row, idx) => [
            idx + 1,
            row.date,
            row.invoice_number,
            row.customer_name,
            row.payment_method,
            row.status,
            row.total_amount,
            row.paid_amount,
            row.outstanding_amount,
            row.notes || ''
        ])
    ];
    // Summary row
    if (filteredPayments.value.length > 0) {
        sheetData.push([
            '', '', '', '', 'Total',
            '',
            summary.value.totalAmount,
            summary.value.totalPaid,
            summary.value.totalOutstanding,
            ''
        ]);
    }
    const ws = XLSX.utils.aoa_to_sheet(sheetData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Laporan Pembayaran & Piutang');
    XLSX.writeFile(wb, `Laporan_Pembayaran_Piutang_${props.tenantName || 'Toko'}.xlsx`);
};
</script>

<template>
    <Head title="Laporan Pembayaran & Piutang" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h1 class="text-xl md:text-2xl font-black text-gray-900 dark:text-gray-100">
                    Pembayaran & Piutang {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <button @click="exportToExcel" class="w-full sm:w-auto h-11 sm:h-10 rounded-xl bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 dark:shadow-none flex items-center justify-center gap-2 font-bold text-white transition-all active:scale-95">
                    <FileText class="h-5 w-5" />
                    Export Excel
                </button>
            </div>
            <!-- Filter Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-6 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <label class="font-bold uppercase tracking-wider text-[10px] text-gray-500 whitespace-nowrap">Status:</label>
                    <select v-model="filterType" class="flex-grow sm:flex-grow-0 h-11 sm:h-10 px-4 border rounded-xl bg-white dark:bg-gray-900 text-sm shadow-sm outline-none cursor-pointer">
                        <option value="all">Semua</option>
                        <option value="paid">Lunas</option>
                        <option value="outstanding">Belum Lunas</option>
                    </select>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <label class="font-bold uppercase tracking-wider text-[10px] text-gray-500 whitespace-nowrap">Tanggal:</label>
                    <input
                        type="date"
                        v-model="filterDate"
                        class="flex-grow sm:flex-grow-0 h-11 sm:h-10 px-4 border rounded-xl bg-white dark:bg-gray-900 text-sm shadow-sm outline-none"
                    />
                </div>
            </div>
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none p-8 border border-gray-100 dark:border-gray-700 transition-transform hover:scale-[1.02] duration-300">
                    <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 mb-2 uppercase tracking-widest">Total Penjualan</h3>
                    <p class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ formatCurrency(summary.totalAmount) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none p-8 border border-gray-100 dark:border-gray-700 transition-transform hover:scale-[1.02] duration-300">
                    <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 mb-2 uppercase tracking-widest">Total Terbayar</h3>
                    <p class="text-2xl font-black text-green-600 dark:text-green-400">{{ formatCurrency(summary.totalPaid) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none p-8 border border-gray-100 dark:border-gray-700 transition-transform hover:scale-[1.02] duration-300">
                    <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 mb-2 uppercase tracking-widest">Total Piutang (Outstanding)</h3>
                    <p class="text-2xl font-black text-red-600 dark:text-red-400">{{ formatCurrency(summary.totalOutstanding) }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none p-8 border border-gray-100 dark:border-gray-700 mb-8 animate-fade-in">
                <h3 class="text-xl font-black text-gray-900 dark:text-white mb-4">Deskripsi Laporan</h3>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-lg">
                    Laporan pembayaran dan piutang, menampilkan status pembayaran, customer, invoice, jumlah terbayar, dan outstanding. Cocok untuk monitoring cashflow dan penagihan.
                </p>
            </div>
            <div class="rounded-2xl border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
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
                            <TableHead @click="handleSort('invoice_number')" class="cursor-pointer">
                                Invoice
                                <span v-if="sortBy === 'invoice_number'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('customer_name')" class="cursor-pointer">
                                Customer
                                <span v-if="sortBy === 'customer_name'">
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
                            <TableHead @click="handleSort('status')" class="cursor-pointer">
                                Status
                                <span v-if="sortBy === 'status'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('total_amount')" class="cursor-pointer">
                                Total
                                <span v-if="sortBy === 'total_amount'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('paid_amount')" class="cursor-pointer">
                                Terbayar
                                <span v-if="sortBy === 'paid_amount'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead @click="handleSort('outstanding_amount')" class="cursor-pointer">
                                Outstanding
                                <span v-if="sortBy === 'outstanding_amount'">
                                    <span v-if="sortDirection === 'asc'">&#9650;</span>
                                    <span v-else>&#9660;</span>
                                </span>
                            </TableHead>
                            <TableHead>Catatan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="filteredPayments.length === 0">
                            <TableCell colspan="10" class="text-center text-muted-foreground py-8">
                                Belum ada data pembayaran/piutang.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(row, index) in filteredPayments" :key="row.id">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell>{{ row.date }}</TableCell>
                            <TableCell>{{ row.invoice_number }}</TableCell>
                            <TableCell>{{ row.customer_name || 'UMUM' }}</TableCell>
                            <TableCell>{{ row.payment_method }}</TableCell>
                            <TableCell>
                                <span :class="row.outstanding_amount === 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ row.outstanding_amount === 0 ? 'Lunas' : 'Belum Lunas' }}
                                </span>
                            </TableCell>
                            <TableCell>{{ formatCurrency(row.total_amount) }}</TableCell>
                            <TableCell>{{ formatCurrency(row.paid_amount) }}</TableCell>
                            <TableCell>{{ formatCurrency(row.outstanding_amount) }}</TableCell>
                            <TableCell>{{ row.notes }}</TableCell>
                        </TableRow>
                        <!-- Summary Row -->
                        <TableRow v-if="filteredPayments.length > 0" class="bg-gray-100 dark:bg-gray-900 font-bold">
                            <TableCell colspan="6" class="text-right">Total</TableCell>
                            <TableCell>{{ formatCurrency(summary.totalAmount) }}</TableCell>
                            <TableCell>{{ formatCurrency(summary.totalPaid) }}</TableCell>
                            <TableCell>{{ formatCurrency(summary.totalOutstanding) }}</TableCell>
                            <TableCell></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mt-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Informasi Laporan Pembayaran & Piutang</h3>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-4">
                    <li>Menampilkan seluruh pembayaran dan piutang customer.</li>
                    <li>Dapat diexport ke Excel untuk kebutuhan audit dan penagihan.</li>
                    <li>Filter status lunas/belum lunas dan tanggal transaksi.</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>