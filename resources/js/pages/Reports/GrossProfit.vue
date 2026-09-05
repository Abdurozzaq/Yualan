<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PremiumLock from '@/components/PremiumLock.vue';
import { BarChart3 } from 'lucide-vue-next';

const props = defineProps<{
    tenantSlug: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Laporan',
        href: '#',
    },
    {
        title: 'Laba Kotor',
        href: '#',
    },
];
</script>

<template>
    <Head title="Laba Kotor - Premium" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PremiumLock 
            title="Laba Kotor" 
            description="Pantau pendapatan kotor Anda sebelum dikurangi beban operasional. Fitur ini membantu Anda memahami performa penjualan harian secara mendalam."
            :icon="BarChart3"
            gradient="from-blue-600 to-indigo-700"
        />
    </AppLayout>
</template>