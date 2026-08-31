<script setup lang="ts">
import { onMounted, ref } from 'vue';

/**
 * Bandeau d'information sur les traceurs (L08). INFORMATIF : le service n'emploie
 * aucun traceur publicitaire ni de mesure d'audience tierce, seulement des
 * cookies techniques. Il n'y a donc pas de consentement à recueillir, et pas de
 * faux boutons « accepter / refuser » — seulement une prise de connaissance.
 */
const STORAGE_KEY = 'cesizen-cookie-notice-dismissed';
const visible = ref(false);

onMounted(() => {
    try {
        visible.value = localStorage.getItem(STORAGE_KEY) !== '1';
    } catch {
        visible.value = true;
    }
});

function dismiss() {
    visible.value = false;

    try {
        localStorage.setItem(STORAGE_KEY, '1');
    } catch {
        // Stockage indisponible : le bandeau réapparaîtra, sans conséquence.
    }
}
</script>

<template>
    <div
        v-if="visible"
        class="fixed inset-x-0 bottom-0 z-50 border-t border-border bg-background/95 p-4 backdrop-blur"
        role="region"
        aria-label="Information sur les cookies"
    >
        <div
            class="mx-auto flex max-w-4xl flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-muted-foreground">
                Ce site n'utilise que des cookies strictement nécessaires à son
                fonctionnement. Aucun traceur publicitaire ni de mesure
                d'audience tierce n'est employé.
                <a
                    href="/politique-de-confidentialite"
                    class="underline underline-offset-4 hover:text-foreground"
                    >En savoir plus</a
                >.
            </p>
            <button
                type="button"
                class="shrink-0 rounded-md border border-input bg-background px-4 py-2 font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                @click="dismiss"
            >
                J'ai compris
            </button>
        </div>
    </div>
</template>
