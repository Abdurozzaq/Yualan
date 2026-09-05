<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Reactive state for managing the current step
const currentStep = ref(1);

// Form data using Inertia's useForm hook
const form = useForm({
    registration_type: 'company', // Forced to company
    // User account details
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    // Company registration specific (maps to tenant fields)
    company_name: '',
    company_email: '', // Tenant email
    company_phone: '',
    company_address: '',
    company_city: '',
    company_state: '',
    company_zip_code: '',
    company_country: '',
    business_type: '',
});

// Computed property to determine the title based on the current step
const pageTitle = computed(() => {
    switch (currentStep.value) {
        case 1:
            return 'Detail Akun Pemilik';
        case 2:
            return 'Detail Bisnis / Perusahaan';
        default:
            return 'Daftar Akun Baru';
    }
});

// Computed property to determine the description based on the current step
const pageDescription = computed(() => {
    switch (currentStep.value) {
        case 1:
            return 'Masukkan detail akun yang akan Anda gunakan untuk masuk.';
        case 2:
            return 'Lengkapi informasi bisnis Anda untuk mulai berjualan.';
        default:
            return 'Lengkapi detail Anda untuk membuat akun.';
    }
});

// Function to navigate to the next step
const nextStep = () => {
    currentStep.value++;
};

// Function to navigate to the previous step
const prevStep = () => {
    currentStep.value--;
};

// Function to handle form submission
const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: (errors) => {
            if (errors.name || errors.email || errors.password || errors.password_confirmation) {
                currentStep.value = 1;
            } else {
                currentStep.value = 2;
            }
        }
    });
};
</script>

<template>
    <AuthBase :title="pageTitle" :description="pageDescription">
        <Head title="Daftar Akun Bisnis" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <!-- Step 1: User Account Details -->
            <div v-if="currentStep === 1" class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="name" class="font-bold text-gray-700 dark:text-gray-300">Nama Lengkap Pemilik</Label>
                    <Input id="name" type="text" required autofocus autocomplete="name" v-model="form.name" placeholder="Nama Lengkap Anda" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="font-bold text-gray-700 dark:text-gray-300">Alamat Email Utama</Label>
                    <Input id="email" type="email" required autocomplete="email" v-model="form.email" placeholder="email@example.com" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="font-bold text-gray-700 dark:text-gray-300">Kata Sandi</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Kata Sandi"
                        class="h-12 rounded-xl"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="font-bold text-gray-700 dark:text-gray-300">Konfirmasi Kata Sandi</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Konfirmasi Kata Sandi"
                        class="h-12 rounded-xl"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="pt-4">
                    <Button type="button" @click="nextStep" class="w-full h-14 rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xl shadow-blue-200 dark:shadow-none font-black text-lg transition-all active:scale-95">
                        Lanjut ke Detail Bisnis
                    </Button>
                </div>
            </div>

            <!-- Step 2: Company Details -->
            <div v-else-if="currentStep === 2" class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="company_name" class="font-bold text-gray-700 dark:text-gray-300">Nama Bisnis / Perusahaan</Label>
                    <Input id="company_name" type="text" required v-model="form.company_name" placeholder="Nama Bisnis Anda" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.company_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="company_email" class="font-bold text-gray-700 dark:text-gray-300">Email Bisnis</Label>
                    <Input id="company_email" type="email" required v-model="form.company_email" placeholder="email.bisnis@example.com" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.company_email" />
                </div>
                <div class="grid gap-2">
                    <Label for="company_phone" class="font-bold text-gray-700 dark:text-gray-300">Nomor Telepon Bisnis</Label>
                    <Input id="company_phone" type="text" v-model="form.company_phone" placeholder="Contoh: 08123456789" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.company_phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="business_type" class="font-bold text-gray-700 dark:text-gray-300">Tipe Bisnis</Label>
                    <Input id="business_type" type="text" required v-model="form.business_type" placeholder="Contoh: Toko, Restoran, Kafe" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.business_type" />
                </div>
                <div class="grid gap-2">
                    <Label for="company_address" class="font-bold text-gray-700 dark:text-gray-300">Alamat Lengkap Bisnis</Label>
                    <Input id="company_address" type="text" v-model="form.company_address" placeholder="Alamat Lengkap" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.company_address" />
                </div>

                <div class="flex flex-col sm:flex-row gap-4 mt-6">
                    <Button type="button" @click="prevStep" variant="outline" class="w-full sm:w-1/3 h-14 rounded-xl border-2 font-bold transition-all">
                        Kembali
                    </Button>
                    <Button type="submit" :disabled="form.processing" class="w-full sm:w-2/3 h-14 rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xl shadow-blue-200 dark:shadow-none font-black text-lg transition-all active:scale-95">
                        <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                        Selesaikan Pendaftaran
                    </Button>
                </div>
            </div>

            <div class="text-center text-sm text-muted-foreground mt-6">
                Sudah memiliki akun bisnis?
                <TextLink :href="route('login')" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">Masuk Sekarang</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
