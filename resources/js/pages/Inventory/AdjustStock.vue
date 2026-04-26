<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

import { Button } from '@/components/ui/button';
// ...existing code...
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { ref, watch } from 'vue';
import { formatCurrency } from '@/utils/formatters';
import { useForm } from '@inertiajs/vue3';

interface Product {
    id: string;
    name: string;
    stock: number;
    cost_price: number;
}

const props = defineProps<{
    products: Product[];
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
        href: route('inventory.overview', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Penyesuaian Stok',
        href: route('inventory.adjust.form', { tenantSlug: props.tenantSlug }),
    },
];

const form = useForm({
    product_id: '',
    quantity_change: 0, // Can be positive (add) or negative (reduce)
    reason: '',
});

// Autocomplete product search
const productSearch = ref('');
const productResults = ref<Product[]>([]);
const searchingProduct = ref(false);
const showProductDropdown = ref(false);

const fetchProducts = async (query: string) => {
    searchingProduct.value = true;
    try {
        const res = await fetch(`/${props.tenantSlug}/inventory/search-products?q=${encodeURIComponent(query)}`);
        if (res.ok) {
            const data = await res.json();
            productResults.value = data.products;
            showProductDropdown.value = true;
        } else {
            productResults.value = [];
            showProductDropdown.value = false;
        }
    } catch (e) {
        console.log(e);
        productResults.value = [];
        showProductDropdown.value = false;
    }
    searchingProduct.value = false;
};

watch(productSearch, (val) => {
    if (val.length >= 2) {
        fetchProducts(val);
    } else {
        productResults.value = [];
        showProductDropdown.value = false;
    }
});


const submitAdjustStock = () => {
    form.post(route('inventory.adjust', { tenantSlug: props.tenantSlug }), {
        onSuccess: () => {
            form.reset();
            alert('Penyesuaian stok berhasil dicatat!');
        },
        onError: (errors) => {
            console.error("Submission errors:", errors);
            alert('Terjadi kesalahan saat mencatat penyesuaian stok. Silakan periksa input Anda.');
        },
    });
};
</script>

<template>
    <Head title="Penyesuaian Stok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h1 class="text-xl md:text-2xl font-black text-gray-900 dark:text-gray-100">
                    Penyesuaian Stok {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-gray-100 dark:shadow-none p-8 max-w-2xl mx-auto w-full border border-gray-100 dark:border-gray-700 animate-fade-in">
                <form @submit.prevent="submitAdjustStock" class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="product_search" class="font-bold text-gray-700 dark:text-gray-300">Produk</Label>
                            <div class="relative w-full">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <Input
                                    id="product_search"
                                    v-model="productSearch"
                                    placeholder="Cari nama produk..."
                                    autocomplete="off"
                                    class="pl-10 h-12 rounded-xl text-lg border-gray-200 dark:border-gray-700 shadow-sm"
                                />
                                <div v-if="productSearch.length >= 2 && showProductDropdown" class="absolute z-50 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl w-full mt-2 shadow-2xl max-h-72 overflow-auto">
                                    <div v-if="searchingProduct" class="p-4 text-sm text-gray-500 animate-pulse">Mencari...</div>
                                    <template v-else>
                                        <div v-if="productResults.length === 0" class="p-4 text-sm text-gray-500">Produk tidak ditemukan</div>
                                        <div
                                            v-for="product in productResults"
                                            :key="product.id"
                                            @click="form.product_id = product.id; productSearch = product.name; showProductDropdown = false"
                                            class="p-4 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/30 border-b border-gray-100 dark:border-gray-800 last:border-0 transition-colors"
                                        >
                                            <div class="font-bold text-gray-900 dark:text-gray-100">{{ product.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Stok: {{ product.stock }} | HPP: {{ formatCurrency(product.cost_price) }}</div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        <InputError :message="form.errors.product_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="quantity_change" class="font-bold text-gray-700 dark:text-gray-300">Perubahan Kuantitas</Label>
                        <Input
                            id="quantity_change"
                            type="number"
                            v-model.number="form.quantity_change"
                            required
                            placeholder="Contoh: 5 atau -3"
                            class="h-11 sm:h-12 rounded-xl text-lg"
                        />
                        <InputError :message="form.errors.quantity_change" />
                        <p class="text-xs sm:text-sm text-muted-foreground bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-900/30">
                            <strong>Tips:</strong> Masukkan nilai positif (+) untuk menambah stok (misal: barang datang), atau nilai negatif (-) untuk mengurangi stok (misal: barang rusak/hilang).
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="reason" class="font-bold text-gray-700 dark:text-gray-300">Alasan Penyesuaian</Label>
                        <Textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            required
                            placeholder="Misalnya: Koreksi stok fisik, kerusakan barang, kehilangan, dll."
                            class="rounded-xl p-4"
                        />
                        <InputError :message="form.errors.reason" />
                    </div>
                    <div class="pt-4">
                        <Button type="submit" :disabled="form.processing" class="w-full h-14 sm:h-12 rounded-xl text-lg font-bold bg-blue-600 hover:bg-blue-700 shadow-xl shadow-blue-200 dark:shadow-none transition-all active:scale-95">
                            <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                            Catat Penyesuaian
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
