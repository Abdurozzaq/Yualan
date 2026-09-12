<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { ShieldAlert, LogOut, LayoutDashboard, Users, Menu, X } from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const handleLogout = () => {
    router.post(route('env_admin.logout'));
};

const navItems = [
    { name: 'Dashboard', route: 'env_admin.dashboard', icon: LayoutDashboard },
    { name: 'User Management', route: 'env_admin.users', icon: Users },
];
</script>

<template>
    <div class="min-h-screen bg-[#09090b] text-gray-200 font-sans selection:bg-blue-500/30 flex flex-col md:flex-row relative">
        <!-- Modern background elements -->
        <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-blue-600/5 blur-[120px]"></div>
            <div class="absolute top-[60%] -right-[10%] w-[40%] h-[60%] rounded-full bg-indigo-600/5 blur-[120px]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+CjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0ibm9uZSI+PC9yZWN0Pgo8Y2lyY2xlIGN4PSIyIiBjeT0iMiIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAyKSI+PC9jaXJjbGU+Cjwvc3ZnPg==')] opacity-50"></div>
        </div>

        <!-- Mobile Header -->
        <div class="md:hidden flex items-center justify-between p-4 bg-gray-950/60 backdrop-blur-xl border-b border-white/5 z-50 sticky top-0">
            <div class="flex items-center gap-3">
                <div class="bg-blue-500/10 p-1.5 rounded-lg border border-blue-500/20 shadow-[0_0_15px_rgba(59,130,246,0.1)]">
                    <ShieldAlert class="h-5 w-5 text-blue-400" />
                </div>
                <h1 class="text-lg font-bold text-white tracking-tight">Adminstrator Core</h1>
            </div>
            <button @click="toggleMobileMenu" class="text-gray-400 hover:text-white p-2 focus:outline-none">
                <Menu v-if="!isMobileMenuOpen" class="h-6 w-6" />
                <X v-else class="h-6 w-6" />
            </button>
        </div>

        <!-- Sidebar -->
        <aside 
            :class="[ 
                'fixed inset-y-0 left-0 z-40 w-64 bg-gray-950/80 backdrop-blur-xl border-r border-white/5 transform transition-transform duration-300 ease-in-out md:translate-x-0 md:static flex flex-col',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="hidden md:flex items-center gap-3 px-6 py-6 border-b border-white/5">
                <div class="bg-blue-500/10 p-2 rounded-xl border border-blue-500/20 shadow-[0_0_15px_rgba(59,130,246,0.1)]">
                    <ShieldAlert class="h-6 w-6 text-blue-400" />
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight leading-tight">Admin Core</h1>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">System Terminal</p>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link 
                    v-for="item in navItems" 
                    :key="item.name" 
                    :href="route(item.route)"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all group',
                        route().current(item.route) 
                            ? 'bg-blue-600/10 text-blue-400 border border-blue-500/20 shadow-[0_0_20px_rgba(59,130,246,0.1)]' 
                            : 'text-gray-400 hover:text-white hover:bg-white/5 border border-transparent'
                    ]"
                >
                    <component :is="item.icon" :class="['h-5 w-5', route().current(item.route) ? 'text-blue-400' : 'text-gray-500 group-hover:text-gray-300']" />
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-white/5">
                <button 
                    @click="handleLogout"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500 hover:text-white hover:border-red-500 hover:shadow-[0_0_20px_rgba(239,68,68,0.3)] transition-all duration-300 text-sm font-semibold"
                >
                    <LogOut class="h-4 w-4" />
                    <span>Terminate Session</span>
                </button>
            </div>
        </aside>

        <!-- Overlay for mobile menu -->
        <div 
            v-if="isMobileMenuOpen" 
            @click="toggleMobileMenu"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 md:hidden"
        ></div>

        <!-- Main Content Area -->
        <main class="flex-1 relative z-10 w-full min-h-screen">
            <slot />
        </main>
    </div>
</template>
