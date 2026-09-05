<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PremiumLock from '@/components/PremiumLock.vue';
import { TrendingUp } from 'lucide-vue-next';

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
        title: 'Laba Bersih',
        href: '#',
    },
];
</script>

<template>
    <Head title="Laba Bersih - Premium" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PremiumLock 
            title="Laba Bersih" 
            description="Dapatkan gambaran nyata keuntungan bisnis Anda setelah dikurangi semua biaya operasional. Analisis margin bersih Anda secara akurat."
            :icon="TrendingUp"
            gradient="from-violet-600 to-purple-700"
        />
    </AppLayout>
</template>