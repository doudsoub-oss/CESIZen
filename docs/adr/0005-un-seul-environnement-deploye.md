# ADR 0005 — Un seul environnement déployé : la recette

- **Statut :** accepté
- **Date :** 2026-09-02
- **Lot :** L11–L17 (chaîne de déploiement)
- **Renvois dossier :** 3.2, Tableau 16

## Contexte

Le dossier assume explicitement (3.2, Tableau 16) que **seule la recette est
déployée**. La production est **décrite en cible** mais non mise en service : sa
mise en route relève d'une décision du Ministère. Il fallait décider comment
traiter cette limite : la masquer (simuler une production) ou l'assumer et
construire la chaîne en conséquence.

## Décision

**Assumer honnêtement un unique environnement déployé, la recette.** La chaîne de
déploiement en recette est réelle et automatique (L16) ; la chaîne de production
est fournie **en cible** (L17), sans secrets et avec approbation manuelle, sans
jamais simuler un déploiement.

## Conséquences

- La compétence évaluée (déploiement de la recette, retour arrière) est démontrée
  **réellement**, pas simulée.
- Le workflow de production existe comme documentation exécutable (séquence,
  double étiquette, sauvegarde avant migration) mais reste inerte tant que
  l'environnement « production » n'est pas approvisionné en secrets.
- Cette limite est **documentée**, pas dissimulée (cohérent avec la déclaration
  d'accessibilité L10 et la posture générale du dossier).
- Le retour arrière repose sur le redéploiement d'une étiquette antérieure et la
  règle expansion/contraction (L16), applicable dès la première version étiquetée.

## Alternatives écartées

- **Simuler une production :** écartée — malhonnête, et contraire à ce que le
  Tableau 16 annonce (production « décrite en cible »).
- **Ne rien prévoir pour la production :** écartée — la cible doit être décrite
  (séquence, garanties) pour qu'une mise en service future soit maîtrisée.
- **Déployer réellement une production sans mandat :** hors du périmètre décidé
  par le Ministère.
