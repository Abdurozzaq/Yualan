<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue'; // Import computed

const page = usePage();
const tenantSlug = computed(() => page.props.tenantSlug as string | undefined); // Get tenantSlug from page props

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
    },
    {
        title: 'Password',
        href: '/settings/password',
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
    },
    {
        title: 'Tenant Info',
        href: tenantSlug.value ? route('tenant.settings.info', { tenantSlug: tenantSlug.value }) : '#',
    },
];

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';

// Computed property to get the base path for comparison (e.g., /tenant-slug/settings/tenant-info)
const currentBasePath = computed(() => {
    // Remove the origin and query string, then normalize trailing slash
    const path = currentPath.split('?')[0];
    return path.endsWith('/') && path.length > 1 ? path.slice(0, -1) : path;
});

// Computed property to check if a navigation item is active
const isNavItemActive = (itemHref: string) => {
    // Normalize the itemHref to match the currentBasePath format
    const normalizedItemHref = itemHref.split('?')[0];
    return currentBasePath.value === normalizedItemHref;
};
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Settings" description="Manage your profile and account settings" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': isNavItemActive(item.href) }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
