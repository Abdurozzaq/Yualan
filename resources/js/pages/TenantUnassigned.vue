<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

// Mengakses props dari Inertia, termasuk informasi autentikasi pengguna
const page = usePage();
const user = computed(() => page.props.auth.user);

// Mengambil nama aplikasi dari variabel lingkungan VITE_APP_NAME
const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS'; // Fallback jika tidak terdefinisi

// Computed property untuk menentukan apakah tenant pengguna tidak aktif
const isTenantInactive = computed(() => {
    return user.value && user.value.tenant_id && user.value.tenant && !user.value.tenant.is_active;
});

// Computed property untuk menentukan URL yang benar untuk tombol "Login" atau "Dashboard"
const loginOrDashboardLink = computed(() => {
    // Jika user sudah login dan tenantnya tidak aktif, tombol ini akan menjadi "Logout"
    if (isTenantInactive.value) {
        return route('logout'); // Arahkan ke logout
    }
    // Logika yang sudah ada:
    if (user.value) {
        if (user.value.tenant_id && user.value.tenant && user.value.tenant.slug) {
            return route('tenant.dashboard', { tenantSlug: user.value.tenant.slug });
        } else if (user.value.role === 'superadmin') {
            return route('superadmin.dashboard');
        }
    }
    return route('login');
});

// Computed property untuk teks tombol "Login" atau "Dashboard"
const loginOrDashboardText = computed(() => {
    if (isTenantInactive.value) {
        return 'Logout'; // Jika tenant tidak aktif, sarankan logout
    }
    return user.value ? 'Kembali ke Dashboard' : 'Login';
});


// Computed property untuk menentukan judul halaman AuthBase
const authBaseTitle = computed(() => {
    if (isTenantInactive.value) {
        return 'Tenant Dinonaktifkan';
    }
    return 'Belum Terhubung ke Tenant';
});

// Computed property untuk menentukan deskripsi halaman AuthBase
const authBaseDescription = computed(() => {
    if (isTenantInactive.value) {
        return 'Akses Anda ke tenant telah dinonaktifkan.';
    }
    return 'Anda belum memiliki akses ke tenant mana pun.';
});

// Form data to link user to a tenant
const form = useForm({
    invitation_code: '',
    email: '', // User's email to verify identity
});

// Function to handle form submission
const submit = () => {
    form.post(route('tenant.link'), {
        onSuccess: () => {
            alert('Permintaan penggabungan tenant berhasil dikirim! Silakan login kembali.');
            form.reset(); // Clear the form
            router.visit(route('login')); // Use router.visit for Inertia navigation
        },
        onError: () => {
            // Errors will be displayed by InputError component
        },
        onFinish: () => {
            // Any final actions after success or error
        }
    });
};
</script>

<template>
    <AuthBase :title="authBaseTitle" :description="authBaseDescription">
        <Head title="Tenant Tidak Terhubung" />

        <div class="flex flex-col items-center justify-center text-center gap-4 p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700">

            <!-- Conditional message for inactive tenant -->
            <div v-if="isTenantInactive" class="text-center w-full max-w-sm">
                <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100 mb-4">
                    Akses Bisnis Dinonaktifkan
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                    Mohon maaf, akses Anda ke bisnis <strong class="text-blue-600 dark:text-blue-400">{{ user?.tenant?.name }}</strong> telah dinonaktifkan.
                    Silakan hubungi administrator atau support <strong class="text-gray-900 dark:text-gray-100">{{ appName }}</strong> untuk mengaktifkan kembali.
                </p>
                <!-- Tombol Logout jika tenant tidak aktif -->
                <Button :as="Link" :href="route('logout')" method="post" class="w-full h-12 rounded-xl bg-red-600 hover:bg-red-700 shadow-lg shadow-red-200 dark:shadow-none font-bold">
                    Keluar Sesi
                </Button>
            </div>

            <!-- Original content for unassigned tenant -->
            <div v-else class="text-center w-full max-w-sm">
                <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100 mb-4">
                    Bisnis Belum Terhubung
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
                    Silakan hubungi pemilik bisnis untuk mendapatkan kode undangan, lalu masukkan di bawah ini.
                </p>

                <form @submit.prevent="submit" class="w-full space-y-5">
                    <div class="grid gap-2 text-left">
                        <Label for="invitation_code" class="font-bold text-sm ml-1">Kode Undangan Bisnis</Label>
                        <Input
                            id="invitation_code"
                            type="text"
                            placeholder="Masukkan kode undangan"
                            v-model="form.invitation_code"
                            required
                            autofocus
                            class="h-12 rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 focus:ring-blue-500"
                        />
                        <InputError :message="form.errors.invitation_code" />
                    </div>

                    <div class="grid gap-2 text-left">
                        <Label for="email" class="font-bold text-sm ml-1">Alamat Email Terdaftar</Label>
                        <Input
                            id="email"
                            type="email"
                            placeholder="nama@email.com"
                            v-model="form.email"
                            required
                            class="h-12 rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 focus:ring-blue-500"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <Button type="submit" class="w-full h-12 rounded-xl bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 dark:shadow-none font-bold mt-2" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Hubungkan Sekarang
                    </Button>
                </form>
            </div>

            <div class="text-sm text-muted-foreground mt-4">
                {{ isTenantInactive ? 'Perlu bantuan lain?' : 'Sudah terhubung ke tenant?' }}&nbsp;
                <Link :href="loginOrDashboardLink" :method="isTenantInactive ? 'post' : 'get'" class="underline underline-offset-4">
                    {{ loginOrDashboardText }}
                </Link>
            </div>
        </div>
    </AuthBase>
</template>

