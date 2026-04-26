<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue'; // Assuming this path is correct
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Reactive state for managing the current step and registration type
const currentStep = ref(1);
const registrationType = ref<'personal' | 'company' | null>(null);

// Form data using Inertia's useForm hook
const form = useForm({
    registration_type: '', // 'personal' or 'company'
    // User account details
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    // Personal registration specific
    invitation_code: '',
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
            return 'Pilih Tipe Pendaftaran';
        case 2:
            return 'Detail Akun Anda';
        case 3:
            return registrationType.value === 'personal' ? 'Kode Undangan & Persetujuan' : 'Detail Perusahaan Anda';
        default:
            return 'Daftar Akun Baru';
    }
});

// Computed property to determine the description based on the current step
const pageDescription = computed(() => {
    switch (currentStep.value) {
        case 1:
            return 'Pilih bagaimana Anda ingin mendaftar untuk memulai.';
        case 2:
            return 'Masukkan detail yang akan Anda gunakan untuk masuk.';
        case 3:
            return registrationType.value === 'personal'
                ? 'Masukkan kode undangan Anda'
                : 'Lengkapi informasi perusahaan Anda.';
        default:
            return 'Lengkapi detail Anda untuk membuat akun.';
    }
});

// Function to navigate to the next step
const nextStep = () => {
    if (currentStep.value === 1 && !registrationType.value) {
        // Basic validation for step 1
        alert('Silakan pilih tipe pendaftaran.'); // Using alert for simplicity, consider a custom modal
        return;
    }
    // Set the form's registration_type based on selection
    form.registration_type = registrationType.value || '';
    currentStep.value++;
};

// Function to navigate to the previous step
const prevStep = () => {
    currentStep.value--;
};

// Function to handle form submission
const submit = () => {
    // Determine the route based on registration type
    // You'll need to define a single backend route that handles both types
    // e.g., route('register.process')
    form.post(route('register'), { // Assuming 'register' route handles the logic
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: (errors) => {
            // If there are errors, check which step they belong to and navigate back
            if (errors.name || errors.email || errors.password || errors.password_confirmation) {
                currentStep.value = 2;
            } else if (errors.invitation_code || errors.company_name || errors.company_email || errors.business_type) {
                currentStep.value = 3;
            }
        }
    });
};
</script>

<template>
    <AuthBase :title="pageTitle" :description="pageDescription">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <!-- Step 1: Choose Registration Type -->
            <div v-if="currentStep === 1" class="grid gap-6">
                <div class="grid gap-4">
                    <Button
                        type="button"
                        @click="registrationType = 'personal'"
                        :class="{ 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600 shadow-lg shadow-blue-200 dark:shadow-none': registrationType === 'personal', 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700': registrationType !== 'personal' }"
                        class="w-full h-14 rounded-xl border-2 transition-all duration-200 font-bold"
                    >
                        Daftar sebagai Perorangan
                    </Button>
                    <p class="text-xs text-center text-muted-foreground px-4">
                        Jika Anda mendaftar sebagai perorangan, Anda memerlukan kode undangan dari pemilik bisnis.
                    </p>
                </div>
                <div class="grid gap-4">
                    <Button
                        type="button"
                        @click="registrationType = 'company'"
                        :class="{ 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600 shadow-lg shadow-blue-200 dark:shadow-none': registrationType === 'company', 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700': registrationType !== 'company' }"
                        class="w-full h-14 rounded-xl border-2 transition-all duration-200 font-bold"
                    >
                        Daftar sebagai Perusahaan
                    </Button>
                    <p class="text-xs text-center text-muted-foreground px-4">
                        Daftarkan perusahaan baru untuk mulai mengelola bisnis Anda sendiri.
                    </p>
                </div>
                <Button type="button" @click="nextStep" :disabled="!registrationType" class="mt-4 w-full h-12 rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-200 dark:shadow-none font-bold">
                    Lanjutkan
                </Button>
            </div>

            <!-- Step 2: User Account Details -->
            <div v-else-if="currentStep === 2" class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="name" class="font-bold">Nama Lengkap</Label>
                    <Input id="name" type="text" required autofocus autocomplete="name" v-model="form.name" placeholder="Nama Lengkap Anda" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="font-bold">Alamat Email</Label>
                    <Input id="email" type="email" required autocomplete="email" v-model="form.email" placeholder="email@example.com" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="font-bold">Kata Sandi</Label>
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
                    <Label for="password_confirmation" class="font-bold">Konfirmasi Kata Sandi</Label>
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

                <div class="flex flex-col sm:flex-row gap-3 mt-4">
                    <Button type="button" @click="prevStep" variant="outline" class="w-full sm:w-1/2 h-12 rounded-xl">
                        Kembali
                    </Button>
                    <Button type="button" @click="nextStep" class="w-full sm:w-1/2 h-12 rounded-xl bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 dark:shadow-none font-bold">
                        Lanjutkan
                    </Button>
                </div>
            </div>

            <!-- Step 3: Type-Specific Details -->
            <div v-else-if="currentStep === 3" class="grid gap-4">
                <div v-if="registrationType === 'personal'" class="grid gap-2">
                    <Label for="invitation_code" class="font-bold">Kode Undangan</Label>
                    <Input id="invitation_code" type="text" required v-model="form.invitation_code" placeholder="Masukkan kode undangan Anda" class="h-12 rounded-xl" />
                    <InputError :message="form.errors.invitation_code" />
                    <p class="text-xs text-muted-foreground mt-2 leading-relaxed">
                        Akun perorangan memerlukan kode undangan dari pemilik bisnis. Jika Anda ingin membuka bisnis baru, silakan kembali dan pilih "Daftar sebagai Perusahaan".
                    </p>
                </div>

                <div v-else-if="registrationType === 'company'" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="company_name" class="font-bold">Nama Perusahaan</Label>
                        <Input id="company_name" type="text" required v-model="form.company_name" placeholder="Nama Perusahaan Anda" class="h-12 rounded-xl" />
                        <InputError :message="form.errors.company_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company_email" class="font-bold">Email Bisnis</Label>
                        <Input id="company_email" type="email" required v-model="form.company_email" placeholder="email.bisnis@example.com" class="h-12 rounded-xl" />
                        <InputError :message="form.errors.company_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company_phone" class="font-bold">Nomor Telepon Bisnis</Label>
                        <Input id="company_phone" type="text" v-model="form.company_phone" placeholder="Contoh: 08123456789" class="h-12 rounded-xl" />
                        <InputError :message="form.errors.company_phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="business_type" class="font-bold">Tipe Bisnis</Label>
                        <Input id="business_type" type="text" required v-model="form.business_type" placeholder="Contoh: Toko, Restoran, Kafe" class="h-12 rounded-xl" />
                        <InputError :message="form.errors.business_type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company_address" class="font-bold">Alamat Bisnis</Label>
                        <Input id="company_address" type="text" v-model="form.company_address" placeholder="Alamat Lengkap" class="h-12 rounded-xl" />
                        <InputError :message="form.errors.company_address" />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mt-4">
                    <Button type="button" @click="prevStep" variant="outline" class="w-full sm:w-1/2 h-12 rounded-xl">
                        Kembali
                    </Button>
                    <Button type="submit" :disabled="form.processing" class="w-full sm:w-1/2 h-12 rounded-xl bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 dark:shadow-none font-bold">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Daftar Akun
                    </Button>
                </div>
            </div>

            <div class="text-center text-sm text-muted-foreground mt-6">
                Sudah punya akun?
                <TextLink :href="route('login')" class="underline underline-offset-4">Masuk</TextLink>
            </div>
        </form>
    </AuthBase>
</template>

