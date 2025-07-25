<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { DollarSign, Package, Users, BarChart, CreditCard, Cloud, ShieldCheck } from 'lucide-vue-next'; // Import icons

// Mengakses props dari Inertia, termasuk informasi autentikasi pengguna
const page = usePage();
const user = computed(() => page.props.auth.user);

// Mengambil nama aplikasi dari variabel lingkungan VITE_APP_NAME
// Pastikan variabel ini didefinisikan di file .env Anda (misal: VITE_APP_NAME="Yualan POS")
// dan di-expose melalui vite.config.js jika diperlukan.
const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS'; // Fallback jika tidak terdefinisi

// Computed property untuk menentukan URL dashboard yang benar
const dashboardLink = computed(() => {
    if (user.value) {
        // Jika user sudah login
        if (user.value.role === 'superadmin') {
            return route('superadmin.dashboard');
        } else if (user.value.tenant_id && user.value.tenant && user.value.tenant.slug) {
            return route('tenant.dashboard', { tenantSlug: user.value.tenant.slug });
        } else {
            // Jika user login tapi belum terhubung ke tenant, arahkan ke halaman unassigned
            return route('tenant.unassigned');
        }
    }
    // Jika user belum login, arahkan ke halaman login
    return route('login');
});

// Fitur-fitur utama Yualan POS
const features = [
    {
        icon: DollarSign,
        title: 'Manajemen Penjualan Intuitif',
        description: 'Catat transaksi dengan cepat, kelola diskon, dan berikan kembalian dengan mudah. Mendukung berbagai metode pembayaran.',
    },
    {
        icon: Package,
        title: 'Kontrol Inventaris Akurat',
        description: 'Lacak stok produk secara real-time, kelola penerimaan barang, dan lakukan penyesuaian stok dengan efisien.',
    },
    {
        icon: Users,
        title: 'Manajemen Pelanggan Terpadu',
        description: 'Simpan data pelanggan, pantau riwayat pembelian, dan bangun loyalitas pelanggan dengan mudah.',
    },
    {
        icon: BarChart,
        title: 'Laporan Bisnis Mendalam',
        description: 'Dapatkan wawasan berharga tentang penjualan, laba kotor, dan kinerja stok melalui laporan yang komprehensif.',
    },
    {
        icon: CreditCard,
        title: 'Integrasi Pembayaran Digital',
        description: 'Terhubung langsung dengan iPaymu untuk menerima pembayaran online, memperluas pilihan pembayaran bagi pelanggan Anda.',
    },
    {
        icon: Cloud,
        title: 'Akses Cloud Kapan Saja',
        description: 'Kelola bisnis Anda dari mana saja, kapan saja, dengan akses berbasis cloud yang aman dan andal.',
    },
    {
        icon: ShieldCheck,
        title: 'Keamanan Data Terjamin',
        description: 'Data bisnis Anda dilindungi dengan standar keamanan tinggi, memastikan informasi sensitif tetap aman.',
    },
];
</script>

<template>
    <Head :title="`Selamat Datang di ${appName}`">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen flex flex-col bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 font-inter">
        <!-- Header Navigasi -->
        <header class="w-full absolute top-0 right-0 p-6 sm:p-8 z-10">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="user"
                    :href="dashboardLink"
                    class="px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200 shadow-md"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="px-4 py-2 rounded-md text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200"
                    >
                        Login
                    </Link>
                    <Link
                        :href="route('register')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200 shadow-md"
                    >
                        Daftar
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="relative flex flex-col items-center justify-center text-center px-4 sm:px-6 lg:px-8 py-24 sm:py-32 md:py-40 bg-gradient-to-br from-blue-500 to-indigo-600 text-white overflow-hidden">
            <div class="absolute inset-0 opacity-20 bg-pattern-dots"></div> <!-- Background pattern -->
            <div class="relative z-10 max-w-4xl mx-auto">
                <h1 class="text-5xl sm:text-6xl md:text-7xl font-extrabold leading-tight mb-6 animate-fade-in-up">
                    Kelola Bisnis Anda Lebih Mudah dengan <span class="text-yellow-300">{{ appName }}</span>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl mb-10 opacity-90 animate-fade-in-up delay-200">
                    Sistem Point of Sale yang intuitif, efisien, dan terintegrasi untuk membantu Anda berkembang.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up delay-400">
                    <Link
                        :href="route('login')"
                        class="w-full sm:w-auto px-10 py-4 rounded-full text-xl font-bold bg-white text-indigo-700 hover:bg-gray-100 transition-all duration-300 shadow-xl transform hover:scale-105"
                    >
                        Mulai Sekarang
                    </Link>
                
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-16 sm:py-24 bg-white dark:bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl font-extrabold text-gray-900 dark:text-gray-100 mb-12">
                    Fitur Unggulan {{ appName }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <div v-for="(feature, index) in features" :key="index" class="flex flex-col items-center p-6 bg-gray-100 dark:bg-gray-700 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 transform hover:-translate-y-2">
                        <component :is="feature.icon" class="h-12 w-12 text-blue-600 dark:text-blue-400 mb-4" />
                        <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ feature.title }}</h3>
                        <p class="text-gray-600 dark:text-gray-300">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action Section -->
        <section class="py-16 sm:py-24 bg-indigo-700 dark:bg-indigo-900 text-white text-center">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-extrabold mb-6">Siap Mengembangkan Bisnis Anda?</h2>
                <p class="text-xl opacity-90 mb-10">
                    Bergabunglah dengan ribuan bisnis yang telah merasakan kemudahan dan efisiensi bersama {{ appName }}.
                </p>
                <Link
                    :href="route('register')"
                    class="inline-block px-12 py-4 rounded-full text-xl font-bold bg-white text-indigo-700 hover:bg-gray-100 transition-all duration-300 shadow-xl transform hover:scale-105"
                >
                    Daftar Sekarang!
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 text-center bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p>&copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.</p>
                <div class="mt-4 flex justify-center space-x-4">
                    <Link :href="route('login')" class="hover:underline">Login</Link>
                    <Link :href="route('register')" class="hover:underline">Daftar</Link>
                    <!-- Tambahkan tautan lain seperti Kebijakan Privasi, Syarat & Ketentuan jika ada -->
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Basic animations for hero section */
@keyframes fadeInFromBottom {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInFromBottom 0.8s ease-out forwards;
}

.animate-fade-in-up.delay-200 {
    animation-delay: 0.2s;
}

.animate-fade-in-up.delay-400 {
    animation-delay: 0.4s;
}

/* Simple pattern for hero background */
.bg-pattern-dots {
    background-image: radial-gradient(currentColor 1px, transparent 1px);
    background-size: 20px 20px;
    color: rgba(255, 255, 255, 0.1); /* Light dots for dark background */
}

/* Ensure Inter font is applied */
.font-inter {
    font-family: 'Inter', sans-serif;
}
</style>
