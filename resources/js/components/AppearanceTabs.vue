<script setup lang="ts">
import { defineProps } from 'vue';

const props = defineProps<{
    theme: string;
    themeOptions: Array<{ value: string; label: string }>;
    setTheme: (newTheme: string) => void;
}>();
</script>

<template>
    <div class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
        <button
            v-for="option in props.themeOptions"
            :key="option.value"
            @click="props.setTheme(option.value)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                props.theme === option.value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            <span class="text-sm">{{ option.label }}</span>
        </button>
    </div>
</template>
