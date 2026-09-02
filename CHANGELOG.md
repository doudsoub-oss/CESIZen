# Journal des modifications

Toutes les évolutions notables de CESIZen sont consignées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le
projet respecte le [versionnement sémantique](https://semver.org/lang/fr/) : une
étiquette `vMAJEUR.MINEUR.CORRECTIF` est posée à chaque mise en production, avec
une note de version récapitulant les tickets clos et **les correctifs de sécurité
en tête** (dossier 3.3).

## [Non publié]

## [0.1.0] - 2026-09-02

Première version consolidée : application fonctionnelle (Bloc 2) et mise en
conformité sécurité/déploiement (Bloc 3).

### Ajouté

- **Module Comptes** : inscription, connexion, double authentification (Fortify),
  gestion de profil, administration des utilisateurs et des rôles.
- **Module Informations** : catégories et contenus, pages publiques, menus.
- **Module Diagnostics** : questionnaire de stress perçu, exécution anonyme ou
  authentifiée, historique, interprétation des résultats.
- **Droit à la portabilité** (RGPD, article 20) : export JSON des données de la
  personne connectée.
- **Déclaration d'accessibilité** RGAA et améliorations ARIA (L10).
- **Chaîne d'intégration continue** en neuf étapes (L13) et **analyse statique**
  par paliers (L14).
- **Chaîne de déploiement** en recette avec retour arrière (L16) ; chaîne de
  production **décrite en cible** (L17).
- **Sauvegardes chiffrées** AES-256, rétention 30 jours, 3-2-1 (L18) ;
  **restauration éprouvée** avec registre (L19).
- **Sonde de disponibilité** Uptime Kuma, `/up` vérifiant l'accès à la base (L20).
- **Suivi des demandes** (formulaires d'issue, étiquettes, L21) et **veille
  outillée** (Dependabot, journal, L22).
- **Journal des décisions d'architecture** (`docs/adr/`) et documentation
  d'exploitation.

### Modifié

- **Durcissement du transport** : en-têtes de sécurité, CSP, forçage HTTPS (L04).
- **Cloisonnement des environnements** : robots.txt en recette, garde
  d'environnement (L06).
- **Durées de conservation** : préavis d'inactivité, purge des comptes inactifs
  et du journal d'audit (L09).
- **Persistance** sessions/cache/files sur PostgreSQL (voir ADR 0003).

### Sécurité

- **Chiffrement applicatif au repos** des résultats de diagnostic (donnée de
  santé, article 9 RGPD), clé hors base (L05, ADR 0002).
- **Consentement** à l'information et traçabilité (L08).
- **Limitation de débit** sur l'authentification et l'export (L03).
- **Détection de secrets** (gitleaks) et **audit des dépendances** bloquant au
  niveau critique dans la chaîne d'intégration.
- **Journal d'audit** des actions sensibles (authentification, 2FA, données de
  compte).

[Non publié]: https://github.com/doudsoub-oss/CESIZen/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/doudsoub-oss/CESIZen/releases/tag/v0.1.0
