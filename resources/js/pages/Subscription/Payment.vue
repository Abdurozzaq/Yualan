<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    pricingPlans: Array,
});

const form = useForm({
    plan_id: null,
});

const subscribe = (planId) => {
    form.plan_id = planId;
    form.post(route('subscription.subscribe'), {
        onFinish: () => {
            //
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};
const getDiscountedPrice = (plan) => {
    if (plan.discount_percentage && plan.discount_percentage > 0) {
        return plan.price - (plan.price * plan.discount_percentage / 100);
    }
    return plan.price;
};
</script>

<template>
    <Head title="Subscription Payment" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Pembayaran Langganan</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-2xl font-bold mb-4">Pilih Paket Langganan Anda</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                            <div v-for="plan in pricingPlans" :key="plan.id" class="border p-4 sm:p-6 rounded-lg flex flex-col h-full">
                                <h4 class="text-xl font-semibold">{{ plan.plan_name }}</h4>
                                <p class="text-gray-500 mt-2">{{ plan.plan_description }}</p>
                                <div class="my-4">
                                    <template v-if="plan.discount_percentage && plan.discount_percentage > 0">
                                        <div class="text-sm text-red-500 font-semibold mb-1">
                                            Diskon {{ plan.discount_percentage }}%
                                        </div>
                                        <div class="flex flex-col items-start">
                                            <span class="text-xs text-gray-400 line-through">{{ formatCurrency(plan.price) }}</span>
                                            <span class="text-3xl font-bold text-green-600 leading-tight">{{ formatCurrency(getDiscountedPrice(plan)) }}</span>
                                            <span class="text-xs text-gray-500">/{{ plan.period_type === 'monthly' ? 'bulan' : 'tahun' }}</span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="text-3xl font-bold">
                                            {{ formatCurrency(plan.price) }}
                                            <span class="text-lg font-normal">/{{ plan.period_type === 'monthly' ? 'bulan' : 'tahun' }}</span>
                                        </div>
                                    </template>
                                </div>
                                <button @click="subscribe(plan.id)" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600" :disabled="form.processing">
                                    {{ form.processing ? 'Memproses...' : 'Pilih Paket' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
