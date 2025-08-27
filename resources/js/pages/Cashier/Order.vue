<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
// Modal error dialog state
const errorDialog = ref<{ show: boolean, message: string, info?: string } | null>(null);
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PlusCircle, MinusCircle, XCircle, ShoppingCart, LoaderCircle, DollarSign, Percent, ReceiptText, Image as ImageIcon } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/utils/formatters'; // Make sure this utility exists and works

interface Product {
    id: string;
    name: string;
    price: number;
    stock: number;
    unit: string | null;
    category_id: string | null;
    category?: { id: string; name: string };
    image?: string | null; // Add image property
}

interface Customer {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
}

interface Category {
    id: string;
    name: string;
}

interface SaleItemFormData {
    product_id: string;
    quantity: number;
    price: number; // Price at the time of adding to cart
    name: string; // Product name for display
    unit: string | null;
    stock: number; // Current stock for validation
}

const props = defineProps<{
    products: Product[];
    categories: Category[];
    customers: Customer[];
    tenantSlug: string;
    tenantName: string;
    ipaymuConfigured: boolean;
    ipaymuRedirectUrl?: string;
    midtransConfigured: boolean;
    midtransClientKey?: string;
    vouchers: Voucher[];
    promos: Promo[];
}>();

// TypeScript: declare window.snap for Snap.js
declare global {
    interface Window {
        snap?: any;
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Pemesanan',
        href: route('sales.order', { tenantSlug: props.tenantSlug }),
    },
];

const selectedCategory = ref<string | null>(null);
const searchTerm = ref('');
const cartItems = ref<SaleItemFormData[]>([]);
const selectedCustomer = ref<string | null>(null);

// Voucher & Promo UI State
interface Voucher {
    code: string;
    name: string;
    type: 'percentage' | 'percentage_max' | 'nominal';
    value: number;
    max_nominal?: number;
    expiry_date: string;
}
interface Promo {
    code: string;
    name: string;
    type: 'buyxgetx' | 'buyxgetanother';
    buyQty: number;
    getQty: number;
    productId?: string;
    anotherProductId?: string;
    expiryDate: string;
    typeLabel: string;
}

const availableVouchers = ref<Voucher[]>(props.vouchers || []);
const selectedVoucherCodes = ref<string[]>([]);
const voucherInputCode = ref('');
const voucherError = ref('');
const selectedVouchers = computed(() => {
    return selectedVoucherCodes.value
        .map(code => availableVouchers.value.find(v => v.code === code))
        .filter((v): v is Voucher => !!v);
});

const handleVoucherInput = async () => {
    voucherError.value = '';
    const code = voucherInputCode.value.trim();
    if (!code) {
        voucherError.value = 'Masukkan kode voucher.';
        return;
    }
    if (selectedVoucherCodes.value.includes(code)) {
        voucherError.value = 'Voucher sudah dipilih.';
        return;
    }
    if (selectedVoucherCodes.value.length >= 1) {
        voucherError.value = 'Maksimal 1 voucher.';
        return;
    }
    // Fetch voucher by code from backend for latest status
    try {
        const res = await fetch(route('sales.getVoucherByCode', { tenantSlug: props.tenantSlug, code }), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (!data.voucher) {
            voucherError.value = 'Voucher tidak ditemukan.';
            return;
        }
        if (data.voucher.used) {
            voucherError.value = 'Voucher sudah digunakan.';
            return;
        }
        // Valid voucher, add to selected
        selectedVoucherCodes.value.push(data.voucher.code);
        // Optionally update availableVouchers
        if (!availableVouchers.value.find(v => v.code === data.voucher.code)) {
            availableVouchers.value.push(data.voucher);
        }
        voucherInputCode.value = '';
    } catch (e) {
        voucherError.value = 'Gagal validasi voucher.';
    }
};

const availablePromos = ref<Promo[]>(props.promos || []);
const selectedPromoCode = ref<string | null>(null);
const selectedPromo = computed(() => {
    return availablePromos.value.find(p => p.code === selectedPromoCode.value) || null;
});

// Form data for sale submission
const form = useForm({
    items: [] as { product_id: string; quantity: number }[],
    customer_id: null as string | null,
    discount_amount: 0,
    tax_rate: 0, // Default tax rate
    payment_method: 'cash', // Default to cash
    paid_amount: 0,
    notes: '',
});

// Computed property for filtered products based on category and search
const filteredProducts = computed(() => {
    let products = props.products;

    if (selectedCategory.value) {
        products = products.filter(product => product.category_id === selectedCategory.value);
    }

    if (searchTerm.value) {
        const lowerCaseSearch = searchTerm.value.toLowerCase();
        products = products.filter(product =>
            product.name.toLowerCase().includes(lowerCaseSearch) ||
            product.unit?.toLowerCase().includes(lowerCaseSearch)
        );
    }
    return products;
});

// Add product to cart
const addToCart = (product: Product) => {
    const existingItem = cartItems.value.find(item => item.product_id === product.id);

    if (existingItem) {
        if (existingItem.quantity < existingItem.stock) {
            existingItem.quantity++;
        } else {
            alert(`Stok ${product.name} tidak mencukupi.`);
        }
    } else {
        if (product.stock > 0) {
            cartItems.value.push({
                product_id: product.id,
                quantity: 1,
                price: product.price,
                name: product.name,
                unit: product.unit,
                stock: product.stock,
            });
        } else {
            alert(`Produk ${product.name} sedang tidak tersedia (stok kosong).`);
        }
    }
};

// Update quantity in cart
const updateCartQuantity = (item: SaleItemFormData, delta: number) => {
    const newQuantity = item.quantity + delta;
    if (newQuantity > 0 && newQuantity <= item.stock) {
        item.quantity = newQuantity;
    } else if (newQuantity <= 0) {
        removeFromCart(item.product_id);
    } else if (newQuantity > item.stock) {
        alert(`Stok ${item.name} tidak mencukupi.`);
    }
};

// Remove item from cart
const removeFromCart = (productId: string) => {
    cartItems.value = cartItems.value.filter(item => item.product_id !== productId);
};

// Calculate subtotal for each item
const getItemSubtotal = (item: SaleItemFormData) => {
    return item.quantity * item.price;
};

// Calculate overall subtotal
const overallSubtotal = computed(() => {
    return cartItems.value.reduce((sum, item) => sum + getItemSubtotal(item), 0);
});

// Calculate total voucher discount for up to 3 vouchers
const voucherDiscount = computed(() => {
    const sub = overallSubtotal.value;
    let total = 0;
    selectedVouchers.value.forEach(voucher => {
        if (!voucher) return;
        if (voucher.type === 'percentage') {
            total += sub * (voucher.value / 100);
        } else if (voucher.type === 'percentage_max') {
            const percent = sub * (voucher.value / 100);
            total += voucher.max_nominal ? Math.min(percent, voucher.max_nominal) : percent;
        } else if (voucher.type === 'nominal') {
            total += voucher.value;
        }
    });
    return total;
});

const totalAmount = computed(() => {
    const sub = overallSubtotal.value;
    const discounted = sub - form.discount_amount - voucherDiscount.value;
    const taxed = discounted + (discounted * (form.tax_rate / 100));
    return Math.max(0, taxed); // Ensure total is not negative
});

// Calculate change
const changeAmount = computed(() => {
    if (form.payment_method === 'cash') {
        return form.paid_amount - totalAmount.value;
    }
    return 0; // No change for iPaymu
});

// Watch totalAmount to update paid_amount if iPaymu is selected
watch(totalAmount, (newTotal) => {
    if (form.payment_method === 'ipaymu') {
        form.paid_amount = newTotal;
    }
});

// Watch payment_method to adjust paid_amount
watch(() => form.payment_method, (newMethod) => {
    if (newMethod === 'ipaymu') {
        form.paid_amount = totalAmount.value;
    } else {
        // Reset paid_amount if switching back to cash, or keep it if already entered
        if (form.paid_amount < totalAmount.value) {
            form.paid_amount = totalAmount.value; // Ensure at least total amount is set for cash
        }
    }
});


// Submit sale

// Load Snap.js script for Midtrans
onMounted(() => {
    if (props.midtransConfigured) {
        if (!document.getElementById('midtrans-snapjs')) {
            // Ambil client key dari props jika ada, jika tidak fetch dari backend
            let clientKey = props.midtransClientKey || '';
            if (!clientKey) {
                // Fetch client key dari backend via AJAX
                fetch(route('tenant.midtransClientKey', { tenantSlug: props.tenantSlug }))
                    .then(res => res.json())
                    .then(data => {
                        clientKey = data.clientKey || '';
                        const script = document.createElement('script');
                        script.id = 'midtrans-snapjs';
                        script.type = 'text/javascript';
                        script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
                        script.setAttribute('data-client-key', clientKey);
                        document.body.appendChild(script);
                    });
            } else {
                const script = document.createElement('script');
                script.id = 'midtrans-snapjs';
                script.type = 'text/javascript';
                script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
                script.setAttribute('data-client-key', clientKey);
                document.body.appendChild(script);
            }
        }
    }
});

const handleMidtransPay = (snapToken: any) => {
    if (window.snap && snapToken) {
        // Fungsi untuk redirect ke receipt dengan sales.id
        const redirectToReceiptByOrderId = (orderId: string) => {
            if (!orderId) {
                alert('Order ID tidak ditemukan di response Midtrans.');
                return;
            }
            // Fetch sale_id dari backend
            fetch(route('sales.getSaleIdByOrderId', { tenantSlug: props.tenantSlug, orderId }), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(res => res.json())
                .then(data => {
                    if (data.saleId) {
                        window.location.href = route('sales.receipt', { tenantSlug: props.tenantSlug, sale: data.saleId });
                    } else {
                        alert('Tidak dapat menemukan ID penjualan (UUID) dari order_id.');
                    }
                })
                .catch(() => {
                    alert('Gagal mengambil sales.id dari order_id.');
                });
        };
        window.snap.pay(snapToken, {
            onSuccess: function(result: any) {
                if (result && result.order_id) {
                    redirectToReceiptByOrderId(result.order_id);
                } else {
                    alert('Pembayaran berhasil, tetapi order_id tidak ditemukan.');
                }
            },
            onPending: function(result: any) {
                if (result && result.order_id) {
                    redirectToReceiptByOrderId(result.order_id);
                } else {
                    alert('Pembayaran pending, tetapi order_id tidak ditemukan.');
                }
            },
            onError: function(result: any) {
                if (result && result.order_id) {
                    redirectToReceiptByOrderId(result.order_id);
                } else {
                    alert('Pembayaran gagal, tetapi order_id tidak ditemukan.');
                }
            },
            onClose: function() {
                // Optionally handle close event
            }
        });
    }
};

const submitSale = async () => {
    if (cartItems.value.length === 0) {
        alert('Keranjang belanja kosong. Tambahkan produk terlebih dahulu.');
        return;
    }

    // Prepare items for submission
    form.items = cartItems.value.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
    }));
    form.customer_id = selectedCustomer.value;

    // Add voucher discount to discount_amount automatically
    if (selectedVouchers.value.length > 0) {
        form.discount_amount = (form.discount_amount || 0) + voucherDiscount.value;
    }

    // If vouchers are selected, validate again and mark as used
    for (const voucher of selectedVouchers.value) {
        if (!voucher) continue;
        try {
            const res = await fetch(route('sales.useVoucher', { tenantSlug: props.tenantSlug, code: voucher.code }), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ code: voucher.code }),
            });
            const data = await res.json();
            if (!data.success) {
                alert(data.message || 'Voucher gagal dipakai.');
                return;
            }
        } catch (e) {
            alert('Gagal memproses voucher.');
            return;
        }
    }

    // Adjust paid_amount for iPaymu before submission
    if (form.payment_method === 'ipaymu') {
        form.paid_amount = totalAmount.value;
        fetch(route('sales.store', { tenantSlug: props.tenantSlug }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ ...form.data(), voucher_code: selectedVoucher.value?.code || null }),
        })
        .then(async res => {
            let data;
            try {
                data = await res.json();
            } catch (e) {
                errorDialog.value = { show: true, message: 'Gagal parsing response dari server.' };
                return;
            }
            if (data.success && data.payment_url) {
                window.location.href = data.payment_url;
            } else {
                errorDialog.value = {
                    show: true,
                    message: data.error || 'Gagal mendapatkan URL pembayaran iPaymu.',
                    info: data.info || ''
                };
            }
        })
        .catch((err) => {
            errorDialog.value = { show: true, message: 'Terjadi kesalahan saat menginisiasi pembayaran iPaymu.' };
        });
        return;
    }

    // Untuk cash dan midtrans tetap pakai form.post
    // Add voucher_codes to form data for backend
    (form as any).voucher_codes = selectedVoucherCodes.value;
    form.post(route('sales.store', { tenantSlug: props.tenantSlug }), {
        onSuccess: (page) => {
            if (form.payment_method === 'midtrans' && page.props.snapToken) {
                handleMidtransPay(page.props.snapToken);
            } else if (form.payment_method === 'cash') {
                cartItems.value = [];
                form.reset();
                selectedCustomer.value = null;
                selectedVoucherCodes.value = [];
                voucherInputCode.value = '';
                alert('Pesanan berhasil dibuat!');
            }
        },
        onError: (errors) => {
            console.error('Submission errors:', errors);
            let errorMessage = 'Terjadi kesalahan saat memproses pesanan.';
            if (errors.items) errorMessage += '\n' + errors.items;
            if (errors.paid_amount) errorMessage += '\n' + errors.paid_amount;
            alert(errorMessage);
        },
        onFinish: () => {
            // Any final actions after success or error
        }
    });
};

// Set initial paid_amount to total_amount when component mounts or totalAmount changes
onMounted(() => {
    form.paid_amount = totalAmount.value;

    // Check for flash messages on mount
    const pageProps = usePage().props;
    if (pageProps.flash && typeof pageProps.flash === 'object' && 'success' in pageProps.flash && pageProps.flash.success) {
        alert(pageProps.flash.success);
    }
    if (pageProps.flash && typeof pageProps.flash === 'object' && 'error' in pageProps.flash && pageProps.flash.error) {
        alert(pageProps.flash.error);
    }
});

// Watch totalAmount to keep paid_amount updated if it's less than total and payment method is cash
watch(totalAmount, (newTotal) => {
    if (form.payment_method === 'cash' && form.paid_amount < newTotal) {
        form.paid_amount = newTotal;
    }
});

</script>

<template>
    <Head title="Pemesanan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Error Modal Dialog -->
        <transition name="fade">
            <div v-if="errorDialog && errorDialog.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-5 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 max-w-md w-full text-center relative animate-fadeIn">
                <button @click="errorDialog.show = false" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
                <div class="flex flex-col items-center mb-4">
                    <svg class="w-12 h-12 text-red-500 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0z"/></svg>
                    <h3 class="text-2xl font-bold text-red-600">Terjadi Kesalahan</h3>
                </div>
                <p class="mb-4 text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ errorDialog.message }}</p>
                <div v-if="errorDialog.info" class="mb-6">
                    <div class="flex items-center justify-center gap-2 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-200 rounded px-4 py-3 border border-blue-200 dark:border-blue-800">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/></svg>
                        <div class="text-left">
                            <div class="font-semibold text-blue-700 dark:text-blue-200 mb-1">Additional Info</div>
                            <div class="text-sm leading-relaxed">{{ errorDialog.info }}</div>
                        </div>
                    </div>
                </div>
                <button @click="errorDialog.show = false" class="px-6 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition font-semibold shadow">Tutup</button>
            </div>
        </div>
        </transition>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto lg:flex-row">
            <!-- Product List Section (Left/Top) -->
            <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 overflow-y-auto max-h-[calc(100vh-120px)] lg:max-h-full">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Daftar Produk</h2>

                <div class="mb-4 flex flex-col sm:flex-row gap-3">
                    <Input
                        type="text"
                        v-model="searchTerm"
                        placeholder="Cari produk..."
                        class="flex-1"
                    />
                    <Select v-model="selectedCategory">
                        <SelectTrigger class="w-full sm:w-[200px]">
                            <SelectValue placeholder="Filter Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">Semua Kategori</SelectItem>
                            <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addToCart(product)"
                        :class="[
                            'relative bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow-sm cursor-pointer hover:shadow-md transition-all duration-200 border',
                            product.stock === 0 ? 'opacity-50 cursor-not-allowed border-red-400' : 'border-gray-200 dark:border-gray-600'
                        ]"
                    >
                        <img
                            v-if="product.image"
                            :src="`/storage/${product.image}`"
                            alt="Product Image"
                            class="w-full h-24 object-cover rounded-md mb-2"
                        />
                        <div v-else class="w-full h-24 bg-gray-200 dark:bg-gray-600 rounded-md mb-2 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <ImageIcon class="w-10 h-10" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-base leading-tight mb-1">{{ product.name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ product.category?.name || 'Uncategorized' }}</p>
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(product.price) }}</p>
                        <p :class="['text-xs font-medium', product.stock <= 5 && product.stock > 0 ? 'text-orange-500' : product.stock === 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400']">
                            Stok: {{ product.stock }} {{ product.unit || 'pcs' }}
                        </p>
                        <div v-if="product.stock === 0" class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center rounded-lg">
                            <span class="text-white font-bold text-lg">SOLD OUT</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart and Payment Section (Right/Bottom) -->
            <div class="w-full lg:w-[400px] bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 flex flex-col max-h-[calc(100vh-120px)]">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <ShoppingCart class="h-6 w-6" /> Keranjang Belanja
                </h2>

                <div class="flex-1 overflow-y-auto pr-2 mb-4">
                    <div v-if="cartItems.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-10">
                        Keranjang kosong. Tambahkan produk!
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="item in cartItems" :key="item.product_id" class="flex items-center justify-between border-b pb-2 last:border-b-0">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ formatCurrency(item.price) }} x {{ item.quantity }} {{ item.unit || 'pcs' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="ghost" size="icon" @click="updateCartQuantity(item, -1)">
                                    <MinusCircle class="h-4 w-4" />
                                </Button>
                                <span class="font-semibold w-8 text-center">{{ item.quantity }}</span>
                                <Button variant="ghost" size="icon" @click="updateCartQuantity(item, 1)">
                                    <PlusCircle class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="removeFromCart(item.product_id)" class="text-red-500">
                                    <XCircle class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Section -->

                <div class="border-t pt-4 mt-auto">
                    <!-- Subtotal -->
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300 mb-2">
                        <span>Subtotal:</span>
                        <span class="font-semibold">{{ formatCurrency(overallSubtotal) }}</span>
                    </div>

                    <!-- Voucher & Promo Section -->
                    <div class="mb-4">
                        <Label for="voucher" class="text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <ReceiptText class="h-4 w-4" /> Voucher:
                        </Label>
                        <div class="flex gap-2 mt-1">
                            <Input
                                id="voucher_code"
                                type="text"
                                v-model="voucherInputCode"
                                placeholder="Masukkan kode voucher"
                                class="flex-1"
                            />
                            <Button @click="handleVoucherInput" type="button">Cek Voucher</Button>
                        </div>
                        <div v-if="voucherError" class="text-red-500 text-sm mt-1">{{ voucherError }}</div>
                        <div v-if="selectedVouchers.length > 0" class="mt-2 space-y-2">
                            <div v-for="voucher in selectedVouchers" :key="voucher?.code" class="p-2 rounded bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-200 flex items-center gap-2">
                                <ReceiptText class="h-4 w-4" />
                                <span v-if="voucher" class="font-semibold">{{ voucher.name }}</span>
                                <span v-if="voucher" class="text-xs">({{ voucher.type }})</span>
                                <span v-if="voucher" class="text-xs text-gray-400 ml-auto">Exp: {{ voucher.expiry_date }}</span>
                                <Button v-if="voucher" size="sm" variant="ghost" class="text-red-500 ml-2" @click="selectedVoucherCodes = selectedVoucherCodes.filter(c => c !== voucher.code)">Hapus</Button>
                            </div>
                        </div>
                        <div v-if="voucherDiscount > 0" class="text-green-600 dark:text-green-400 text-sm mt-1">Diskon voucher: -{{ formatCurrency(voucherDiscount) }}</div>
                    </div>
                    <div class="mb-4">
                        <Label for="promo" class="text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <Percent class="h-4 w-4" /> Promo:
                        </Label>
                        <Select v-model="selectedPromoCode">
                            <SelectTrigger class="w-full mt-1">
                                <SelectValue placeholder="Pilih Promo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Tidak Pakai Promo</SelectItem>
                                <SelectItem v-for="promo in availablePromos" :key="promo.code" :value="promo.code">
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{ promo.name }}</span>
                                        <span class="text-xs text-gray-500">{{ promo.typeLabel }}</span>
                                        <span class="text-xs text-gray-400">Exp: {{ promo.expiryDate }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="selectedPromo" class="mt-2 p-2 rounded bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200">
                            <div class="flex items-center gap-2">
                                <Percent class="h-4 w-4" />
                                <span class="font-semibold">{{ selectedPromo.name }}</span>
                                <span class="text-xs">({{ selectedPromo.typeLabel }})</span>
                                <span class="text-xs text-gray-400 ml-auto">Exp: {{ selectedPromo.expiryDate }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Discount -->
                    <div class="flex justify-between items-center mb-2">
                        <Label for="discount" class="text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <DollarSign class="h-4 w-4" /> Diskon:
                        </Label>
                        <Input
                            id="discount"
                            type="number"
                            step="0.01"
                            v-model.number="form.discount_amount"
                            class="w-32 text-right"
                            min="0"
                            :max="overallSubtotal"
                        />
                    </div>

                    <!-- Tax -->
                    <div class="flex justify-between items-center mb-2">
                        <Label for="tax_rate" class="text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <Percent class="h-4 w-4" /> Pajak (%):
                        </Label>
                        <Input
                            id="tax_rate"
                            type="number"
                            step="0.01"
                            v-model.number="form.tax_rate"
                            class="w-32 text-right"
                            min="0"
                            max="100"
                        />
                    </div>

                    <!-- Total & Payment -->
                    <div class="flex justify-between font-bold text-2xl text-gray-900 dark:text-gray-100 border-t pt-3 mt-3 mb-2">
                        <span>TOTAL:</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <Label for="payment_method" class="text-gray-700 dark:text-gray-300">Metode Pembayaran:</Label>
                        <Select v-model="form.payment_method">
                            <SelectTrigger class="w-[150px]">
                                <SelectValue placeholder="Pilih Metode" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="cash">Tunai</SelectItem>
                                <SelectItem value="ipaymu" :disabled="!ipaymuConfigured">
                                    iPaymu
                                    <span v-if="!ipaymuConfigured" class="text-xs text-red-500 ml-2">(Belum dikonfigurasi)</span>
                                </SelectItem>
                                <SelectItem value="midtrans" :disabled="!midtransConfigured">
                                    Midtrans
                                    <span v-if="!midtransConfigured" class="text-xs text-red-500 ml-2">(Belum dikonfigurasi)</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Paid Amount & Change (Cash only) -->
                    <div v-if="form.payment_method === 'cash'" class="flex justify-between items-center mb-2">
                        <Label for="paid_amount" class="text-gray-700 dark:text-gray-300">Jumlah Dibayar:</Label>
                        <Input
                            id="paid_amount"
                            type="number"
                            step="0.01"
                            v-model.number="form.paid_amount"
                            class="w-32 text-right"
                            :min="totalAmount"
                        />
                    </div>
                    <div v-if="form.payment_method === 'cash'" class="flex justify-between font-bold text-xl text-green-600 dark:text-green-400 mb-2">
                        <span>Kembalian:</span>
                        <span>{{ formatCurrency(changeAmount) }}</span>
                    </div>

                    <!-- Customer (Optional) -->
                    <div class="flex justify-between items-center mb-2">
                        <Label for="customer" class="text-gray-700 dark:text-gray-300">Pelanggan (Opsional)</Label>
                        <Select v-model="selectedCustomer">
                            <SelectTrigger class="w-[200px]">
                                <SelectValue placeholder="Pilih Pelanggan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Umum</SelectItem>
                                <SelectItem v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Notes (Optional) -->
                    <div class="mb-4">
                        <Label for="notes" class="text-gray-700 dark:text-gray-300">Catatan (Opsional):</Label>
                        <Textarea id="notes" v-model="form.notes" rows="2" class="mt-1" />
                    </div>

                    <!-- Process Button -->
                    <Button @click="submitSale" :disabled="form.processing || cartItems.length === 0 || (form.payment_method === 'cash' && form.paid_amount < totalAmount)" class="w-full py-3 text-lg">
                        <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                        Proses Pesanan
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
