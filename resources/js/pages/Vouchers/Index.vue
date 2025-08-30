<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { LoaderCircle, PlusCircle, Edit, Trash2, Printer } from 'lucide-vue-next';
import { computed, watch, ref } from 'vue';

interface Voucher {
    id: number;
    code: string;
    name: string;
    type: 'percentage' | 'percentage_max' | 'nominal';
    value: number;
    max_nominal: number | null;
    expiry_date: string;
    // removed is_active
    used: boolean;
}

const props = defineProps<{
    vouchers: any;
    tenantSlug: string;
    tenantName?: string;
    filters: any;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }) },
    { label: 'Master Voucher', href: '#' },
]);

const currentPerPage = ref(props.filters.perPage || 10);
const currentSortBy = ref(props.filters.sortBy || 'created_at');
const currentSortDirection = ref(props.filters.sortDirection || 'desc');
const currentSearch = ref(props.filters.search || '');

const form = useForm({
    code: '',
    name: '',
    type: 'percentage' as 'percentage' | 'percentage_max' | 'nominal',
    value: 0,
    max_nominal: null as number | null,
    expiry_date: '',
    // removed is_active
    _method: 'post' as 'post' | 'put',
});

const isFormDialogOpen = ref(false);
const isConfirmDeleteDialogOpen = ref(false);
const voucherToDelete = ref<Voucher | null>(null);
const formTitle = computed(() => (form.id ? 'Edit Voucher' : 'Tambah Voucher Baru'));

// Print dialog state & functions
const isPrintDialogOpen = ref(false);
const voucherToPrint = ref<Voucher | null>(null);

function openPrintDialog(voucher: Voucher) {
    voucherToPrint.value = voucher;
    isPrintDialogOpen.value = true;
}

function closePrintDialog() {
    isPrintDialogOpen.value = false;
    voucherToPrint.value = null;
}

function printVoucher() {
    // Print voucher dengan desain modern dan ukuran kertas 8,6cm x 5,4cm
    const printWindow = window.open('', '', 'height=400,width=400');
    printWindow?.document.write(`
        <html>
        <head>
            <title>Print Voucher</title>
            <style>
                @media print {
                    @page {
                        size: 8.6cm 5.4cm;
                        margin: 0;
                    }
                    body {
                        width: 8.6cm;
                        height: 5.4cm;
                        margin: 0;
                        padding: 0;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                }
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    padding: 0;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 100vw;
                    height: 100vh;
                }
                .voucher-container {
                    width: 8.2cm;
                    background: white;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                    padding: 0.2cm;
                    position: relative;
                    overflow: hidden;
                }
                .voucher-header {
                    background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 8px 8px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: -0.2cm -0.2cm 8px -0.2cm;
                }
                .voucher-title {
                    font-size: 0.75rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .voucher-tenant {
                    font-size: 0.6rem;
                    opacity: 0.9;
                }
                .voucher-code {
                    background: #f1f5f9;
                    padding: 8px;
                    border-radius: 6px;
                    margin: 0 0 10px 0;
                    text-align: center;
                    font-weight: 800;
                    font-size: 0.85rem;
                    letter-spacing: 1px;
                    color: #4f46e5;
                    border: 1px dashed #cbd5e1;
                }
                .voucher-info {
                    display: grid;
                    grid-template-columns: 0.9fr 1.1fr;
                    gap: 6px;
                    padding: 0 8px;
                }
                .voucher-label {
                    color: #64748b;
                    font-weight: 500;
                    font-size: 0.55rem;
                    text-align: right;
                }
                .voucher-value {
                    font-weight: 600;
                    color: #1e293b;
                    font-size: 0.55rem;
                }
                .voucher-divider {
                    border-top: 1px dashed #e2e8f0;
                    margin: 8px 0;
                    position: relative;
                }
                .divider-icon {
                    position: absolute;
                    top: -8px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: white;
                    padding: 0 5px;
                    color: #94a3b8;
                }
                .voucher-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 0 8px;
                    margin-top: 5px;
                }
                .voucher-status {
                    font-weight: 700;
                    font-size: 0.55rem;
                    padding: 3px 8px;
                    border-radius: 20px;
                }
                .voucher-status.aktif {
                    background: #dcfce7;
                    color: #16a34a;
                }
                .voucher-status.nonaktif {
                    background: #fee2e2;
                    color: #dc2626;
                }
                .voucher-status.used {
                    background: #dbeafe;
                    color: #2563eb;
                }
                .voucher-status.unused {
                    background: #e5e7eb;
                    color: #64748b;
                }
                .voucher-note {
                    font-size: 0.45rem;
                    color: #64748b;
                    text-align: center;
                    margin-top: 5px;
                }
                .voucher-barcode {
                    text-align: center;
                    margin: 5px 0;
                    font-family: 'Libre Barcode 39', monospace;
                    font-size: 1.2rem;
                    letter-spacing: 1px;
                }
                .voucher-background-pattern {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    opacity: 0.03;
                    z-index: 0;
                    background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23000000' fill-opacity='1' fill-rule='evenodd'%3E%3Ccircle cx='3' cy='3' r='3'/%3E%3Ccircle cx='13' cy='13' r='3'/%3E%3C/g%3E%3C/svg%3E");
                }
                .voucher-corner {
                    position: absolute;
                    width: 15px;
                    height: 15px;
                    border-color: #4f46e5;
                    border-style: solid;
                }
            
            </style>
            <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
        </head>
        <body onload="window.print();window.close();">
            <div class="voucher-container">
                <div class="voucher-background-pattern"></div>
                <div class="voucher-header">
                    <div class="voucher-title">Voucher Diskon</div>
                    <div class="voucher-tenant">${props.tenantName ? props.tenantName : ''}</div>
                </div>
                <div class="voucher-code">${voucherToPrint.value?.code ?? ''}</div>
                <div class="voucher-info">
                    <span class="voucher-label">Nama:</span>
                    <span class="voucher-value">${voucherToPrint.value?.name ?? ''}</span>
                    <span class="voucher-label">Tipe:</span>
                    <span class="voucher-value">${voucherToPrint.value?.type ?? ''}</span>
                    <span class="voucher-label">Nilai:</span>
                    <span class="voucher-value">${voucherToPrint.value?.value ?? ''}</span>
                    <span class="voucher-label">Expired:</span>
                    <span class="voucher-value">${voucherToPrint.value?.expiry_date ?? ''}</span>
                </div>
                <div class="voucher-divider">
                    <span class="divider-icon">✦</span>
                </div>
                <div class="voucher-footer">
                    <div class="voucher-status unused">${voucherToPrint.value?.used ? 'Sudah digunakan' : 'Belum digunakan'}</div>
                    <div class="voucher-note">*Tunjukkan voucher ini saat transaksi*</div>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow?.document.close();
}


const submitForm = () => {
    if (form.id) {
        form.post(route('vouchers.update', { tenantSlug: props.tenantSlug, voucher: form.id }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('vouchers.store', { tenantSlug: props.tenantSlug }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
            },
        });
    }
};

const openConfirmDeleteDialog = (voucher: Voucher) => {
    voucherToDelete.value = voucher;
    isConfirmDeleteDialogOpen.value = true;
};

const deleteVoucher = () => {
    if (!voucherToDelete.value) return;
    form.delete(route('vouchers.destroy', { tenantSlug: props.tenantSlug, voucher: voucherToDelete.value.id }), {
        onSuccess: () => {
            isConfirmDeleteDialogOpen.value = false;
            voucherToDelete.value = null;
        },
    });
};

watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch], () => {
    router.get(route('vouchers.index'), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['vouchers', 'filters'],
    });
}, { deep: true });

let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('vouchers.index'), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['vouchers', 'filters'],
        });
    }, 300);
};
</script>

<template>
    <Head title="Master Voucher" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Master Voucher {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <div class="flex gap-2">
                    <Button @click="openFormDialog()" class="flex items-center gap-2">
                        <PlusCircle class="h-4 w-4" />
                        Tambah Voucher
                    </Button>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari voucher..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
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
                            <TableHead>Nilai</TableHead>
                            <TableHead>Max Nominal</TableHead>
                            <TableHead>Expired</TableHead>
                            <TableHead>Used</TableHead>
                            <TableHead class="w-[100px] text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="props.vouchers.data.length === 0">
                            <TableRow>
                                <TableCell colspan="10" class="text-center text-muted-foreground py-8">
                                    Belum ada voucher yang ditambahkan atau tidak ada hasil yang cocok.
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow v-for="(voucher, index) in props.vouchers.data" :key="voucher.id">
                                <TableCell>{{ props.vouchers.from + index }}</TableCell>
                                <TableCell>{{ voucher.code }}</TableCell>
                                <TableCell>{{ voucher.name }}</TableCell>
                                <TableCell>{{ voucher.type }}</TableCell>
                                <TableCell>{{ voucher.value }}</TableCell>
                                <TableCell>{{ voucher.max_nominal || '-' }}</TableCell>
                                <TableCell>{{ voucher.expiry_date }}</TableCell>
                                <TableCell>
                                    <span :class="voucher.used ? 'text-blue-600 font-bold' : 'text-gray-500'">
                                        {{ voucher.used ? 'Sudah digunakan' : 'Belum digunakan' }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right flex gap-1 justify-end">
                                    <Button variant="ghost" size="icon" @click="openFormDialog(voucher)" class="mr-1" :disabled="voucher.used">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="openConfirmDeleteDialog(voucher)" :disabled="voucher.used">
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="openPrintDialog(voucher)" :disabled="voucher.used" title="Print Voucher">
                                        <Printer class="h-4 w-4 text-blue-500" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>
            <div v-if="props.vouchers.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.vouchers.from }} hingga {{ props.vouchers.to }} dari {{ props.vouchers.total }} voucher
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.vouchers.links"
                        :key="index"
                        :as="Link"
                        :href="link.url || '#'"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        class="px-3 py-1 rounded-md text-sm"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Form Dialog -->
        <Dialog v-model:open="isFormDialogOpen">
            <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ formTitle }}</DialogTitle>
                    <DialogDescription>
                        Isi detail voucher di bawah ini. Klik simpan saat Anda selesai.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="code" class="text-right">Kode</Label>
                        <Input id="code" v-model="form.code" required class="col-span-3" />
                    </div>
                    <InputError :message="form.errors.code" />
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">Nama</Label>
                        <Input id="name" v-model="form.name" required class="col-span-3" />
                    </div>
                    <InputError :message="form.errors.name" />
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="type" class="text-right">Tipe</Label>
                        <select id="type" v-model="form.type" required class="col-span-3 border rounded px-2 py-1">
                            <option value="percentage">Persentase</option>
                            <option value="percentage_max">Persentase + Max Nominal</option>
                            <option value="nominal">Nominal</option>
                        </select>
                    </div>
                    <InputError :message="form.errors.type" />
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="value" class="text-right">Nilai</Label>
                        <Input id="value" type="number" v-model.number="form.value" min="0" required class="col-span-3" />
                    </div>
                    <InputError :message="form.errors.value" />
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="max_nominal" class="text-right">Max Nominal</Label>
                        <Input id="max_nominal" type="number" :value="form.max_nominal ?? ''" @input="form.max_nominal = $event.target.value ? Number($event.target.value) : null" min="0" class="col-span-3" />
                    </div>
                    <InputError :message="form.errors.max_nominal" />
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="expiry_date" class="text-right">Expired</Label>
                        <Input id="expiry_date" type="date" v-model="form.expiry_date" required class="col-span-3" />
                    </div>
                    <InputError :message="form.errors.expiry_date" />
                    <div class="grid grid-cols-4 items-center gap-4">
                        <!-- Status field removed -->
                    </div>
                    <!-- Error for is_active removed -->
                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isConfirmDeleteDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Penghapusan</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus voucher "<strong>{{ voucherToDelete?.name }}</strong>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteVoucher" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Print Dialog -->
        <Dialog v-model:open="isPrintDialogOpen">
            <DialogContent class="max-w-[400px] p-0">
                <div class="flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 via-white to-gray-200 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 rounded-2xl shadow-lg p-8 m-0 border border-gray-200 dark:border-gray-700">
                    <div id="voucher-print-area" class="w-full text-center">
                        <h2 class="text-2xl font-extrabold mb-4 text-blue-700 dark:text-blue-400 tracking-wide uppercase">
                            Voucher <span v-if="tenantName">({{ tenantName }})</span>
                        </h2>
                        <div class="flex flex-col gap-2 items-center mb-4">
                            <div class="flex justify-between w-full max-w-xs">
                                <span class="font-medium text-gray-600 dark:text-gray-300">Kode</span>
                                <span class="font-bold tracking-widest text-blue-700 dark:text-blue-400">{{ voucherToPrint?.code }}</span>
                            </div>
                            <div class="flex justify-between w-full max-w-xs">
                                <span class="font-medium text-gray-600 dark:text-gray-300">Nama</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ voucherToPrint?.name }}</span>
                            </div>
                            <div class="flex justify-between w-full max-w-xs">
                                <span class="font-medium text-gray-600 dark:text-gray-300">Tipe</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ voucherToPrint?.type }}</span>
                            </div>
                            <div class="flex justify-between w-full max-w-xs">
                                <span class="font-medium text-gray-600 dark:text-gray-300">Nilai</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ voucherToPrint?.value }}</span>
                            </div>
                            <div class="flex justify-between w-full max-w-xs">
                                <span class="font-medium text-gray-600 dark:text-gray-300">Expired</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ voucherToPrint?.expiry_date }}</span>
                            </div>
                        </div>
                        <div class="border-t border-dashed border-gray-300 dark:border-gray-700 my-4"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">*Tunjukkan voucher ini saat transaksi</div>
                    </div>
                </div>
                <DialogFooter>
                    <div class="flex flex-row gap-2 justify-center mt-2 mx-auto w-full max-w-xs mb-5">
                        <Button @click="printVoucher" class="flex-1 flex items-center justify-center gap-2">
                            <Printer class="h-4 w-4" />
                            Print
                        </Button>
                        <Button variant="outline" @click="closePrintDialog" class="flex-1">Tutup</Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>