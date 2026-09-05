<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Lock, Zap, ShieldCheck } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
    title: string;
    description?: string;
    icon?: any;
    gradient?: string;
}

const props = withDefaults(defineProps<Props>(), {
    description: 'Fitur ini eksklusif untuk pengguna Yualan POS Premium. Tingkatkan operasional bisnis Anda dengan fitur analisis mendalam dan manajemen tim yang lebih baik.',
    icon: Lock,
    gradient: 'from-blue-600 to-indigo-700'
});
</script>

<template>
    <div class="flex flex-col items-center justify-center min-h-[70vh] p-4 text-center animate-in fade-in zoom-in duration-700">
        <div :class="['bg-gradient-to-br p-8 sm:p-16 rounded-[2.5rem] shadow-2xl max-w-2xl w-full text-white overflow-hidden relative group border-4 border-white/10', gradient]">
            <!-- Decorative background elements -->
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-all duration-700"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-black/10 rounded-full blur-3xl group-hover:bg-black/20 transition-all duration-700"></div>
            
            <div class="relative z-10 flex flex-col items-center">
                <div class="bg-white/10 p-6 rounded-3xl backdrop-blur-xl mb-8 shadow-inner border border-white/30">
                    <component :is="props.icon" class="h-14 w-14 text-white" />
                </div>
                
                <h1 class="text-4xl sm:text-5xl font-black mb-6 tracking-tight leading-tight">
                    {{ title }} <br/>Locked
                </h1>
                <p class="text-xl text-white/80 mb-10 leading-relaxed max-w-md mx-auto">
                    {{ description }}
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 w-full justify-center">
                    <a href="https://yualan.web.id" target="_blank" class="bg-white text-gray-900 font-black px-10 py-5 rounded-2xl shadow-xl hover:bg-gray-100 transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg">
                        <Zap class="h-6 w-6 fill-gray-900" />
                        Upgrade ke Premium
                    </a>
                </div>

                <div class="mt-10 flex items-center gap-2 text-white/60 text-sm font-bold uppercase tracking-widest">
                    <ShieldCheck class="h-4 w-4" />
                    Secure Enterprise Node
                </div>
            </div>
        </div>
        
        <p class="mt-8 text-gray-500 text-sm font-medium">
            Yualan POS Community Edition Dedicated Enterprise
        </p>
    </div>
</template>
