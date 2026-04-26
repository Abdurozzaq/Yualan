<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingCart, ArrowLeft, HelpCircle } from 'lucide-vue-next';

const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS';
const faqs = [
  {
    q: 'Apa itu Yualan POS?',
    a: 'Yualan POS adalah sistem Point of Sale tercanggih yang memudahkan pengelolaan bisnis, penjualan, inventaris, dan laporan secara real-time.'
  },
  {
    q: 'Bagaimana cara mendaftar?',
    a: 'Klik tombol "Daftar Bisnis" di halaman utama, isi data yang diperlukan, dan Anda bisa langsung mulai mengelola bisnis Anda.'
  },
  {
    q: 'Apakah data saya aman?',
    a: 'Ya, kami menggunakan infrastruktur cloud enterprise dengan enkripsi tingkat tinggi untuk memastikan seluruh data bisnis Anda tersimpan dengan aman.'
  },
  {
    q: 'Bagaimana sistem pembayaran?',
    a: 'Kami mendukung berbagai metode pembayaran otomatis melalui gateway terpercaya untuk memudahkan aktivasi layanan Anda.'
  },
  {
    q: 'Siapa yang bisa saya hubungi?',
    a: 'Tim support profesional kami siap membantu Anda 24/7 melalui email support@yualan.com atau melalui help desk di dalam aplikasi.'
  }
];
</script>

<template>
  <Head :title="`FAQ | ${appName}`" />
  
  <div class="min-h-screen relative overflow-hidden bg-white dark:bg-gray-950 font-sans flex flex-col items-center px-4 py-20">
    <!-- Background Elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] bg-blue-100/50 dark:bg-blue-900/20 blur-[120px] rounded-full animate-pulse"></div>
        <div class="absolute -bottom-[10%] -left-[10%] w-[40%] h-[40%] bg-indigo-100/50 dark:bg-indigo-900/20 blur-[120px] rounded-full animate-pulse delay-700"></div>
    </div>

    <div class="relative z-10 w-full max-w-3xl mx-auto space-y-12 animate-fade-in">
        <!-- Header -->
        <div class="text-center space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 shadow-xl shadow-blue-200 dark:shadow-none mb-4">
                <HelpCircle class="text-white w-8 h-8" />
            </div>
            <h1 class="text-4xl sm:text-5xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight">
                Frequently Asked <span class="text-blue-600">Questions</span>
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 font-medium">
                Temukan jawaban cepat untuk pertanyaan umum seputar {{ appName }}.
            </p>
        </div>

        <!-- FAQ Cards -->
        <div class="space-y-4">
            <div v-for="(item, idx) in faqs" :key="idx" class="bg-white dark:bg-gray-900 rounded-2xl p-6 sm:p-8 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-start gap-3">
                    <span class="text-blue-600 font-black">Q:</span>
                    {{ item.q }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed pl-8 border-l-2 border-gray-100 dark:border-gray-800">
                    {{ item.a }}
                </p>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="text-center pt-8">
            <Link :href="route('home')" class="group inline-flex items-center justify-center gap-2 px-10 py-4 rounded-2xl text-lg font-bold bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-blue-600 dark:hover:bg-blue-600 dark:hover:text-white transition-all duration-300 shadow-xl active:scale-95">
                <ArrowLeft class="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
                Kembali ke Beranda
            </Link>
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
