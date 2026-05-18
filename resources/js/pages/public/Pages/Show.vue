<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { ContentDetail } from '@/types';

defineProps<{
    content: ContentDetail;
}>();
</script>

<template>
    <Head :title="content.title" />

    <PublicLayout>
        <article class="mx-auto w-full max-w-3xl px-4 py-10">
            <header class="mb-6">
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ content.title }}
                </h1>
                <p
                    v-if="content.excerpt"
                    class="mt-4 text-base text-muted-foreground"
                >
                    {{ content.excerpt }}
                </p>
            </header>
            <div
                class="prose prose-neutral dark:prose-invert max-w-none whitespace-pre-line"
            >
                {{ content.body }}
            </div>
        </article>
    </PublicLayout>
</template>
