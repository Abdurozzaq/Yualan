<script setup lang="ts">
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue'; // Import ref for reactive state
import { ChevronDown, ChevronUp, Lock } from 'lucide-vue-next'; // Import icons for expand/collapse
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface Props {
    items: NavItem[]; // Now 'items' is required and expected to be passed
}

const props = defineProps<Props>();

// Reactive object to keep track of which submenus are open
// Key is the item.title, value is a boolean (true for open, false for closed)
const openSubmenus = ref<Record<string, boolean>>({});

// Function to toggle the open/closed state of a submenu
const toggleSubmenu = (itemTitle: string) => {
    openSubmenus.value[itemTitle] = !openSubmenus.value[itemTitle];
};
</script>

<template>
    <TooltipProvider :delay-duration="0">
        <SidebarMenu>
            <template v-for="item in props.items" :key="item.title">
                <SidebarMenuItem>
                    <!-- If item has children, render a toggle button -->
                    <template v-if="item.children && item.children.length > 0">
                        <SidebarMenuButton @click="toggleSubmenu(item.title)">
                            <component :is="item.icon" class="h-4 w-4" />
                            <span class="flex-1 text-left">{{ item.title }}</span>
                            <div v-if="item.isLocked" class="mr-1">
                                <Tooltip shadow>
                                    <TooltipTrigger as-child>
                                        <Lock class="h-3 w-3 text-amber-500" />
                                    </TooltipTrigger>
                                    <TooltipContent side="right" class="bg-gray-900 text-white border-none text-[10px] font-bold uppercase tracking-widest px-2 py-1">
                                        Premium Feature
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                            <component :is="openSubmenus[item.title] ? ChevronUp : ChevronDown" class="ml-auto h-4 w-4 transition-transform duration-200 opacity-50" />
                        </SidebarMenuButton>
                        <!-- Submenu items, conditionally rendered -->
                        <div v-if="openSubmenus[item.title]" class="ml-4 mt-1 space-y-1">
                            <SidebarMenuItem v-for="child in item.children" :key="child.title">
                                <SidebarMenuButton as-child>
                                    <Link :href="child.href" class="flex items-center gap-2">
                                        <component :is="child.icon" v-if="child.icon" class="h-4 w-4" />
                                        <span class="flex-1">{{ child.title }}</span>
                                        <div v-if="child.isLocked">
                                            <Tooltip shadow>
                                                <TooltipTrigger as-child>
                                                    <Lock class="h-3 w-3 text-amber-500" />
                                                </TooltipTrigger>
                                                <TooltipContent side="right" class="bg-gray-900 text-white border-none text-[10px] font-bold uppercase tracking-widest px-2 py-1">
                                                    Premium Feature
                                                </TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </div>
                    </template>
                    <!-- If item has no children, render a direct link -->
                    <template v-else>
                        <SidebarMenuButton as-child>
                            <Link :href="item.href" class="flex items-center gap-2">
                                <component :is="item.icon" class="h-4 w-4" />
                                <span class="flex-1">{{ item.title }}</span>
                                <div v-if="item.isLocked">
                                    <Tooltip shadow>
                                        <TooltipTrigger as-child>
                                            <Lock class="h-3 w-3 text-amber-500" />
                                        </TooltipTrigger>
                                        <TooltipContent side="right" class="bg-gray-900 text-white border-none text-[10px] font-bold uppercase tracking-widest px-2 py-1">
                                            Premium Feature
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </template>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </TooltipProvider>
</template>
