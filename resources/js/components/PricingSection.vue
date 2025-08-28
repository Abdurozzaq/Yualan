<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Check, Crown, Star } from 'lucide-vue-next';
import { formatCurrency } from '@/utils/formatters';

type PeriodType = 'monthly' | 'quarterly' | 'yearly' | string;

interface PricingPlan {
  id: string;
  plan_name: string;
  plan_description?: string | null;
  period_type: PeriodType;
  price: number | string;
  discount_percentage?: number | string;
}

const props = defineProps<{
  plans: PricingPlan[];
  trialDays?: number | string;
  isAuthenticated?: boolean;
}>();

const normalizedPlans = computed(() => {
  const order: Record<string, number> = { monthly: 1, quarterly: 2, yearly: 3 };
  return [...(props.plans || [])]
    .map((p) => ({
      ...p,
      price: Number(p.price),
      discount_percentage: Number(p.discount_percentage || 0),
    }))
    .sort((a, b) => (order[a.period_type] || 99) - (order[b.period_type] || 99) || a.price - b.price);
});

const bestDealId = computed(() => {
  let maxDiscount = -1;
  let id: string | null = null;
  for (const p of normalizedPlans.value) {
    const d = Number(p.discount_percentage || 0);
    if (d > maxDiscount) {
      maxDiscount = d;
      id = p.id;
    }
  }
  return id;
});

function periodLabel(period: PeriodType) {
  switch (period) {
    case 'monthly':
      return 'bulan';
    case 'quarterly':
      return 'triwulan';
    case 'yearly':
      return 'tahun';
    default:
      return String(period);
  }
}

function discountedPrice(plan: { price: number; discount_percentage?: number }) {
  const d = Number(plan.discount_percentage || 0);
  if (d <= 0) return plan.price;
  return plan.price - (plan.price * d) / 100;
}
</script>

<template>
  <section class="py-16 sm:py-24 bg-gradient-to-b from-white to-gray-50 dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200 text-xs font-semibold">
          <Crown class="h-4 w-4" /> Paket Harga
        </span>
        <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-gray-100">Pilih Paket Sesuai Kebutuhan</h2>
        <p class="mt-2 text-gray-600 dark:text-gray-300">Harga transparan, fitur lengkap, dan bisa ditingkatkan kapan saja.</p>
        <div v-if="Number(trialDays) > 0" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200 text-sm font-semibold ring-1 ring-emerald-300/60 dark:ring-emerald-700/50 shadow-sm">
          <Star class="h-4 w-4" /> Coba gratis {{ Number(trialDays) }} hari — tanpa kartu kredit
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        <div
          v-for="plan in normalizedPlans"
          :key="plan.id"
          class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-md ring-1 ring-gray-200/70 dark:ring-gray-700/60 overflow-hidden group hover:shadow-xl transition-shadow"
        >
          <div v-if="plan.id === bestDealId" class="absolute right-4 top-4 z-10 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200 text-xs font-semibold">
            <Star class="h-3.5 w-3.5" /> Terpopuler
          </div>

          <div class="p-6 border-b border-gray-100/70 dark:border-gray-700/60">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ plan.plan_name }}</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 min-h-10">{{ plan.plan_description || '—' }}</p>
          </div>

          <div class="p-6 flex flex-col gap-4">
            <div>
              <div v-if="Number(plan.discount_percentage) > 0" class="mb-1 text-xs font-semibold text-rose-600 dark:text-rose-300">
                Hemat {{ Number(plan.discount_percentage) }}%
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">{{ formatCurrency(discountedPrice(plan)) }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">/ {{ periodLabel(plan.period_type) }}</span>
              </div>
              <div v-if="Number(plan.discount_percentage) > 0" class="text-xs text-gray-400 line-through">{{ formatCurrency(Number(plan.price)) }}</div>
            </div>

            <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
              <!-- Pembayaran -->
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Integrasi pembayaran otomatis dengan iPaymu</li>

              <!-- Akses berbasis peran -->
              <li class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Akses Berbasis Peran</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Admin (Pemilik Toko): Mengelola toko & data bisnis</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Kasir: Akses terbatas untuk penjualan & transaksi</li>

              <!-- Data master -->
              <li class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Data Master</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Produk: SKU, kategori, harga, dan stok</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Pelanggan: Data kontak & histori transaksi</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Kategori Produk: Kelompokkan item dengan mudah</li>

              <!-- Alur transaksi -->
              <li class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Alur Transaksi Efisien</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Pembuatan pesanan & pemrosesan cepat</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Metode pembayaran: Tunai, QRIS, E-Wallet</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Kwitansi otomatis & bisa dicetak</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Riwayat pemesanan lengkap & dapat difilter</li>

              <!-- Inventaris (NEW) -->
              <li class="mt-3 text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Fitur Inventaris Terbaru (NEW!)</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Supplier: Kelola informasi pemasok</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Inventaris & ringkasan: Stok per produk real-time</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Riwayat pergerakan: Telusuri keluar/masuk barang</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Penerimaan barang: Catat pembelian & penambahan stok</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Penyesuaian stok: Koreksi stok fisik dengan mudah</li>

              <!-- Laporan bisnis -->
              <li class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Laporan Bisnis</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Laba kotor (Gross Profit)</li>
              <li class="flex items-start gap-2"><Check class="h-4 w-4 text-green-600 mt-0.5" /> Nilai Stok: Total nilai barang di gudang</li>
            </ul>

            <div class="pt-2">
              <Link
                v-if="isAuthenticated"
                :href="route('subscription.payment')"
                class="inline-flex w-full justify-center items-center gap-2 rounded-xl bg-indigo-600 text-white font-semibold py-2.5 hover:bg-indigo-700 transition-colors"
              >
                Pilih Paket
              </Link>
              <Link
                v-else
                :href="route('register')"
                class="inline-flex w-full justify-center items-center gap-2 rounded-xl bg-indigo-600 text-white font-semibold py-2.5 hover:bg-indigo-700 transition-colors"
              >
                Daftar untuk Mulai
              </Link>
              <p v-if="Number(trialDays) > 0" class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">
                Termasuk uji coba gratis {{ Number(trialDays) }} hari. Batalkan kapan saja.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* no extra styles needed; Tailwind handles visuals */
</style>
