<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShieldCheck, ArrowLeft, Info } from 'lucide-vue-next';

const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS';
</script>

<template>
  <Head :title="`Kebijakan Refund | ${appName}`" />

  <div class="min-h-screen relative overflow-hidden bg-white dark:bg-gray-950 font-sans flex flex-col items-center px-4 py-20">
    <!-- Background Elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-100/50 dark:bg-blue-900/20 blur-[120px] rounded-full animate-pulse"></div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-indigo-100/50 dark:bg-indigo-900/20 blur-[120px] rounded-full animate-pulse delay-700"></div>
    </div>

    <div class="relative z-10 w-full max-w-3xl mx-auto space-y-12 animate-fade-in">
        <!-- Header -->
        <div class="text-center space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 shadow-xl shadow-blue-200 dark:shadow-none mb-4">
                <ShieldCheck class="text-white w-8 h-8" />
            </div>
            <h1 class="text-4xl sm:text-5xl font-black text-gray-900 dark:text-white tracking-tighter leading-tight">
                Kebijakan <span class="text-blue-600">Refund</span>
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 font-medium">
                Ketentuan pengembalian dana dan transaksi untuk {{ appName }}.
            </p>
        </div>

        <!-- Content Card -->
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-8 sm:p-12 border border-gray-100 dark:border-gray-800 shadow-2xl space-y-8">
            <div class="space-y-6">
                <div class="flex gap-4 items-start">
                    <div class="mt-1 bg-blue-50 dark:bg-blue-900/30 p-2 rounded-lg">
                        <Info class="w-5 h-5 text-blue-600" />
                    </div>
                    <div class="space-y-4 text-gray-600 dark:text-gray-400 leading-relaxed text-lg">
                        <p>
                            Pembayaran yang sudah diproses melalui payment gateway mitra kami tidak dapat dibatalkan secara otomatis demi keamanan transaksi.
                        </p>
                        <p>
                            Permintaan refund dapat diajukan maksimal <strong>3x24 jam</strong> setelah transaksi dilakukan dengan alasan teknis atau kesalahan sistem yang valid.
                        </p>
                        <p>
                            Proses refund akan diverifikasi oleh tim internal <strong>{{ appName }}</strong> dan mitra pembayaran sesuai dengan regulasi perbankan yang berlaku.
                        </p>
                        <p>
                            Mohon diperhatikan bahwa biaya administrasi dari pihak ketiga (bank/gateway) mungkin tidak termasuk dalam dana yang dikembalikan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-bold text-gray-900 dark:text-white text-center italic">
                    "Dengan melakukan transaksi, Anda dianggap telah memahami dan menyetujui seluruh ketentuan dalam kebijakan refund ini."
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
