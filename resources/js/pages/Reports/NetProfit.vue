<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/utils/formatters';
import { CalendarIcon } from 'lucide-vue-next';

interface Filters {
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    totalRevenue: number;
    totalCogs: number;
    grossProfit: number;
    netProfit: number;
    sales: Array<{
        invoice_number: string;
        customer_name: string;
        total_amount: number;
        total_cogs: number;
        net_profit: number;
        created_at: string;
    }>;
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
        title: 'Laporan',
        href: '#',
    },
    {
        title: 'Laba Bersih',
        href: route('reports.netProfit', { tenantSlug: props.tenantSlug }),
    },
];

const startDate = ref<Date | undefined>(props.filters.start_date ? new Date(props.filters.start_date) : undefined);
const endDate = ref<Date | undefined>(props.filters.end_date ? new Date(props.filters.end_date) : undefined);

// Fungsi format tanggal ke format Indonesia
const formatDate = (date: Date | undefined) => {
    if (!date) return 'Pilih tanggal';
    
    const options: Intl.DateTimeFormatOptions = {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    };
    
    return date.toLocaleDateString('id-ID', options);
};

// Fungsi format ke YYYY-MM-DD untuk request API
const formatDateForApi = (date: Date | undefined) => {
    if (!date) return undefined;
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
};

watch([startDate, endDate], () => {
    router.get(
        route('reports.netProfit', { tenantSlug: props.tenantSlug }),
        {
            start_date: formatDateForApi(startDate.value),
            end_date: formatDateForApi(endDate.value),
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['totalRevenue', 'totalCogs', 'grossProfit', 'netProfit', 'sales', 'filters'],
        }
    );
});

const resetDates = () => {
    startDate.value = undefined;
    endDate.value = undefined;
};

// Fungsi untuk menangani input date manual
const handleDateInput = (e: Event, type: 'start' | 'end') => {
    const input = e.target as HTMLInputElement;
    const date = input.value ? new Date(input.value) : undefined;
    
    if (type === 'start') {
        startDate.value = date;
    } else {
        endDate.value = date;
    }
};
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

            <!-- Date Filter Section - Menggunakan input date native -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Label for="start_date">Dari Tanggal:</Label>
                    <div class="relative">
                        <input
                            type="date"
                            id="start_date"
                            :value="startDate ? startDate.toISOString().split('T')[0] : ''"
                            @change="(e) => handleDateInput(e, 'start')"
                            class="w-[240px] px-3 py-2 border rounded-md bg-background text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                        <CalendarIcon class="absolute right-3 top-2.5 h-4 w-4 text-muted-foreground pointer-events-none" />
                    </div>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Label for="end_date">Sampai Tanggal:</Label>
                    <div class="relative">
                        <input
                            type="date"
                            id="end_date"
                            :value="endDate ? endDate.toISOString().split('T')[0] : ''"
                            @change="(e) => handleDateInput(e, 'end')"
                            class="w-[240px] px-3 py-2 border rounded-md bg-background text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                        <CalendarIcon class="absolute right-3 top-2.5 h-4 w-4 text-muted-foreground pointer-events-none" />
                    </div>
                </div>
                <Button @click="resetDates" variant="outline" class="w-full sm:w-auto mt-2 sm:mt-0">Reset Tanggal</Button>
            </div>

            <!-- Tampilkan tanggal yang dipilih dalam format Indonesia -->
            <div v-if="startDate || endDate" class="flex gap-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                <span v-if="startDate">Dari: {{ formatDate(startDate) }}</span>
                <span v-if="endDate">Sampai: {{ formatDate(endDate) }}</span>
            </div>

            <!-- Report Summary Cards -->
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
                                <th class="px-4 py-2 text-left">Tanggal</th>
                                <th class="px-4 py-2 text-left">Invoice</th>
                                <th class="px-4 py-2 text-left">Customer</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-right">HPP</th>
                                <th class="px-4 py-2 text-right">Laba Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sale in sales" :key="sale.invoice_number" class="border-b">
                                <td class="px-4 py-2">{{ sale.created_at }}</td>
                                <td class="px-4 py-2">{{ sale.invoice_number }}</td>
                                <td class="px-4 py-2">{{ sale.customer_name }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(sale.total_amount) }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(sale.total_cogs) }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(sale.net_profit) }}</td>
                            </tr>
                            <tr v-if="sales.length === 0">
                                <td colspan="6" class="px-4 py-2 text-center text-gray-500">Tidak ada data penjualan.</td>
                            </tr>
                        </tbody>
                    </table>
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