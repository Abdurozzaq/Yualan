<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PremiumLock from '@/components/PremiumLock.vue';
import { Users } from 'lucide-vue-next';

const props = defineProps<{
    tenantSlug: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Karyawan',
        href: '#',
    },
];
</script>

<template>
    <Head title="Manajemen Karyawan - Premium" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PremiumLock 
            title="Manajemen Karyawan" 
            description="Kelola tim Anda, atur hak akses kasir, dan pantau performa staf secara real-time dengan fitur Manajemen Karyawan Premium."
            :icon="Users"
            gradient="from-purple-600 to-fuchsia-700"
        />
    </AppLayout>
</template>