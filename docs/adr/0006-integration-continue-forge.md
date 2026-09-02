# ADR 0006 — Chaîne d'intégration intégrée à la forge

- **Statut :** accepté
- **Date :** 2026-09-02
- **Lot :** L13
- **Renvois dossier :** annexe C.1, Tableau 19

## Contexte

La qualité et la sécurité doivent être vérifiées à chaque évolution, de façon
reproductible. L'annexe C.1 retient une **chaîne d'intégration intégrée à la
forge** (GitHub Actions) plutôt qu'un serveur d'intégration auto-hébergé, jugé
disproportionné (à exploiter, mettre à jour, sécuriser). Le Tableau 19 fixe les
étapes attendues et leur caractère bloquant.

## Décision

**Chaîne d'intégration GitHub Actions**, reproduisant une à une les **neuf étapes
du Tableau 19** : détection de secrets, style, types, analyse statique, tests
(sur PostgreSQL, parité avec la production), audit des dépendances, contrôle du
Dockerfile, construction de l'image (arm64), analyse de vulnérabilités de
l'image ; agrégées par une vérification unique requise (« chaîne verte »).
Aucun exécuteur auto-hébergé.

## Conséquences

- Chaîne déclenchée à chaque poussée et demande de fusion ; aucun secret requis
  pour l'intégration (les secrets vivent dans les environnements de déploiement).
- Parité de test avec la production : PostgreSQL 16 en service, mêmes versions.
- Le déploiement en recette est **conditionné à la réussite complète** de la
  chaîne (L16).
- Contrepartie assumée : dépendance à la disponibilité de la forge et aux minutes
  d'exécution ; acceptable au regard du coût d'un exécuteur auto-hébergé.

## Alternatives écartées

- **Serveur d'intégration auto-hébergé (Jenkins, exécuteur self-hosted) :**
  écarté par C.1 comme disproportionné (exploitation, mises à jour, sécurité d'un
  service supplémentaire).
- **Vérifications uniquement locales (hooks) :** non reproductibles, contournables ;
  insuffisantes pour garantir la « chaîne verte » exigée avant déploiement.
