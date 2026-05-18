<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const status = computed(() => page.props.flash?.status ?? null);
const error = computed(() => page.props.flash?.error ?? null);
</script>

<template>
    <div
        v-if="status || error"
        class="px-4 pt-4"
        role="status"
        aria-live="polite"
    >
        <div
            v-if="status"
            class="flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
        >
            <CheckCircle2 class="size-4" aria-hidden="true" />
            <span>{{ status }}</span>
        </div>
        <div
            v-if="error"
            class="flex items-center gap-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100"
        >
            <TriangleAlert class="size-4" aria-hidden="true" />
            <span>{{ error }}</span>
        </div>
    </div>
</template>
