<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ShoppingCart, ArrowRight, UserPlus, LogIn } from 'lucide-vue-next';

const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <Head :title="`Selamat Datang di ${appName}`" />

    <div class="min-h-screen relative overflow-hidden bg-white dark:bg-gray-950 font-sans flex flex-col justify-center items-center px-4">
        <!-- Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-100/50 dark:bg-blue-900/20 blur-[120px] rounded-full animate-pulse"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-indigo-100/50 dark:bg-indigo-900/20 blur-[120px] rounded-full animate-pulse delay-700"></div>
        </div>

        <div class="relative z-10 w-full max-w-4xl mx-auto text-center space-y-12 animate-fade-in">
            <!-- Logo/Icon Section -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-blue-600 shadow-2xl shadow-blue-200 dark:shadow-none mb-4 transform hover:scale-105 transition-transform duration-300">
                <ShoppingCart class="text-white w-10 h-10" />
            </div>

            <div class="space-y-4">
                <h1 class="text-4xl sm:text-6xl md:text-7xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight">
                    Kelola Bisnis Lebih <br/> 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Mudah & Profesional</span>
                </h1>
                <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto font-medium leading-relaxed">
                    Solusi Point of Sale tercanggih untuk UMKM hingga Enterprise. 
                    Mulai transformasi bisnis Anda bersama <span class="font-bold text-gray-900 dark:text-white">{{ appName }}</span>.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <template v-if="isAuthenticated">
                    <Link
                        :href="route('dashboard.default')"
                        class="group w-full sm:w-auto px-10 py-4 rounded-2xl text-lg font-bold bg-blue-600 text-white hover:bg-blue-700 transition-all duration-300 shadow-xl shadow-blue-200 dark:shadow-none flex items-center justify-center gap-2 active:scale-95"
                    >
                        Masuk ke Dashboard
                        <ArrowRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </template>

                <template v-else>
                    <Link
                        href="/login"
                        class="group w-full sm:w-auto px-10 py-4 rounded-2xl text-lg font-bold bg-blue-600 text-white hover:bg-blue-700 transition-all duration-300 shadow-xl shadow-blue-200 dark:shadow-none flex items-center justify-center gap-2 active:scale-95"
                    >
                        <LogIn class="w-5 h-5" />
                        Masuk Sekarang
                    </Link>
                    <Link
                        href="/register"
                        class="group w-full sm:w-auto px-10 py-4 rounded-2xl text-lg font-bold bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-2 border-gray-100 dark:border-gray-800 hover:border-blue-600 dark:hover:border-blue-500 transition-all duration-300 flex items-center justify-center gap-2 active:scale-95"
                    >
                        <UserPlus class="w-5 h-5" />
                        Daftar Bisnis
                    </Link>
                </template>
            </div>

            <!-- Footer Links -->
            <div class="pt-8 border-t border-gray-100 dark:border-gray-900 flex flex-wrap justify-center gap-x-8 gap-y-4 text-sm font-bold text-gray-400 dark:text-gray-600">
                <Link :href="route('faq')" class="hover:text-blue-600 transition-colors">FAQ</Link>
                <Link :href="route('terms')" class="hover:text-blue-600 transition-colors">Syarat & Ketentuan</Link>
                <Link :href="route('refund')" class="hover:text-blue-600 transition-colors">Kebijakan Refund</Link>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
