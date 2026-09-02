<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
</script>

<template>
    <Head title="Politique de confidentialité" />

    <PublicLayout>
        <section class="mx-auto w-full max-w-3xl px-4 py-10">
            <header class="mb-8">
                <h1 class="text-3xl font-semibold tracking-tight">
                    Politique de confidentialité
                </h1>
                <p class="mt-2 text-muted-foreground">
                    Protection de vos données personnelles dans le cadre du
                    service CESIZen.
                </p>
            </header>

            <div class="space-y-8 text-sm leading-relaxed">
                <section>
                    <h2 class="mb-2 text-lg font-semibold">
                        Responsable de traitement
                    </h2>
                    <p>
                        Le responsable du traitement est le
                        <strong
                            >Ministère de la Santé et de la Prévention</strong
                        >. Pour toute question relative à vos données, vous
                        pouvez contacter le délégué à la protection des données
                        du Ministère.
                    </p>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">Finalités</h2>
                    <p>
                        Le service a pour finalité la sensibilisation à la santé
                        mentale et l'aide à la gestion du stress : consultation
                        de contenus d'information, réalisation de diagnostics de
                        stress et conservation facultative de leur historique.
                    </p>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">Bases légales</h2>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>
                            <strong>Article 6.1.e</strong> du RGPD — mission
                            d'intérêt public, pour le fonctionnement du service
                            et la gestion des comptes.
                        </li>
                        <li>
                            <strong>Article 9.2.a</strong> du RGPD —
                            <strong>consentement explicite</strong> pour la
                            conservation des résultats de diagnostic, qui sont
                            des données relatives à la santé.
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">
                        Catégories de données
                    </h2>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Données de compte : nom, adresse électronique.</li>
                        <li>
                            Données de santé : résultats de diagnostic (score,
                            interprétation, réponses), uniquement si vous
                            consentez à leur conservation.
                        </li>
                    </ul>
                    <p class="mt-2">
                        Les résultats de diagnostic conservés sont
                        <strong>chiffrés au repos</strong>.
                    </p>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">Destinataires</h2>
                    <p>
                        Vos données sont destinées aux seuls services habilités
                        du responsable de traitement. Elles ne sont ni vendues,
                        ni cédées à des tiers.
                    </p>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">
                        Durées de conservation
                    </h2>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>
                            Compte : supprimé après 24 mois d'inactivité, avec
                            un préavis à 23 mois.
                        </li>
                        <li>
                            Résultats de diagnostic : conservés tant que le
                            compte existe et que vous n'en demandez pas la
                            suppression.
                        </li>
                        <li>Journal d'audit : 12 mois glissants.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">
                        Transferts hors Union européenne
                    </h2>
                    <p>
                        <strong
                            >Aucun transfert de données hors de l'Union
                            européenne</strong
                        >
                        n'est réalisé.
                    </p>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">
                        Traceurs (cookies)
                    </h2>
                    <p>
                        Le service n'utilise que des cookies
                        <strong>strictement nécessaires</strong> à son
                        fonctionnement (session, préférences). Il n'emploie
                        <strong
                            >aucun traceur publicitaire ni de mesure d'audience
                            tierce</strong
                        >.
                    </p>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-semibold">
                        Exercice de vos droits
                    </h2>
                    <p>
                        Vous disposez d'un droit d'accès, de rectification,
                        d'effacement, d'opposition et à la portabilité de vos
                        données. Depuis votre espace personnel, vous pouvez
                        <strong>télécharger vos données</strong> et
                        <strong>supprimer</strong> un résultat de diagnostic ou
                        votre compte à tout moment. Vous pouvez également saisir
                        la CNIL.
                    </p>
                </section>
            </div>
        </section>
    </PublicLayout>
</template>
