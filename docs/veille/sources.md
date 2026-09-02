# Veille — sources et rythmes

> Reprend le **Tableau 27** du dossier. *Une veille dont les conclusions ne sont
> pas consignées ne se distingue pas d'une veille qui n'a pas eu lieu* (4.5) :
> chaque information traitée donne lieu à une entrée datée dans
> [`journal.md`](journal.md).

| Domaine | Sources | Rythme | Débouché |
| :-- | :-- | :-- | :-- |
| Dépendances PHP | `composer audit` (chaîne CI, étape 6) · Dependabot `composer` · GitHub Advisory Database | à chaque exécution de chaîne + hebdomadaire | PR `type:dette` ; **blocage de la chaîne si critique** |
| Dépendances JavaScript | `npm audit` (chaîne CI, étape 6) · Dependabot `npm` · GitHub Advisory Database | à chaque exécution de chaîne + hebdomadaire | PR `type:dette` ; **blocage de la chaîne si critique** |
| Image et paquets système | Trivy sur l'image (chaîne CI, étape 9) · Dependabot `docker` | à chaque exécution de chaîne + hebdomadaire | PR `type:dette` ; **blocage si critique corrigeable** |
| Actions de la forge | Dependabot `github-actions` | hebdomadaire | PR `type:dette` |
| Fin de support (langage, cadriciel, SGBD) | php.net/supported-versions · laravel.com/docs/releases · postgresql.org/support/versioning | trimestriel | entrée au journal + planification de migration |
| Vulnérabilités générales | CERT-FR (cert.ssi.gouv.fr) · GitHub Advisory Database | hebdomadaire | entrée au journal ; ticket `type:anomalie`/`securite:*` si applicable |

## Principes

- **Applicabilité d'abord.** Une vulnérabilité n'est traitée que si le composant
  est **réellement utilisé** (et selon qu'il l'est en production ou seulement en
  développement/CI). L'analyse d'applicabilité est consignée au journal.
- **Décision explicite.** Chaque entrée conclut par *traiter*, *surveiller* ou
  *écarter*. Une décision « écarter » **sans motif écrit** est un défaut.
- **Traçabilité.** Les PR Dependabot portent `type:dette` ; les décisions et leur
  motif vivent dans `journal.md`.
