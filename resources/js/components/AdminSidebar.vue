<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookText,
    ClipboardList,
    FolderTree,
    LayoutGrid,
    ListChecks,
    PanelsTopLeft,
    ScrollText,
    Users as UsersIcon,
} from 'lucide-vue-next';
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
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as contentsIndex } from '@/routes/admin/contents';
import { index as menusIndex } from '@/routes/admin/menus';
import { index as questionnairesIndex } from '@/routes/admin/questionnaires';
import { index as usersIndex } from '@/routes/admin/users';
import type { NavItem } from '@/types';

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Tableau de bord',
        href: adminDashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Catégories',
        href: categoriesIndex(),
        icon: FolderTree,
    },
    {
        title: 'Contenus',
        href: contentsIndex(),
        icon: BookText,
    },
    {
        title: 'Menus',
        href: menusIndex(),
        icon: PanelsTopLeft,
    },
    {
        title: 'Questionnaires',
        href: questionnairesIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Utilisateurs',
        href: usersIndex(),
        icon: UsersIcon,
    },
    {
        title: "Journaux d'audit",
        href: auditLogsIndex(),
        icon: ScrollText,
    },
    {
        title: 'Retour au site',
        href: home(),
        icon: ListChecks,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="adminDashboard()">
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
