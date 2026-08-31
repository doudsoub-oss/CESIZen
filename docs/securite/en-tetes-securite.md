# Durcissement du transport et en-têtes (lot L04)

> Applique les dispositifs du Tableau 10 côté application. Traite **R5**
> (interception du transport) et **R12** (en-têtes de sécurité manquants).

## Vue d'ensemble

- Middleware `app/Http/Middleware/EnTetesDeSecurite.php`, ajouté au groupe `web`
  (`bootstrap/app.php`).
- Confiance des mandataires : `trustProxies(at: '*')` (`bootstrap/app.php`).
- Forçage des URL en HTTPS hors développement : `URL::forceScheme('https')`
  (`AppServiceProvider`), sur les environnements déployés uniquement.
- Nonce CSP partagé avec Vite : `Vite::useCspNonce()` (`AppServiceProvider`).

## En-têtes émis

| En-tête | Valeur | Condition |
| :-- | :-- | :-- |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | **uniquement si la requête est en HTTPS** (jamais en clair / local) |
| `X-Content-Type-Options` | `nosniff` | toujours |
| `X-Frame-Options` | `DENY` | toujours |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | toujours |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), interest-cohort=()` | toujours |
| `Content-Security-Policy[-Report-Only]` | voir ci-dessous | toujours |

## Politique de sécurité de contenu (CSP)

Directives émises :

```
default-src 'self';
script-src 'self' 'nonce-<nonce Vite>';
style-src 'self' 'unsafe-inline';
img-src 'self' data:;
font-src 'self';
connect-src 'self';
frame-ancestors 'none';
base-uri 'self';
form-action 'self';
object-src 'none'
```

- **Nonce** : `script-src` autorise les scripts portant le nonce généré par
  `Vite::useCspNonce()`. Les balises `@vite` le portent automatiquement.
- **`style-src 'unsafe-inline'`** est nécessaire tant que Vue injecte des styles
  de transition en ligne. On ne prétend donc pas à une CSP plus stricte qu'elle
  ne l'est.

### Mode rapport, puis bascule par paliers

L'en-tête est piloté par `CSP_REPORT_ONLY` (config `security.csp_report_only`,
défaut **true**) :

- `true` → `Content-Security-Policy-Report-Only` : les violations sont observées
  **sans bloquer**. C'est le mode de départ, à conserver en recette le temps de
  relever les violations réelles.
- `false` → `Content-Security-Policy` : mode **bloquant**.

> **Décision.** La bascule en mode bloquant n'est **pas** faite dans ce lot. Elle
> se décide après observation en recette, exactement comme le dossier décrit
> l'analyse statique « introduite à titre informatif puis rendue bloquante par
> paliers ». La bascule est un simple changement de variable d'environnement, sans
> redéploiement de code.

## Transport chiffré

- **HSTS** n'est émis que si `$request->isSecure()` : jamais en développement
  local servi en clair.
- **Redirection HTTP → HTTPS** : sur les environnements déployés
  (`production` / `staging` / `recette`), toute requête non chiffrée est
  redirigée (301) vers son équivalent HTTPS. Désactivée en `local` et en `testing`
  (sans quoi la suite de tests, servie en clair, serait cassée).
- **`trustProxies(at: '*')`** : indispensable derrière Traefik (L12). Sans elle,
  `X-Forwarded-Proto` est ignoré, l'application se croit en HTTP et génère des URL
  et des redirections cassées. Le test `..._over_https_behind_a_trusted_proxy` le
  vérifie via `X-Forwarded-Proto`.

## Cookie de session

Déjà conforme dans `config/session.php` (piloté par variables d'environnement) :
`SESSION_SECURE_COOKIE` (true hors local), `SESSION_SAME_SITE=lax`,
`http_only=true`, pilote `database` inchangé.

## Tests

`tests/Feature/Securite/SecurityHeadersTest.php` :
- présence des cinq en-têtes de durcissement ;
- HSTS absent en HTTP, présent derrière un mandataire de confiance en HTTPS ;
- CSP en mode rapport par défaut, bloquante quand configurée ;
- cookie de session `Secure` + `HttpOnly` + `SameSite=Lax` ;
- redirection HTTPS hors local/testing, absente en testing.
