<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, EyeOff, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createCategory,
    destroy as destroyCategory,
    edit as editCategory,
    index as indexCategories,
    toggleActive,
} from '@/routes/admin/categories';
import type { BreadcrumbItem, CategoryRow, Paginator } from '@/types';

defineProps<{
    categories: Paginator<CategoryRow>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Catégories', href: indexCategories() },
];

function confirmDelete(event: Event, message: string) {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Catégories" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Catégories
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ categories.total }} catégorie{{
                            categories.total > 1 ? 's' : ''
                        }}.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="createCategory()">
                        <Plus class="size-4" />
                        Nouvelle catégorie
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
                            <th class="px-4 py-3">Parent</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-center">Contenus</th>
                            <th class="px-4 py-3">État</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="category in categories.data"
                            :key="category.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ category.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ category.parent?.name ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ category.slug }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ category.contents_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        category.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        category.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <Link
                                        :href="toggleActive(category.id).url"
                                        method="patch"
                                        as="button"
                                        preserve-scroll
                                        class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                        :aria-label="
                                            category.is_active
                                                ? 'Désactiver'
                                                : 'Activer'
                                        "
                                    >
                                        <component
                                            :is="
                                                category.is_active
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="size-4"
                                        />
                                    </Link>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        as-child
                                        aria-label="Modifier"
                                    >
                                        <Link :href="editCategory(category.id)">
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Link
                                        :href="destroyCategory(category.id).url"
                                        method="delete"
                                        as="button"
                                        class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                        aria-label="Supprimer"
                                        @click="
                                            (event) =>
                                                confirmDelete(
                                                    event,
                                                    `Supprimer la catégorie « ${category.name} » ? Les contenus liés seront détachés.`,
                                                )
                                        "
                                    >
                                        <Trash2 class="size-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!categories.data.length">
                            <td
                                colspan="6"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                Aucune catégorie pour le moment.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :links="categories.links"
                :last-page="categories.last_page"
            />
        </div>
    </AdminLayout>
</template>
