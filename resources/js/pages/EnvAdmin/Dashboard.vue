<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EnvAdminLayout from '@/layouts/EnvAdminLayout.vue';
import { Users, Store, Box, DollarSign, Activity, GitBranch } from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        users: number;
        tenants: number;
        products: number;
        salesCount: number;
    };
    version: string;
}>();

// Utility for formatting currency
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

// Stat cards config
const statCards = [
    { 
        title: 'Total Users', 
        value: props.stats.users, 
        icon: Users, 
        color: 'from-blue-600/20 to-blue-400/5', 
        textColor: 'text-blue-400',
        borderColor: 'border-blue-500/20'
    },
    { 
        title: 'Active Tenants', 
        value: props.stats.tenants, 
        icon: Store, 
        color: 'from-purple-600/20 to-purple-400/5', 
        textColor: 'text-purple-400',
        borderColor: 'border-purple-500/20'
    },
    { 
        title: 'Total Products', 
        value: props.stats.products, 
        icon: Box, 
        color: 'from-pink-600/20 to-pink-400/5', 
        textColor: 'text-pink-400',
        borderColor: 'border-pink-500/20'
    },
    { 
        title: 'Total Sales', 
        value: props.stats.salesCount, 
        icon: Activity, 
        color: 'from-amber-600/20 to-amber-400/5', 
        textColor: 'text-amber-400',
        borderColor: 'border-amber-500/20'
    }
];
</script>

<template>
    <EnvAdminLayout>
        <Head title="System Dashboard" />
        
        <div class="p-4 sm:p-8 space-y-6">
            
            <!-- Welcome / Version Card -->
            <div class="bg-gray-900/40 backdrop-blur-md border border-white/10 rounded-2xl sm:rounded-[2rem] p-6 sm:p-8 shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <!-- Inner glow -->
                <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
                
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Yualan POS Dashboard</h2>
                    <p class="text-gray-400 mt-2 max-w-xl text-sm leading-relaxed">
                        Welcome to the central command center. Monitor application telemetry, usage statistics, and overall system health in real-time.
                    </p>
                </div>
                
                <div class="flex items-center gap-3 bg-white/5 border border-white/10 px-4 py-2.5 rounded-xl">
                    <GitBranch class="h-5 w-5 text-emerald-400" />
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">System Version</div>
                        <div class="text-white font-semibold text-sm">{{ version }}</div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div 
                    v-for="stat in statCards" 
                    :key="stat.title"
                    :class="['bg-gradient-to-br backdrop-blur-md border rounded-2xl p-6 shadow-xl relative overflow-hidden group transition-all duration-300 hover:shadow-2xl hover:-translate-y-1', stat.color, stat.borderColor]"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400 text-sm font-semibold mb-1">{{ stat.title }}</p>
                            <h4 class="text-3xl font-black text-white tracking-tight">{{ stat.value.toLocaleString() }}</h4>
                        </div>
                        <div :class="['p-3 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10 group-hover:scale-110 transition-transform duration-300', stat.textColor]">
                            <component :is="stat.icon" class="h-6 w-6" />
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </EnvAdminLayout>
</template>
