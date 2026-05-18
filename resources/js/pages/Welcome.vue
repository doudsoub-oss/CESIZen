<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    HeartHandshake,
    Sparkles,
} from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { index as informationsIndex } from '@/routes/informations';

defineProps<{
    canRegister?: boolean;
}>();

const highlights = [
    {
        icon: BookOpen,
        title: 'Ressources fiables',
        description:
            'Des articles et fiches pratiques pour comprendre le stress et apprendre à le gérer.',
    },
    {
        icon: Sparkles,
        title: 'Diagnostic guidé',
        description:
            'Évaluez votre stress perçu avec un questionnaire validé scientifiquement (PSS-10).',
    },
    {
        icon: HeartHandshake,
        title: 'Confidentialité',
        description:
            'Les visiteurs peuvent réaliser le diagnostic anonymement ; seuls les utilisateurs connectés gardent un historique.',
    },
];
</script>

<template>
    <Head title="Accueil">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <PublicLayout>
        <section
            class="mx-auto flex w-full max-w-6xl flex-col gap-12 px-4 py-12 lg:py-20"
        >
            <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <p
                        class="mb-3 inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                    >
                        Ministère de la santé et de la prévention
                    </p>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                        Comprendre, mesurer et apaiser votre stress.
                    </h1>
                    <p class="mt-4 max-w-xl text-lg text-muted-foreground">
                        CESIZen vous accompagne pour identifier vos sources de
                        stress, mesurer votre niveau et découvrir des techniques
                        de relaxation accessibles à tous.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            href="/diagnostic"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
                        >
                            Lancer le diagnostic
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            :href="informationsIndex()"
                            class="inline-flex items-center gap-2 rounded-md border border-border px-4 py-2 text-sm font-medium hover:bg-accent"
                        >
                            Explorer les informations
                        </Link>
                    </div>
                </div>

                <div
                    class="relative rounded-3xl border border-border bg-gradient-to-br from-primary/10 via-card to-card p-8 shadow-sm"
                >
                    <div class="grid gap-4 sm:grid-cols-3">
                        <article
                            v-for="highlight in highlights"
                            :key="highlight.title"
                            class="flex flex-col items-start gap-3 rounded-2xl bg-background/60 p-4 shadow-sm backdrop-blur"
                        >
                            <component
                                :is="highlight.icon"
                                class="size-6 text-primary"
                                aria-hidden="true"
                            />
                            <h2 class="text-sm leading-tight font-semibold">
                                {{ highlight.title }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ highlight.description }}
                            </p>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
