<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import {
    category as categoryRoute,
    index as informationsIndex,
} from '@/routes/informations';
import type { CategorySummary } from '@/types';

type CategoryDetail = CategorySummary & {
    parent: CategorySummary | null;
};

defineProps<{
    category: CategoryDetail;
}>();
</script>

<template>
    <Head :title="category.name" />

    <PublicLayout>
        <section class="mx-auto w-full max-w-5xl px-4 py-10">
            <nav
                class="mb-4 flex items-center gap-1 text-sm text-muted-foreground"
                aria-label="Fil d'Ariane"
            >
                <Link :href="informationsIndex()" class="hover:underline">
                    Informations
                </Link>
                <template v-if="category.parent">
                    <ChevronRight class="size-3.5" />
                    <Link
                        :href="categoryRoute({ slug: category.parent.slug })"
                        class="hover:underline"
                    >
                        {{ category.parent.name }}
                    </Link>
                </template>
                <ChevronRight class="size-3.5" />
                <span class="text-foreground">{{ category.name }}</span>
            </nav>

            <header class="mb-8">
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ category.name }}
                </h1>
                <p
                    v-if="category.description"
                    class="mt-2 max-w-2xl text-muted-foreground"
                >
                    {{ category.description }}
                </p>
            </header>

            <section
                v-if="category.children && category.children.length"
                class="mb-10"
            >
                <h2 class="mb-3 text-lg font-semibold">Sous-catégories</h2>
                <ul class="grid gap-3 sm:grid-cols-2">
                    <li v-for="child in category.children" :key="child.id">
                        <Link
                            :href="categoryRoute({ slug: child.slug })"
                            class="block rounded-lg border border-border bg-card p-4 transition hover:border-primary/40 hover:shadow"
                        >
                            <h3 class="text-sm font-semibold">
                                {{ child.name }}
                            </h3>
                            <p
                                v-if="child.description"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ child.description }}
                            </p>
                        </Link>
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="mb-3 text-lg font-semibold">Contenus</h2>
                <div
                    v-if="category.contents && category.contents.length"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <article
                        v-for="content in category.contents"
                        :key="content.id"
                        class="flex flex-col rounded-xl border border-border bg-card p-5 shadow-sm"
                    >
                        <h3 class="text-base font-semibold">
                            <Link
                                :href="
                                    categoryRoute({ slug: category.slug }) +
                                    '/' +
                                    content.slug
                                "
                                class="hover:underline"
                            >
                                {{ content.title }}
                            </Link>
                        </h3>
                        <p
                            v-if="content.excerpt"
                            class="mt-2 text-sm text-muted-foreground"
                        >
                            {{ content.excerpt }}
                        </p>
                        <Link
                            :href="
                                categoryRoute({ slug: category.slug }) +
                                '/' +
                                content.slug
                            "
                            class="mt-auto pt-3 text-sm font-medium text-primary hover:underline"
                        >
                            Lire l'article
                        </Link>
                    </article>
                </div>
                <p v-else class="text-muted-foreground italic">
                    Aucun contenu publié dans cette catégorie pour le moment.
                </p>
            </section>
        </section>
    </PublicLayout>
</template>
