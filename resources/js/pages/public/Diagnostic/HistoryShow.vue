<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { history } from '@/routes/diagnostic';
import type { Diagnostic } from '@/types';

const props = defineProps<{
    diagnostic: Diagnostic;
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

function colorClass(color: string | null | undefined): string {
    switch (color) {
        case 'green':
            return 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100';
        case 'yellow':
            return 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100';
        case 'red':
            return 'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100';
        default:
            return 'border-border bg-card';
    }
}

const interpretation = props.diagnostic.resultInterpretation ?? null;
</script>

<template>
    <Head title="Détail du diagnostic" />

    <PublicLayout>
        <section class="mx-auto w-full max-w-3xl px-4 py-10">
            <Link
                :href="history()"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à l'historique
            </Link>

            <header class="mb-6">
                <p
                    class="text-sm tracking-wider text-muted-foreground uppercase"
                >
                    {{ diagnostic.questionnaire?.title ?? '—' }}
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Score : {{ diagnostic.score_total }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ formatDate(diagnostic.completed_at) }}
                </p>
            </header>

            <article
                v-if="interpretation"
                :class="[
                    'mb-8 rounded-2xl border p-5 shadow-sm',
                    colorClass(interpretation.color),
                ]"
            >
                <h2 class="text-lg font-semibold">
                    {{ interpretation.title }}
                </h2>
                <p class="mt-2 text-sm whitespace-pre-line">
                    {{ interpretation.description }}
                </p>
                <p
                    v-if="interpretation.recommendations"
                    class="mt-3 rounded-md bg-white/50 p-3 text-sm whitespace-pre-line dark:bg-black/20"
                >
                    {{ interpretation.recommendations }}
                </p>
            </article>

            <section
                v-if="diagnostic.responses && diagnostic.responses.length"
                class="rounded-xl border border-border bg-card p-5 shadow-sm"
            >
                <h2 class="mb-4 text-lg font-semibold">Vos réponses</h2>
                <ol class="flex flex-col gap-3">
                    <li
                        v-for="(response, idx) in diagnostic.responses"
                        :key="response.id"
                        class="rounded-md border border-border p-3 text-sm"
                    >
                        <p class="font-medium">
                            {{ idx + 1 }}. {{ response.question?.text ?? '—' }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            Réponse :
                            <span class="text-foreground">
                                {{ response.answerOption?.label ?? '—' }}
                            </span>
                            <span class="ml-2 text-xs">
                                ({{ response.score }} pt{{
                                    response.score > 1 ? 's' : ''
                                }})
                            </span>
                        </p>
                    </li>
                </ol>
            </section>
        </section>
    </PublicLayout>
</template>
