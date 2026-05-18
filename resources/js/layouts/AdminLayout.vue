<script setup lang="ts">
import AdminSidebar from '@/components/AdminSidebar.vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import FlashBanner from '@/components/FlashBanner.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AdminSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <FlashBanner />
            <slot />
        </AppContent>
    </AppShell>
</template>
