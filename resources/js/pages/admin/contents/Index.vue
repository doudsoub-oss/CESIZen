<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createContent,
    destroy as destroyContent,
    edit as editContent,
    index as indexContents,
} from '@/routes/admin/contents';
import type { BreadcrumbItem, ContentRow, Paginator } from '@/types';

defineProps<{
    contents: Paginator<ContentRow>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Contenus', href: indexContents() },
];

const typeLabels: Record<string, string> = {
    page: 'Page',
    article: 'Article',
    resource: 'Ressource',
};

function confirmDelete(event: Event, message: string) {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Contenus" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Contenus
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ contents.total }} contenu{{
                            contents.total > 1 ? 's' : ''
                        }}.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="createContent()">
                        <Plus class="size-4" />
                        Nouveau contenu
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
                            <th class="px-4 py-3">Titre</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">État</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="content in contents.data"
                            :key="content.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ content.title }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ content.category?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ typeLabels[content.type] ?? content.type }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        content.is_published
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        content.is_published
                                            ? 'Publié'
                                            : 'Brouillon'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        as-child
                                        aria-label="Modifier"
                                    >
                                        <Link :href="editContent(content.id)">
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Link
                                        :href="destroyContent(content.id).url"
                                        method="delete"
                                        as="button"
                                        class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                        aria-label="Supprimer"
                                        @click="
                                            (event) =>
                                                confirmDelete(
                                                    event,
                                                    `Supprimer le contenu « ${content.title} » ?`,
                                                )
                                        "
                                    >
                                        <Trash2 class="size-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!contents.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                Aucun contenu pour le moment.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :links="contents.links"
                :last-page="contents.last_page"
            />
        </div>
    </AdminLayout>
</template>
