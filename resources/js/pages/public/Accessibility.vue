<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

defineProps<{
    etabliLe: string;
}>();
</script>

<template>
    <Head title="Déclaration d'accessibilité" />

    <PublicLayout>
        <article class="mx-auto w-full max-w-3xl px-4 py-10">
            <header class="mb-8">
                <h1 class="text-3xl font-semibold tracking-tight">
                    Déclaration d'accessibilité
                </h1>
                <p class="mt-4 text-base text-muted-foreground">
                    Le Ministère de la santé et de la prévention s'engage à
                    rendre CESIZen accessible, conformément à l'article 47 de la
                    loi n° 2005-102 du 11 février 2005.
                </p>
            </header>

            <section class="space-y-3">
                <h2 class="text-xl font-semibold">État de conformité</h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    CESIZen est
                    <strong class="text-foreground"
                        >partiellement conforme</strong
                    >
                    avec le Référentiel général d'amélioration de
                    l'accessibilité (RGAA, version 4.1), en raison des
                    non-conformités énumérées ci-dessous. Le niveau visé est le
                    niveau AA.
                </p>
            </section>

            <section class="mt-8 space-y-3">
                <h2 class="text-xl font-semibold">
                    Résultats des tests et méthode d'évaluation
                </h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    <strong class="text-foreground"
                        >Aucun audit RGAA complet n'a été conduit à ce
                        jour.</strong
                    >
                    Une revue manuelle partielle a été menée sur les cinq
                    parcours principaux : page d'accueil, inscription,
                    connexion, questionnaire de diagnostic et espace personnel.
                    Les correctifs à faible coût et à fort impact identifiés
                    lors de cette revue ont été appliqués. L'audit complet reste
                    inscrit au plan d'action.
                </p>
            </section>

            <section class="mt-8 space-y-3">
                <h2 class="text-xl font-semibold">
                    Contenus non accessibles connus
                </h2>
                <ul
                    class="list-disc space-y-2 pl-6 text-sm leading-relaxed text-muted-foreground"
                >
                    <li>
                        Les libellés d'interface des pages de connexion et
                        d'inscription sont rédigés en anglais alors que la
                        langue de la page est déclarée en français (RGAA 8.7).
                    </li>
                    <li>
                        Les contrastes de couleurs n'ont pas été vérifiés de
                        manière systématique sur l'ensemble des composants (RGAA
                        3.2, 3.3).
                    </li>
                    <li>
                        Aucun test de restitution par lecteur d'écran (NVDA,
                        VoiceOver) n'a été réalisé sur l'ensemble des parcours.
                    </li>
                    <li>
                        La navigation exclusivement au clavier n'a pas été
                        validée de façon exhaustive sur les composants
                        interactifs riches.
                    </li>
                </ul>
            </section>

            <section class="mt-8 space-y-3">
                <h2 class="text-xl font-semibold">
                    Établissement de cette déclaration
                </h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Cette déclaration a été établie le
                    <strong class="text-foreground">{{ etabliLe }}</strong
                    >, sur la base d'une revue manuelle partielle. Elle sera
                    mise à jour à l'issue de l'audit RGAA complet.
                </p>
            </section>

            <section class="mt-8 space-y-3">
                <h2 class="text-xl font-semibold">Retour d'information</h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Si vous constatez un défaut d'accessibilité vous empêchant
                    d'accéder à un contenu ou une fonctionnalité, vous pouvez le
                    signaler à l'administrateur du site via les coordonnées
                    figurant dans les
                    <a
                        href="/pages/mentions-legales"
                        class="text-foreground underline underline-offset-4 hover:decoration-current"
                        >mentions légales</a
                    >. Nous nous efforçons de vous répondre dans les meilleurs
                    délais.
                </p>
            </section>

            <section class="mt-8 space-y-3">
                <h2 class="text-xl font-semibold">Voies de recours</h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Si vous ne parvenez pas à obtenir une réponse de notre part,
                    vous êtes en droit de saisir le Défenseur des droits :
                </p>
                <ul
                    class="list-disc space-y-2 pl-6 text-sm leading-relaxed text-muted-foreground"
                >
                    <li>
                        via le formulaire en ligne
                        <a
                            href="https://formulaire.defenseurdesdroits.fr/"
                            rel="noopener noreferrer"
                            target="_blank"
                            class="text-foreground underline underline-offset-4 hover:decoration-current"
                            >formulaire.defenseurdesdroits.fr</a
                        >&nbsp;;
                    </li>
                    <li>
                        par courrier gratuit, sans affranchissement : Défenseur
                        des droits, Libre réponse 71120, 75342 Paris CEDEX 07.
                    </li>
                </ul>
            </section>
        </article>
    </PublicLayout>
</template>
