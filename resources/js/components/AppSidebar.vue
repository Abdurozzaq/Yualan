<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
// Pastikan semua ikon yang digunakan diimpor
import { Folder, LayoutGrid, Tag, Users, ShoppingBag, History, Warehouse, BarChart } from 'lucide-vue-next'; // Tambahkan Warehouse dan BarChart

import AppLogo from './AppLogo.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { useSidebar } from '@/components/ui/sidebar/utils';

// Theme switcher logic
// Removed unused toggleTheme function

const page = usePage();
const tenantSlug = computed(() => page.props.tenantSlug as string | undefined);
const userRole = computed(() => page.props.auth.user?.role as string | undefined); // Dapatkan userRole dari props Inertia
// Tenant info via AJAX to ensure fresh data even when Inertia props don't include tenant
const tenant = ref<any | null>(null);

// Define trialDays, update when tenant changes
const trialDays = computed(() => tenant.value?.isInternal ?? 'INTERNAL');

async function fetchTenantInfo(slug?: string) {
    if (!slug) {
        tenant.value = null;
        return;
    }
    try {
        // Prefer Ziggy route if available
        const url = route ? route('tenant.info', { tenantSlug: slug }) : `/${slug}/tenant/info`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        tenant.value = await res.json();
    } catch (e) {
        console.error('Failed to fetch tenant info', e);
        tenant.value = null;
    }
}

// Fetch on mount and whenever tenantSlug changes
onMounted(() => fetchTenantInfo(tenantSlug.value));
watch(tenantSlug, (slug) => fetchTenantInfo(slug));

// Definisikan item navigasi utama sebagai computed property
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: tenantSlug.value ? route('tenant.dashboard', { tenantSlug: tenantSlug.value }) : route('dashboard.default'),
            icon: LayoutGrid,
        },
    ];



    // Hanya tambahkan tautan jika tenantSlug tersedia dan user bukan superadmin
    if (tenantSlug.value && userRole.value !== 'superadmin') {
        items.push({
            title: 'Pemesanan',
            href: route('sales.order', { tenantSlug: tenantSlug.value }),
            icon: ShoppingBag,
        });
        items.push({
            title: 'Riwayat Penjualan',
            href: route('sales.history', { tenantSlug: tenantSlug.value }),
            icon: History,
        });
        
        items.push({
            title: 'Master Data',
            children: [
                { title: 'Kategori', href: route('categories.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Produk', href: route('products.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Pelanggan', href: route('customers.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Supplier', href: route('suppliers.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Voucher', href: route('vouchers.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Promo', href: route('promos.index', { tenantSlug: tenantSlug.value }) },
            ],
            icon: Folder,
            href: '' // href for parent is optional if children exist
        });
        items.push({
            title: 'Inventaris', // NEW MAIN MENU ITEM
            children: [
                { title: 'Ringkasan Inventaris', href: route('inventory.overview', { tenantSlug: tenantSlug.value }) },
                { title: 'Riwayat Pergerakan', href: route('inventory.movements', { tenantSlug: tenantSlug.value }) },
                { title: 'Penerimaan Barang', href: route('inventory.receive.form', { tenantSlug: tenantSlug.value }) },
                { title: 'Penyesuaian Stok', href: route('inventory.adjust.form', { tenantSlug: tenantSlug.value }) },
                // Optional: Return Goods (uncomment if you add the route and component)
                // { title: 'Pengembalian Barang', href: route('inventory.return.form', { tenantSlug: tenantSlug.value }) },
            ],
            icon: Warehouse,
            href: '' // href for parent is optional if children exist
        });
        items.push({
            title: 'Laporan', // NEW MAIN MENU ITEM
            children: [
                { title: 'Laba Kotor', href: route('reports.grossProfit', { tenantSlug: tenantSlug.value }) },
                { title: 'Laba Bersih', href: route('reports.netProfit', { tenantSlug: tenantSlug.value }) },
                { title: 'Nilai Stok', href: route('reports.stock', { tenantSlug: tenantSlug.value }) },
                { title: 'Produk Terlaris & Margin', href: route('reports.product-margin', { tenantSlug: tenantSlug.value }) }, // <-- Tambahkan ini
                // Laporan Penjualan Detail: Export transaksi penjualan lengkap (per hari/bulan/tahun), termasuk item, metode pembayaran, diskon, pajak, dan kasir.
                { title: 'Penjualan Detail', href: route('reports.salesDetail', { tenantSlug: tenantSlug.value }) },
                // Tambahkan link Pembayaran & Piutang
                { title: 'Pembayaran & Piutang', href: route('reports.payments', { tenantSlug: tenantSlug.value }) },
            ],
            icon: BarChart,
            href: '' // href for parent is optional if children exist
        });
        items.push({
            title: 'Karyawan',
            href: route('employees.index', { tenantSlug: tenantSlug.value }),
            icon: Users,
        });
    }

    return items;
});

const footerNavItems = computed<NavItem[]>(() => []);

const isSubscriptionExpired = computed(() => {
    
    if (userRole.value !== 'superadmin') {
        const today = new Date();
      const todayFormatted = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
      return todayFormatted >= tenant.value?.subscription_ends_at;
    } else {
        return null;
    }
  
});

const { state } = useSidebar(); // state: ComputedRef<'expanded' | 'collapsed'>
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="h-screen shadow-lg border-r border-gray-200 dark:border-gray-800 flex flex-col bg-white dark:bg-gray-900"
    >
        <SidebarHeader class="py-4 px-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-center bg-white dark:bg-gray-900">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton style="margin-left: -8px;" size="lg" as-child>
                        <Link :href="tenantSlug ? route('tenant.dashboard', { tenantSlug: tenantSlug }) : route('dashboard.default')">
                            <AppLogo class="w-10 h-10" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="flex-1 px-2 py-4 overflow-y-auto">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter
            class="px-4 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900"
        >
            <!-- Community Edition Branding -->
            <div
                v-if="state !== 'collapsed'"
                class="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-100 dark:border-blue-900/30"
            >
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></div>
                        <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Active Node</p>
                    </div>
                    <p class="text-xs font-black text-gray-900 dark:text-gray-100 leading-tight">Yualan Community Edition</p>
                    <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-tight">Dedicated Enterprise</p>
                </div>
            </div>
            <div class=" pt-3 mt-2">
                <NavFooter :items="footerNavItems" class="mb-2" />
                <NavUser />
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
