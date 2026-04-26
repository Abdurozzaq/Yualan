<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Lock, ChevronRight } from 'lucide-vue-next';

interface TenantData {
    name: string;
}

interface Props {
    tenant: TenantData;
    tenantSlug: string;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Pengaturan Tenant',
        href: route('tenant.settings.info', { tenantSlug: props.tenantSlug }),
    },
];

const form = useForm({
    name: props.tenant.name,
});

const submit = () => {
    form.patch(route('tenant.settings.update', { tenantSlug: props.tenantSlug }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Pengaturan Tenant" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Informasi Tenant"
                    description="Perbarui nama tenant dan profil bisnis Anda."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-gray-100 dark:border-gray-800 p-8 shadow-sm transition-all hover:shadow-md">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                            <div class="w-2 h-6 bg-blue-600 rounded-full"></div>
                            Informasi Bisnis
                        </h3>
                        <div class="grid gap-6">
                            <div class="grid gap-2">
                                <Label for="name" class="font-bold text-gray-700 dark:text-gray-300">Nama Bisnis / Tenant</Label>
                                <Input
                                    id="name"
                                    class="h-12 rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm focus:ring-2 focus:ring-blue-500"
                                    v-model="form.name"
                                    required
                                    autocomplete="organization"
                                    placeholder="Nama Bisnis Anda"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Locked Invitation Code Section -->
                            <div class="mt-4 p-6 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border-2 border-dashed border-gray-200 dark:border-gray-800 relative overflow-hidden group">
                                <div class="absolute top-0 right-0 p-3">
                                    <Lock class="h-5 w-5 text-amber-500 opacity-50 group-hover:opacity-100 transition-opacity" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label class="font-bold text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                        Kode Undangan Tenant
                                        <span class="text-[10px] bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded-full uppercase tracking-tighter font-black">Premium</span>
                                    </Label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                        Fitur Multi-User dan Kode Undangan tersedia di versi <strong>Premium</strong>. 
                                        Undang tim Anda untuk berkolaborasi dalam satu sistem yang terintegrasi.
                                    </p>
                                    <a href="https://yualan.web.id" target="_blank" class="text-blue-600 dark:text-blue-400 text-xs font-bold hover:underline mt-1 flex items-center gap-1">
                                        Pelajari Paket Premium <ChevronRight class="h-3 w-3" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-6 pt-6">
                        <Button :disabled="form.processing" class="h-14 px-10 rounded-xl bg-blue-600 hover:bg-blue-700 shadow-xl shadow-blue-200 dark:shadow-none w-full sm:w-auto font-black text-lg transition-all active:scale-95">
                            Simpan Semua Perubahan
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0 translate-x-4"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0 translate-x-4"
                        >
                            <div v-show="form.recentlySuccessful" class="flex items-center gap-2 text-green-600 dark:text-green-400 font-bold bg-green-50 dark:bg-green-900/30 px-4 py-2 rounded-lg border border-green-100 dark:border-green-900/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                Berhasil disimpan.
                            </div>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
