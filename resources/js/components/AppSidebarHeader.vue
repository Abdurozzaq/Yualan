<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button'; // Import Button component
import { Sun, Moon } from 'lucide-vue-next'; // Import icons
import { ref, onMounted, computed } from 'vue'; // Import Vue reactivity functions
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// Reactive state for the current theme
const theme = ref<'light' | 'dark'>('light'); // Default to light

// Computed property to determine the icon to display
const themeIcon = computed(() => {
    return theme.value === 'light' ? Moon : Sun;
});

// Function to toggle the theme
const toggleTheme = () => {
    if (theme.value === 'light') {
        theme.value = 'dark';
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        theme.value = 'light';
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// On component mount, check for saved theme preference or system preference
onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        theme.value = savedTheme as 'light' | 'dark';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        // If no saved theme, check system preference
        theme.value = 'dark';
        document.documentElement.classList.add('dark');
    }
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>

