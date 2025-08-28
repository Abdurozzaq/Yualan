<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps<{ role: string }>();

</script>

<template>
    <Head title="Subscription Expired" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Informasi Langganan</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-bold mb-4">Langganan Anda Telah Berakhir</h3>
                        <p class="mb-4">
                            Akses Anda ke fitur-fitur aplikasi telah dibatasi karena langganan Anda saat ini tidak aktif atau telah berakhir.
                        </p>
                        <p class="mb-6">
                            Silakan perbarui langganan Anda untuk melanjutkan penggunaan layanan kami secara penuh.
                        </p>

                        <div v-if="role === 'admin'">
                            <Link :href="route('subscription.payment')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 disabled:opacity-25 transition">
                                Lakukan Pembayaran
                            </Link>
                        </div>
                         <div v-else>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                Silakan hubungi administrator tenant Anda untuk memperbarui langganan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
