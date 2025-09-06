<script setup lang="ts">
// TypeScript: declare window.snap for Snap.js
declare global {
    interface Window {
        snap?: any;
    }
}
// Show Midtrans retry button if payment_method is midtrans and status is pending/failed
const showMidtransRetryButton = computed(() => {
    return props.sale.payment_method === 'midtrans' &&
        (props.sale.status === 'pending' || props.sale.status === 'failed');
});

// Retry Midtrans payment: fetch snapToken from backend, then call Snap.js
const retryMidtransPayment = () => {
    // Fetch snapToken from backend
    fetch(route('sales.midtransRetry', { tenantSlug: props.tenantSlug, sale: props.sale.id }), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
    })
        .then(res => res.json())
        .then(data => {
            if (data.snapToken) {
                // Call Snap.js
                if (window.snap) {
                    window.snap.pay(data.snapToken, {
                        onSuccess: function() {
                            window.location.reload();
                        },
                        onPending: function() {
                            window.location.reload();
                        },
                        onError: function() {
                            window.location.reload();
                        },
                        onClose: function() {}
                    });
                } else {
                    alert('Midtrans Snap.js belum dimuat. Silakan refresh halaman.');
                }
            } else {
                alert('Gagal mendapatkan Snap Token dari server.');
            }
        })
        .catch(() => {
            alert('Gagal memulai pembayaran ulang Midtrans.');
        });
};
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3'; // Import router
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Printer, CheckCircle, XCircle, Clock, Wallet } from 'lucide-vue-next'; // Import Wallet icon
import { formatCurrency } from '@/utils/formatters'; // Import formatCurrency helper

interface SaleItem {
    id: string;
    product_id: string;
    product: {
        name: string;
        unit: string | null;
    };
    quantity: number;
    price: number;
    subtotal: number;
}

interface Customer {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
}

interface User {
    id: string;
    name: string;
}

interface Sale {
    id: string;
    invoice_number: string;
    total_amount: number;
    discount_amount: number;
    tax_amount: number;
    paid_amount: number;
    change_amount: number;
    payment_method: string;
    status: string;
    notes: string | null;
    created_at: string;
    sale_items: SaleItem[];
    customer: Customer | null;
    user: User; // Cashier
}

const props = defineProps<{
    sale: Sale;
    tenantSlug: string;
    tenantName: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Pemesanan',
        href: route('sales.order', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Resi',
        href: route('sales.receipt', { tenantSlug: props.tenantSlug, sale: props.sale.id }),
    },
];

const saleStatusColor = computed(() => {
    switch (props.sale.status) {
        case 'completed': return 'text-green-600 bg-green-100 dark:bg-green-800 dark:text-green-200';
        case 'pending': return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-800 dark:text-yellow-200';
        case 'cancelled':
        case 'failed': return 'text-red-600 bg-red-100 dark:bg-red-800 dark:text-red-200';
        default: return 'text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-300';
    }
});

const saleStatusIcon = computed(() => {
    switch (props.sale.status) {
        case 'completed': return CheckCircle;
        case 'pending': return Clock;
        case 'cancelled':
        case 'failed': return XCircle;
        default: return null;
    }
});

const formattedDate = computed(() => {
    return new Date(props.sale.created_at).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const printReceipt = () => {
    // Redirect to the Laravel route that generates the PDF
    window.open(route('sales.receipt.pdf', { tenantSlug: props.tenantSlug, sale: props.sale.id }), '_blank');
};

const printReceiptThermal = () => {
    // Redirect to the Laravel route that generates the thermal PDF
    window.open(route('sales.receipt.thermal', { tenantSlug: props.tenantSlug, sale: props.sale.id }), '_blank');
};

// New function to re-initiate iPaymu payment
const reinitiateIpaymuPayment = () => {
    router.post(route('sales.reinitiatePayment', { tenantSlug: props.tenantSlug, sale: props.sale.id }), {}, {
        onSuccess: (page: any) => {
            // Check if the backend returned a redirect URL for iPaymu
            if (page.props.ipaymuRedirectUrl) {
                window.location.href = page.props.ipaymuRedirectUrl;
            } else {
                // If no redirect URL, maybe show a success message or just refresh the page
                // The page should already be updated by Inertia on success
                alert('Permintaan pembayaran ulang berhasil diproses. Silakan periksa status transaksi.');
            }
        },
        onError: (errors) => {
            console.error('Error re-initiating iPaymu payment:', errors);
            alert('Gagal memulai pembayaran ulang: ' + (errors.message || 'Terjadi kesalahan.'));
        },
        preserveScroll: true,
    });
};

// Computed property to determine if the "Pay Now" button should be shown
const showPayNowButton = computed(() => {
    return props.sale.payment_method === 'ipaymu' &&
           (props.sale.status === 'pending' || props.sale.status === 'failed' || props.sale.status === 'cancelled');
});
</script>

<template>
    <Head :title="`Resi Penjualan #${sale.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4 print:hidden">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Resi Penjualan
                </h1>
                <div class="flex gap-2">
                    <!-- <Button v-if="showPayNowButton" @click="reinitiateIpaymuPayment" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white">
                        <Wallet class="h-4 w-4" /> Bayar Sekarang (iPaymu)
                    </Button> -->
                    <!-- <Button v-if="showMidtransRetryButton" @click="retryMidtransPayment" class="flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white">
                        <Wallet class="h-4 w-4" /> Bayar Ulang (Midtrans)
                    </Button> -->
                    <Button v-if="sale.status === 'completed'" @click="printReceiptThermal" class="flex items-center gap-2">
                        <Printer class="h-4 w-4" /> Cetak Resi (Thermal)
                    </Button>
                    <Button v-if="sale.status === 'completed'" @click="printReceipt" class="flex items-center gap-2">
                        <Printer class="h-4 w-4" /> Cetak Resi (PDF)
                    </Button>
                    <Link v-if="sale.status !== 'completed'" :href="route('sales.order', { tenantSlug: props.tenantSlug, orderId: props.sale.id })">
                        <Button variant="outline">
                            Kembali ke Pemesanan
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Receipt Content - This div will be the only one printed -->
            <div id="receipt-printable-area" class="bg-white dark:bg-gray-800 rounded-md shadow-sm p-6 max-w-2xl mx-auto w-full print:shadow-none print:p-0">
                <div class="text-center mb-6">
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">{{ tenantName }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Terima kasih atas pesanan Anda!</p>
                </div>

                <div class="flex justify-between items-center mb-4 border-b pb-4 border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">INVOICE #{{ sale.invoice_number }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tanggal: {{ formattedDate }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kasir: {{ sale.user.name }}</p>
                        <p v-if="sale.customer" class="text-sm text-gray-600 dark:text-gray-400">Pelanggan: {{ sale.customer.name }}</p>
                    </div>
                    <div :class="['px-3 py-1 rounded-full text-sm font-semibold', saleStatusColor]">
                        <component :is="saleStatusIcon" class="inline-block h-4 w-4 mr-1" />
                        {{ sale.status.toUpperCase() }}
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-gray-900 dark:text-gray-100">Detail Pesanan:</h3>
                    <div class="space-y-2">
                        <div v-for="item in sale.sale_items" :key="item.id" class="flex justify-between items-center text-gray-800 dark:text-gray-200">
                            <span class="flex-grow">{{ item.product.name }} ({{ item.quantity }} {{ item.product.unit || 'pcs' }})</span>
                            <span class="font-medium">{{ formatCurrency(item.price) }}</span>
                            <span class="ml-4 w-20 text-right">{{ formatCurrency(item.subtotal) }}</span>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4 mt-6 border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300 mb-2">
                        <span>Subtotal:</span>
                        <span>{{ formatCurrency(sale.sale_items.reduce((sum, item) => sum + item.subtotal, 0)) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300 mb-2">
                        <span>Diskon:</span>
                        <span>- {{ formatCurrency(sale.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300 mb-2">
                        <span>Pajak:</span>
                        <span>+ {{ formatCurrency(sale.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-2xl text-gray-900 dark:text-gray-100 border-t pt-3 mt-3">
                        <span>TOTAL:</span>
                        <span>{{ formatCurrency(sale.total_amount) }}</span>
                    </div>
                </div>

                <div class="border-t pt-4 mt-6 border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300 mb-2">
                        <span>Metode Pembayaran:</span>
                        <span class="font-semibold">{{ sale.payment_method.toUpperCase() }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300 mb-2">
                        <span>Jumlah Dibayar:</span>
                        <span>{{ formatCurrency(sale.paid_amount) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-xl text-green-600 dark:text-green-400">
                        <span>Kembalian:</span>
                        <span>{{ formatCurrency(sale.change_amount) }}</span>
                    </div>
                </div>

                <p v-if="sale.notes" class="text-sm text-gray-600 dark:text-gray-400 mt-6">
                    Catatan: {{ sale.notes }}
                </p>

                <div class="text-center mt-8 text-sm text-gray-500 dark:text-gray-400">
                    Terima kasih telah berbelanja di {{ tenantName }}!
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Use Windows system font stack for a clean, modern look */
#receipt-printable-area {
    font-family: 'Segoe UI', Arial, 'Helvetica Neue', Helvetica, sans-serif;
    font-size: 15px;
    letter-spacing: 0.01em;
    color: #222;
}
</style>
