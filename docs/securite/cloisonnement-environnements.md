# Cloisonnement des environnements (lot L06)

> Empêche l'exposition d'un environnement hors production. Traite **R10**
> (débogage actif, données réelles ou indexation en recette). Voir aussi le
> Tableau 16.

## 1. Garde-fou de débogage

`App\Support\EnvironmentGuard::ensureDebugIsDisabled()`, appelé au tout début de
`AppServiceProvider::boot()`, **lève une `RuntimeException` et interrompt le
démarrage** si l'environnement est `production`, `staging` ou `recette` **et** que
`APP_DEBUG` est vrai.

Un débogage actif hors développement doit **empêcher l'application de démarrer**,
pas produire un simple avertissement. En `local` et `testing`, le débogage reste
autorisé.

> **APP_ENV=local hors poste de développement.** Il n'existe pas de signal
> technique fiable pour distinguer « poste de développement » d'« serveur » à
> partir de la seule valeur `APP_ENV=local`. Le contrôle est donc **procédural** :
> la chaîne de déploiement (L12/L16) fixe explicitement `APP_ENV=recette` (ou
> `production`), et le garde-fou de débogage ci-dessus est le verrou technique qui
> rattrape l'erreur la plus dommageable — un environnement déployé en mode
> débogage.

## 2. Pages d'erreur

Le garde-fou garantit `APP_DEBUG=false` en recette : les pages d'erreur 500 y sont
donc les pages génériques de Laravel, **sans trace, ni variable, ni requête**.
C'est une conséquence structurelle du garde-fou, pas un réglage séparé.

## 3. Non-indexation de la recette

Deux dispositifs, actifs **uniquement** quand `APP_ENV=recette` :

- **En-tête** `X-Robots-Tag: noindex, nofollow` ajouté par le middleware
  `EnTetesDeSecurite`.
- **Route** `/robots.txt` renvoyant `Disallow: /` en recette, et un fichier
  permissif (`Disallow:`) ailleurs.

## 4. Jeu de données de recette

`Database\Seeders\RecetteSeeder` produit un jeu **intégralement fictif** :

- comptes générés par les factories, tous avec des adresses **non routables**
  (`safeEmail` → `@example.org/.net/.com`, réservés) — aucune adresse susceptible
  d'exister ;
- un administrateur, un super-administrateur, des comptes usagers ;
- des contenus éditoriaux ;
- un questionnaire complet et scorable, avec des diagnostics rattachés à des
  comptes.

Il **refuse de s'exécuter en production** (garde en tête de `run()` levant une
`RuntimeException`).

> **Ordre d'exécution (rappel du plan).** Le chiffrement des diagnostics (L05)
> doit précéder tout peuplement de la recette. Ne lancer `RecetteSeeder` en
> recette qu'après L05.

Exécution en recette :

```bash
php artisan db:seed --class=Database\\Seeders\\RecetteSeeder --force
```

## 5. Tests

`tests/Feature/Securite/EnvironmentIsolationTest.php` :
- le garde-fou lève l'exception en production/staging/recette avec débogage ;
- il laisse passer local/testing, et les environnements déployés sans débogage ;
- l'en-tête `noindex` est présent en recette, absent ailleurs ;
- `/robots.txt` interdit tout en recette, permissif ailleurs ;
- le seeder refuse de tourner en production ;
- le jeu produit est fictif (adresses non routables, diagnostics rattachés).
