<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps, ref, computed } from 'vue';
import Modal from '@/components/Modal.vue';
import SettingInput from '@/components/SettingInput.vue'; // Import the new component
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    settings: Array // Expect an array of setting objects
});

// Create a form data object from the settings array
const formData = props.settings.reduce((acc: Record<string, string>, setting: { key: string; value?: string }) => {
    acc[setting.key] = setting.value || '';
    return acc;
}, {});

const form = useForm(formData);

// Modal state
const showModal = ref(false);
const modalTitle = ref('');
const modalMessage = ref('');
const isError = ref(false);

const submit = () => {
    form.post(route('superadmin.settings.store'), {
        onSuccess: () => {
            isError.value = false;
            modalTitle.value = 'Success!';
            modalMessage.value = 'Your settings have been saved successfully.';
            showModal.value = true;
        },
    onError: () => {
            isError.value = true;
            modalTitle.value = 'Error!';
            modalMessage.value = 'There was an error saving your settings. Please check the form and try again.';
            showModal.value = true;
        }
    });
};

const closeModal = () => {
    showModal.value = false;
};
</script>

<template>
    <Head title="SaaS Settings" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                SaaS Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg transition-shadow duration-300 hover:shadow-2xl">
                    <div class="p-6 md:p-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">General Settings</h3>
                        <p class="text-md text-gray-600 dark:text-gray-400 mb-8">
                            Manage the general settings for the application.
                        </p>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div v-for="setting in settings" :key="setting.key">
                                <SettingInput 
                                    v-model="form[setting.key]"
                                    :setting="setting"
                                    :error="form.errors[setting.key]"
                                />
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-transform transform hover:scale-105">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Success/Error -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6 text-center">
                <div class="flex justify-center">
                    <CheckCircleIcon v-if="!isError" class="h-16 w-16 text-green-500" />
                    <XCircleIcon v-if="isError" class="h-16 w-16 text-red-500" />
                </div>
                <h3 class="mt-5 text-lg font-medium text-gray-900 dark:text-gray-100">{{ modalTitle }}</h3>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ modalMessage }}</p>
                </div>
                <div class="mt-6">
                    <button @click="closeModal" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="isError ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'">
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
