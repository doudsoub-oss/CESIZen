<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { index as diagnosticIndex } from '@/routes/diagnostic';
import { show as showHistoryItem } from '@/routes/diagnostic/history';
import type { Diagnostic, Paginator } from '@/types';

defineProps<{
    diagnostics: Paginator<Diagnostic>;
}>();

function formatDate(raw: string | null): string {
    if (!raw) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'long',
            timeStyle: 'short',
        }).format(new Date(raw));
    } catch {
        return raw;
    }
}

function colorDot(color: string | null | undefined): string {
    switch (color) {
        case 'green':
            return 'bg-emerald-500';
        case 'yellow':
            return 'bg-amber-500';
        case 'red':
            return 'bg-red-500';
        default:
            return 'bg-muted-foreground';
    }
}
</script>

<template>
    <Head title="Mon historique" />

    <PublicLayout>
        <section class="mx-auto w-full max-w-4xl px-4 py-10">
            <header class="mb-6 flex items-end justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">
                        Mon historique
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ diagnostics.total }} diagnostic{{
                            diagnostics.total > 1 ? 's' : ''
                        }}
                        enregistré{{ diagnostics.total > 1 ? 's' : '' }}.
                    </p>
                </div>
                <Link
                    :href="diagnosticIndex()"
                    class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground hover:bg-primary/90"
                >
                    Nouveau diagnostic
                </Link>
            </header>

            <div
                v-if="diagnostics.data.length"
                class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
            >
                <ul class="divide-y divide-border">
                    <li
                        v-for="diagnostic in diagnostics.data"
                        :key="diagnostic.id"
                        class="flex items-center justify-between gap-4 p-4"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold">
                                {{ diagnostic.questionnaire?.title ?? '—' }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(diagnostic.completed_at) }}
                            </p>
                            <p
                                v-if="diagnostic.result_interpretation"
                                class="mt-1 flex items-center gap-2 text-sm"
                            >
                                <span
                                    class="inline-block size-2 rounded-full"
                                    :class="
                                        colorDot(
                                            diagnostic.result_interpretation
                                                .color,
                                        )
                                    "
                                    aria-hidden="true"
                                />
                                {{ diagnostic.result_interpretation.title }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="text-lg font-bold">
                                {{ diagnostic.score_total }}
                            </span>
                            <Link
                                :href="showHistoryItem(diagnostic.id)"
                                class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                            >
                                Détails
                                <ArrowRight class="size-4" />
                            </Link>
                        </div>
                    </li>
                </ul>
            </div>
            <p v-else class="text-muted-foreground italic">
                Aucun diagnostic enregistré pour le moment.
            </p>

            <nav
                v-if="diagnostics.last_page > 1"
                class="mt-6 flex flex-wrap gap-1"
                aria-label="Pagination"
            >
                <template v-for="(link, idx) in diagnostics.links" :key="idx">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border border-border px-3 py-1.5 text-sm hover:bg-accent"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground hover:bg-primary'
                                : ''
                        "
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="rounded-md px-3 py-1.5 text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </section>
    </PublicLayout>
</template>
