<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{ url: string | null; label: string; active: boolean }>;
    lastPage: number;
}>();
</script>

<template>
    <nav
        v-if="lastPage > 1"
        class="mt-6 flex flex-wrap gap-1"
        aria-label="Pagination"
    >
        <template v-for="(link, idx) in links" :key="idx">
            <Link
                v-if="link.url"
                :href="link.url"
                class="rounded-md border border-border px-3 py-1.5 text-sm hover:bg-accent"
                :class="
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground hover:bg-primary'
                        : ''
                "
                preserve-scroll
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
</template>
