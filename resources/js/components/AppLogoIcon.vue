<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { ShoppingCart } from 'lucide-vue-next';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();

const appName = "Yualan POS";
</script>

<template>
    <div class="flex items-center gap-3 select-none group">
        <!-- Logo Icon -->
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-200 dark:shadow-none group-hover:scale-105 transition-transform duration-300">
            <ShoppingCart class="size-6 text-white" />
        </div>
        
        <!-- Text Branding -->
        <div class="flex flex-col leading-tight">
            <span class="text-xl font-black tracking-tighter text-gray-900 dark:text-white uppercase">
                Yualan<span class="text-blue-600">POS</span>
            </span>
            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 dark:text-gray-600">
                Community Edition
            </span>
        </div>
    </div>
</template>
