<script setup lang="ts">
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const errorDialog = ref<{ show: boolean, message: string, info?: string } | null>(null);
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PlusCircle, MinusCircle, XCircle, ShoppingCart, LoaderCircle, DollarSign, Percent, ReceiptText, Image as ImageIcon, ArrowLeft, Search, ScanBarcode, ChevronRight, CreditCard, Wallet, Banknote } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/utils/formatters'; // Make sure this utility exists and works

interface Product {
    id: string;
    tenant_id?: string;
    category_id: string | null;
    name: string;
    sku?: string | null;
    description?: string | null;
    price: number;
    stock: number;
    unit: string | null;
    image?: string | null;
    category?: { id: string; name: string };
    is_food_item?: boolean;
    ingredients?: string | null;
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
    buy_qty: number;
    get_qty: number;
    product_id?: string;
    another_product_id?: string;
    expiry_date: string;
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
        console.log(e);
        voucherError.value = 'Gagal validasi voucher.';
    }
};

const availablePromos = ref<Promo[]>(props.promos || []);
// Multiple promo support
const selectedPromoCodes = ref<string[]>([]);
const selectedPromos = computed(() => {
    return selectedPromoCodes.value
        .map(code => availablePromos.value.find(p => p.code === code))
        .filter((p): p is Promo => !!p);
});

// Promo otomatis dan notifikasi promo yang hampir aktif
// Multiple promo support: cari semua promo yang aktif dan sesuai isi keranjang
const activePromos = computed(() => {
    const result: Promo[] = [];
    for (const promo of availablePromos.value) {
        if (promo.type === 'buyxgetx' || promo.type === 'buyxgetanother') {
            // Hitung jumlah produk yang relevan di cart
            let buy_qty = 0;
            cartItems.value.forEach(item => {
                if (item.product_id === promo.product_id) {
                    buy_qty += item.quantity;
                }
            });
            if (buy_qty >= promo.buy_qty) {
                result.push(promo);
            }
        }
    }
    return result;
});

const showPossiblePromoAlert = ref(true);
const possiblePromos = computed(() => {
    // Promo yang hampir aktif (kurang produk)
    const result: { promo: Promo; missing: number; productName?: string }[] = [];
    for (const promo of availablePromos.value) {
        if (promo.type === 'buyxgetx' || promo.type === 'buyxgetanother') {
            // Cek apakah ada produk relevan di cart
            const item = cartItems.value.find(i => i.product_id === promo.product_id);
            const qty = item ? item.quantity : 0;
            if (qty < promo.buy_qty && qty > 0) {
                // Kurang berapa produk untuk dapat promo
                const prod = props.products.find(p => p.id === promo.product_id);
                result.push({ promo, missing: promo.buy_qty - qty, productName: prod?.name });
            }
        }
    }
    return result;
});

// Watcher: jika promo aktif, otomatis set selectedPromoCode
// Watcher: jika ada promo aktif, otomatis set selectedPromoCodes (multiple)
watch(activePromos, (promos) => {
    if (promos.length > 0) {
        selectedPromoCodes.value = promos.map(p => p.code);
    } else {
        selectedPromoCodes.value = [];
    }
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

// Add product to cart
const addToCart = (product: Product) => {
    // Cari item berbayar (price > 0) di cart
    const paidItem = cartItems.value.find(item => item.product_id === product.id && item.price > 0);

    if (paidItem) {
        if (paidItem.quantity < paidItem.stock) {
            paidItem.quantity++;
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

    // Reset semua free item di cart sebelum hitung promo
    cartItems.value = cartItems.value.filter(item => item.price !== 0 || item.price === 0);

    // Hitung semua promo dan akumulasi free item untuk setiap produk
    const freeItemMap = new Map<string, number>();
    for (const promo of availablePromos.value) {
        const isActive = new Date(promo.expiry_date) >= new Date();
        if (!isActive) continue;
        let buy_qty = 0;
        cartItems.value.forEach(item => {
            if (item.product_id === promo.product_id && item.price > 0) {
                buy_qty += item.quantity;
            }
        });
        const multiplier = Math.floor(buy_qty / promo.buy_qty);
        const freeProduct_id = promo.type === 'buyxgetx' ? promo.product_id : promo.another_product_id;
        if (multiplier >= 1 && freeProduct_id) {
            const expectedQty = promo.get_qty * multiplier;
            // Akumulasi free item
            freeItemMap.set(freeProduct_id, (freeItemMap.get(freeProduct_id) || 0) + expectedQty);
        }
    }
    // Update cart: set quantity free item sesuai akumulasi, hapus jika tidak ada promo
    for (const [freeProduct_id, qty] of freeItemMap.entries()) {
        const prod = props.products.find(p => p.id === freeProduct_id);
        if (!prod) continue;
        const freeItem = cartItems.value.find(item => item.product_id === freeProduct_id && item.price === 0);
        if (freeItem) {
            freeItem.quantity = qty;
        } else {
            cartItems.value.push({
                product_id: prod.id,
                quantity: qty,
                price: 0,
                name: prod.name,
                unit: prod.unit,
                stock: prod.stock,
            });
        }
    }
    // Hapus free item yang tidak dapat promo
    cartItems.value = cartItems.value.filter(item => {
        if (item.price !== 0) return true;
        return freeItemMap.has(item.product_id);
    });
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
const removeFromCart = (product_id: string) => {
    cartItems.value = cartItems.value.filter(item => item.product_id !== product_id);
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



// Load Snap.js script for Midtrans
onMounted(() => {
    if (props.midtransConfigured) {
        if (!document.getElementById('midtrans-snapjs')) {
            let clientKey = props.midtransClientKey || '';
            if (!clientKey) {
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
        const redirectToReceiptByOrderId = (orderId: string) => {
            if (!orderId) {
                alert('Order ID tidak ditemukan di response Midtrans.');
                return;
            }
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

// Edit mode state: get order id from Inertia page props if present
const page = usePage();
const orderEditId = ref<string | null>((page.props.orderId as string) || null);

// Modal error dialog state
const showMobileCart = ref(false);
const showOrderSettings = ref(false);

const quickCashAmounts = computed(() => {
    const total = totalAmount.value;
    if (total <= 0) return [];
    
    const amounts = new Set<number>();
    amounts.add(total); // Uang Pas
    
    // Rounded up to nearest 5k, 10k, 20k, 50k, 100k
    const roundings = [1000, 2000, 5000, 10000, 20000, 50000, 100000];
    roundings.forEach(r => {
        const rounded = Math.ceil(total / r) * r;
        if (rounded > total && rounded <= total + 200000) {
            amounts.add(rounded);
        }
    });

    return Array.from(amounts).sort((a, b) => a - b).slice(0, 6);
});

const setQuickCash = (amount: number) => {
    cashInputAmount.value = amount;
};

const setQuickDiscount = (percent: number) => {
    form.discount_amount = Math.round(overallSubtotal.value * (percent / 100));
};

const setQuickTax = (percent: number) => {
    form.tax_rate = percent;
};

// Pagination state & fetch
const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const paginatedProducts = ref<Product[]>([]);
const isLoadingProducts = ref(false);

// Sorting state
const sortField = ref<'name' | 'price'>('name');
const sortDirection = ref<'asc' | 'desc'>('asc');

// Declare selectedCategory before usage
const selectedCategory = ref<string | null>(null);
const searchTerm = ref('');

const fetchProducts = async () => {
    isLoadingProducts.value = true;
    try {
        const params = new URLSearchParams({
            page: currentPage.value.toString(),
            per_page: perPage.value.toString(),
            category_id: selectedCategory.value || '',
            search: searchTerm.value || '',
            sort_field: sortField.value,
            sort_direction: sortDirection.value,
        });
        const res = await fetch(route('sales.paginatedProducts', { tenantSlug: props.tenantSlug }) + '?' + params.toString(), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const data = await res.json();
    paginatedProducts.value = data.products;
    totalPages.value = data.total_pages || 1;
    } catch (e) {
        console.error('Gagal fetch produk:', e);
    } finally {
        isLoadingProducts.value = false;
    }
};

const debouncedFetch = useDebounceFn(() => {
    fetchProducts();
}, 300);

watch([selectedCategory, searchTerm], () => {
    currentPage.value = 1;
    debouncedFetch();
});

watch([currentPage, sortField, sortDirection], () => {
    fetchProducts();
});

onMounted(() => {
    fetchProducts();

    // If editing an order (orderEditId exists), fetch order data and populate form/cart
    if (orderEditId.value) {
        // Fetch order detail by ID
        fetch(route('sales.show', { tenantSlug: props.tenantSlug, sale: orderEditId.value }), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.sale) return;
                const sale = data.sale;
                // Populate cart items
                if (Array.isArray(sale.items)) {
                    cartItems.value = sale.items.map((item: any) => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        price: item.price,
                        name: item.product_name || '',
                        unit: item.unit || null,
                        stock: item.stock ?? 9999, // fallback if not provided
                    }));
                }
                // Discount
                form.discount_amount = sale.discount_amount || 0;
                // Tax
                form.tax_rate = sale.tax_rate || 0;
                // Payment method
                form.payment_method = sale.payment_method || 'cash';
                // Paid amount
                form.paid_amount = sale.paid_amount || 0;
                // Customer
                selectedCustomer.value = sale.customer_id || null;
                // Notes
                form.notes = sale.notes || '';
                // Vouchers
                if (Array.isArray(sale.voucher_codes)) {
                    selectedVoucherCodes.value = sale.voucher_codes;
                } else if (sale.voucher_code) {
                    selectedVoucherCodes.value = [sale.voucher_code];
                }
                // Promos
                if (Array.isArray(sale.promo_codes)) {
                    selectedPromoCodes.value = sale.promo_codes;
                } else if (sale.promo_code) {
                    selectedPromoCodes.value = [sale.promo_code];
                }
            })
            .catch(e => {
                console.error('Gagal mengambil data order:', e);
            });
    }
});

// State for processing save order pending
const isProcessingSaveOrder = ref(false);
// Save order as pending (cash flow)
const saveOrderPending = async () => {
    if (cartItems.value.length === 0) {
        alert('Keranjang belanja kosong. Tambahkan produk terlebih dahulu.');
        return;
    }
    isProcessingSaveOrder.value = true;
    // Hanya kirim item berbayar ke backend; free item akan dihitung ulang oleh backend berdasarkan promo
    const paidItems = cartItems.value.filter(item => item.price > 0);
    const itemsMap = new Map<string, number>();
    paidItems.forEach(item => {
        itemsMap.set(item.product_id, (itemsMap.get(item.product_id) || 0) + item.quantity);
    });
    const formData: Record<string, any> = {
        items: Array.from(itemsMap.entries()).map(([product_id, quantity]) => ({ product_id, quantity })),
        customer_id: selectedCustomer.value,
        discount_amount: form.discount_amount,
        tax_rate: form.tax_rate,
        payment_method: form.payment_method,
        notes: form.notes,
        voucher_codes: selectedVoucherCodes.value,
        status: 'pending',
        promo_codes: selectedPromoCodes.value
    };
    // Remove paid_amount if present (not needed for pending)
    delete formData.paid_amount;
    try {
        let response;
        if (orderEditId.value) {
            // Update existing order
            response = await axios.put(route('sales.update', { tenantSlug: props.tenantSlug, sale: orderEditId.value }), formData, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        } else {
            // Create new order
            response = await axios.post(route('sales.store', { tenantSlug: props.tenantSlug }), formData, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            console.log('Create response:', response.data);
            // Redirect to edit mode (with id in URL) using Inertia
            if (response.data && response.data.saleId) {
                orderEditId.value = response.data.saleId;
                window.location.href = route('sales.order', { tenantSlug: props.tenantSlug, orderId: response.data.saleId });
            }
        }
        alert(orderEditId.value ? 'Order berhasil diperbarui!' : 'Order berhasil disimpan sebagai pending!');
    } catch (error: any) {
        let errorMessage = 'Terjadi kesalahan saat menyimpan order.';
        if (error.response && error.response.data && error.response.data.errors) {
            const errors = error.response.data.errors;
            if (errors.items) errorMessage += '\n' + errors.items;
        }
        alert(errorMessage);
    } finally {
        isProcessingSaveOrder.value = false;
        delete (form as any).status;
        delete (form as any).promo_codes;
    }
};


// State for cash payment modal
const showCashModal = ref(false);
const cashInputAmount = ref(0);
const cashInputError = ref('');

const payOrder = async () => {
    if (cartItems.value.length === 0) {
        alert('Keranjang belanja kosong. Tambahkan produk terlebih dahulu.');
        return;
    }

    // Selalu simpan/update order dulu sebelum bayar (non-cash)
    let currentOrderId = orderEditId.value;
    let saveError = '';
    const saveData: Record<string, any> = {
        // Hanya kirim item berbayar; free item akan dihitung ulang di backend
        items: (() => {
            const paidItems = cartItems.value.filter(item => item.price > 0);
            const itemsMap = new Map<string, number>();
            paidItems.forEach(item => {
                itemsMap.set(item.product_id, (itemsMap.get(item.product_id) || 0) + item.quantity);
            });
            return Array.from(itemsMap.entries()).map(([product_id, quantity]) => ({ product_id, quantity }));
        })(),
        customer_id: selectedCustomer.value,
        discount_amount: form.discount_amount,
        tax_rate: form.tax_rate,
        payment_method: form.payment_method,
        notes: form.notes,
        voucher_codes: selectedVoucherCodes.value,
        status: 'paid',
        promo_codes: selectedPromoCodes.value
    };
    // paid_amount hanya untuk proses pembayaran
    saveData.paid_amount = form.payment_method === 'cash' ? undefined : totalAmount.value;

    try {
        let response;
        if (currentOrderId) {
            // Update existing order
            response = await axios.put(route('sales.update', { tenantSlug: props.tenantSlug, sale: currentOrderId }), saveData, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        } else {
            // Create new order
            response = await axios.post(route('sales.store', { tenantSlug: props.tenantSlug }), saveData, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.data && response.data.saleId) {
                currentOrderId = response.data.saleId;
                orderEditId.value = response.data.saleId;
                // Update URL jika perlu
                window.history.replaceState({}, '', route('sales.order', { tenantSlug: props.tenantSlug, orderId: response.data.saleId }));
            }
        }
    } catch (error: any) {
        saveError = 'Gagal menyimpan order sebelum pembayaran.';
        if (error.response && error.response.data && error.response.data.errors) {
            const errors = error.response.data.errors;
            if (errors.items) saveError += '\n' + errors.items;
        }
        alert(saveError);
        return;
    }

    // Setelah order pasti tersimpan, lanjut proses pembayaran
    if (form.payment_method === 'cash') {
        cashInputAmount.value = totalAmount.value;
        cashInputError.value = '';
        showCashModal.value = true;
        return;
    }

    // Non-cash: proses pembayaran sesuai metode
    form.items = cartItems.value.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
    }));
    form.customer_id = selectedCustomer.value;
    (form as any).voucher_codes = selectedVoucherCodes.value;
    (form as any).status = 'paid';
    (form as any).promo_codes = selectedPromoCodes.value;
    const id = currentOrderId;
    if (form.payment_method === 'ipaymu') {
        form.paid_amount = totalAmount.value;
        // Kirim request pembayaran iPaymu pakai axios
        try {
            const response = await axios.post(route('sales.store', { tenantSlug: props.tenantSlug, id }), {
                ...form,
            }, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.data && response.data.payment_url) {
                window.location.href = response.data.payment_url;
            } else {
                errorDialog.value = {
                    show: true,
                    message: 'Gagal mendapatkan URL pembayaran iPaymu.',
                    info: ''
                };
            }
        } catch (error: any) {
            let errorMessage = 'Terjadi kesalahan saat membayar order (iPaymu).';
            if (error.response && error.response.data && error.response.data.errors) {
                const errors = error.response.data.errors;
                if (errors.items) errorMessage += '\n' + errors.items;
                if (errors.paid_amount) errorMessage += '\n' + errors.paid_amount;
            }
            alert(errorMessage);
        } finally {
            delete (form as any).status;
            delete (form as any).promo_codes;
        }
        return;
    }
    if (form.payment_method === 'midtrans') {
        form.post(route('sales.store', { tenantSlug: props.tenantSlug, id }), {
            onSuccess: (page: any) => {
                if (page.props && page.props.snapToken) {
                    handleMidtransPay(page.props.snapToken);
                } else {
                    errorDialog.value = {
                        show: true,
                        message: 'Gagal mendapatkan Snap Token Midtrans.',
                        info: ''
                    };
                }
            },
            onError: (errors: any) => {
                let errorMessage = 'Terjadi kesalahan saat membayar order (Midtrans).';
                if (errors.items) errorMessage += '\n' + errors.items;
                if (errors.paid_amount) errorMessage += '\n' + errors.paid_amount;
                alert(errorMessage);
            },
            onFinish: () => {
                delete (form as any).status;
                delete (form as any).promo_codes;
            }
        });
        return;
    }
};

// Proses cash setelah input modal
const confirmCashPayment = async () => {
    cashInputError.value = '';
    // Cek apakah orderId sudah ada di URL (orderEditId)
    if (!orderEditId.value) {
        cashInputError.value = 'Silakan simpan order terlebih dahulu sebelum melakukan pembayaran.';
        return;
    }
    if (cashInputAmount.value < totalAmount.value) {
        cashInputError.value = 'Jumlah yang dibayar kurang dari total.';
        return;
    }
    // Hanya kirim item berbayar ke backend
    const paidItems = cartItems.value.filter(item => item.price > 0);
    const itemsMap = new Map<string, number>();
    paidItems.forEach(item => {
        itemsMap.set(item.product_id, (itemsMap.get(item.product_id) || 0) + item.quantity);
    });
    form.items = Array.from(itemsMap.entries()).map(([product_id, quantity]) => ({
        product_id,
        quantity,
    }));
    form.customer_id = selectedCustomer.value;
    (form as any).voucher_codes = selectedVoucherCodes.value;
    (form as any).status = 'paid';
    (form as any).promo_codes = selectedPromoCodes.value;
    form.paid_amount = cashInputAmount.value;

    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('orderId');
    // Pass the orderId as a parameter in the route
    form.post(route('sales.store', { tenantSlug: props.tenantSlug, id }), {
        onSuccess: (page: any) => {
            cartItems.value = [];
            form.reset();
            selectedCustomer.value = null;
            selectedVoucherCodes.value = [];
            voucherInputCode.value = '';
            showCashModal.value = false;
            // Get orderId from URL query param
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = urlParams.get('orderId');
            if (orderId) {
                window.location.href = route('sales.receipt', { tenantSlug: props.tenantSlug, sale: orderId });
            } else {
                // fallback: try from page.props or show error
                if (page.props && page.props.saleId) {
                    window.location.href = route('sales.receipt', { tenantSlug: props.tenantSlug, sale: page.props.saleId });
                } else {
                    alert('Gagal mendapatkan ID order dari URL.');
                }
            }
        },
        onError: (errors: any) => {
            let errorMessage = 'Terjadi kesalahan saat membayar order.';
            if (errors.items) errorMessage += '\n' + errors.items;
            if (errors.paid_amount) errorMessage += '\n' + errors.paid_amount;
            cashInputError.value = errorMessage;
        },
        onFinish: () => {
            delete (form as any).status;
            delete (form as any).promo_codes;
        }
    });
};

// Set initial paid_amount to total_amount when component mounts or totalAmount changes
onMounted(() => {
    form.paid_amount = totalAmount.value;

    // Optionally, fetch order data and populate cart/form here if orderEditId exists

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


const appDomain = import.meta.env.VITE_API_DOMAIN || 'http://localhost:8000';

</script>

<template>
    <Head title="Pemesanan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Alert bar fullwidth untuk possible promo -->
        <transition name="fade">
            <div v-if="possiblePromos.length > 0 && showPossiblePromoAlert" class="mb-4 mx-4 px-4 py-3 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-lg border border-orange-300 dark:border-orange-700 flex flex-col gap-2 relative">
                <button @click="showPossiblePromoAlert = false" class="absolute top-2 right-3 text-orange-700 dark:text-orange-200 hover:text-red-500 text-xl font-bold">&times;</button>
                <div class="flex items-center gap-2 mb-1">
                    <Percent class="h-5 w-5 text-orange-600" />
                    <span class="font-bold text-base">Ada promo yang hampir aktif!</span>
                </div>
                <div v-for="item in possiblePromos" :key="item.promo.code" class="flex flex-wrap items-center gap-1 text-sm">
                    <span class="font-semibold">{{ item.promo.name }}</span>
                    <span class="text-xs bg-orange-200 dark:bg-orange-700 px-2 py-0.5 rounded-full">{{ item.promo.type }}</span>
                    <span class="text-xs text-gray-700 dark:text-gray-300">Kurang <span class="font-bold">{{ item.missing }}</span> produk <span class="font-bold">{{ item.productName }}</span></span>
                    <span class="text-xs text-gray-500 ml-auto">Exp: {{ item.promo.expiry_date }}</span>
                </div>
            </div>
        </transition>
        
        <!-- Error Modal Dialog -->
        <transition name="fade">
            <div v-if="errorDialog && errorDialog.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4 text-center relative animate-fadeIn">
                    <button @click="errorDialog.show = false" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
                    <div class="flex flex-col items-center mb-4">
                        <svg class="w-12 h-12 text-red-500 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0z"/></svg>
                        <h3 class="text-xl font-bold text-red-600">Terjadi Kesalahan</h3>
                    </div>
                    <p class="mb-4 text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ errorDialog.message }}</p>
                    <div v-if="errorDialog.info" class="mb-4">
                        <div class="flex items-start justify-center gap-2 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-200 rounded px-3 py-2 border border-blue-200 dark:border-blue-800 text-sm">
                            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/></svg>
                            <div class="text-left">
                                <div class="font-semibold text-blue-700 dark:text-blue-200 mb-1">Informasi Tambahan</div>
                                <div class="leading-relaxed">{{ errorDialog.info }}</div>
                            </div>
                        </div>
                    </div>
                    <button @click="errorDialog.show = false" class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold shadow text-sm">Tutup</button>
                </div>
            </div>
        </transition>
        
        <div class="flex flex-col gap-4 p-4 lg:flex-row relative">
            <!-- Mobile Cart Toggle Button -->
            <div v-if="!showMobileCart && cartItems.length > 0" class="lg:hidden fixed bottom-6 right-6 z-40 transition-transform hover:scale-110 active:scale-95">
                <Button @click="showMobileCart = true" size="lg" class="rounded-full h-16 w-16 shadow-2xl bg-blue-600 hover:bg-blue-700 p-0 relative border-4 border-white dark:border-gray-800">
                    <ShoppingCart class="h-8 w-8 text-white" />
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-extrabold rounded-full h-6 w-6 flex items-center justify-center border-2 border-white shadow-sm">{{ cartItems.reduce((s, i) => s + i.quantity, 0) }}</span>
                </Button>
            </div>

            <!-- Product List Section (Left/Top) -->
            <div :class="['flex-1 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 md:p-6 overflow-hidden flex flex-col min-h-[500px]', showMobileCart ? 'hidden lg:flex' : 'flex']">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">Catalog</h2>
                    <div class="text-xs text-gray-500 font-medium bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full lg:hidden">
                        {{ paginatedProducts.length }} Products Found
                    </div>
                </div>

                <div class="mb-6 space-y-4">
                    <!-- Search Input with Icon -->
                    <div class="relative group">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" />
                        <Input
                            id="searchTerm"
                            type="text"
                            v-model="searchTerm"
                            class="pl-10 pr-12 h-12 text-base rounded-xl border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm"
                        />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                            <button v-if="searchTerm" @click="searchTerm = ''" class="text-gray-400 hover:text-gray-600">
                                <XCircle class="h-5 w-5" />
                            </button>
                            <button class="text-blue-600 hover:text-blue-700 bg-blue-50 dark:bg-blue-900/40 p-1.5 rounded-lg">
                                <ScanBarcode class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Category Chips for Mobile / Horizontal Scroll -->
                    <div class="flex overflow-x-auto gap-2 pb-2 scrollbar-hide no-scrollbar -mx-1 px-1">
                        <button 
                            @click="selectedCategory = null"
                            :class="['px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all border shrink-0', selectedCategory === null ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-200 dark:shadow-none' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-blue-300']"
                        >
                            Semua
                        </button>
                        <button 
                            v-for="category in categories" 
                            :key="category.id"
                            @click="selectedCategory = category.id"
                            :class="['px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all border shrink-0', selectedCategory === category.id ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-200 dark:shadow-none' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-blue-300']"
                        >
                            {{ category.name }}
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <div class="flex flex-col">
                            <Label class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1 ml-1">Sort By</Label>
                            <Select v-model="sortField">
                                <SelectTrigger class="h-10 rounded-lg border-gray-200 dark:border-gray-700 shadow-sm">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="name">Name</SelectItem>
                                    <SelectItem value="price">Price</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col">
                            <Label class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1 ml-1">Order</Label>
                            <Select v-model="sortDirection">
                                <SelectTrigger class="h-10 rounded-lg border-gray-200 dark:border-gray-700 shadow-sm">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="asc">Ascending</SelectItem>
                                    <SelectItem value="desc">Descending</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 overflow-y-auto flex-1 pb-2">
                    <div v-if="isLoadingProducts" class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        Memuat produk...
                    </div>
                    <div
                        v-for="product in paginatedProducts"
                        :key="product.id"
                        @click="addToCart(product)"
                        :class="[
                            'relative bg-gray-50 dark:bg-gray-700 p-3 rounded-lg shadow-sm cursor-pointer hover:shadow-md transition-all duration-200 border flex flex-col',
                            product.stock === 0 ? 'opacity-50 cursor-not-allowed border-red-300' : 'border-gray-200 dark:border-gray-600'
                        ]"
                    >
                        <div class="w-full h-28 xs:h-24 sm:h-28 md:h-24 lg:h-28 xl:h-24 bg-gray-100 dark:bg-gray-600 rounded-md mb-2 overflow-hidden flex items-center justify-center">
                            <img
                                v-if="product.image"
                                :src="appDomain + '/file/' + product.image"
                                alt="Product Image"
                                class="w-full h-full object-cover rounded-md"
                            />
                            <div v-else class="text-gray-400 dark:text-gray-500 flex items-center justify-center">
                                <ImageIcon class="w-8 h-8" />
                            </div>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm leading-tight mb-1 line-clamp-2">{{ product.name }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">{{ product.category?.name || 'Uncategorized' }}</p>
                        <p v-if="product.sku" class="text-xs text-gray-500 dark:text-gray-400 mb-1">SKU: <span class="font-mono">{{ product.sku }}</span></p>
                        <p v-if="product.description" class="text-xs text-gray-700 dark:text-gray-300 mb-1 line-clamp-2">{{ product.description.length > 60 ? product.description.slice(0, 60) + '...' : product.description }}</p>
                        <p v-if="product.unit" class="text-xs text-gray-500 dark:text-gray-400 mb-1">Satuan: {{ product.unit }}</p>
                        <p class="text-base font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(product.price) }}</p>
                        <p :class="['text-xs font-medium mt-auto pt-1', product.stock <= 5 && product.stock > 0 ? 'text-orange-500' : product.stock === 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400']">
                            Stok: {{ product.stock }} {{ product.unit || 'pcs' }}
                        </p>
                        <p v-if="product.is_food_item && product.ingredients" class="text-xs text-green-700 dark:text-green-300 mb-1 line-clamp-2">Bahan: {{ product.ingredients.length > 60 ? product.ingredients.slice(0, 60) + '...' : product.ingredients }}</p>
                        <div v-if="product.stock === 0" class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center rounded-lg">
                            <span class="text-white font-bold text-sm">SOLD OUT</span>
                        </div>
                    </div>
                </div>
                <!-- Pagination Navigation -->
                <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-4">
                    <button
                        class="px-3 py-1 rounded border bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 disabled:opacity-50"
                        :disabled="currentPage === 1 || isLoadingProducts"
                        @click="currentPage--"
                    >
                        Prev
                    </button>
                    <span class="font-semibold text-sm">Halaman {{ currentPage }} / {{ totalPages }}</span>
                    <button
                        class="px-3 py-1 rounded border bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 disabled:opacity-50"
                        :disabled="currentPage === totalPages || isLoadingProducts"
                        @click="currentPage++"
                    >
                        Next
                    </button>
                </div>
            </div>

            <!-- Cart and Payment Section (Right/Bottom) -->
            <div :class="['w-full lg:w-96 xl:w-[420px] bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-4 md:p-6 flex flex-col min-h-[500px]', showMobileCart ? 'flex fixed inset-0 z-50 lg:relative lg:inset-auto' : 'hidden lg:flex']">
                <!-- Mobile Header for Cart -->
                <div class="lg:hidden flex items-center justify-between mb-6 pb-4 border-b">
                    <Button variant="ghost" @click="showMobileCart = false" class="p-0 h-10 w-10">
                        <ArrowLeft class="h-6 w-6" />
                    </Button>
                    <h2 class="text-xl font-bold">Review Order</h2>
                    <div class="w-10"></div>
                </div>

                <h2 class="hidden lg:flex text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4 items-center gap-2">
                    <ShoppingCart class="h-5 w-5 md:h-6 md:w-6 text-blue-600" /> Keranjang Belanja
                </h2>

                <div class="flex-1 overflow-y-auto min-h-0 pr-1 mb-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                    <div v-if="cartItems.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-6">
                        Keranjang kosong. Tambahkan produk!
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="item in cartItems" :key="item.product_id" class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100 text-sm truncate">{{ item.name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ formatCurrency(item.price) }} x {{ item.quantity }} {{ item.unit || 'pcs' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 ml-2 bg-white dark:bg-gray-800 rounded-lg p-1 shadow-sm border border-gray-100 dark:border-gray-600">
                                <Button variant="ghost" size="icon" @click="updateCartQuantity(item, -1)" class="h-9 w-9 text-gray-500">
                                    <MinusCircle class="h-5 w-5" />
                                </Button>
                                <span class="font-bold text-sm w-8 text-center">{{ item.quantity }}</span>
                                <Button variant="ghost" size="icon" @click="updateCartQuantity(item, 1)" class="h-9 w-9 text-blue-600">
                                    <PlusCircle class="h-5 w-5" />
                                </Button>
                                <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>
                                <Button variant="ghost" size="icon" @click="removeFromCart(item.product_id)" class="h-9 w-9 text-red-500">
                                    <XCircle class="h-5 w-5" />
                                </Button>
                    </div>
                </div>
            </div>
        </div>

                <!-- Summary Section -->
                <div class="space-y-3 mt-auto bg-gray-50 dark:bg-gray-900/50 -mx-4 -mb-4 p-4 rounded-t-3xl border-t shadow-[0_-10px_20px_rgba(0,0,0,0.05)] lg:bg-transparent lg:p-0 lg:rounded-none lg:border-t-0 lg:shadow-none lg:mx-0 lg:mb-0">
                    <!-- Subtotal & Total (Always visible) -->
                    <div class="flex flex-col gap-1">
                        <div class="flex justify-between items-center text-gray-500 dark:text-gray-400 text-xs">
                            <span>Subtotal:</span>
                            <span class="font-medium">{{ formatCurrency(overallSubtotal) }}</span>
                        </div>
                        <div v-if="voucherDiscount > 0" class="flex justify-between items-center text-green-600 dark:text-green-400 text-xs">
                            <span>Voucher:</span>
                            <span class="font-medium">-{{ formatCurrency(voucherDiscount) }}</span>
                        </div>
                        <div v-if="form.discount_amount > 0" class="flex justify-between items-center text-orange-600 dark:text-orange-400 text-xs">
                            <span>Diskon:</span>
                            <span class="font-medium">-{{ formatCurrency(form.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between font-black text-xl text-gray-900 dark:text-gray-100 pt-1">
                            <span>TOTAL:</span>
                            <span class="text-blue-600">{{ formatCurrency(totalAmount) }}</span>
                        </div>
                    </div>

                    <!-- Collapsible Settings Trigger (Mobile Only) -->
                    <Button 
                        variant="outline" 
                        size="sm" 
                        @click="showOrderSettings = !showOrderSettings" 
                        class="w-full lg:hidden flex items-center justify-between h-10 rounded-xl border-dashed border-gray-300"
                    >
                        <span class="flex items-center gap-2">
                            <ReceiptText class="h-4 w-4" />
                            Opsi Tambahan (Voucher, Diskon, dll)
                        </span>
                        <ChevronRight :class="['h-4 w-4 transition-transform', showOrderSettings ? 'rotate-90' : '']" />
                    </Button>

                    <!-- Settings Content (Collapsible on mobile) -->
                    <div :class="['space-y-4 overflow-y-auto transition-all duration-300', showOrderSettings ? 'max-h-[300px] py-2' : 'max-h-0 overflow-hidden lg:max-h-none lg:py-0']">
                        <!-- Voucher & Promo Section -->
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <Label for="voucher" class="text-gray-700 dark:text-gray-300 flex items-center gap-1 text-sm font-medium mb-1">
                            <ReceiptText class="h-4 w-4" /> Voucher:
                        </Label>
                        <div class="flex gap-2">
                            <Input
                                id="voucher_code"
                                type="text"
                                v-model="voucherInputCode"
                                placeholder="Masukkan kode voucher"
                                class="flex-1 text-sm"
                            />
                            <Button @click="handleVoucherInput" type="button" size="sm" class="whitespace-nowrap">Cek</Button>
                        </div>
                        <div v-if="voucherError" class="text-red-500 text-xs mt-1">{{ voucherError }}</div>
                        <div v-if="selectedVouchers.length > 0" class="mt-2 space-y-2">
                            <div v-for="voucher in selectedVouchers" :key="voucher?.code" class="p-2 rounded bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-200 flex items-center gap-2 text-sm">
                                <ReceiptText class="h-3.5 w-3.5 flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold truncate">{{ voucher.name }}</div>
                                    <div class="text-xs opacity-80 flex items-center gap-1 mt-0.5">
                                        <span>{{ voucher.type }}</span>
                                        <span class="text-xs">• Exp: {{ voucher.expiry_date }}</span>
                                    </div>
                                </div>
                                <Button size="sm" variant="ghost" class="text-red-500 h-7 w-7 p-0" @click="selectedVoucherCodes = selectedVoucherCodes.filter(c => c !== voucher.code)">
                                    <XCircle class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                        <div v-if="voucherDiscount > 0" class="text-green-600 dark:text-green-400 text-xs mt-2 font-medium">
                            Diskon voucher: -{{ formatCurrency(voucherDiscount) }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <Label for="promo" class="text-gray-700 dark:text-gray-300 flex items-center gap-1 text-sm font-medium mb-1">
                            <Percent class="h-4 w-4" /> Promo:
                        </Label>
                        <Select v-model="selectedPromoCodes" multiple>
                            <SelectTrigger class="w-full text-sm">
                                <SelectValue placeholder="Pilih Promo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="promo in availablePromos" :key="promo.code" :value="promo.code" class="text-sm">
                                    <div class="flex flex-col py-1">
                                        <span class="font-semibold">{{ promo.name }}</span>
                                        <span class="text-xs text-gray-500">{{ promo.type }}</span>
                                        <span class="text-xs text-gray-400">Exp: {{ promo.expiry_date }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="selectedPromos.length > 0" class="mt-2 space-y-2">
                            <div v-for="promo in selectedPromos" :key="promo.code" class="p-2 rounded bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-200 text-sm">
                                <div class="flex items-center gap-2">
                                    <Percent class="h-3.5 w-3.5 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold truncate">{{ promo.name }}</div>
                                        <div class="text-xs opacity-80 flex items-center gap-1 mt-0.5">
                                            <span>{{ promo.type }}</span>
                                            <span>• Exp: {{ promo.expiry_date }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discount -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <Label for="discount" class="text-gray-700 dark:text-gray-300 flex items-center gap-1 text-sm font-medium">
                                <DollarSign class="h-4 w-4" /> Diskon (Nominal)
                            </Label>
                            <Input
                                id="discount"
                                type="number"
                                v-model.number="form.discount_amount"
                                class="w-32 text-right font-bold text-blue-600 border-gray-200 dark:border-gray-700"
                                min="0"
                            />
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <button v-for="pct in [5, 10, 15, 20]" :key="pct" @click="setQuickDiscount(pct)" class="text-[10px] bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded hover:bg-blue-100 dark:hover:bg-blue-900 transition-colors">
                                {{ pct }}%
                            </button>
                            <button @click="form.discount_amount = 0" class="text-[10px] bg-red-50 text-red-600 px-2 py-1 rounded hover:bg-red-100 transition-colors">
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Tax -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <Label for="tax_rate" class="text-gray-700 dark:text-gray-300 flex items-center gap-1 text-sm font-medium">
                                <Percent class="h-4 w-4" /> Pajak (%)
                            </Label>
                            <Input
                                id="tax_rate"
                                type="number"
                                v-model.number="form.tax_rate"
                                class="w-32 text-right font-bold text-blue-600 border-gray-200 dark:border-gray-700"
                                min="0"
                            />
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <button v-for="pct in [0, 10, 11]" :key="pct" @click="setQuickTax(pct)" :class="['text-[10px] px-2 py-1 rounded transition-colors', form.tax_rate === pct ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700']">
                                {{ pct }}%
                            </button>
                        </div>
                    </div>

                    <!-- Total & Payment (Always visible part for Desktop, Hidden part for Mobile toggle) -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <Label for="payment_method" class="text-gray-700 dark:text-gray-300 text-sm font-bold">Metode:</Label>
                            <Select v-model="form.payment_method" class="w-40">
                            <SelectTrigger class="w-full text-sm">
                                <SelectValue placeholder="Pilih Metode" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="cash" class="text-sm">Tunai</SelectItem>
                                <SelectItem value="ipaymu" :disabled="!ipaymuConfigured" class="text-sm">
                                    iPaymu
                                    <span v-if="!ipaymuConfigured" class="text-xs text-red-500 ml-1">(×)</span>
                                </SelectItem>
                                <SelectItem value="midtrans" :disabled="!midtransConfigured" class="text-sm">
                                    Midtrans
                                    <span v-if="!midtransConfigured" class="text-xs text-red-500 ml-1">(×)</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Modal for cash payment -->
                    <transition name="fade">
                        <div v-if="showCashModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-md">
                            <div class="bg-white dark:bg-gray-900 rounded-t-3xl sm:rounded-3xl shadow-2xl p-6 w-full max-w-md mx-auto relative animate-slideUp sm:animate-fadeIn">
                                <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-700 rounded-full mx-auto mb-6 sm:hidden"></div>
                                <button @click="showCashModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors hidden sm:block">
                                    <XCircle class="h-8 w-8" />
                                </button>
                                
                                <div class="text-center mb-6">
                                    <div class="bg-blue-100 dark:bg-blue-900/30 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <Wallet class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <h3 class="text-2xl font-black text-gray-900 dark:text-gray-100">Pembayaran Tunai</h3>
                                    <p class="text-gray-500 text-sm mt-1">Selesaikan transaksi dengan uang tunai</p>
                                </div>

                                <div class="space-y-6">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                                        <div class="flex justify-between items-center mb-4">
                                            <span class="text-gray-500 font-medium">Total Tagihan</span>
                                            <span class="text-xl font-black text-gray-900 dark:text-white">{{ formatCurrency(totalAmount) }}</span>
                                        </div>
                                        
                                        <Label for="cashInputAmount" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 ml-1">Uang yang Diterima</Label>
                                        <div class="relative">
                                            <Banknote class="absolute left-4 top-1/2 -translate-y-1/2 h-6 w-6 text-green-500" />
                                            <Input
                                                id="cashInputAmount"
                                                type="number"
                                                v-model.number="cashInputAmount"
                                                class="w-full pl-14 h-16 text-2xl font-black text-right border-2 border-blue-100 dark:border-gray-700 rounded-2xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                                                placeholder="0"
                                            />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2">
                                        <Button 
                                            v-for="amt in quickCashAmounts" 
                                            :key="amt" 
                                            @click="setQuickCash(amt)"
                                            variant="outline"
                                            :class="['h-14 rounded-xl text-sm font-bold transition-all border-2', cashInputAmount === amt ? 'bg-blue-600 text-white border-blue-600 shadow-lg' : 'border-gray-100 dark:border-gray-800 hover:border-blue-200']"
                                        >
                                            {{ amt === totalAmount ? 'PAS' : (amt >= 1000 ? (amt/1000) + 'k' : amt) }}
                                        </Button>
                                    </div>

                                    <div v-if="cashInputAmount > totalAmount" class="flex items-center justify-between p-5 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-100 dark:border-green-900/30">
                                        <div class="flex flex-col">
                                            <span class="text-green-600 dark:text-green-400 text-sm font-bold">Kembalian</span>
                                            <span class="text-2xl font-black text-green-700 dark:text-green-300">{{ formatCurrency(cashInputAmount - totalAmount) }}</span>
                                        </div>
                                        <div class="bg-green-200 dark:bg-green-800 h-10 w-10 rounded-full flex items-center justify-center animate-bounce">
                                            <DollarSign class="h-6 w-6 text-green-700 dark:text-green-100" />
                                        </div>
                                    </div>

                                    <div v-if="cashInputError" class="p-3 bg-red-50 text-red-600 text-sm rounded-xl border border-red-100 font-medium">
                                        {{ cashInputError }}
                                    </div>

                                    <div class="flex gap-3">
                                        <Button @click="showCashModal = false" variant="ghost" class="h-14 flex-1 rounded-2xl font-bold text-gray-500 sm:hidden">Batal</Button>
                                        <Button @click="confirmCashPayment" class="h-14 flex-[2] rounded-2xl font-black text-lg bg-blue-600 hover:bg-blue-700 shadow-xl shadow-blue-200 dark:shadow-none">Selesaikan Pembayaran</Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Customer (Optional) -->
                    <div class="flex justify-between items-center">
                        <Label for="customer" class="text-gray-700 dark:text-gray-300 text-sm">Pelanggan (Opsional)</Label>
                        <Select v-model="selectedCustomer" class="w-44">
                            <SelectTrigger class="w-full text-sm">
                                <SelectValue placeholder="Pilih Pelanggan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null" class="text-sm">Umum</SelectItem>
                                <SelectItem v-for="customer in customers" :key="customer.id" :value="customer.id" class="text-sm">
                                    {{ customer.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                        <div>
                            <Label for="notes" class="text-gray-700 dark:text-gray-300 text-sm font-bold">Catatan:</Label>
                            <Textarea id="notes" v-model="form.notes" rows="2" class="mt-1 text-sm rounded-xl" placeholder="Ketik catatan di sini..." />
                        </div>
                    </div>
                </div> <!-- End of Collapsible Settings Content -->

                    <!-- Action Buttons (Always visible) -->
                    <div class="flex gap-3 pt-2">
                        <Button 
                            @click="saveOrderPending" 
                            :disabled="isProcessingSaveOrder || cartItems.length === 0"
                            class="w-1/2 py-2.5 text-base font-semibold"
                            :class="{'bg-gray-400 cursor-not-allowed': isProcessingSaveOrder || cartItems.length === 0}"
                        >
                            <LoaderCircle v-if="isProcessingSaveOrder" class="h-4 w-4 animate-spin mr-2" />
                            Simpan Order
                        </Button>
                        <Button 
                            @click="payOrder" 
                            :disabled="form.processing || cartItems.length === 0 || (form.payment_method === 'cash' && form.paid_amount < totalAmount)"
                            class="w-1/2 py-2.5 text-base font-semibold"
                            :class="{'bg-gray-400 cursor-not-allowed': form.processing || cartItems.length === 0}"
                        >
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Bayar
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #c5c5c5;
    border-radius: 10px;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.animate-slideUp {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.dark ::-webkit-scrollbar-track {
    background: #374151;
}

.dark ::-webkit-scrollbar-thumb {
    background: #6b7280;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>