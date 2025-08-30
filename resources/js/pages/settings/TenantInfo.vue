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
import { watch } from 'vue'; // Commented out as it is unused

interface TenantData {
    name: string;
    ipaymu_api_key: string | null;
    ipaymu_secret_key: string | null;
    ipaymu_mode: string | null;
    invitation_code: string | null;
    midtrans_server_key: string | null;
    midtrans_client_key: string | null;
    midtrans_merchant_id: string | null;
    midtrans_is_production: boolean | null;
}

interface Props {
    tenant: TenantData;
    tenantSlug: string;
    status?: string;
    newInvitationCode?: string; // Prop to receive new code after generation (from flash data)
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
    ipaymu_api_key: props.tenant.ipaymu_api_key || '',
    ipaymu_secret_key: props.tenant.ipaymu_secret_key || '',
    ipaymu_mode: props.tenant.ipaymu_mode || 'production',
    invitation_code: props.tenant.invitation_code || '',
    midtrans_server_key: props.tenant.midtrans_server_key || '',
    midtrans_client_key: props.tenant.midtrans_client_key || '',
    midtrans_merchant_id: props.tenant.midtrans_merchant_id || '',
    midtrans_is_production: typeof props.tenant.midtrans_is_production === 'boolean' ? props.tenant.midtrans_is_production : false,
});


// Sinkronkan form dengan props.tenant setiap kali props.tenant berubah (misal setelah reload)
watch(
    () => props.tenant,
    (tenant) => {
        form.name = tenant.name || '';
        form.ipaymu_api_key = tenant.ipaymu_api_key || '';
        form.ipaymu_secret_key = tenant.ipaymu_secret_key || '';
        form.ipaymu_mode = tenant.ipaymu_mode || 'production';
        form.invitation_code = tenant.invitation_code || '';
        form.midtrans_server_key = tenant.midtrans_server_key || '';
        form.midtrans_client_key = tenant.midtrans_client_key || '';
        form.midtrans_merchant_id = tenant.midtrans_merchant_id || '';
        form.midtrans_is_production = typeof tenant.midtrans_is_production === 'boolean' ? tenant.midtrans_is_production : false;
    },
    { immediate: true }
);

// Tetap update invitation_code jika dapat flash data baru
watch(() => props.newInvitationCode, (newCode) => {
    if (newCode) {
        form.invitation_code = newCode;
    }
});

const submit = () => {
    // Pastikan midtrans_is_production dikirim sebagai boolean
    form.midtrans_is_production = Boolean(form.midtrans_is_production);
    form.patch(route('tenant.settings.update', { tenantSlug: props.tenantSlug }), {
        preserveScroll: true,
    });
};

const generateNewCode = () => {
    // This will send a POST request to the backend to generate a new code.
    // The backend will then redirect back, and the new code will be available
    // in the `newInvitationCode` prop via Inertia's flash data.
    useForm({}).post(route('tenant.settings.generateInvitationCode', { tenantSlug: props.tenantSlug }), {
        preserveScroll: true,
        onSuccess: () => {
            // The `watch` effect above will automatically update `form.invitation_code`
            // when `props.newInvitationCode` is received.
            window.location.reload(); // Optionally reload the page to reflect changes immediately
        },
        onError: (errors) => {
            console.error("Error generating code:", errors);
            // You might want to display a more user-friendly error message here
        }
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
                    description="Perbarui nama tenant dan kredensial iPaymu Anda."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- GROUP: Informasi Tenant -->
                    <div class="rounded-lg border p-4 mb-6">
                        <h3 class="font-semibold text-lg mb-2">Informasi Tenant</h3>
                        <div class="grid gap-2">
                            <Label for="name">Nama Tenant</Label>
                            <Input
                                id="name"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autocomplete="organization"
                                placeholder="Nama Bisnis Anda"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <!-- New: Invitation Code Field -->
                        <div class="grid gap-2 mt-4">
                            <Label for="invitation_code">Kode Undangan Tenant</Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="invitation_code"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.invitation_code"
                                    placeholder="Kode unik untuk undangan tenant"
                                />
                                <Button type="button" @click="generateNewCode" :disabled="form.processing" variant="outline">
                                    Generate Baru
                                </Button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.invitation_code" />
                            <p class="text-sm text-muted-foreground">
                                Gunakan kode ini untuk mengundang pengguna baru ke tenant Anda.
                            </p>
                        </div>
                    </div>

                    <!-- GROUP: Pengaturan Midtrans -->
                    <div class="rounded-lg border p-4 mb-6">
                        <h3 class="font-semibold text-lg mb-2">Pengaturan Midtrans</h3>
                        <div class="grid gap-2">
                            <Label for="midtrans_server_key">Server Key</Label>
                            <Input
                                id="midtrans_server_key"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.midtrans_server_key"
                                autocomplete="off"
                                placeholder="Server Key dari dashboard Midtrans"
                            />
                            <InputError class="mt-2" :message="form.errors.midtrans_server_key" />
                        </div>
                        <div class="grid gap-2 mt-2">
                            <Label for="midtrans_client_key">Client Key</Label>
                            <Input
                                id="midtrans_client_key"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.midtrans_client_key"
                                autocomplete="off"
                                placeholder="Client Key dari dashboard Midtrans"
                            />
                            <InputError class="mt-2" :message="form.errors.midtrans_client_key" />
                        </div>
                        <div class="grid gap-2 mt-2">
                            <Label for="midtrans_merchant_id">Merchant ID</Label>
                            <Input
                                id="midtrans_merchant_id"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.midtrans_merchant_id"
                                autocomplete="off"
                                placeholder="Merchant ID dari dashboard Midtrans"
                            />
                            <InputError class="mt-2" :message="form.errors.midtrans_merchant_id" />
                        </div>
                        <div class="grid gap-2 mt-2">
                            <Label for="midtrans_is_production">Mode</Label>
                            <select
                                id="midtrans_is_production"
                                class="mt-1 block w-full border rounded px-3 py-2 focus:outline-none focus:ring"
                                v-model="form.midtrans_is_production"
                            >
                                <option :value="false">Sandbox</option>
                                <option :value="true">Production</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.midtrans_is_production" />
                        </div>
                    </div>

                    <!-- GROUP: Pengaturan iPaymu -->
                    <div class="rounded-lg border p-4 mb-6">
                        <h3 class="font-semibold text-lg mb-2">Pengaturan iPaymu</h3>
                        <div class="grid gap-2">
                            <Label for="ipaymu_mode">Mode</Label>
                            <select
                                id="ipaymu_mode"
                                class="mt-1 block w-full border rounded px-3 py-2 focus:outline-none focus:ring"
                                v-model="form.ipaymu_mode"
                            >
                                <option value="production">Production</option>
                                <option value="sandbox">Sandbox</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.ipaymu_mode" />
                        </div>
                        <div class="grid gap-2 mt-2">
                            <Label for="ipaymu_api_key">API Key (VA)</Label>
                            <Input
                                id="ipaymu_api_key"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.ipaymu_api_key"
                                autocomplete="off"
                                placeholder="Contoh: 117xxxxxxx"
                            />
                            <InputError class="mt-2" :message="form.errors.ipaymu_api_key" />
                        </div>
                        <div class="grid gap-2 mt-2">
                            <Label for="ipaymu_secret_key">Secret Key</Label>
                            <Input
                                id="ipaymu_secret_key"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.ipaymu_secret_key"
                                autocomplete="off"
                                placeholder="Contoh: pSxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                            />
                            <InputError class="mt-2" :message="form.errors.ipaymu_secret_key" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Simpan</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600 dark:text-neutral-400">Tersimpan.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
