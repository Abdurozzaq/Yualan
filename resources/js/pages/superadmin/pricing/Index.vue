<script setup lang="ts">
import SuperadminLayout from '@/layouts/app/SuperadminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle, PlusCircle, Edit, Trash2, ChevronUp, ChevronDown, Search } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/utils/formatters';

interface PricingPlan {
  id: string;
  plan_name: string;
  plan_description: string | null;
  period_type: 'monthly' | 'quarterly' | 'yearly';
  price: number | string;
  discount_percentage: number | string;
  created_at: string;
  updated_at: string;
}

interface PaginatedPlans {
  data: PricingPlan[];
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

const props = defineProps<{ plans: PaginatedPlans; filters: Filters }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Superadmin Dashboard', href: route('superadmin.dashboard') },
  { title: 'Pricing Plans', href: route('superadmin.pricing.index') },
];

// Filters state
const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');
const currentFilterField = ref(props.filters.filterField || 'plan_name');

watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch, currentFilterField], () => {
  router.get(route('superadmin.pricing.index'), {
    perPage: currentPerPage.value,
    sortBy: currentSortBy.value,
    sortDirection: currentSortDirection.value,
    search: currentSearch.value,
    filterField: currentFilterField.value,
  }, { preserveState: true, preserveScroll: true, only: ['plans', 'filters'] });
});

let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(route('superadmin.pricing.index'), {
      perPage: currentPerPage.value,
      sortBy: currentSortBy.value,
      sortDirection: currentSortDirection.value,
      search: currentSearch.value,
      filterField: currentFilterField.value,
    }, { preserveState: true, preserveScroll: true, only: ['plans', 'filters'] });
  }, 300);
};

const handleSort = (field: string) => {
  if (currentSortBy.value === field) {
    currentSortDirection.value = currentSortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    currentSortBy.value = field;
    currentSortDirection.value = 'asc';
  }
};

// Dialogs and forms
const isFormDialogOpen = ref(false);
const isConfirmDeleteDialogOpen = ref(false);

const form = useForm({
  id: null as string | null,
  plan_name: '',
  plan_description: '',
  period_type: 'monthly' as 'monthly' | 'quarterly' | 'yearly',
  price: 0,
  discount_percentage: 0,
});

const formTitle = computed(() => (form.id ? 'Edit Pricing Plan' : 'Tambah Pricing Plan'));

const openFormDialog = (plan: PricingPlan | null = null) => {
  form.clearErrors();
  form.reset();
  if (plan) {
    form.id = plan.id;
    form.plan_name = plan.plan_name;
    form.plan_description = plan.plan_description || '';
    form.period_type = plan.period_type;
    form.price = Number(plan.price);
    form.discount_percentage = Number(plan.discount_percentage);
  }
  isFormDialogOpen.value = true;
};

const submitForm = () => {
  if (form.id) {
    form.put(route('superadmin.pricing.update', { pricing: form.id }), {
      preserveScroll: true,
      onSuccess: () => { isFormDialogOpen.value = false; form.reset(); },
    });
  } else {
    form.post(route('superadmin.pricing.store'), {
      preserveScroll: true,
      onSuccess: () => { isFormDialogOpen.value = false; form.reset(); },
    });
  }
};

const planToDelete = ref<PricingPlan | null>(null);
const openConfirmDeleteDialog = (plan: PricingPlan) => {
  planToDelete.value = plan;
  isConfirmDeleteDialogOpen.value = true;
};

const deletePlan = () => {
  if (!planToDelete.value) return;
  form.delete(route('superadmin.pricing.destroy', { pricing: planToDelete.value.id }), {
    preserveScroll: true,
    onSuccess: () => { isConfirmDeleteDialogOpen.value = false; planToDelete.value = null; },
  });
};
</script>

<template>
  <Head title="Superadmin - Pricing Plans" />
  <SuperadminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Pricing Plans</h1>
        <Button @click="openFormDialog()" class="flex items-center gap-2">
          <PlusCircle class="h-4 w-4" />
          Tambah Plan
        </Button>
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
        <div class="relative w-full sm:w-1/2 md:w-1/3">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
          <Input type="text" placeholder="Cari plan..." v-model="currentSearch" @input="applySearch" class="pl-9 pr-3 py-2" />
        </div>
        <div class="w-full sm:w-auto">
          <Select v-model="currentFilterField">
            <SelectTrigger class="w-full sm:w-[200px]"><SelectValue placeholder="Filter Berdasarkan" /></SelectTrigger>
            <SelectContent>
              <SelectItem value="plan_name">Nama Plan</SelectItem>
              <SelectItem value="plan_description">Deskripsi</SelectItem>
              <SelectItem value="period_type">Periode</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="w-full sm:w-auto">
          <Select v-model.number="currentPerPage">
            <SelectTrigger class="w-full sm:w-[120px]"><SelectValue placeholder="Per Halaman" /></SelectTrigger>
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
              <TableHead class="cursor-pointer" @click="handleSort('plan_name')">
                <div class="flex items-center gap-1">
                  Nama Plan
                  <template v-if="currentSortBy === 'plan_name'">
                    <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                    <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                  </template>
                </div>
              </TableHead>
              <TableHead>Deskripsi</TableHead>
              <TableHead class="cursor-pointer" @click="handleSort('period_type')">
                <div class="flex items-center gap-1">
                  Periode
                  <template v-if="currentSortBy === 'period_type'">
                    <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                    <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                  </template>
                </div>
              </TableHead>
              <TableHead class="cursor-pointer" @click="handleSort('price')">
                <div class="flex items-center gap-1">
                  Harga
                  <template v-if="currentSortBy === 'price'">
                    <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                    <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                  </template>
                </div>
              </TableHead>
              <TableHead>Diskon (%)</TableHead>
              <TableHead class="w-[120px] text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="props.plans.data.length === 0">
              <TableCell colspan="7" class="text-center text-muted-foreground py-8">Belum ada plan.</TableCell>
            </TableRow>
            <TableRow v-for="(plan, index) in props.plans.data" :key="plan.id">
              <TableCell>{{ props.plans.from + index }}</TableCell>
              <TableCell class="font-medium">{{ plan.plan_name }}</TableCell>
              <TableCell>{{ plan.plan_description || '-' }}</TableCell>
              <TableCell class="uppercase">{{ plan.period_type }}</TableCell>
              <TableCell>{{ formatCurrency(Number(plan.price)) }}</TableCell>
              <TableCell>{{ Number(plan.discount_percentage) }}%</TableCell>
              <TableCell class="text-right">
                <Button variant="ghost" size="icon" @click="openFormDialog(plan)" class="mr-1">
                  <Edit class="h-4 w-4" />
                  <span class="sr-only">Edit</span>
                </Button>
                <Button variant="ghost" size="icon" @click="openConfirmDeleteDialog(plan)">
                  <Trash2 class="h-4 w-4 text-red-500" />
                  <span class="sr-only">Delete</span>
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <div v-if="props.plans.last_page > 1" class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Menampilkan {{ props.plans.from }} hingga {{ props.plans.to }} dari {{ props.plans.total }} plans
        </div>
        <div class="flex items-center gap-2">
          <Button
            v-for="(link, index) in props.plans.links"
            :key="index"
            :as="Link"
            :href="link.url || '#'"
            :disabled="!link.url"
            :variant="link.active ? 'default' : 'outline'"
            class="px-3 py-1 rounded-md text-sm">
            <span v-html="link.label"></span>
          </Button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog v-model:open="isFormDialogOpen">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>{{ formTitle }}</DialogTitle>
          <DialogDescription>Isi detail pricing plan di bawah ini, lalu simpan.</DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label for="plan_name">Nama Plan</Label>
            <Input id="plan_name" v-model="form.plan_name" required />
            <InputError :message="form.errors.plan_name" />
          </div>
          <div class="grid gap-2">
            <Label for="plan_description">Deskripsi (Opsional)</Label>
            <Textarea id="plan_description" v-model="form.plan_description" rows="3" />
            <InputError :message="form.errors.plan_description" />
          </div>
          <div class="grid gap-2">
            <Label for="period_type">Periode</Label>
            <Select v-model="form.period_type">
              <SelectTrigger class="w-full"><SelectValue placeholder="Pilih periode" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="monthly">Bulanan</SelectItem>
                <SelectItem value="quarterly">Triwulan</SelectItem>
                <SelectItem value="yearly">Tahunan</SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.period_type" />
          </div>
          <div class="grid gap-2">
            <Label for="price">Harga</Label>
            <Input id="price" type="number" step="0.01" min="0" v-model.number="form.price" required />
            <InputError :message="form.errors.price" />
          </div>
          <div class="grid gap-2">
            <Label for="discount_percentage">Diskon (%)</Label>
            <Input id="discount_percentage" type="number" step="0.01" min="0" max="100" v-model.number="form.discount_percentage" />
            <InputError :message="form.errors.discount_percentage" />
          </div>
          <DialogFooter>
            <Button type="submit" :disabled="form.processing">
              <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
              Simpan Plan
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
            Apakah Anda yakin ingin menghapus plan "<strong>{{ planToDelete?.plan_name }}</strong>"? Tindakan ini tidak dapat dibatalkan.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="isConfirmDeleteDialogOpen = false">Batal</Button>
          <Button variant="destructive" @click="deletePlan" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
            Hapus
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </SuperadminLayout>
</template>
