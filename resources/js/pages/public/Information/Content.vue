<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import {
    category as categoryRoute,
    index as informationsIndex,
} from '@/routes/informations';
import type { CategorySummary, ContentDetail } from '@/types';

defineProps<{
    category: CategorySummary;
    content: ContentDetail;
}>();

const formatPublishedAt = (raw: string | null): string | null => {
    if (!raw) {
        return null;
    }

    try {
        return new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'long',
        }).format(new Date(raw));
    } catch {
        return null;
    }
};
</script>

<template>
    <Head :title="content.title" />

    <PublicLayout>
        <article class="mx-auto w-full max-w-3xl px-4 py-10">
            <nav
                class="mb-4 flex flex-wrap items-center gap-1 text-sm text-muted-foreground"
                aria-label="Fil d'Ariane"
            >
                <Link :href="informationsIndex()" class="hover:underline">
                    Informations
                </Link>
                <ChevronRight class="size-3.5" />
                <Link
                    :href="categoryRoute({ slug: category.slug })"
                    class="hover:underline"
                >
                    {{ category.name }}
                </Link>
                <ChevronRight class="size-3.5" />
                <span class="text-foreground">{{ content.title }}</span>
            </nav>

            <header class="mb-6">
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ content.title }}
                </h1>
                <p
                    v-if="formatPublishedAt(content.published_at)"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    Publié le {{ formatPublishedAt(content.published_at) }}
                    <template v-if="content.author">
                        par {{ content.author.name }}
                    </template>
                </p>
                <p
                    v-if="content.excerpt"
                    class="mt-4 text-base text-muted-foreground"
                >
                    {{ content.excerpt }}
                </p>
            </header>

            <!-- Body is admin-authored Markdown rendered to sanitized HTML server-side. -->
            <div class="markdown-content" v-html="content.body_html" />
        </article>
    </PublicLayout>
</template>
