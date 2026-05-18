<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Save, UserPlus } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { register } from '@/routes';
import { history, index as diagnosticIndex } from '@/routes/diagnostic';
import type { Questionnaire, ResultInterpretation } from '@/types';

const props = defineProps<{
    questionnaire: Questionnaire;
    score: number;
    interpretation: ResultInterpretation | null;
    saved: boolean;
    diagnosticId: number | null;
}>();

const colorClass = (color: string | null | undefined): string => {
    switch (color) {
        case 'green':
            return 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100';
        case 'yellow':
            return 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100';
        case 'red':
            return 'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100';
        default:
            return 'border-border bg-card text-foreground';
    }
};

const icon = (color: string | null | undefined): string => {
    switch (color) {
        case 'green':
            return '✓';
        case 'yellow':
            return '!';
        case 'red':
            return '⚠';
        default:
            return '•';
    }
};

void props;
</script>

<template>
    <Head title="Résultat du diagnostic" />

    <PublicLayout>
        <section class="mx-auto w-full max-w-3xl px-4 py-10">
            <header class="mb-6">
                <p
                    class="text-sm tracking-wider text-muted-foreground uppercase"
                >
                    Résultat — {{ questionnaire.title }}
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Votre score : {{ score }}
                </h1>
            </header>

            <article
                v-if="interpretation"
                :class="[
                    'rounded-2xl border p-6 shadow-sm',
                    colorClass(interpretation.color),
                ]"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="inline-flex size-9 items-center justify-center rounded-full bg-white/60 text-lg font-bold dark:bg-black/30"
                        aria-hidden="true"
                    >
                        {{ icon(interpretation.color) }}
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold">
                            {{ interpretation.title }}
                        </h2>
                        <p class="mt-1 text-sm opacity-70">
                            Score entre
                            {{ interpretation.min_score }} et
                            {{ interpretation.max_score }}
                        </p>
                    </div>
                </div>

                <p class="mt-4 text-sm whitespace-pre-line">
                    {{ interpretation.description }}
                </p>

                <div
                    v-if="interpretation.recommendations"
                    class="mt-4 rounded-md bg-white/50 p-4 text-sm dark:bg-black/20"
                >
                    <h3 class="mb-1 font-semibold">Nos recommandations</h3>
                    <p class="whitespace-pre-line">
                        {{ interpretation.recommendations }}
                    </p>
                </div>
            </article>
            <p
                v-else
                class="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground"
            >
                Aucune interprétation disponible pour ce score. Contactez un
                administrateur si le problème persiste.
            </p>

            <div
                v-if="saved"
                class="mt-6 flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
            >
                <Save class="size-4" aria-hidden="true" />
                <span>
                    Votre diagnostic a été enregistré dans votre historique.
                </span>
            </div>

            <aside
                v-else
                class="mt-6 rounded-md border border-dashed border-border bg-muted/40 p-4 text-sm"
            >
                <p class="flex items-start gap-2">
                    <UserPlus
                        class="mt-0.5 size-4 text-primary"
                        aria-hidden="true"
                    />
                    <span>
                        Vous êtes en visite anonyme. Pour conserver l'historique
                        de vos diagnostics et suivre leur évolution,
                        <Link
                            :href="register()"
                            class="font-medium text-primary hover:underline"
                            >créez un compte</Link
                        >.
                    </span>
                </p>
            </aside>

            <div class="mt-8 flex flex-wrap gap-3">
                <Link
                    :href="diagnosticIndex()"
                    class="rounded-md border border-border px-4 py-2 text-sm hover:bg-accent"
                >
                    Refaire un diagnostic
                </Link>
                <Link
                    v-if="saved"
                    :href="history()"
                    class="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90"
                >
                    Voir mon historique
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
