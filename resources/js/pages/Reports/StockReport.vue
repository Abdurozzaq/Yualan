<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3'; // import router here
import { computed, ref } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/utils/formatters'; // Import the formatter
import { Package, AlertTriangle, CheckCircle } from 'lucide-vue-next'; // Icons for stock status
import * as XLSX from 'xlsx'; // pastikan sudah install: npm i xlsx

interface Product {
    name: string;
    sku: string | null;
    stock: number;
    cost_price: number;
    price: number;
    unit: string | null;
}

const props = defineProps<{
    products: Product[];
    totalStockValue: number;
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
        href: '#', // Placeholder for reports main page if any
    },
    {
        title: 'Nilai Stok',
        href: route('reports.stock', { tenantSlug: props.tenantSlug }),
    },
];

// Computed property for sorting (if needed, though controller already sorts by name)
const sortedProducts = computed(() => {
    // Products are already sorted by name from the controller,
    // but you can add client-side sorting here if more dynamic sorting is needed.
    return props.products;
});

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

const exportToExcel = () => {
    // Prepare worksheet data
    const sheetData = [
        [
            'No.',
            'Nama Produk',
            'SKU',
            'Stok Saat Ini',
            'Unit',
            'Harga Pokok Rata-rata',
            'Harga Jual',
            'Nilai Stok Produk',
            'Status Stok'
        ],
        ...sortedProducts.value.map((product, idx) => {
            const status = getStockStatus(product.stock).text;
            return [
                idx + 1,
                product.name,
                product.sku || '-',
                product.stock,
                product.unit || '-',
                product.cost_price,
                product.price,
                product.stock * product.cost_price,
                status
            ];
        }),
        [
            '', '', '', '', '', '', 'Total Nilai Stok', props.totalStockValue, ''
        ]
    ];

    // Create worksheet and workbook
    const ws = XLSX.utils.aoa_to_sheet(sheetData);
    // Format header row
    for (let i = 0; i < 9; i++) {
        ws[XLSX.utils.encode_cell({ r: 0, c: i })].s = {
            font: { bold: true },
            fill: { fgColor: { rgb: 'E0ECFF' } }
        };
    }
    // Format total row
    ws[XLSX.utils.encode_cell({ r: sheetData.length - 1, c: 6 })].s = {
        font: { bold: true },
        fill: { fgColor: { rgb: 'FFF9C4' } }
    };
    ws[XLSX.utils.encode_cell({ r: sheetData.length - 1, c: 7 })].s = {
        font: { bold: true },
        fill: { fgColor: { rgb: 'FFF9C4' } }
    };

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Laporan Stok');

    // Export
    XLSX.writeFile(wb, `Laporan_Stok_${props.tenantName || 'Toko'}.xlsx`);
};

const search = ref('');
const sort = ref('name_asc'); // default sort by name ascending

const handleSearchSort = () => {
    router.get(
        route('reports.stock', { tenantSlug: props.tenantSlug }),
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
    <Head title="Laporan Nilai Stok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Laporan Nilai Stok {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <Button @click="exportToExcel" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
                    <Package class="h-5 w-5" />
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
                    <option value="name_asc">Nama Produk (A-Z)</option>
                    <option value="name_desc">Nama Produk (Z-A)</option>
                    <option value="stock_asc">Stok Terendah</option>
                    <option value="stock_desc">Stok Tertinggi</option>
                    <option value="price_asc">Harga Jual Terendah</option>
                    <option value="price_desc">Harga Jual Tertinggi</option>
                </select>
            </div>

            <!-- Total Stock Value Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Total Nilai Stok (Harga Pokok)</h3>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(totalStockValue) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Ini adalah perkiraan nilai total inventaris Anda berdasarkan harga pokok rata-rata produk.
                </p>
            </div>

            <!-- Product Stock List Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead>Nama Produk</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Stok Saat Ini</TableHead>
                            <TableHead>Unit</TableHead>
                            <TableHead>Harga Pokok Rata-rata</TableHead>
                            <TableHead>Harga Jual</TableHead>
                            <TableHead>Nilai Stok Produk</TableHead>
                            <TableHead>Status Stok</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="sortedProducts.length === 0">
                            <TableCell colspan="9" class="text-center text-muted-foreground py-8">
                                Belum ada produk yang tercatat dalam inventaris.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(product, index) in sortedProducts" :key="product.sku || product.name + index">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell class="font-medium">{{ product.name }}</TableCell>
                            <TableCell>{{ product.sku || '-' }}</TableCell>
                            <TableCell>{{ product.stock }}</TableCell>
                            <TableCell>{{ product.unit || '-' }}</TableCell>
                            <TableCell>{{ formatCurrency(product.cost_price) }}</TableCell>
                            <TableCell>{{ formatCurrency(product.price) }}</TableCell>
                            <TableCell class="font-semibold">{{ formatCurrency(product.stock * product.cost_price) }}</TableCell>
                            <TableCell>
                                <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold', getStockStatus(product.stock).class]">
                                    <component :is="getStockStatus(product.stock).icon" class="h-3 w-3" />
                                    {{ getStockStatus(product.stock).text }}
                                </span>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell colspan="7" class="text-right font-bold text-gray-700 dark:text-gray-200 bg-blue-50 dark:bg-blue-900">
                                Total Nilai Stok
                            </TableCell>
                            <TableCell class="font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900">
                                {{ formatCurrency(totalStockValue) }}
                            </TableCell>
                            <TableCell class="bg-blue-50 dark:bg-blue-900"></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mt-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Informasi Laporan Stok</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Laporan ini memberikan gambaran tentang inventaris Anda saat ini, termasuk kuantitas stok, harga pokok, harga jual, dan total nilai stok berdasarkan harga pokok.
                </p>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-4">
                    <li>Stok Saat Ini: Jumlah unit produk yang tersedia di gudang atau toko Anda.</li>
                    <li>Harga Pokok Rata-rata: Biaya rata-rata per unit produk yang dihitung berdasarkan metode rata-rata tertimbang dari penerimaan barang.</li>
                    <li>Nilai Stok Produk: Total biaya produk yang tersedia di stok (Stok Saat Ini x Harga Pokok Rata-rata).</li>
                    <li>Status Stok: Indikator cepat untuk mengidentifikasi produk dengan stok rendah atau habis.</li>
                </ul>
                <p class="text-gray-600 dark:text-gray-400 mt-4">
                    Memantau laporan stok ini secara teratur membantu Anda mengelola inventaris secara efisien, menghindari kehabisan stok, dan mengidentifikasi kelebihan stok.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
