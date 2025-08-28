<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle, PlusCircle, Edit, Trash2, ChevronUp, ChevronDown, Search, Printer } from 'lucide-vue-next'; // Import Printer icon
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Props from controller
interface Customer {
    id: string;
    tenant_id: string;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginatedCustomers {
    data: Customer[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    sortBy: string;
    sortDirection: 'asc' | 'desc';
    perPage: number;
    search: string | null;
    filterField: string | null;
}

const props = defineProps<{
    customers: PaginatedCustomers;
    filters: Filters;
    tenantSlug: string;
    tenantName: string;
}>();

// Inertia page props

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Pelanggan',
        href: route('customers.index', { tenantSlug: props.tenantSlug }),
    },
];

// Reactive state for filters and sorting
const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');
const currentFilterField = ref(props.filters.filterField || 'name'); // Default filter field

// Form state for adding/editing customers
const form = useForm({
    id: null as string | null, // Used for editing
    name: '',
    email: '',
    phone: '',
    address: '',
});

// State for dialogs
const isFormDialogOpen = ref(false);
const isConfirmDeleteDialogOpen = ref(false);
const customerToDelete = ref<Customer | null>(null);

// Form title for dialog
const formTitle = computed(() => (form.id ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru'));

// Function to open the add/edit dialog
const openFormDialog = (customer: Customer | null = null) => {
    form.reset(); // Reset form state
    if (customer) {
        form.id = customer.id;
        form.name = customer.name;
        form.email = customer.email || '';
        form.phone = customer.phone || '';
        form.address = customer.address || '';
    }
    isFormDialogOpen.value = true;
};

// Function to handle form submission (create or update)
const submitForm = () => {
    if (form.id) {
        // Update existing customer
        form.put(route('customers.update', { tenantSlug: props.tenantSlug, customer: form.id }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
            },
            onError: () => {
                // Errors will be displayed by InputError component
            },
        });
    } else {
        // Create new customer
        form.post(route('customers.store', { tenantSlug: props.tenantSlug }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
            },
            onError: () => {
                // Errors will be displayed by InputError component
            },
        });
    }
};

// Function to open delete confirmation dialog
const openConfirmDeleteDialog = (customer: Customer) => {
    customerToDelete.value = customer;
    isConfirmDeleteDialogOpen.value = true;
};

// Function to handle customer deletion
const deleteCustomer = () => {
    if (!customerToDelete.value) {
        alert('Pelanggan tidak ditemukan. Tidak dapat menghapus.');
        return;
    }
    form.delete(route('customers.destroy', { tenantSlug: props.tenantSlug, customer: customerToDelete.value.id }), {
        onSuccess: () => {
            isConfirmDeleteDialogOpen.value = false;
            customerToDelete.value = null;
        },
        onError: () => {
            // Handle error, maybe show a toast
        },
    }, {
        preserveScroll: true, // Keep scroll position after deletion
    });
};

// Function to handle sorting
const handleSort = (field: string) => {
    if (currentSortBy.value === field) {
        currentSortDirection.value = currentSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortBy.value = field;
        currentSortDirection.value = 'asc';
    }
};

// Watch for changes in filters and trigger Inertia visit
watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch, currentFilterField], () => {
    router.get(route('customers.index', { tenantSlug: props.tenantSlug }), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
        filterField: currentFilterField.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['customers', 'filters'],
    });
}, { deep: true });

// Function to apply search filter on input change (e.g., debounce)
let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('customers.index', { tenantSlug: props.tenantSlug }), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
            filterField: currentFilterField.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['customers', 'filters'],
        });
    }, 300); // Debounce for 300ms
};

// Function to open ID card in a new tab
const exportIdCard = (customer: Customer) => {
    const url = route('customers.idCard', { tenantSlug: props.tenantSlug, customer: customer.id });
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="Master Pelanggan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Master Pelanggan {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <Button @click="openFormDialog()" class="flex items-center gap-2">
                    <PlusCircle class="h-4 w-4" />
                    Tambah Pelanggan
                </Button>
            </div>

            <!-- Filter and Search Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari pelanggan..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model="currentFilterField">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filter Berdasarkan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="name">Nama Pelanggan</SelectItem>
                            <SelectItem value="email">Email</SelectItem>
                            <SelectItem value="phone">Telepon</SelectItem>
                            <SelectItem value="address">Alamat</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model.number="currentPerPage">
                        <SelectTrigger class="w-full sm:w-[100px]">
                            <SelectValue placeholder="Per Halaman" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="5">5</SelectItem>
                            <SelectItem :value="10">10</SelectItem>
                            <SelectItem :value="25">25</SelectItem>
                            <SelectItem :value="50">50</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Customer List Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('name')"
                            >
                                <div class="flex items-center gap-1">
                                    Nama Pelanggan
                                    <template v-if="currentSortBy === 'name'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('email')"
                            >
                                <div class="flex items-center gap-1">
                                    Email
                                    <template v-if="currentSortBy === 'email'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('phone')"
                            >
                                <div class="flex items-center gap-1">
                                    Telepon
                                    <template v-if="currentSortBy === 'phone'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Alamat</TableHead>
                            <TableHead class="w-[150px] text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.customers.data.length === 0">
                            <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                                Belum ada pelanggan yang ditambahkan atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(customer, index) in props.customers.data" :key="customer.id">
                            <TableCell>{{ props.customers.from + index }}</TableCell>
                            <TableCell class="font-medium">{{ customer.name }}</TableCell>
                            <TableCell>{{ customer.email || '-' }}</TableCell>
                            <TableCell>{{ customer.phone || '-' }}</TableCell>
                            <TableCell>{{ customer.address || '-' }}</TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" @click="exportIdCard(customer)" class="mr-1">
                                    <Printer class="h-4 w-4" />
                                    <span class="sr-only">Cetak ID Card</span>
                                </Button>
                                <Button variant="ghost" size="icon" @click="openFormDialog(customer)" class="mr-1">
                                    <Edit class="h-4 w-4" />
                                    <span class="sr-only">Edit</span>
                                </Button>
                                <Button variant="ghost" size="icon" @click="openConfirmDeleteDialog(customer)">
                                    <Trash2 class="h-4 w-4 text-red-500" />
                                    <span class="sr-only">Delete</span>
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Controls -->
            <div v-if="props.customers.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.customers.from }} hingga {{ props.customers.to }} dari {{ props.customers.total }} pelanggan
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.customers.links"
                        :key="index"
                        :as="Link"
                        :href="link.url || '#'"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        class="px-3 py-1 rounded-md text-sm"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Add/Edit Customer Dialog -->
        <Dialog v-model:open="isFormDialogOpen">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>{{ formTitle }}</DialogTitle>
                    <DialogDescription>
                        Isi detail pelanggan di bawah ini. Klik simpan saat Anda selesai.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">Nama Pelanggan</Label>
                        <Input id="name" v-model="form.name" required class="col-span-3" />
                        <InputError :message="form.errors.name" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="email" class="text-right">Email (Opsional)</Label>
                        <Input id="email" type="email" v-model="form.email" class="col-span-3" />
                        <InputError :message="form.errors.email" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="phone" class="text-right">Telepon (Opsional)</Label>
                        <Input id="phone" type="text" v-model="form.phone" class="col-span-3" />
                        <InputError :message="form.errors.phone" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="address" class="text-right">Alamat (Opsional)</Label>
                        <Textarea id="address" v-model="form.address" rows="3" class="col-span-3" />
                        <InputError :message="form.errors.address" class="col-span-4 col-start-2" />
                    </div>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Simpan Pelanggan
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
                        Apakah Anda yakin ingin menghapus pelanggan "<strong>{{ customerToDelete?.name }}</strong>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteCustomer" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

