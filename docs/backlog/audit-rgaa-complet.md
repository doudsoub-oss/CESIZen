# Audit RGAA complet — niveau AA

- **Type :** dette de conformité (accessibilité)
- **Priorité :** P2
- **Statut :** ouvert
- **Créé le :** 2026-09-01 (L10)
- **Engagement dossier :** §2.6 — « le niveau AA du référentiel général est visé ; l'audit
  n'a pas été conduit à ce jour et figure au plan d'action ».

## Contexte

La [déclaration d'accessibilité](../../resources/js/pages/public/Accessibility.vue) publiée
au L10 déclare un état **partiellement conforme**, sur la base d'une revue manuelle partielle
des cinq parcours principaux. Ce ticket porte l'audit complet promis par le dossier.

## Objectif

Mener un audit RGAA 4.1 complet visant le niveau AA sur l'ensemble des parcours, corriger
les non-conformités, puis mettre à jour la déclaration d'accessibilité en conséquence.

## Non-conformités déjà connues (à traiter)

- Libellés d'interface des pages de connexion et d'inscription en anglais alors que la langue
  déclarée est le français (RGAA 8.7) — chantier i18n.
- Contrastes de couleurs non vérifiés systématiquement sur tous les composants (RGAA 3.2, 3.3).
- Aucun test de restitution par lecteur d'écran (NVDA, VoiceOver).
- Navigation clavier non validée exhaustivement sur les composants interactifs riches.

## Déjà corrigé au L10 (faible coût, fort impact)

- Annonce des erreurs de formulaire aux technologies d'assistance (`role="alert"` sur
  `InputError`) — impacte tous les formulaires.
- Vocalisation dynamique du compteur de questions restantes du diagnostic (`aria-live`).

## Charge estimée

- Audit outillé (axe-core / Playwright) + audit manuel des 106 critères : **3 j**
- Correctifs de non-conformités (contrastes, i18n auth, ARIA, clavier) : **4 à 6 j**
- Re-test et mise à jour de la déclaration : **1 j**
- **Total : ≈ 8 à 10 jours-homme**

## Critères de clôture

- [ ] Audit RGAA 4.1 complet réalisé et taux de conformité mesuré
- [ ] Non-conformités bloquantes corrigées
- [ ] Déclaration d'accessibilité mise à jour (état, méthode, date, taux)

> À promouvoir en issue GitHub lors du L21 (gabarits d'issues), en conservant ce contenu.
