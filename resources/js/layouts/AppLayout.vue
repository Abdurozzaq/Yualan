<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
import { ref, onMounted } from 'vue';
const theme = ref('light');
const themeOptions = [
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
    { value: 'orange-theme', label: 'Orange' },
    { value: 'blue-theme', label: 'Blue' },
    { value: 'green-theme', label: 'Green' },
    { value: 'purple-theme', label: 'Purple' },
];
function setTheme(newTheme) {
    document.documentElement.classList.remove('dark', 'modern-theme');
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
            <div style="position:fixed;top:1rem;right:1rem;z-index:1000;">
                <select v-model="theme" @change="setTheme(theme)" style="padding:0.5rem 1rem;border-radius:6px;border:1px solid #ccc;background:var(--color-background);color:var(--color-foreground);">
                    <option v-for="opt in themeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>
        <AppSidebarLayout :breadcrumbs="breadcrumbs">
                <slot />
        </AppSidebarLayout>
</template>
