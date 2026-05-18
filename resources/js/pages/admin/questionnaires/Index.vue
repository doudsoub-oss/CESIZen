<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createQuestionnaire,
    destroy as destroyQuestionnaire,
    edit as editQuestionnaire,
    index as indexQuestionnaires,
} from '@/routes/admin/questionnaires';
import type { Paginator, Questionnaire, BreadcrumbItem } from '@/types';

defineProps<{
    questionnaires: Paginator<Questionnaire>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Questionnaires', href: indexQuestionnaires() },
];

function confirmDelete(event: Event, message: string) {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Questionnaires" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Questionnaires
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ questionnaires.total }} questionnaire{{
                            questionnaires.total > 1 ? 's' : ''
                        }}.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="createQuestionnaire()">
                        <Plus class="size-4" />
                        Nouveau questionnaire
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
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3">État</th>
                            <th class="px-4 py-3 text-center">Questions</th>
                            <th class="px-4 py-3 text-center">Interprét.</th>
                            <th class="px-4 py-3 text-center">Diagnostics</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="q in questionnaires.data"
                            :key="q.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ q.title }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ q.slug }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        q.is_active ? 'default' : 'secondary'
                                    "
                                >
                                    {{ q.is_active ? 'Actif' : 'Inactif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ q.questions_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ q.interpretations_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ q.diagnostics_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        as-child
                                        aria-label="Modifier"
                                    >
                                        <Link :href="editQuestionnaire(q.id)">
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Link
                                        :href="destroyQuestionnaire(q.id).url"
                                        method="delete"
                                        as="button"
                                        class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                        aria-label="Supprimer"
                                        @click="
                                            (event) =>
                                                confirmDelete(
                                                    event,
                                                    `Supprimer le questionnaire « ${q.title} » ?`,
                                                )
                                        "
                                    >
                                        <Trash2 class="size-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!questionnaires.data.length">
                            <td
                                colspan="7"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                Aucun questionnaire pour le moment.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
