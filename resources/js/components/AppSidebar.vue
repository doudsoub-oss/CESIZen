<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, HeartPulse, Home, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as diagnosticIndex } from '@/routes/diagnostic';
import { index as informationsIndex } from '@/routes/informations';
import type { NavItem } from '@/types';

const page = usePage();

const isAdmin = computed(() => {
    const role = page.props.auth.user?.role;

    return role === 'admin' || role === 'super_admin';
});

// The authenticated area must stay connected to the public site so users can
// read information and run diagnostics, and back-office users can reach the
// admin panel.
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Accueil du site', href: home(), icon: Home },
        { title: 'Informations', href: informationsIndex(), icon: BookOpen },
        { title: 'Diagnostic', href: diagnosticIndex(), icon: HeartPulse },
    ];

    if (isAdmin.value) {
        items.push({
            title: 'Administration',
            href: adminDashboard(),
            icon: ShieldCheck,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="home()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
