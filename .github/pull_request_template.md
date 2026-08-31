<!--
Gabarit de demande de fusion CESIZen. Renseigne chaque section ; supprime les
lignes d'aide. Une PR ne fusionne qu'avec une chaîne verte et une revue (§2.7).
-->

## Ticket lié

<!-- Ex. : Closes #42 -->
Closes #

## Nature du changement

<!-- Cocher une seule case -->
- [ ] Anomalie (correction d'un défaut)
- [ ] Évolution (nouvelle fonctionnalité ou amélioration)
- [ ] Dette technique (maintenance, outillage, dépendances)
- [ ] Documentation

## Description

<!-- Ce que fait ce changement, et pourquoi. -->

## Risques traités / points de sécurité

<!-- Risque du dossier concerné (R1..R12) le cas échéant, ou « aucun ». -->

## Tests ajoutés

<!-- Quels tests couvrent ce changement ? Rappel : un test doit échouer avant le
     correctif et passer après. Coller la commande de filtre utilisée. -->
- [ ] Des tests ont été ajoutés ou mis à jour
- [ ] `php artisan test` est vert en local

## Impact migration

<!-- Si aucune migration : cocher « Sans migration ». Sinon, préciser le type
     selon la règle expansion/contraction (voir CONTRIBUTING.md §4). -->
- [ ] Sans migration
- [ ] **Expansion** (ajout rétrocompatible : colonne/table/index ajouté)
- [ ] **Contraction** (retrait : suppression/renommage — la version d'expansion
      correspondante est déjà déployée)

## Points d'attention pour la revue

<!-- Ce sur quoi tu veux que le relecteur porte son attention : choix
     structurant, zone fragile, décision d'architecture (ADR), accessibilité… -->
