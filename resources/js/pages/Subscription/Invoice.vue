

<template>
  <div class="invoice-page bg-gray-100 min-h-screen py-10 flex items-center justify-center print:bg-white print:py-0 print:min-h-0">
    <div class="invoice-container bg-white p-10 rounded-xl shadow-2xl max-w-2xl w-full mx-auto mt-8 print:p-8 print:shadow-none print:mt-0 print:bg-white print:max-w-full print:rounded-none print:text-black print:w-full print:border print:border-gray-300">
      <div class="flex justify-between items-center mb-10 print:flex-row print:mb-6 border-b pb-4 print:border-b print:pb-2">
        <div>
          <h1 class="text-4xl font-extrabold text-blue-700 mb-1 print:text-black print:text-3xl">INVOICE</h1>
          <div class="text-gray-500 text-base print:text-black print:text-sm">{{ appName }}</div>
        </div>
        <div class="text-right print:text-right">
          <div class="text-gray-700 font-bold text-lg print:text-black">#{{ invoice.id }}</div>
          <div class="text-xs text-gray-400 print:text-black">Tanggal: {{ formatDate(invoice.created_at) }}</div>
        </div>
      </div>
      <div class="mb-8 print:mb-4">
        <div class="font-semibold text-lg mb-1 print:text-base print:mb-0.5">Pelanggan</div>
        <div class="text-gray-800 text-base print:text-black print:text-sm">{{ invoice.tenant?.name }}</div>
        <div class="text-gray-500 text-sm print:text-black print:text-xs">{{ invoice.tenant?.email }}</div>
      </div>
      <div class="mb-8 print:mb-4">
        <table class="w-full text-sm print:text-xs">
          <tbody>
            <tr>
              <td class="py-1 text-gray-500 font-medium w-1/3">Plan</td>
              <td class="py-1 font-semibold text-gray-800 print:text-black">{{ invoice.plan_name }}</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-500 font-medium">Expired</td>
              <td class="py-1 font-semibold text-gray-800 print:text-black">{{ formatDate(invoice.expired_at) }}</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-500 font-medium">Transaction ID</td>
              <td class="py-1 font-mono text-gray-800 print:text-black">{{ invoice.transaction_id }}</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-500 font-medium">Status</td>
              <td class="py-1 font-semibold">
                <span class="text-green-600 print:text-black">Berhasil</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mb-10 print:mb-6 border-t pt-6 print:pt-4 print:border-t print:border-gray-300">
        <div class="flex justify-between items-center print:block">
          <div class="text-lg font-semibold print:text-base">Total</div>
          <div class="text-2xl font-extrabold text-blue-700 print:text-black print:text-xl">Rp {{ formatCurrency(invoice.amount) }}</div>
        </div>
      </div>
      <div class="mb-6 text-gray-500 text-sm print:text-black print:text-xs">Terima kasih telah melakukan pembayaran langganan.</div>
      <div class="flex gap-3 print:hidden">
        <button @click="printInvoice" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold shadow hover:bg-blue-700 transition">Print</button>
        <button @click="goToHistory" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg font-semibold shadow hover:bg-gray-300 transition">Back to Invoice History</button>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">

import { usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'SaaS App';
const invoice = usePage().props.invoice || {};
const tenantSlug = computed(() => usePage().props.tenantSlug);

function printInvoice() {
  window.print();
}

function goToHistory() {
  router.visit(route('invoices.history', { tenantSlug: tenantSlug.value }));
}

function formatDate(date: string | number | Date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatCurrency(amount: number | string) {
  if (!amount) return '0';
  return Number(amount).toLocaleString('id-ID');
}
</script>

<style scoped>
@media print {
  body, html {
    background: #fff !important;
    color: #000 !important;
  }
  .print\:hidden { display: none !important; }
  .print\:p-0 { padding: 0 !important; }
  .print\:p-8 { padding: 2rem !important; }
  .print\:shadow-none { box-shadow: none !important; }
  .print\:mt-0 { margin-top: 0 !important; }
  .print\:bg-white { background: #fff !important; }
  .print\:max-w-full { max-width: 100vw !important; }
  .print\:rounded-none { border-radius: 0 !important; }
  .print\:text-black { color: #000 !important; }
  .print\:border-t { border-top: 1px solid #ccc !important; }
  .print\:pt-4 { padding-top: 1rem !important; }
  .print\:pt-2 { padding-top: 0.5rem !important; }
  .print\:mb-6 { margin-bottom: 1.5rem !important; }
  .print\:mb-4 { margin-bottom: 1rem !important; }
  .print\:block { display: block !important; }
  .print\:flex-row { flex-direction: row !important; }
  .print\:items-start { align-items: flex-start !important; }
  .print\:mb-2 { margin-bottom: 0.5rem !important; }
  .print\:text-xs { font-size: 0.75rem !important; }
  .print\:text-sm { font-size: 0.875rem !important; }
  .print\:text-base { font-size: 1rem !important; }
  .print\:text-xl { font-size: 1.25rem !important; }
  .print\:text-3xl { font-size: 1.875rem !important; }
  .print\:w-full { width: 100% !important; }
  .print\:border { border: 1px solid #ccc !important; }
}
</style>
