<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm, Link, router } from '@inertiajs/vue3'; // Import router
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle, PlusCircle, Edit, Trash2, ChevronUp, ChevronDown, Search } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Props from controller
interface Category {
    id: string;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginatedCategories {
    data: Category[];
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
    categories: PaginatedCategories;
    filters: Filters;
    // tenantSlug dan tenantName akan tersedia jika halaman dirender di bawah rute tenant
    tenantSlug?: string;
    tenantName?: string;
}>();

// Inertia page props
const page = usePage();
// Menggunakan props yang diterima atau dari usePage().props jika tidak langsung dari controller
const tenantSlug = computed(() => props.tenantSlug || page.props.tenantSlug as string | undefined);
const tenantName = computed(() => props.tenantName || page.props.tenantName as string | undefined);


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: tenantSlug.value ? route('tenant.dashboard', { tenantSlug: tenantSlug.value }) : route('dashboard.default'),
    },
    {
        title: 'Kategori',
        href: tenantSlug.value ? route('categories.index', { tenantSlug: tenantSlug.value }) : '#',
    },
];

// Reactive state for filters and sorting
const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');
const currentFilterField = ref(props.filters.filterField || 'name'); // Default filter field

// Form state for adding/editing categories
const form = useForm({
    id: null as string | null, // Used for editing
    name: '',
    description: '',
});

// State for dialogs
const isFormDialogOpen = ref(false);
const isConfirmDeleteDialogOpen = ref(false);
const categoryToDelete = ref<Category | null>(null);

// Form title for dialog
const formTitle = computed(() => (form.id ? 'Edit Kategori' : 'Tambah Kategori Baru'));

// Function to open the add/edit dialog
const openFormDialog = (category: Category | null = null) => {
    form.reset(); // Reset form state
    if (category) {
        form.id = category.id;
        form.name = category.name;
        form.description = category.description || '';
    }
    isFormDialogOpen.value = true;
};

// Function to handle form submission (create or update)
const submitForm = () => {
    if (!tenantSlug.value) {
        alert('Tenant slug tidak ditemukan. Tidak dapat menyimpan kategori.');
        return;
    }
    if (form.id) {
        // Update existing category
        form.put(route('categories.update', { tenantSlug: tenantSlug.value, category: form.id }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
            },
            onError: () => {
                // Errors will be displayed by InputError component
            },
        });
    } else {
        // Create new category
        form.post(route('categories.store', { tenantSlug: tenantSlug.value }), {
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
const openConfirmDeleteDialog = (category: Category) => {
    categoryToDelete.value = category;
    isConfirmDeleteDialogOpen.value = true;
};

// Function to handle category deletion
const deleteCategory = () => {
    if (!tenantSlug.value || !categoryToDelete.value) {
        alert('Tenant slug atau kategori tidak ditemukan. Tidak dapat menghapus.');
        return;
    }
    form.delete(route('categories.destroy', { tenantSlug: tenantSlug.value, category: categoryToDelete.value.id }), {
        onSuccess: () => {
            isConfirmDeleteDialogOpen.value = false;
            categoryToDelete.value = null;
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
    if (!tenantSlug.value) {
        console.warn('Tenant slug tidak ditemukan, tidak dapat menerapkan filter.');
        return;
    }
    // Only trigger visit if currentSearch is not empty or filterField is selected
    // or if sorting/pagination changes
    router.get(route('categories.index', { tenantSlug: tenantSlug.value }), { // Changed page.visit to router.get
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
        filterField: currentFilterField.value,
    }, {
        preserveState: true, // Keep form state
        preserveScroll: true, // Keep scroll position
        only: ['categories', 'filters'], // Only request these props
    });
}, { deep: true });

// Function to apply search filter on input change (e.g., debounce)
let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (!tenantSlug.value) {
            console.warn('Tenant slug tidak ditemukan, tidak dapat menerapkan pencarian.');
            return;
        }
        router.get(route('categories.index', { tenantSlug: tenantSlug.value }), { // Changed page.visit to router.get
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
            filterField: currentFilterField.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['categories', 'filters'],
        });
    }, 300); // Debounce for 300ms
};

</script>

<template>
    <Head title="Master Kategori" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Master Kategori {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <Button @click="openFormDialog()" class="flex items-center gap-2">
                    <PlusCircle class="h-4 w-4" />
                    Tambah Kategori
                </Button>
            </div>

            <!-- Filter and Search Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari kategori..."
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
                            <SelectItem value="name">Nama Kategori</SelectItem>
                            <SelectItem value="description">Deskripsi</SelectItem>
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

            <!-- Category List Table -->
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
                                    Nama Kategori
                                    <template v-if="currentSortBy === 'name'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div
                                ></TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('description')"
                            >
                                <div class="flex items-center gap-1">
                                    Deskripsi
                                    <template v-if="currentSortBy === 'description'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead class="w-[120px] text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.categories.data.length === 0">
                            <TableCell colspan="4" class="text-center text-muted-foreground py-8">
                                Belum ada kategori yang ditambahkan atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(category, index) in props.categories.data" :key="category.id">
                            <TableCell>{{ props.categories.from + index }}</TableCell>
                            <TableCell class="font-medium">{{ category.name }}</TableCell>
                            <TableCell>{{ category.description || '-' }}</TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" @click="openFormDialog(category)" class="mr-1">
                                    <Edit class="h-4 w-4" />
                                    <span class="sr-only">Edit</span>
                                </Button>
                                <Button variant="ghost" size="icon" @click="openConfirmDeleteDialog(category)">
                                    <Trash2 class="h-4 w-4 text-red-500" />
                                    <span class="sr-only">Delete</span>
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Controls -->
            <div v-if="props.categories.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.categories.from }} hingga {{ props.categories.to }} dari {{ props.categories.total }} kategori
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.categories.links"
                        :key="index"
                        :as="Link"
                        :href="link.url || '#'"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        class="px-3 py-1 rounded-md text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Add/Edit Category Dialog -->
        <Dialog v-model:open="isFormDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ formTitle }}</DialogTitle>
                    <DialogDescription>
                        Isi detail kategori di bawah ini. Klik simpan saat Anda selesai.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Nama Kategori</Label>
                        <Input id="name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Deskripsi (Opsional)</Label>
                        <Textarea id="description" v-model="form.description" rows="3" />
                        <InputError :message="form.errors.description" />
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Simpan Kategori
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
                        Apakah Anda yakin ingin menghapus kategori "<strong>{{ categoryToDelete?.name }}</strong>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteCategory" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

