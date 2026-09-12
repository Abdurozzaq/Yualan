<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound, LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('env_admin.authenticate'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="System Administration" />
    <div class="min-h-screen relative flex items-center justify-center bg-[#09090b] overflow-hidden selection:bg-blue-500/30">
        <!-- Modern background elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-blue-600/10 blur-[120px]"></div>
            <div class="absolute top-[60%] -right-[10%] w-[40%] h-[60%] rounded-full bg-indigo-600/10 blur-[120px]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+CjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0ibm9uZSI+PC9yZWN0Pgo8Y2lyY2xlIGN4PSIyIiBjeT0iMiIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSI+PC9jaXJjbGU+Cjwvc3ZnPg==')] opacity-50"></div>
        </div>

        <div class="w-full max-w-md p-6 relative z-10 animate-in fade-in zoom-in-95 duration-500">
            <div class="bg-gray-900/40 backdrop-blur-xl border border-white/10 p-8 sm:p-10 rounded-[2rem] shadow-2xl relative overflow-hidden">
                <!-- Inner glow line -->
                <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
                
                <div class="flex flex-col items-center mb-8">
                    <div class="h-16 w-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/20 mb-6 transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        <KeyRound class="h-8 w-8 text-white" stroke-width="1.5" />
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight text-center">
                        Core Access
                    </h1>
                    <p class="text-sm text-gray-400 mt-2 text-center font-medium">
                        Secure administration terminal
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <KeyRound class="h-5 w-5 text-gray-500 group-focus-within:text-blue-400 transition-colors" />
                            </div>
                            <input
                                id="password"
                                type="password"
                                v-model="form.password"
                                placeholder="Enter master key..."
                                required
                                class="w-full bg-gray-950/50 border border-white/10 text-white placeholder:text-gray-600 text-sm rounded-xl py-4 pl-12 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300 shadow-inner"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-red-400 mt-2 font-medium flex items-center gap-1">
                            <span class="inline-block w-1 h-1 bg-red-400 rounded-full"></span>
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full relative group overflow-hidden bg-white text-gray-950 font-bold text-sm py-4 rounded-xl shadow-[0_0_40px_-10px_rgba(255,255,255,0.3)] hover:shadow-[0_0_60px_-15px_rgba(255,255,255,0.5)] transition-all duration-300 flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                    >
                        <span class="absolute w-0 h-0 transition-all duration-300 ease-out bg-blue-500 rounded-full group-hover:w-full group-hover:h-56 opacity-10"></span>
                        <LoaderCircle v-if="form.processing" class="h-5 w-5 mr-2 animate-spin" />
                        <span class="relative">Authenticate</span>
                    </button>
                </form>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-500 font-mono">
                    SYS.ADMIN.PORTAL_V2.0
                </p>
            </div>
        </div>
    </div>
</template>
