<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import { LoaderCircle, PlusCircle, Edit, Trash2, Search } from 'lucide-vue-next';

interface Promo {
    id: number;
    code: string;
    name: string;
    type: 'buyxgetx' | 'buyxgetanother';
    buy_qty: number;
    get_qty: number;
    product_id?: number;
    another_product_id?: number;
    expiry_date: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface PaginatedPromos {
    data: Promo[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    promos: PaginatedPromos;
    filters: any;
    tenantSlug: string;
    tenantName: string;
    products: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }) },
    { title: 'Promo', href: route('promos.index', { tenantSlug: props.tenantSlug }) },
];

const currentPerPage = ref(props.filters.perPage || 10);
const currentSortBy = ref(props.filters.sortBy || 'expiry_date');
const currentSortDirection = ref(props.filters.sortDirection || 'desc');
const currentSearch = ref(props.filters.search || '');

const form = useForm({
    id: null as number | null,
    code: '',
    name: '',
    type: 'buyxgetx' as 'buyxgetx' | 'buyxgetanother',
    buy_qty: 1,
    get_qty: 1,
    product_id: null as number | null,
    another_product_id: null as number | null,
    expiry_date: '',
    is_active: 'true',
    _method: 'post' as 'post' | 'put',
});

const isFormDialogOpen = ref(false);
const isConfirmDeleteDialogOpen = ref(false);
const promoToDelete = ref<Promo | null>(null);
const formTitle = computed(() => (form.id ? 'Edit Promo' : 'Tambah Promo Baru'));

const openFormDialog = (promo: Promo | null = null) => {
    form.reset();
    form.clearErrors();
    if (promo) {
        form.id = promo.id;
        form.code = promo.code;
        form.name = promo.name;
        form.type = promo.type;
        form.buy_qty = promo.buy_qty;
        form.get_qty = promo.get_qty;
        form.product_id = promo.product_id || null;
        form.another_product_id = promo.another_product_id || null;
        form.expiry_date = promo.expiry_date;
    form.is_active = promo.is_active ? 'true' : 'false';
        form._method = 'put';
    } else {
        form._method = 'post';
    form.is_active = 'true';
    }
    isFormDialogOpen.value = true;
};

const submitForm = () => {
    const payload = {
        id: form.id,
        code: form.code,
        name: form.name,
        type: form.type,
        buy_qty: form.buy_qty,
        get_qty: form.get_qty,
        product_id: form.product_id !== null ? String(form.product_id) : null,
        another_product_id: form.another_product_id !== null ? String(form.another_product_id) : null,
        expiry_date: form.expiry_date,
        is_active: form.is_active === 'true',
        _method: form._method,
    };
    const handleResponse = {
        onSuccess: () => {
            isFormDialogOpen.value = false;
            form.reset();
        },
        onError: (errors) => {
            console.error('Form error:', errors);
        },
            onFinish: () => {
            // Jika terjadi redirect tanpa pesan
        }
    };
    if (form.id) {
        router.post(route('promos.update', { tenantSlug: props.tenantSlug, promo: form.id }), payload, handleResponse);
    } else {
        router.post(route('promos.store', { tenantSlug: props.tenantSlug }), payload, handleResponse);
    }
};

const openConfirmDeleteDialog = (promo: Promo) => {
    promoToDelete.value = promo;
    isConfirmDeleteDialogOpen.value = true;
};

const deletePromo = () => {
    if (!promoToDelete.value) return;
    form.delete(route('promos.destroy', { tenantSlug: props.tenantSlug, promo: promoToDelete.value.id }), {
        onSuccess: () => {
            isConfirmDeleteDialogOpen.value = false;
            promoToDelete.value = null;
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
        },
        onFinish: () => {
            // Jika terjadi redirect tanpa pesan
        }
    });
};

watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch], () => {
    router.get(route('promos.index'), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['promos', 'filters'],
    });
}, { deep: true });

let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('promos.index'), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['promos', 'filters'],
        });
    }, 300);
};
</script>

<template>
    <!-- ...existing code... -->
    <Head title="Master Promo" />
    <AppLayout :breadcrumbs="breadcrumbs">
            <div v-if="$page.props.success" class="mb-4">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.success }}</span>
                </div>
            </div>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h1 class="text-xl md:text-2xl font-black text-gray-900 dark:text-gray-100">
                    Master Promo {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <div class="flex flex-col sm:flex-row gap-2">
                    <Button @click="openFormDialog()" class="flex items-center justify-center gap-2 h-11 sm:h-10 rounded-xl shadow-lg shadow-blue-200 dark:shadow-none bg-blue-600 hover:bg-blue-700">
                        <PlusCircle class="h-4 w-4" />
                        Tambah Promo
                    </Button>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-6 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="relative w-full sm:flex-grow">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari promo berdasarkan kode atau nama..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 h-11 sm:h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500 shadow-sm w-full"
                    />
                </div>
            </div>
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead>Kode</TableHead>
                            <TableHead>Nama</TableHead>
                            <TableHead>Tipe</TableHead>
                            <TableHead>Beli Qty</TableHead>
                            <TableHead>Gratis Qty</TableHead>
                            <TableHead>Produk</TableHead>
                            <TableHead>Produk Gratis</TableHead>
                            <TableHead>Expired</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-[100px] text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.promos.data.length === 0">
                            <TableCell colspan="11" class="text-center text-muted-foreground py-8">
                                Belum ada promo yang ditambahkan atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(promo, index) in props.promos.data" :key="promo.id">
                            <TableCell>{{ props.promos.from + index }}</TableCell>
                            <TableCell>{{ promo.code }}</TableCell>
                            <TableCell>{{ promo.name }}</TableCell>
                            <TableCell>{{ promo.type }}</TableCell>
                            <TableCell>{{ promo.buy_qty }}</TableCell>
                            <TableCell>{{ promo.get_qty }}</TableCell>
                                <TableCell>{{ promo.product_id ? ((props.products || []).find(p => p.id === promo.product_id)?.name || promo.product_id) : '-' }}</TableCell>
                                <TableCell>{{ promo.another_product_id ? ((props.products || []).find(p => p.id === promo.another_product_id)?.name || promo.another_product_id) : '-' }}</TableCell>
                            <TableCell>{{ promo.expiry_date }}</TableCell>
                            <TableCell>
                                <span :class="promo.is_active ? 'text-green-600' : 'text-red-600'">
                                    {{ promo.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" @click="openFormDialog(promo)" class="mr-1">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="openConfirmDeleteDialog(promo)">
                                    <Trash2 class="h-4 w-4 text-red-500" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <div v-if="props.promos.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.promos.from }} hingga {{ props.promos.to }} dari {{ props.promos.total }} promo
                </div>
                <div class="flex items-center gap-2">
                    <div v-for="(link, index) in props.promos.links" 
                            :key="index">
                        <Button
                            
                            :as="Link"
                            :href="link.url || '#'"
                            :disabled="!link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            class="px-3 py-1 rounded-md text-sm">
                        
                            {{  link.label }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
        <Dialog v-model:open="isFormDialogOpen">
            <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ formTitle }}</DialogTitle>
                    <DialogDescription>
                        Isi detail promo di bawah ini. Klik simpan saat Anda selesai.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="grid gap-4 py-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="code" class="sm:text-right font-bold">Kode</Label>
                        <Input id="code" v-model="form.code" required class="sm:col-span-3 h-11 sm:h-10 rounded-xl" />
                    </div>
                    <InputError :message="form.errors.code" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="name" class="sm:text-right font-bold">Nama</Label>
                        <Input id="name" v-model="form.name" required class="sm:col-span-3 h-11 sm:h-10 rounded-xl" />
                    </div>
                    <InputError :message="form.errors.name" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="type" class="sm:text-right font-bold">Tipe</Label>
                        <Select v-model="form.type">
                            <SelectTrigger class="sm:col-span-3 h-11 sm:h-10 rounded-xl px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
                                <SelectValue placeholder="Pilih Tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="buyxgetx">Buy X Get X</SelectItem>
                                <SelectItem value="buyxgetanother">Buy X Get Another Product</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <InputError :message="form.errors.type" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="buy_qty" class="sm:text-right font-bold">Beli Qty</Label>
                        <Input id="buy_qty" type="number" v-model.number="form.buy_qty" required class="sm:col-span-3 h-11 sm:h-10 rounded-xl" min="1" />
                    </div>
                    <InputError :message="form.errors.buy_qty" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="get_qty" class="sm:text-right font-bold">Gratis Qty</Label>
                        <Input id="get_qty" type="number" v-model.number="form.get_qty" required class="sm:col-span-3 h-11 sm:h-10 rounded-xl" min="1" />
                    </div>
                    <InputError :message="form.errors.get_qty" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div v-if="form.type === 'buyxgetx'" class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="product_id" class="sm:text-right font-bold">Produk</Label>
                        <Select v-model="form.product_id">
                            <SelectTrigger class="sm:col-span-3 h-11 sm:h-10 rounded-xl px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
                                <SelectValue placeholder="Pilih Produk" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="product in props.products" :key="product.id" :value="product.id">
                                    {{ product.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <InputError v-if="form.type === 'buyxgetx'" :message="form.errors.product_id" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div v-if="form.type === 'buyxgetanother'" class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="product_id" class="sm:text-right font-bold">Produk Dibeli</Label>
                        <Select v-model="form.product_id">
                            <SelectTrigger class="sm:col-span-3 h-11 sm:h-10 rounded-xl px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
                                <SelectValue placeholder="Pilih Produk Dibeli" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="product in props.products" :key="product.id" :value="product.id">
                                    {{ product.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <InputError v-if="form.type === 'buyxgetanother'" :message="form.errors.product_id" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div v-if="form.type === 'buyxgetanother'" class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="another_product_id" class="sm:text-right font-bold">Produk Gratis</Label>
                        <Select v-model="form.another_product_id">
                            <SelectTrigger class="sm:col-span-3 h-11 sm:h-10 rounded-xl px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
                                <SelectValue placeholder="Pilih Produk Gratis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="product in props.products" :key="product.id" :value="product.id">
                                    {{ product.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <InputError v-if="form.type === 'buyxgetanother'" :message="form.errors.another_product_id" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="expiry_date" class="sm:text-right font-bold">Expired</Label>
                        <Input id="expiry_date" type="date" v-model="form.expiry_date" required class="sm:col-span-3 h-11 sm:h-10 rounded-xl" />
                    </div>
                    <InputError :message="form.errors.expiry_date" class="sm:col-span-3 sm:col-start-2" />
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 sm:items-center gap-2 sm:gap-4">
                        <Label for="is_active" class="sm:text-right font-bold">Status</Label>
                        <Select v-model="form.is_active">
                            <SelectTrigger class="sm:col-span-3 h-11 sm:h-10 rounded-xl px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
                                <SelectValue placeholder="Pilih Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="true">Aktif</SelectItem>
                                <SelectItem value="false">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <InputError :message="form.errors.is_active" class="sm:col-span-3 sm:col-start-2" />
                    
                    <DialogFooter class="mt-4 gap-2 flex-col sm:flex-row">
                        <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto h-12 sm:h-10 rounded-xl bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 dark:shadow-none">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Simpan Promo
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <Dialog v-model:open="isConfirmDeleteDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Penghapusan</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus promo "<strong>{{ promoToDelete?.name }}</strong>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deletePromo" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
