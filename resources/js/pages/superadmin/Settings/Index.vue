<script setup lang="ts">
import SuperadminLayout from '@/layouts/app/SuperadminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Settings, Zap } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Superadmin Dashboard', href: '#' },
    { title: 'SaaS Settings', href: '#' },
];
</script>

<template>
    <Head title="SaaS Settings Disabled" />

    <SuperadminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col items-center justify-center min-h-[70vh] p-4 text-center animate-fade-in">
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-8 sm:p-16 rounded-[2.5rem] shadow-2xl max-w-2xl w-full text-white overflow-hidden relative group border-4 border-white/10">
                <!-- Decorative background elements -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl group-hover:bg-orange-500/20 transition-all duration-700"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-all duration-700"></div>
                
                <div class="relative z-10 flex flex-col items-center">
                    <div class="bg-white/10 p-6 rounded-3xl backdrop-blur-xl mb-8 shadow-inner border border-white/30">
                        <Settings class="h-14 w-14 text-orange-400" />
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl font-black mb-6 tracking-tight leading-tight">
                        SaaS Settings <br/>Dinonaktifkan
                    </h1>
                    <p class="text-xl text-gray-400 mb-10 leading-relaxed max-w-md mx-auto">
                        Konfigurasi SaaS dinonaktifkan dalam versi <strong>Community Edition</strong> untuk meminimalkan kompleksitas infrastruktur.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 w-full justify-center">
                        <a href="https://yualan.web.id" target="_blank" class="bg-orange-600 text-white font-black px-10 py-5 rounded-2xl shadow-xl hover:bg-orange-700 transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg">
                            <Zap class="h-6 w-6 fill-white" />
                            Gunakan Versi SaaS
                        </a>
                    </div>
                </div>
            </div>
            
            <p class="mt-8 text-gray-500 text-sm font-medium">
                Yualan POS Community Edition Dedicated Enterprise
            </p>
        </div>
    </SuperadminLayout>
</template>
