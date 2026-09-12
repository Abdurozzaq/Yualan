<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div class="flex items-center gap-3 select-none group">
        <!-- Logo Image -->
        <img 
            src="/android/launchericon-192x192.png" 
            alt="Yualan Logo" 
            class="h-10 w-10 shrink-0 rounded-xl object-contain shadow-md shadow-blue-200 dark:shadow-none group-hover:scale-105 transition-transform duration-300"
        />
        
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
