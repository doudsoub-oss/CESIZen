<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookText,
    ClipboardList,
    FolderTree,
    PanelsTopLeft,
    Users as UsersIcon,
} from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as contentsIndex } from '@/routes/admin/contents';
import { index as menusIndex } from '@/routes/admin/menus';
import { index as questionnairesIndex } from '@/routes/admin/questionnaires';
import { index as usersIndex } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
];

const sections = [
    {
        title: 'Catégories',
        description: 'Arbre éditorial des contenus.',
        href: categoriesIndex(),
        icon: FolderTree,
    },
    {
        title: 'Contenus',
        description: 'Articles, ressources et pages publiques.',
        href: contentsIndex(),
        icon: BookText,
    },
    {
        title: 'Menus',
        description: 'Navigation principale et pied de page.',
        href: menusIndex(),
        icon: PanelsTopLeft,
    },
    {
        title: 'Questionnaires',
        description: 'Outil de diagnostic et interprétations.',
        href: questionnairesIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Utilisateurs',
        description: 'Gestion des comptes et des rôles.',
        href: usersIndex(),
        icon: UsersIcon,
    },
];
</script>

<template>
    <Head title="Administration" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <header>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Tableau de bord administrateur
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Accédez aux modules de gestion de CESIZen.
                </p>
            </header>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="section in sections"
                    :key="section.title"
                    :href="section.href"
                    class="group flex items-start gap-3 rounded-xl border border-border bg-card p-4 shadow-sm transition hover:border-primary/40 hover:shadow"
                >
                    <component
                        :is="section.icon"
                        class="mt-0.5 size-6 text-primary"
                        aria-hidden="true"
                    />
                    <div>
                        <h2
                            class="text-sm font-semibold group-hover:text-primary"
                        >
                            {{ section.title }}
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ section.description }}
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
