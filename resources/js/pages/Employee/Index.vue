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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { LoaderCircle, PlusCircle, Edit, Trash2, ChevronUp, ChevronDown, Search } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { format } from 'date-fns';

interface Employee {
    id: string;
    name: string;
    email: string;
    role: 'admin' | 'cashier';
    created_at: string;
    deleted_at?: string | null; // Soft delete indicator
}

interface PaginatedEmployees {
    data: Employee[];
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
}

const props = defineProps<{
    employees: PaginatedEmployees;
    filters: Filters;
    tenantSlug: string;
    tenantName: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }) },
    { title: 'Karyawan', href: route('employees.index', { tenantSlug: props.tenantSlug }) },
];

const page = usePage();
const currentUserRole = computed(() => page.props.auth?.user?.role ?? 'cashier'); // Get current user role

const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');

const form = useForm({
    id: null as string | null,
    name: '',
    email: '',
    role: 'cashier' as 'admin' | 'cashier',
    password: '',
    _method: 'post' as 'post' | 'put',
});

const isFormDialogOpen = ref(false);
const isConfirmDeleteDialogOpen = ref(false);
const employeeToDelete = ref<Employee | null>(null);
const isChangePasswordDialogOpen = ref(false);
const employeeToChangePassword = ref<Employee | null>(null);
const changePasswordForm = useForm({
    password: '',
});

const formTitle = computed(() => (form.id ? 'Edit Karyawan' : 'Tambah Karyawan'));

const openFormDialog = (employee: Employee | null = null) => {
    form.reset();
    form.clearErrors();
    if (employee) {
        form.id = employee.id;
        form.name = employee.name;
        form.email = employee.email;
        form.role = employee.role;
        form.password = '';
        form._method = 'put';
    } else {
        form._method = 'post';
    }
    isFormDialogOpen.value = true;
};

const submitForm = () => {
    if (form.id) {
        form.post(route('employees.update', { tenantSlug: props.tenantSlug, employee: form.id }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('employees.store', { tenantSlug: props.tenantSlug }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
            },
        });
    }
};

const openConfirmDeleteDialog = (employee: Employee) => {
    employeeToDelete.value = employee;
    isConfirmDeleteDialogOpen.value = true;
};

const deleteEmployee = () => {
    if (!employeeToDelete.value) return;
    form.delete(route('employees.destroy', { tenantSlug: props.tenantSlug, employee: employeeToDelete.value.id }), {
        onSuccess: () => {
            isConfirmDeleteDialogOpen.value = false;
            employeeToDelete.value = null;
        },
    }, { preserveScroll: true });
};

const openChangePasswordDialog = (employee: Employee) => {
    employeeToChangePassword.value = employee;
    changePasswordForm.reset();
    changePasswordForm.clearErrors();
    isChangePasswordDialogOpen.value = true;
};

const submitChangePassword = () => {
    if (!employeeToChangePassword.value) return;
    changePasswordForm.put(route('employees.change_password', { tenantSlug: props.tenantSlug, employee: employeeToChangePassword.value.id }), {
        onSuccess: () => {
            isChangePasswordDialogOpen.value = false;
            employeeToChangePassword.value = null;
            changePasswordForm.reset();
        },
    });
};

const restoreEmployee = (employee: Employee) => {
    form.put(route('employees.restore', { tenantSlug: props.tenantSlug, employee: employee.id }), {
        onSuccess: () => {
            // Optionally reset form/dialog state
        },
    });
};

const handleSort = (field: string) => {
    if (currentSortBy.value === field) {
        currentSortDirection.value = currentSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortBy.value = field;
        currentSortDirection.value = 'asc';
    }
};

watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch], () => {
    router.get(route('employees.index', { tenantSlug: props.tenantSlug }), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['employees', 'filters'],
    });
}, { deep: true });

let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('employees.index', { tenantSlug: props.tenantSlug }), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['employees', 'filters'],
        });
    }, 300);
};

function formatDate(dateStr: string) {
    if (!dateStr) return '';
    try {
        return format(new Date(dateStr), 'dd MMM yyyy HH:mm');
    } catch {
        return dateStr;
    }
}
</script>

<template>
    <Head title="Manajemen Karyawan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Manajemen Karyawan {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <Button @click="openFormDialog()" class="flex items-center gap-2">
                    <PlusCircle class="h-4 w-4" />
                    Tambah Karyawan
                </Button>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari karyawan..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
                    />
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
                                    Nama
                                    <template v-if="currentSortBy === 'name'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('role')"
                            >
                                <div class="flex items-center gap-1">
                                    Peran
                                    <template v-if="currentSortBy === 'role'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Dibuat</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-[180px] text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.employees.data.length === 0">
                            <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                                Belum ada karyawan yang ditambahkan atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="(employee, index) in props.employees.data"
                            :key="employee.id"
                            :class="employee.deleted_at ? 'bg-red-50 dark:bg-red-900 opacity-60' : ''"
                        >
                            <TableCell>{{ props.employees.from + index }}</TableCell>
                            <TableCell class="font-medium">{{ employee.name }}</TableCell>
                            <TableCell>{{ employee.email }}</TableCell>
                            <TableCell>
                                <span v-if="employee.role === 'admin'" class="px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs">Admin</span>
                                <span v-else class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs">Kasir</span>
                            </TableCell>
                            <TableCell>{{ formatDate(employee.created_at) }}</TableCell>
                            <TableCell>
                                <span v-if="employee.deleted_at" class="px-2 py-1 rounded bg-red-100 text-red-800 text-xs">Terhapus</span>
                                <span v-else class="px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs">Aktif</span>
                            </TableCell>
                            <TableCell class="text-right flex gap-1 justify-end">
                                <Button variant="ghost" size="icon" @click="openFormDialog(employee)" class="mr-1" :disabled="!!employee.deleted_at">
                                    <Edit class="h-4 w-4" />
                                    <span class="sr-only">Edit</span>
                                </Button>
                                <Button
                                    v-if="currentUserRole === 'admin' && !employee.deleted_at"
                                    variant="ghost"
                                    size="icon"
                                    @click="openChangePasswordDialog(employee)"
                                    class="mr-1"
                                >
                                    <LoaderCircle class="h-4 w-4" />
                                    <span class="sr-only">Change Password</span>
                                </Button>
                                <Button
                                    v-if="!employee.deleted_at"
                                    variant="ghost"
                                    size="icon"
                                    @click="openConfirmDeleteDialog(employee)"
                                >
                                    <Trash2 class="h-4 w-4 text-red-500" />
                                    <span class="sr-only">Delete</span>
                                </Button>
                                <Button
                                    v-if="employee.deleted_at"
                                    variant="outline"
                                    size="sm"
                                    @click="restoreEmployee(employee)"
                                >
                                    Restore
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div v-if="props.employees.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.employees.from }} hingga {{ props.employees.to }} dari {{ props.employees.total }} karyawan
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.employees.links"
                        :key="index"
                        :as="Link"
                        :href="link.url || '#'"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        class="px-3 py-1 rounded-md text-sm"
                        >{{ link.label }}
                    />
                </div>
            </div>
        </div>

        <!-- Add/Edit Employee Dialog -->
        <Dialog v-model:open="isFormDialogOpen">
            <DialogContent class="sm:max-w-[500px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ formTitle }}</DialogTitle>
                    <DialogDescription>
                        Isi detail karyawan di bawah ini. Klik simpan saat Anda selesai.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">Nama</Label>
                        <Input id="name" v-model="form.name" required class="col-span-3" />
                        <InputError :message="form.errors.name" class="col-span-4 col-start-2" />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="email" class="text-right">Email</Label>
                        <Input id="email" v-model="form.email" required type="email" class="col-span-3" />
                        <InputError :message="form.errors.email" class="col-span-4 col-start-2" />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="role" class="text-right">Peran</Label>
                        <Select v-model="form.role">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="Pilih Peran" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="cashier">Kasir</SelectItem>
                                <SelectItem value="admin">Admin</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.role" class="col-span-4 col-start-2" />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="password" class="text-right">Password</Label>
                        <Input id="password" v-model="form.password" :required="!form.id" type="password" class="col-span-3" />
                        <InputError :message="form.errors.password" class="col-span-4 col-start-2" />
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Simpan Karyawan
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
                        Apakah Anda yakin ingin menghapus karyawan "<strong>{{ employeeToDelete?.name }}</strong>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteEmployee" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Change Password Dialog -->
        <Dialog v-model:open="isChangePasswordDialogOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Ubah Password</DialogTitle>
                    <DialogDescription>
                        Masukkan password baru untuk karyawan "<strong>{{ employeeToChangePassword?.name }}</strong>".
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitChangePassword" class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="new-password" class="text-right">Password Baru</Label>
                        <Input id="new-password" v-model="changePasswordForm.password" required type="password" class="col-span-3" />
                        <InputError :message="changePasswordForm.errors.password" class="col-span-4 col-start-2" />
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="changePasswordForm.processing">
                            <LoaderCircle v-if="changePasswordForm.processing" class="h-4 w-4 animate-spin mr-2" />
                            Simpan Password
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>