<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS';
</script>

<template>
    <Head :title="`Selamat Datang di ${appName}`">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen flex items-center justify-center bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 font-inter">
        <div class="text-center space-y-8">
            <div class="flex justify-center">
                <img
                    src="/yPOS.png"
                    alt="Logo"
                    class="h-20 w-20 object-contain"
                />
            </div>

            <div>
                <h1 class="text-3xl sm:text-4xl font-extrabold mb-2">
                    Selamat Datang di {{ appName }}
                </h1>
                <p class="text-gray-600 dark:text-gray-300">
                    Silakan login atau daftar untuk mulai menggunakan aplikasi.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Link
                    :href="route('login')"
                    class="px-8 py-3 rounded-full text-base font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200 shadow-md"
                >
                    Login
                </Link>
                <Link
                    :href="route('register')"
                    class="px-8 py-3 rounded-full text-base font-semibold bg-white text-blue-600 border border-blue-600 hover:bg-blue-50 transition-colors duration-200 shadow-md"
                >
                    Register
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-inter {
    font-family: 'Inter', sans-serif;
}
</style>
