<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import EnvAdminLayout from '@/layouts/EnvAdminLayout.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Search, KeySquare, Trash2, ShieldAlert } from 'lucide-vue-next';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    users: any;
    filters: any;
}>();

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get(route('env_admin.users'), { search: search.value }, { preserveState: true, replace: true });
};

// Password Change Dialog
const isPasswordDialogOpen = ref(false);
const selectedUserForPassword = ref<any>(null);
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const openPasswordDialog = (user: any) => {
    selectedUserForPassword.value = user;
    passwordForm.reset();
    isPasswordDialogOpen.value = true;
};

const submitPasswordChange = () => {
    if (!selectedUserForPassword.value) return;
    
    passwordForm.put(route('env_admin.users.password', selectedUserForPassword.value.id), {
        onSuccess: () => {
            isPasswordDialogOpen.value = false;
        },
    });
};

// Delete Dialog
const isDeleteDialogOpen = ref(false);
const selectedUserForDelete = ref<any>(null);

const openDeleteDialog = (user: any) => {
    selectedUserForDelete.value = user;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!selectedUserForDelete.value) return;
    
    router.delete(route('env_admin.users.destroy', selectedUserForDelete.value.id), {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
        }
    });
};

// Format Date
const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <EnvAdminLayout>
        <Head title="User Management" />
        
        <div class="p-4 sm:p-8">
            <div class="bg-gray-900/40 backdrop-blur-md border border-white/10 rounded-2xl sm:rounded-[2rem] shadow-2xl overflow-hidden relative">
                <!-- Inner glow -->
                <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
                
                <div class="p-6 sm:p-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 border-b border-white/5">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Identity Management</h2>
                        <p class="text-sm text-gray-400 mt-1">Supervise and control user access records.</p>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="relative w-full sm:w-80 group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <Search class="h-4 w-4 text-gray-500 group-focus-within:text-blue-400 transition-colors" />
                        </div>
                        <input 
                            v-model="search" 
                            @keyup.enter="handleSearch"
                            placeholder="Query by name or email..." 
                            class="w-full bg-gray-950/50 border border-white/10 text-white placeholder:text-gray-600 text-sm rounded-xl py-3 pl-11 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all shadow-inner"
                        />
                    </div>
                </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-gray-950/50 text-gray-400 font-semibold tracking-wider border-b border-white/5">
                                <tr>
                                    <th scope="col" class="px-6 py-5">User Profile</th>
                                    <th scope="col" class="px-6 py-5">Tenant ID</th>
                                    <th scope="col" class="px-6 py-5">Access Level</th>
                                    <th scope="col" class="px-6 py-5">Registered</th>
                                    <th scope="col" class="px-6 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="user in users.data" :key="user.id" class="group hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-gray-700 to-gray-600 flex items-center justify-center text-xs font-bold text-white shadow-inner">
                                                {{ user.name.substring(0, 2).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-200 group-hover:text-white transition-colors">{{ user.name }}</div>
                                                <div class="text-gray-500 text-xs mt-0.5">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="user.tenant" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                            {{ user.tenant.name }}
                                        </span>
                                        <span v-else class="text-gray-600 text-xs bg-gray-800/50 px-2.5 py-1 rounded-lg border border-gray-700/50">Unassigned</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-1.5">
                                            <div :class="['w-1.5 h-1.5 rounded-full', user.role === 'admin' ? 'bg-indigo-400' : 'bg-green-400']"></div>
                                            <span class="capitalize text-gray-300 font-medium">{{ user.role }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-100 sm:opacity-50 group-hover:opacity-100 transition-opacity">
                                            <button 
                                                @click="openPasswordDialog(user)"
                                                class="p-2 rounded-lg bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-700 border border-white/5 transition-all"
                                                title="Reset Password"
                                            >
                                                <KeySquare class="h-4 w-4" />
                                            </button>
                                            <button 
                                                @click="openDeleteDialog(user)"
                                                class="p-2 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white border border-red-500/20 transition-all"
                                                title="Delete User"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <ShieldAlert class="h-10 w-10 opacity-20 mb-3" />
                                            <p>No identities found matching your query.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-5 border-t border-white/5 flex items-center justify-between" v-if="users.links && users.links.length > 3">
                        <div class="flex gap-1.5">
                            <template v-for="(link, key) in users.links" :key="key">
                                <component
                                    :is="link.url ? 'a' : 'span'"
                                    :href="link.url"
                                    class="px-3.5 py-1.5 rounded-lg text-sm transition-all"
                                    :class="[
                                        link.active ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20 font-bold' : 'bg-gray-800/50 text-gray-400 hover:bg-gray-700 hover:text-white border border-white/5',
                                        !link.url ? 'opacity-30 cursor-not-allowed border-none' : ''
                                    ]"
                                    v-html="link.label"
                                ></component>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Password Change Dialog -->
        <Dialog :open="isPasswordDialogOpen" @update:open="isPasswordDialogOpen = $event">
            <DialogContent class="bg-gray-900 text-gray-200 border border-white/10 shadow-2xl rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-white text-xl">Force Override Password</DialogTitle>
                    <DialogDescription class="text-gray-400">
                        Target identity: <strong class="text-white">{{ selectedUserForPassword?.name }}</strong> ({{ selectedUserForPassword?.email }}).
                    </DialogDescription>
                </DialogHeader>
                
                <form @submit.prevent="submitPasswordChange" class="space-y-5 mt-2">
                    <div class="space-y-2">
                        <Label for="new_password" class="text-gray-300">New Key</Label>
                        <Input id="new_password" type="password" v-model="passwordForm.password" required class="bg-gray-950/50 border border-white/10 text-white focus:border-blue-500/50 focus:ring-blue-500/30" />
                        <span v-if="passwordForm.errors.password" class="text-xs text-red-400">{{ passwordForm.errors.password }}</span>
                    </div>
                    <div class="space-y-2">
                        <Label for="confirm_password" class="text-gray-300">Confirm New Key</Label>
                        <Input id="confirm_password" type="password" v-model="passwordForm.password_confirmation" required class="bg-gray-950/50 border border-white/10 text-white focus:border-blue-500/50 focus:ring-blue-500/30" />
                    </div>
                    
                    <DialogFooter class="pt-5 border-t border-white/5 mt-4">
                        <button type="button" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-400 hover:text-white transition-colors" @click="isPasswordDialogOpen = false">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold bg-blue-600 text-white hover:bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.3)] transition-all flex items-center justify-center disabled:opacity-50" :disabled="passwordForm.processing">
                            Execute Change
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent class="bg-gray-900 text-gray-200 border border-white/10 shadow-2xl rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-red-400 flex items-center gap-2 text-xl">
                        <ShieldAlert class="h-6 w-6" /> Terminate Identity
                    </DialogTitle>
                    <DialogDescription class="text-gray-400 mt-2">
                        Are you sure you want to permanently delete <strong class="text-white">{{ selectedUserForDelete?.name }}</strong>? This action is irreversible and will purge all related records from the system core.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="pt-5 border-t border-white/5 mt-4">
                    <button type="button" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-400 hover:text-white transition-colors" @click="isDeleteDialogOpen = false">Cancel</button>
                    <button type="button" class="px-5 py-2 rounded-lg text-sm font-bold bg-red-600 hover:bg-red-500 text-white shadow-[0_0_15px_rgba(239,68,68,0.3)] transition-all" @click="confirmDelete">Confirm Termination</button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </EnvAdminLayout>
</template>
