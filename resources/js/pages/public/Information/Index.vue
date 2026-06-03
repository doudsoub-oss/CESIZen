<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import {
    category as categoryRoute,
    content as contentRoute,
} from '@/routes/informations';
import type { CategorySummary } from '@/types';

defineProps<{
    categories: CategorySummary[];
}>();
</script>

<template>
    <Head title="Informations" />

    <PublicLayout>
        <section class="mx-auto w-full max-w-6xl px-4 py-10">
            <header class="mb-8">
                <h1 class="text-3xl font-semibold tracking-tight">
                    Informations
                </h1>
                <p class="mt-2 max-w-2xl text-muted-foreground">
                    Découvrez nos articles, ressources et conseils pour mieux
                    comprendre et gérer votre stress au quotidien.
                </p>
            </header>

            <div
                v-if="categories.length"
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <article
                    v-for="category in categories"
                    :key="category.id"
                    class="flex h-full flex-col rounded-2xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2 class="text-lg font-semibold">
                        <Link
                            :href="categoryRoute({ slug: category.slug })"
                            class="hover:underline"
                        >
                            {{ category.name }}
                        </Link>
                    </h2>
                    <p
                        v-if="category.description"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        {{ category.description }}
                    </p>

                    <ul
                        v-if="category.contents && category.contents.length"
                        class="mt-4 flex flex-col gap-1 text-sm"
                    >
                        <li
                            v-for="content in category.contents"
                            :key="content.id"
                        >
                            <Link
                                :href="
                                    contentRoute({
                                        category: category.slug,
                                        content: content.slug,
                                    })
                                "
                                class="text-primary hover:underline"
                            >
                                {{ content.title }}
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-muted-foreground italic">
                        Aucun contenu publié pour le moment.
                    </p>

                    <Link
                        :href="categoryRoute({ slug: category.slug })"
                        class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-medium text-primary"
                    >
                        Voir la catégorie
                        <ArrowRight class="size-4" />
                    </Link>
                </article>
            </div>
            <p v-else class="text-muted-foreground">
                Aucune catégorie active pour le moment.
            </p>
        </section>
    </PublicLayout>
</template>
