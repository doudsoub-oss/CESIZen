<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { show as showDiagnostic } from '@/routes/diagnostic';
import type { Questionnaire } from '@/types';

defineProps<{
    questionnaires: Questionnaire[];
}>();
</script>

<template>
    <Head title="Diagnostic" />

    <PublicLayout>
        <section class="mx-auto w-full max-w-4xl px-4 py-10">
            <header class="mb-8">
                <h1 class="text-3xl font-semibold tracking-tight">
                    Choisissez un diagnostic
                </h1>
                <p class="mt-2 max-w-2xl text-muted-foreground">
                    Plusieurs questionnaires sont disponibles. Choisissez celui
                    qui correspond à votre besoin pour évaluer votre niveau de
                    stress.
                </p>
            </header>

            <div v-if="questionnaires.length" class="grid gap-4 sm:grid-cols-2">
                <article
                    v-for="questionnaire in questionnaires"
                    :key="questionnaire.id"
                    class="flex h-full flex-col rounded-2xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2 class="text-lg font-semibold">
                        {{ questionnaire.title }}
                    </h2>
                    <p
                        v-if="questionnaire.description"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        {{ questionnaire.description }}
                    </p>
                    <Link
                        :href="showDiagnostic({ slug: questionnaire.slug })"
                        class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-medium text-primary"
                    >
                        Commencer
                        <ArrowRight class="size-4" />
                    </Link>
                </article>
            </div>
            <p v-else class="text-muted-foreground italic">
                Aucun diagnostic n'est disponible pour le moment.
            </p>
        </section>
    </PublicLayout>
</template>
