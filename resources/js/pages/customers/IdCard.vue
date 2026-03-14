<script setup lang="ts">
import { computed, onMounted } from 'vue';

interface Customer {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    created_at: string; // Add created_at for "Member Since"
}

const props = defineProps<{
    customer: Customer;
    tenantName: string;
}>();

// Format creation date for "Member Since"
const memberSince = computed(() => {
    if (!props.customer.created_at) return '-';
    return new Date(props.customer.created_at).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

onMounted(() => {
    // Auto-print after short delay for rendering
    setTimeout(() => {
        window.print();
    }, 100);
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900/25 to-slate-900 flex items-center justify-center p-8 print:!min-h-fit print:p-0">
        <!-- Print preview info (hidden on print) -->
        <div class="text-center mb-8 text-white/80 print:hidden">
            <h1 class="text-2xl font-bold mb-2">ID Card Preview</h1>
            <p class="text-sm opacity-75">Page will auto-print in a moment...</p>
        </div>
        
        <!-- ID Card Container -->
        <div id="id-card-content"
             class="w-[85.6mm] h-[53.98mm] bg-gray-950 text-white shadow-2xl flex flex-col justify-between p-4 relative overflow-hidden print:shadow-none print:mx-auto print:my-0">
            <!-- Subtle Background Pattern -->
            <div class="absolute inset-0 opacity-10 print:opacity-20"
                 style="background-image: radial-gradient(circle at top left, rgba(255,255,255,0.1) 0%, transparent 70%);">
            </div>
            <div class="absolute inset-0 opacity-5 print:opacity-10"
                 style="background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent 50%);">
            </div>

            <!-- Top Section: Customer Details -->
            <div class="relative z-10 flex-grow pt-1">
                <h1 class="text-lg font-bold mb-[2px] truncate leading-tight print:text-white">{{ customer.name }}</h1>
                <p v-if="customer.phone" class="text-[9px] text-gray-300 truncate leading-tight print:text-white">Telepon: {{ customer.phone }}</p>
                <p v-if="customer.email" class="text-[9px] text-gray-300 truncate leading-tight print:text-white">Email: {{ customer.email }}</p>
                <p v-if="customer.address" class="text-[8px] text-gray-400 mt-[2px] line-clamp-2 leading-tight print:text-white">Alamat: {{ customer.address }}</p>
            </div>

            <!-- Bottom Section: Tenant Info & Member Since -->
            <div class="relative z-10 text-right mt-2 flex justify-between items-end">
                <div>
                    <p class="text-[8px] text-gray-400 print:text-white">Anggota Sejak:</p>
                    <p class="text-[9px] font-semibold text-gray-200 print:text-white">{{ memberSince }}</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-gray-100 leading-tight print:text-white">{{ tenantName }}</p>
                    <p class="text-[8px] text-gray-400 print:text-white">Kartu Pelanggan</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body, html {
        background: white !important;
        color: white !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
    }
    
    @page {
        size: 85.6mm 53.98mm;
        margin: 0;
    }
    
    .print\:hidden {
        display: none !important;
    }
    
    #id-card-content {
        box-shadow: none !important;
        margin: 0 auto !important;
        position: relative !important;
        transform: none !important;
        width: 85.6mm !important;
        height: 53.98mm !important;
        page-break-after: always;
    }
    
    /* Force white text on dark bg for print */
    #id-card-content * {
        color: white !important;
        text-shadow: none !important;
        background: transparent !important;
    }
    
    /* Ensure gradients print */
    #id-card-content {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}

/* Screen styles */
#id-card-content {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>
