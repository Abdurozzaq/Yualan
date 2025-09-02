<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/utils/formatters';
import { CalendarIcon } from 'lucide-vue-next';

import { watch } from 'vue';
const props = defineProps<{
    totalRevenue: number;
    totalCogs: number;
    grossProfit: number;
    netProfit: number;
    sales: {
        data: Array<{
            invoice_number: string;
            customer_name: string;
            total_amount: number;
            total_cogs: number;
            net_profit: number;
            created_at: string;
        }>;
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
        from?: number;
        to?: number;
        links?: Array<{ url: string | null; label: string; active: boolean }>;
    };
    tenantSlug: string;
    tenantName: string;
    filters: {
        start_date: string;
        end_date: string;
        sort_by: string;
        sort_order: string;
    };
}>();

const sortableFields = [
    { label: 'Tanggal', value: 'created_at' },
    { label: 'Invoice', value: 'invoice_number' },
    { label: 'Customer', value: 'customer_name' },
    { label: 'Total', value: 'total_amount' },
    { label: 'HPP', value: 'total_cogs' },
    { label: 'Laba Bersih', value: 'net_profit' },
];

// Set initial date range to today from backend props.filters
const startDate = ref<string>(props.filters?.start_date || new Date().toISOString().slice(0, 10));
const endDate = ref<string>(props.filters?.end_date || new Date().toISOString().slice(0, 10));
const sortBy = ref<string>(props.filters?.sort_by || 'created_at');
const sortOrder = ref<'asc' | 'desc'>(props.filters?.sort_order === 'asc' ? 'asc' : 'desc');

const sortedSales = ref([...props.sales.data]);
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
        title: 'Laba Bersih',
        href: route('reports.netProfit', { tenantSlug: props.tenantSlug }),
    },
];

function handleSort(field: string) {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
    fetchData();
    sortSales();
}

function fetchData() {
    router.get(
        route('reports.netProfit', { tenantSlug: props.tenantSlug }),
        {
            start_date: startDate.value,
            end_date: endDate.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['totalRevenue', 'totalCogs', 'grossProfit', 'netProfit', 'sales'],
        }
    );
}

function sortSales() {
    sortedSales.value = [...props.sales.data].sort((a, b) => {
        const field = sortBy.value as keyof typeof a;
        let valA = a[field];
        let valB = b[field];
        // Numeric sort for amount fields
        if (["total_amount", "total_cogs", "net_profit"].includes(field)) {
            valA = Number(valA);
            valB = Number(valB);
        }
        if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });
}

watch(() => props.sales.data, () => {
    sortSales();
}, { immediate: true });
    


function handleDateChange(e: Event, type: 'start' | 'end') {
    const val = (e.target as HTMLInputElement).value;
    if (type === 'start') {
        startDate.value = val;
    } else {
        endDate.value = val;
    }
}

function handleSubmitFilter() {
    fetchData();
}
</script>

<template>
    <Head title="Laporan Laba Bersih" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Laporan Laba Bersih {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>
            <!-- Filter Date Range & Sorting -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-4">
                <div class="flex gap-2 items-center">
                    <CalendarIcon class="h-5 w-5 text-blue-500 dark:text-blue-400" />
                    <Label for="start_date">Dari:</Label>
                    <input
                        type="date"
                        id="start_date"
                        :value="startDate"
                        @change="(e) => handleDateChange(e, 'start')"
                        class="px-2 py-1 border rounded-md bg-background text-sm"
                    />
                    <Label for="end_date">Sampai:</Label>
                    <input
                        type="date"
                        id="end_date"
                        :value="endDate"
                        @change="(e) => handleDateChange(e, 'end')"
                        class="px-2 py-1 border rounded-md bg-background text-sm"
                    />
                    <Button @click="handleSubmitFilter" variant="outline" size="sm" class="ml-2">Terapkan</Button>
                </div>
                <div class="flex gap-2 items-center">
                    <Label for="sortBy">Urutkan:</Label>
                    <select id="sortBy" v-model="sortBy" @change="handleSort(sortBy)" class="px-2 py-1 border rounded-md bg-background text-sm">
                        <option v-for="field in sortableFields" :key="field.value" :value="field.value">{{ field.label }}</option>
                    </select>
                    <Button @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'; handleSort(sortBy)" variant="outline" size="sm">
                        <span v-if="sortOrder === 'asc'">⬆️ Asc</span>
                        <span v-else>⬇️ Desc</span>
                    </Button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Total Pendapatan</h3>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(totalRevenue) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Total HPP</h3>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency(totalCogs) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Laba Kotor</h3>
                    <p :class="['text-3xl font-bold', grossProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']">
                        {{ formatCurrency(grossProfit) }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Laba Bersih</h3>
                    <p :class="['text-3xl font-bold', netProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']">
                        {{ formatCurrency(netProfit) }}
                    </p>
                </div>
            </div>
            <!-- Table Sales Data -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Detail Penjualan</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th v-for="field in sortableFields" :key="field.value"
                                    :class="['px-4 py-2', field.value === 'total_amount' || field.value === 'total_cogs' || field.value === 'net_profit' ? 'text-right' : 'text-left', 'cursor-pointer']"
                                    @click="handleSort(field.value)"
                                >
                                    {{ field.label }}
                                    <span v-if="sortBy === field.value">
                                        <svg v-if="sortOrder === 'asc'" xmlns="http://www.w3.org/2000/svg" class="inline h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="inline h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sale in sortedSales" :key="sale.invoice_number" class="border-b">
                                <td class="px-4 py-2">{{ sale.created_at }}</td>
                                <td class="px-4 py-2">{{ sale.invoice_number }}</td>
                                <td class="px-4 py-2">{{ sale.customer_name }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(sale.total_amount) }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(sale.total_cogs) }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(sale.net_profit) }}</td>
                            </tr>
                            <tr v-if="sortedSales.length === 0">
                                <td colspan="6" class="px-4 py-2 text-center text-gray-500">Tidak ada data penjualan.</td>
                            </tr>
                            <tr v-if="sortedSales.length > 0" class="bg-gray-50 dark:bg-gray-700 font-semibold">
                                <td colspan="3" class="px-4 py-2 text-right">Total</td>
                                <td class="px-4 py-2 text-right">
                                    {{ formatCurrency(sortedSales.reduce((sum, s) => sum + s.total_amount, 0)) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    {{ formatCurrency(sortedSales.reduce((sum, s) => sum + s.total_cogs, 0)) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    {{ formatCurrency(sortedSales.reduce((sum, s) => sum + s.net_profit, 0)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col md:flex-row gap-2 mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>Menampilkan <b>{{ sortedSales.length }}</b> data hasil filter.</span>
                    <span v-if="typeof sales.total === 'number'">Total data penjualan tenant: <b>{{ sales.total }}</b></span>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Analisis Laba Bersih</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Laporan ini menampilkan laba bersih Anda, dihitung dari total pendapatan dikurangi total HPP dan biaya lain (jika ada) untuk periode yang dipilih.
                </p>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-4">
                    <li>Total Pendapatan: Jumlah total dari semua penjualan yang berhasil.</li>
                    <li>HPP: Biaya langsung terkait barang yang dijual.</li>
                    <li>Laba Bersih: Pendapatan dikurangi HPP.</li>
                </ul>
                <p class="text-gray-600 dark:text-gray-400 mt-4">
                    Pastikan data penjualan dan HPP Anda akurat untuk laporan laba bersih yang tepat.
                </p>
            </div>
        </div>
    </AppLayout>
</template>