<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ShoppingCart, ArrowRight, UserPlus, LogIn, Server, Zap } from 'lucide-vue-next';

const appName = "Yualan Community Edition";
const subName = "Dedicated Enterprise";

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <Head title="Yualan Community Edition - Dedicated Enterprise" />

    <div class="min-h-screen relative overflow-hidden bg-gray-50 dark:bg-gray-950 font-sans flex flex-col justify-center items-center px-4">
        <!-- Subtle Background -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none opacity-50">
            <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-blue-100 dark:bg-blue-900/10 blur-[120px] rounded-full"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-indigo-100 dark:bg-indigo-900/10 blur-[120px] rounded-full"></div>
        </div>

        <div class="relative z-10 w-full max-w-3xl mx-auto text-center space-y-10 animate-fade-in">
            <!-- Branding -->
            <div class="space-y-6">
                <div class="inline-flex items-center gap-3 bg-white dark:bg-gray-900 px-6 py-3 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 mb-4">
                    <Server class="w-5 h-5 text-blue-600" />
                    <span class="text-sm font-black uppercase tracking-widest text-gray-900 dark:text-gray-100">{{ subName }}</span>
                </div>
                
                <h1 class="text-5xl sm:text-7xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight">
                    {{ appName }}
                </h1>
                
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-xl mx-auto leading-relaxed">
                    Solusi Point of Sale mandiri yang tangguh untuk bisnis skala enterprise yang menginginkan kontrol penuh atas data dan infrastruktur.
                </p>
            </div>

            <!-- Main Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <template v-if="isAuthenticated">
                    <Link
                        :href="route('dashboard.default')"
                        class="group w-full sm:w-auto px-10 py-5 rounded-2xl text-xl font-black bg-blue-600 text-white hover:bg-blue-700 transition-all duration-300 shadow-2xl shadow-blue-200 dark:shadow-none flex items-center justify-center gap-2 active:scale-95"
                    >
                        Masuk ke Dashboard
                        <ArrowRight class="w-6 h-6 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </template>

                <template v-else>
                    <Link
                        href="/login"
                        class="group w-full sm:w-auto px-10 py-5 rounded-2xl text-xl font-black bg-blue-600 text-white hover:bg-blue-700 transition-all duration-300 shadow-2xl shadow-blue-200 dark:shadow-none flex items-center justify-center gap-3 active:scale-95"
                    >
                        <LogIn class="w-6 h-6" />
                        Masuk
                    </Link>
                    <Link
                        href="/register"
                        class="group w-full sm:w-auto px-10 py-5 rounded-2xl text-xl font-black bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-2 border-gray-200 dark:border-gray-800 hover:border-blue-600 dark:hover:border-blue-500 transition-all duration-300 flex items-center justify-center gap-3 active:scale-95"
                    >
                        <UserPlus class="w-6 h-6" />
                        Daftar Baru
                    </Link>
                </template>
            </div>

            <!-- Premium Upsell -->
            <div class="pt-10">
                <div class="inline-block p-1 rounded-[2rem] bg-gradient-to-r from-amber-200 via-orange-300 to-amber-200 dark:from-amber-900/50 dark:to-orange-900/50 shadow-lg">
                    <div class="bg-white dark:bg-gray-950 rounded-[1.8rem] px-8 py-6 flex flex-col md:flex-row items-center gap-6">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                            <Zap class="w-6 h-6 text-amber-600 dark:text-amber-400 fill-amber-600 dark:fill-amber-400" />
                        </div>
                        <div class="text-left">
                            <h4 class="font-black text-gray-900 dark:text-white text-lg">Malas Setup Server Sendiri?</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih <strong>Yualan Premium</strong> untuk solusi terima beres tanpa ribet.</p>
                        </div>
                        <a href="https://yualan.web.id" target="_blank" class="w-full md:w-auto px-6 py-3 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-950 font-bold text-sm hover:scale-105 transition-transform active:scale-95 whitespace-nowrap">
                            Pilih Yualan Premium
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="pt-12 border-t border-gray-100 dark:border-gray-900">
                <p class="text-xs font-black uppercase tracking-[0.3em] text-gray-400 dark:text-gray-600">
                    Created Under <span class="text-blue-600 dark:text-blue-400">PT. Nusavasoft Digital Solutions</span>
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
