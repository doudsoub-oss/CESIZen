<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();

// Hide on the landing page, where there is no in-app "previous" location.
const showBack = computed(() => page.url !== '/' && page.url !== '');

function goBack(): void {
    window.history.back();
}
</script>

<template>
    <button
        v-if="showBack"
        type="button"
        class="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
        aria-label="Revenir à la page précédente"
        @click="goBack"
    >
        <ChevronLeft class="size-4" />
        <span class="hidden sm:inline">Retour</span>
    </button>
</template>
