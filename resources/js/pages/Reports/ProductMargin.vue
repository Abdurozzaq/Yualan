<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PremiumLock from '@/components/PremiumLock.vue';
import { Target } from 'lucide-vue-next';

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
        title: 'Produk Terlaris & Margin',
        href: '#',
    },
];
</script>

<template>
    <Head title="Produk Terlaris - Premium" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PremiumLock 
            title="Produk & Margin" 
            description="Identifikasi produk mana yang memberikan keuntungan tertinggi dan mana yang paling diminati pelanggan Anda."
            :icon="Target"
            gradient="from-orange-600 to-red-700"
        />
    </AppLayout>
</template>
