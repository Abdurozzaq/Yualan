<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
// Pastikan semua ikon yang digunakan diimpor
import { BookOpen, Folder, LayoutGrid, Tag, Package, Users, ShoppingBag, History, Warehouse, BarChart, Truck, FileText } from 'lucide-vue-next'; // Tambahkan Warehouse dan BarChart

import AppLogo from './AppLogo.vue';
import { computed, onMounted, ref, watch } from 'vue';
// Theme switcher logic
const isDark = ref(false);
const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

const page = usePage();
const tenantSlug = computed(() => page.props.tenantSlug as string | undefined);
const userRole = computed(() => page.props.auth.user?.role as string | undefined); // Dapatkan userRole dari props Inertia
// Tenant info via AJAX to ensure fresh data even when Inertia props don't include tenant
const tenant = ref<any | null>(null);

// Define trialDays, update when tenant changes
const trialDays = computed(() => tenant.value?.trial_days ?? 'INTERNAL');

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

    // Jika superadmin (tanpa tenantSlug), tambahkan menu Superadmin
    if (!tenantSlug.value && userRole.value === 'superadmin') {
        items.push({
            title: 'Pricing Plans',
            href: route('superadmin.pricing.index'),
            icon: Tag,
        });
        items.push({
            title: 'SaaS Settings',
            href: route('superadmin.settings.index'),
            icon: Tag,
        });
        
    }

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
        // Tambahkan menu Riwayat Invoice SaaS
        items.push({
            title: 'Riwayat Invoice SaaS',
            href: route('invoices.history', { tenantSlug: tenantSlug.value }),
            icon: Tag,
        });
        items.push({
            title: 'Master Data',
            children: [
                { title: 'Kategori', href: route('categories.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Produk', href: route('products.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Pelanggan', href: route('customers.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Supplier', href: route('suppliers.index', { tenantSlug: tenantSlug.value }) },
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
        items.push({
            title: 'Export Accounting',
            href: route('accurate.login'), // pastikan ini route inertia yang render Login.vue
            icon: FileText,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/Abdurozzaq/Yualan',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://yualan.web.id',
        icon: BookOpen,
    },
];

const isSubscriptionExpired = computed(() => {
    
    if (userRole.value !== 'superadmin') {
        const today = new Date();
      const todayFormatted = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
      return todayFormatted >= props?.tenant?.subscription_ends_at;
    } else {
        return null;
    }
  
});
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
                    <SidebarMenuButton size="lg" as-child>
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
        
            <div
                v-if="userRole !== 'superadmin' && String(trialDays) !== 'INTERNAL'"
                class="p-3 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200"
            >
                <h4 class="font-semibold text-sm mb-2 text-gray-800 dark:text-gray-100">Status Langganan</h4>
                <div class="flex flex-col gap-1">
                    <span>
                        <span class="font-medium">Jenis Paket:</span>
                        <span class="text-blue-600 dark:text-blue-400">&nbsp;{{ tenant?.plan_name || tenant?.pricing_plan_id || 'TRIAL' }}</span>
                    </span>
                    <span>
                        <span class="font-medium">Status:</span>
                        <span :class="tenant?.is_subscribed ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">&nbsp;{{ tenant?.is_subscribed ? 'Aktif' : 'Tidak Aktif' }}</span>
                    </span>
                    <span>
                        <span class="font-medium">Berlaku Hingga:</span>
                        <span>&nbsp;{{ tenant?.subscription_ends_at || '-' }}</span>
                    </span>
                    <div class="text-center" v-if="isSubscriptionExpired">
                        <Link :href="`/subscription/payment`" class="mt-2 block w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-center">
                            Perpanjang Langganan
                        </Link>
                    </div>
                </div>
            </div>
            <div class="border-t pt-3 mt-2">
                <NavFooter :items="footerNavItems" class="mb-2" />
                <NavUser />
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
