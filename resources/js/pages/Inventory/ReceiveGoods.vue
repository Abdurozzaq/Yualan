<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/utils/formatters';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Product {
    id: string;
    name: string;
    stock: number;
    cost_price: number;
}

interface Supplier {
    id: string;
    name: string;
}

const props = defineProps<{
    products: Product[];
    suppliers: Supplier[]; // New prop for suppliers
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
        title: 'Penerimaan Barang',
        href: route('inventory.receive.form', { tenantSlug: props.tenantSlug }),
    },
];

const form = useForm({
    product_id: '',
    quantity: 1,
    cost_per_unit: 0.00,
    supplier_id: null as string | null, // New field for supplier
    reason: '',
});

const selectedProduct = computed(() => {
    return props.products.find(p => p.id === form.product_id);
});

// Watch for product_id change to pre-fill cost_per_unit
watch(selectedProduct, (newProduct) => {
    if (newProduct) {
        form.cost_per_unit = newProduct.cost_price;
    } else {
        form.cost_per_unit = 0.00;
    }
});

const submitReceiveGoods = () => {
    form.post(route('inventory.receive', { tenantSlug: props.tenantSlug }), {
        onSuccess: () => {
            form.reset();
            alert('Penerimaan barang berhasil dicatat!');
        },
        onError: (errors: any) => {
            console.error("Submission errors:", errors);
            alert('Terjadi kesalahan saat mencatat penerimaan barang. Silakan periksa input Anda.');
        },
    });
};
</script>

<template>
    <Head title="Penerimaan Barang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Penerimaan Barang {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 max-w-2xl mx-auto w-full">
                <form @submit.prevent="submitReceiveGoods" class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="product_id">Produk</Label>
                        <Select v-model="form.product_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Produk" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="product in products" :key="product.id" :value="product.id">
                                    {{ product.name }} (Stok: {{ product.stock }}, HPP: {{ formatCurrency(product.cost_price) }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.product_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="quantity">Kuantitas Diterima</Label>
                        <Input
                            id="quantity"
                            type="number"
                            v-model.number="form.quantity"
                            required
                            min="1"
                        />
                        <InputError :message="form.errors.quantity" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="cost_per_unit">Harga Pokok per Unit (saat ini)</Label>
                        <Input
                            id="cost_per_unit"
                            type="number"
                            step="0.01"
                            v-model.number="form.cost_per_unit"
                            required
                            min="0"
                        />
                        <InputError :message="form.errors.cost_per_unit" />
                        <p class="text-sm text-muted-foreground">
                            Harga pokok ini akan digunakan untuk menghitung rata-rata tertimbang harga pokok produk.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="supplier_id">Supplier (Opsional)</Label>
                        <Select v-model="form.supplier_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Supplier" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Tidak Ada Supplier</SelectItem>
                                <SelectItem v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.supplier_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="reason">Alasan Penerimaan (Opsional)</Label>
                        <Textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            placeholder="Misalnya: Pembelian dari Supplier ABC, Pengembalian dari pelanggan, dll."
                        />
                        <InputError :message="form.errors.reason" />
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Catat Penerimaan
                    </Button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
