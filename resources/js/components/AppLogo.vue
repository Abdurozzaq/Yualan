<script setup lang="ts">
import { ShoppingCart } from 'lucide-vue-next';
import { useSidebar } from '@/components/ui/sidebar/utils';

const { state } = useSidebar();
</script>

<template>
    <div class="flex items-center gap-3 select-none group px-2 py-1 transition-all duration-300">
        <!-- Icon -->
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-200 dark:shadow-none group-hover:scale-105 transition-transform duration-300">
            <ShoppingCart class="size-6 text-white" />
        </div>
        
        <!-- Text Branding (Hidden when sidebar is collapsed) -->
        <div v-if="state !== 'collapsed'" class="flex flex-col leading-tight animate-in fade-in slide-in-from-left-2 duration-300">
            <span class="text-lg font-black tracking-tighter text-gray-900 dark:text-white uppercase whitespace-nowrap">
                Yualan<span class="text-blue-600">POS</span>
            </span>
            <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-600 whitespace-nowrap">
                Community Edition
            </span>
        </div>
    </div>
</template>
