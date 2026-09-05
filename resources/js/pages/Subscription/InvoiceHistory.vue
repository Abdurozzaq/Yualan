
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ChevronUp, ChevronDown, Search, Eye } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Props from controller
interface Invoice {
  id: string|number;
  created_at: string;
  plan_name: string;
  expired_at: string;
  amount: number;
}

interface PaginatedInvoices {
  data: Invoice[];
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
  invoices: PaginatedInvoices;
  filters?: Filters;
  tenantSlug: string;
}>();

const breadcrumbs = [
  {
    title: 'Dashboard',
    href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
  },
  { title: 'Riwayat Invoice', href: route('invoices.history', { tenantSlug: props.tenantSlug }) },
];

const currentPerPage = ref(props.filters?.perPage ?? 10);
const currentSortBy = ref(props.filters?.sortBy ?? 'created_at');
const currentSortDirection = ref(props.filters?.sortDirection ?? 'desc');
const currentSearch = ref(props.filters?.search ?? '');
const currentFilterField = ref(props.filters?.filterField ?? 'plan_name');

const handleSort = (field: string) => {
  if (currentSortBy.value === field) {
    currentSortDirection.value = currentSortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    currentSortBy.value = field;
    currentSortDirection.value = 'asc';
  }
};


watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch, currentFilterField], () => {
  router.get(route('invoices.history', { tenantSlug: props.tenantSlug }), {
    perPage: currentPerPage.value,
    sortBy: currentSortBy.value,
    sortDirection: currentSortDirection.value,
    search: currentSearch.value,
    filterField: currentFilterField.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['invoices', 'filters'],
  });
}, { deep: true });

let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(route('invoices.history', { tenantSlug: props.tenantSlug }), {
      perPage: currentPerPage.value,
      sortBy: currentSortBy.value,
      sortDirection: currentSortDirection.value,
      search: currentSearch.value,
      filterField: currentFilterField.value,
    }, {
      preserveState: true,
      preserveScroll: true,
      only: ['invoices', 'filters'],
    });
  }, 300);
};

function formatDate(date: string) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
}
function formatCurrency(amount: number) {
  if (!amount) return '0';
  return Number(amount).toLocaleString('id-ID');
}
</script>

<template>
  <Head title="Riwayat Invoice Langganan" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
          Riwayat Invoice Langganan
        </h1>
      </div>

      <!-- Filter and Search Section -->
      <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
        <div class="relative flex-grow">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
          <Input
            type="text"
            placeholder="Cari invoice..."
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
              <SelectItem value="plan_name">Nama Plan</SelectItem>
              <SelectItem value="created_at">Tanggal</SelectItem>
              <SelectItem value="expired_at">Expired</SelectItem>
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

      <!-- Invoice List Table -->
      <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[50px]">No.</TableHead>
              <TableHead
                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                @click="handleSort('created_at')"
              >
                <div class="flex items-center gap-1">
                  Tanggal
                  <template v-if="currentSortBy === 'created_at'">
                    <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                    <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                  </template>
                </div>
              </TableHead>
              <TableHead
                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                @click="handleSort('plan_name')"
              >
                <div class="flex items-center gap-1">
                  Plan
                  <template v-if="currentSortBy === 'plan_name'">
                    <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                    <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                  </template>
                </div>
              </TableHead>
              <TableHead>Expired</TableHead>
              <TableHead>Total</TableHead>
              <TableHead class="w-[80px] text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="!props.invoices?.data?.length">
              <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                Belum ada invoice yang tercatat atau tidak ada hasil yang cocok.
              </TableCell>
            </TableRow>
            <TableRow v-for="(inv, index) in props.invoices?.data ?? []" :key="inv.id">
              <TableCell>{{ (props.invoices?.from ?? 1) + index }}</TableCell>
              <TableCell>{{ formatDate(inv.created_at) }}</TableCell>
              <TableCell>{{ inv.plan_name }}</TableCell>
              <TableCell>{{ formatDate(inv.expired_at) }}</TableCell>
              <TableCell>Rp{{ formatCurrency(inv.amount) }}</TableCell>
              <TableCell class="text-right">
                <a :href="route('invoice.show', { tenantSlug: props.tenantSlug, id: inv.id })" target="_blank">
                  <Button variant="ghost" size="icon">
                    <Eye class="h-4 w-4" />
                    <span class="sr-only">Lihat Invoice</span>
                  </Button>
                </a>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination Controls -->
      <div v-if="props.invoices.last_page > 1" class="flex justify-between items-center mt-4">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Menampilkan {{ props.invoices.from }} hingga {{ props.invoices.to }} dari {{ props.invoices.total }} invoice
        </div>
        <div class="flex items-center gap-2">
          <Button
            v-for="(link, index) in props.invoices.links"
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
  </AppLayout>
</template>
