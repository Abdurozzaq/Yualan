<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

const theme = ref('light');
const themeOptions = [
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
    { value: 'orange-theme', label: 'Orange' },
    { value: 'blue-theme', label: 'Blue' },
    { value: 'green-theme', label: 'Green' },
    { value: 'purple-theme', label: 'Purple' },
];

function setTheme(newTheme: string) {
    document.documentElement.classList.remove('dark', 'orange-theme', 'blue-theme', 'green-theme', 'purple-theme');
    if (newTheme !== 'light') {
        document.documentElement.classList.add(newTheme);
    }
    theme.value = newTheme;
    localStorage.setItem('theme', newTheme);
}

onMounted(() => {
    const saved = localStorage.getItem('theme');
    if (saved && themeOptions.some(t => t.value === saved)) {
        setTheme(saved);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
                    <AppearanceTabs :theme="theme" :themeOptions="themeOptions" :setTheme="setTheme" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
