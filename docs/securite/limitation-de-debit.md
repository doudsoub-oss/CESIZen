# Limitation de débit (lot L03)

> Plafonnement des tentatives d'authentification, de réinitialisation et
> d'inscription. Traite **R6** (bourrage d'identifiants, criticité 9) et contribue
> à **R7**. Conforme au Tableau 9 du dossier : plafonnement *par adresse **et** par
> compte*.

## Principe

Le plafonnement s'appuie sur le magasin de limitation de Laravel, adossé à
**PostgreSQL** (aucun magasin en mémoire, §3.1). Il est déclaré dans
`app/Providers/FortifyServiceProvider.php` (`configureRateLimiting`) et attaché
aux routes Fortify via `configureRouteThrottling`.

Un dépassement renvoie une réponse **429** portant un message générique en
français — *« Trop de tentatives. Veuillez patienter avant de réessayer. »* — qui
**ne révèle jamais si un compte existe** (identique pour un email connu ou non).

## Seuils retenus

| Surface | Route | Limite(s) | Clé |
| :-- | :-- | :-- | :-- |
| Connexion | `login.store` | **5 / minute** ET **10 / heure** | par (email + IP) **et** par compte (email seul) |
| Double facteur | `two-factor.login.store` | 5 / minute | par session |
| Réinitialisation | `password.email` | **3 / heure** ET **10 / heure** | par email **et** par IP |
| Inscription | `register.store` | **5 / heure** | par IP |
| Vérification e-mail | routes de vérification | 6 / minute (natif Fortify) | par utilisateur |

### Pourquoi deux limites cumulées sur la connexion

- **5/min par (email + IP)** contre le bourrage rapide depuis une même adresse.
- **10/h par compte** protège un compte **ciblé depuis plusieurs adresses** : sans
  cette seconde limite, un attaquant contournerait la première en changeant d'IP.

Les deux limites sont évaluées à chaque tentative ; le dépassement de **l'une**
suffit à refuser la requête. C'est la double protection *par adresse et par
compte* exigée par le dossier, prouvée par deux tests distincts
(`test_login_is_blocked_by_ip_after_five_attempts` et
`test_login_is_blocked_by_account_across_different_ips`).

## Justification du compromis (R6 vs gêne d'un usager légitime)

Les seuils sont volontairement **hauts pour l'usage normal, bas pour une attaque** :

- Un usager qui se trompe de mot de passe dispose de 5 essais par minute — large
  au regard d'une saisie humaine, insuffisant pour un automate.
- La borne horaire par compte (10) autorise plusieurs sessions d'essais légitimes
  dans la journée tout en cassant une attaque distribuée persistante.
- La réinitialisation à 3/heure/email évite qu'un tiers ne noie une boîte mail de
  courriels de réinitialisation, sans bloquer un usager qui redemande un lien.
- L'inscription à 5/heure/IP freine la création massive de comptes sans gêner une
  famille derrière une même IP.

## Réponse et expérience Inertia

La réponse 429 est produite par un rappel `->response()` commun à tous les
limiteurs. Elle porte les en-têtes `Retry-After` / `X-RateLimit-*` standards et un
corps textuel français. Côté SPA, Inertia présente ce message ; il ne s'agit pas
d'une page d'erreur brute et aucune information sur l'existence du compte n'est
divulguée.

## Détail d'implémentation

Fortify enregistre lui-même les routes `register.store` et `password.email` (sans
throttle). Le middleware `throttle:*` leur est attaché après coup, sans modifier
le vendor, au moyen d'un `booted` imbriqué — nécessaire parce que Fortify charge
ses routes dans un `booted` postérieur à celui de ce provider (voir le commentaire
de `configureRouteThrottling`).

## Tests

`tests/Feature/Securite/RateLimitingTest.php` :
- blocage par IP (6ᵉ connexion refusée) ;
- blocage par compte depuis des IP différentes (11ᵉ refusée) ;
- réinitialisation refusée à la 4ᵉ demande pour un même email ;
- inscription refusée à la 6ᵉ depuis une même IP ;
- réinitialisation du compteur après la fenêtre (`Carbon::setTestNow`) ;
- une authentification légitime n'est jamais bloquée en deçà des seuils ;
- la réponse 429 est identique pour un compte connu et inconnu.
