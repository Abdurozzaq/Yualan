<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PremiumLock from '@/components/PremiumLock.vue';
import { Box } from 'lucide-vue-next';

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
        title: 'Nilai Stok',
        href: '#',
    },
];
</script>

<template>
    <Head title="Nilai Stok - Premium" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PremiumLock 
            title="Nilai Stok" 
            description="Ketahui total nilai aset yang tersimpan di gudang Anda saat ini. Fitur ini krusial untuk manajemen arus kas dan inventaris yang efisien."
            :icon="Box"
            gradient="from-emerald-600 to-teal-700"
        />
    </AppLayout>
</template>
