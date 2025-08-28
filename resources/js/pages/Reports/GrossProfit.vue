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
        title: 'Laba Kotor',
        href: route('reports.grossProfit', { tenantSlug: props.tenantSlug }),
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
        route('reports.grossProfit', { tenantSlug: props.tenantSlug }),
        {
            start_date: formatDateForApi(startDate.value),
            end_date: formatDateForApi(endDate.value),
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['totalRevenue', 'totalCogs', 'grossProfit', 'filters'],
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
    <Head title="Laporan Laba Kotor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Laporan Laba Kotor {{ tenantName ? `(${tenantName})` : '' }}
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Total Pendapatan</h3>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(totalRevenue) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Total Harga Pokok Penjualan (HPP)</h3>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency(totalCogs) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Laba Kotor</h3>
                    <p :class="['text-3xl font-bold', grossProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']">
                        {{ formatCurrency(grossProfit) }}
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Analisis Laba Kotor</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Laporan ini menampilkan laba kotor Anda, yang dihitung dari total pendapatan dikurangi total harga pokok penjualan (HPP) untuk periode yang dipilih.
                </p>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-4">
                    <li>Total Pendapatan: Jumlah total dari semua penjualan yang berhasil ($status = 'completed').</li>
                    <li>Harga Pokok Penjualan (HPP): Biaya langsung yang terkait dengan penjualan barang yang dijual. Ini dihitung berdasarkan `cost_price_at_sale` dari setiap item penjualan.</li>
                    <li>Laba Kotor: Pendapatan dikurangi HPP. Ini adalah indikator penting dari efisiensi operasional bisnis Anda sebelum memperhitungkan biaya operasional lainnya.</li>
                </ul>
                <p class="text-gray-600 dark:text-gray-400 mt-4">
                    Pastikan `cost_price` produk Anda akurat untuk mendapatkan laporan laba kotor yang tepat.
                </p>
            </div>
        </div>
    </AppLayout>
</template>