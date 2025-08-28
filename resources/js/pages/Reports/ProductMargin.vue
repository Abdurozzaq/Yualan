<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatPercent } from '@/utils/formatters';
import { TrendingUp } from 'lucide-vue-next';
import * as XLSX from 'xlsx';

interface ProductMargin {
    name: string;
    sku: string | null;
    sold_qty: number;
    price: number;
    cost_price: number;
    margin: number;
    total_profit: number;
    contribution: number;
    unit: string | null;
}

const props = defineProps<{
    products: ProductMargin[];
    totalProfit: number;
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
        title: 'Produk Terlaris & Margin',
        href: route('reports.product-margin', { tenantSlug: props.tenantSlug }),
    },
];

const sortedProducts = computed(() => props.products);

const exportToExcel = () => {
    const sheetData = [
        [
            'No.',
            'Nama Produk',
            'SKU',
            'Terjual',
            'Unit',
            'Harga Jual',
            'Harga Pokok',
            'Margin per Produk',
            'Total Profit',
            'Kontribusi Profit (%)'
        ],
        ...sortedProducts.value.map((product, idx) => [
            idx + 1,
            product.name,
            product.sku || '-',
            product.sold_qty,
            product.unit || '-',
            product.price,
            product.cost_price,
            product.margin,
            product.total_profit,
            product.contribution
        ]),
        [
            '', '', '', '', '', '', '', 'Total Profit', props.totalProfit, ''
        ]
    ];

    const ws = XLSX.utils.aoa_to_sheet(sheetData);
    for (let i = 0; i < 10; i++) {
        ws[XLSX.utils.encode_cell({ r: 0, c: i })].s = {
            font: { bold: true },
            fill: { fgColor: { rgb: 'E0ECFF' } }
        };
    }
    ws[XLSX.utils.encode_cell({ r: sheetData.length - 1, c: 7 })].s = {
        font: { bold: true },
        fill: { fgColor: { rgb: 'FFF9C4' } }
    };
    ws[XLSX.utils.encode_cell({ r: sheetData.length - 1, c: 8 })].s = {
        font: { bold: true },
        fill: { fgColor: { rgb: 'FFF9C4' } }
    };

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Produk Terlaris & Margin');
    XLSX.writeFile(wb, `Laporan_Produk_Terlaris_${props.tenantName || 'Toko'}.xlsx`);
};

const search = ref('');
const sort = ref('sold_qty_desc');

const handleSearchSort = () => {
    router.get(
        route('reports.product-margin', { tenantSlug: props.tenantSlug }),
        {
            search: search.value,
            sort: sort.value,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head title="Laporan Produk Terlaris & Margin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Laporan Produk Terlaris & Margin {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <Button @click="exportToExcel" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
                    <TrendingUp class="h-5 w-5" />
                    Export Excel
                </Button>
            </div>

            <!-- Search & Sort Controls -->
            <div class="flex items-center gap-4 mb-4">
                <input
                    v-model="search"
                    @input="handleSearchSort"
                    type="text"
                    placeholder="Cari produk, SKU, unit..."
                    class="border rounded px-3 py-2 w-64"
                />
                <select v-model="sort" @change="handleSearchSort" class="border rounded px-3 py-2">
                    <option value="sold_qty_desc">Terjual Terbanyak</option>
                    <option value="sold_qty_asc">Terjual Tersedikit</option>
                    <option value="margin_desc">Margin Tertinggi</option>
                    <option value="margin_asc">Margin Terendah</option>
                    <option value="profit_desc">Profit Tertinggi</option>
                    <option value="profit_asc">Profit Terendah</option>
                </select>
            </div>

            <!-- Total Profit Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Total Profit Produk Terlaris</h3>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(totalProfit) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Profit dihitung dari total margin produk terjual.
                </p>
            </div>

            <!-- Product Margin Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead>Nama Produk</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Terjual</TableHead>
                            <TableHead>Unit</TableHead>
                            <TableHead>Harga Jual</TableHead>
                            <TableHead>Harga Pokok</TableHead>
                            <TableHead>Margin per Produk</TableHead>
                            <TableHead>Total Profit</TableHead>
                            <TableHead>Kontribusi Profit (%)</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="sortedProducts.length === 0">
                            <TableCell colspan="10" class="text-center text-muted-foreground py-8">
                                Belum ada produk terjual.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(product, index) in sortedProducts" :key="product.sku || product.name + index">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell class="font-medium">{{ product.name }}</TableCell>
                            <TableCell>{{ product.sku || '-' }}</TableCell>
                            <TableCell>{{ product.sold_qty }}</TableCell>
                            <TableCell>{{ product.unit || '-' }}</TableCell>
                            <TableCell>{{ formatCurrency(product.price) }}</TableCell>
                            <TableCell>{{ formatCurrency(product.cost_price) }}</TableCell>
                            <TableCell class="font-semibold">{{ formatCurrency(product.margin) }}</TableCell>
                            <TableCell class="font-semibold">{{ formatCurrency(product.total_profit) }}</TableCell>
                            <TableCell>{{ formatPercent(product.contribution) }}</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell colspan="8" class="text-right font-bold text-gray-700 dark:text-gray-200 bg-green-50 dark:bg-green-900">
                                Total Profit
                            </TableCell>
                            <TableCell class="font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900">
                                {{ formatCurrency(totalProfit) }}
                            </TableCell>
                            <TableCell class="bg-green-50 dark:bg-green-900"></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mt-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Informasi Laporan Produk Terlaris & Margin</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Laporan ini menampilkan produk terlaris, margin per produk, total profit, dan kontribusi masing-masing produk terhadap profit.
                </p>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-4">
                    <li>Terjual: Jumlah unit produk yang terjual.</li>
                    <li>Margin per Produk: Selisih harga jual dan harga pokok per unit.</li>
                    <li>Total Profit: Margin dikali jumlah terjual.</li>
                    <li>Kontribusi Profit: Persentase profit produk terhadap total profit.</li>
                </ul>
                <p class="text-gray-600 dark:text-gray-400 mt-4">
                    Analisis ini membantu Anda mengetahui produk mana yang paling menguntungkan dan berkontribusi besar ke profit bisnis.
                </p>
            </div>
        </div>
    </AppLayout>
</template>

