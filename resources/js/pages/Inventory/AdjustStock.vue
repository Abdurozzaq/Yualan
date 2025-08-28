<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/utils/formatters';

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
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Penyesuaian Stok {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 max-w-2xl mx-auto w-full">
                <form @submit.prevent="submitAdjustStock" class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="product_id">Produk</Label>
                        <Select v-model="form.product_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Produk" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="product in products" :key="product.id" :value="product.id">
                                    {{ product.name }} (Stok Saat Ini: {{ product.stock }}, HPP: {{ formatCurrency(product.cost_price) }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.product_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="quantity_change">Perubahan Kuantitas</Label>
                        <Input
                            id="quantity_change"
                            type="number"
                            v-model.number="form.quantity_change"
                            required
                            placeholder="Masukkan angka positif untuk menambah, negatif untuk mengurangi"
                        />
                        <InputError :message="form.errors.quantity_change" />
                        <p class="text-sm text-muted-foreground">
                            Masukkan nilai positif untuk menambah stok, atau nilai negatif untuk mengurangi stok.
                            Misalnya, `5` untuk menambah 5 unit, atau `-3` untuk mengurangi 3 unit.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="reason">Alasan Penyesuaian</Label>
                        <Textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            required
                            placeholder="Misalnya: Koreksi stok fisik, kerusakan barang, kehilangan, dll."
                        />
                        <InputError :message="form.errors.reason" />
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Catat Penyesuaian
                    </Button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
