<script setup lang="ts">
import { defineProps } from 'vue';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    modelValue: String,
    setting: Object,
    error: String,
});

const emit = defineEmits(['update:modelValue']);

const updateValue = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};
</script>

<template>
    <div>
        <label :for="setting.key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ setting.display_name }}
        </label>
        <input 
            :type="setting.type" 
            :id="setting.key"
            :value="modelValue"
            @input="updateValue"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 p-3"
            :placeholder="setting.placeholder || ''"
        >
        <div v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</div>
        <div v-if="setting.info" class="flex items-start text-sm text-gray-500 dark:text-gray-400 mt-2">
            <InformationCircleIcon class="h-5 w-5 mr-2 flex-shrink-0 text-gray-400" />
            <span>{{ setting.info }}</span>
        </div>
    </div>
</template>
