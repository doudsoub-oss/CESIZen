<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createMenu,
    destroy as destroyMenu,
    edit as editMenu,
    index as indexMenus,
} from '@/routes/admin/menus';
import type { BreadcrumbItem, Menu } from '@/types';

defineProps<{
    menus: Menu[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Menus', href: indexMenus() },
];

const locationLabels: Record<string, string> = {
    main: 'Principal',
    footer: 'Pied de page',
    sidebar: 'Latéral',
};

function confirmDelete(event: Event, message: string) {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Menus" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Menus</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ menus.length }} menu{{ menus.length > 1 ? 's' : '' }}
                        de navigation.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="createMenu()">
                        <Plus class="size-4" />
                        Nouveau menu
                    </Link>
                </Button>
            </header>

            <div
                class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/40 text-left text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Emplacement</th>
                            <th class="px-4 py-3 text-center">Entrées</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="menu in menus"
                            :key="menu.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ menu.name }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary">
                                    {{
                                        locationLabels[menu.location] ??
                                        menu.location
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ menu.items_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        as-child
                                        aria-label="Modifier"
                                    >
                                        <Link :href="editMenu(menu.id)">
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Link
                                        :href="destroyMenu(menu.id).url"
                                        method="delete"
                                        as="button"
                                        class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                        aria-label="Supprimer"
                                        @click="
                                            (event) =>
                                                confirmDelete(
                                                    event,
                                                    `Supprimer le menu « ${menu.name} » et ses entrées ?`,
                                                )
                                        "
                                    >
                                        <Trash2 class="size-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!menus.length">
                            <td
                                colspan="4"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                Aucun menu pour le moment.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
