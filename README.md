<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Livewire-4-4E56A6?logo=livewire&logoColor=white" alt="Livewire 4">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-38BDF8?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/DB-SQLite-003B57?logo=sqlite&logoColor=white" alt="SQLite">
  <img src="https://img.shields.io/badge/Data-TMDB_API-01D277?logo=themoviedatabase&logoColor=white" alt="TMDB API">
</p>

<h1 align="center">WatchMovie</h1>

<p align="center">
  Une application personnelle de gestion de liste de films à voir, basée sur l'API TMDB.<br>
  Laravel 13 + Livewire 4 — multi-utilisateur sur invitation, à héberger soi-même.
</p>

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Authentification](#authentification)
- [Architecture](#architecture)
- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration TMDB](#configuration-tmdb)
- [Attribution TMDB](#attribution-tmdb)
- [Routes de l'application](#routes-de-lapplication)
- [Modèle de données](#modèle-de-données)
- [Détails techniques](#détails-techniques)
- [Limites connues](#limites-connues)

## Fonctionnalités

### 🏠 Accueil (`/`)
- Bannière d'introduction avec accès rapide à la recherche, au tableau de bord et au bouton **🎲 Surprends-moi** (si au moins un film est « à voir »).
- Compteurs rapides : total, à voir, déjà vus, à revoir.
- Bloc **Ajoutés récemment** (6 derniers films ajoutés, tous statuts confondus).
- Bloc **Bientôt au cinéma, dans votre liste** : croise les films « à voir / cinéma » de la liste avec les sorties à venir TMDB (jusqu'à 4 films).
- Cartes de raccourci vers le tableau de bord, la recherche, les sorties à venir et le bilan annuel.

### 🔎 Recherche (`/recherche`)
- Recherche de films via l'API TMDB, sur 4 champs combinables : **titre**, **réalisateur**, **acteur** et **studio**.
- Un champ pilote la requête TMDB (priorité titre > réalisateur > acteur > studio), les autres champs remplis affinent le résultat côté application.
- Déclenchement automatique de la recherche à partir de 2 caractères saisis.
- Recherche par acteur : distinction rôle **joué** / **doublage**, avec compteur par catégorie.
- Filtre par année minimale.
- Résultats paginés (18 films par page).
- Chaque résultat affiche l'affiche, l'année, la note TMDB, le réalisateur (ou le studio en mode studio) et les 3 premiers acteurs.
- Un film déjà présent dans la liste personnelle est affiché en grisé, avec son statut actuel à la place des boutons d'ajout.
- Ajout à la liste personnelle directement depuis une carte de résultat, avec un tag adapté à la date de sortie :
  - **+ Cinéma** si le film n'est pas encore sorti ou l'est depuis moins de 60 jours.
  - **Déjà vue** / **+ Streaming** / **Revoir** au-delà de ce délai — une confirmation est demandée avant l'ajout direct en « Déjà vue » ou « Revoir ».
- Depuis la modale de détails, ajout en un clic de **toute une saga TMDB** (ex. Star Wars, Toy Story) non encore présente dans la liste, chaque film étant classé cinéma/streaming selon sa propre date de sortie.

### 📋 Tableau de bord (`/tableau-de-bord`)
- Liste personnelle des films ajoutés, filtrable par statut (**à voir**, **déjà vu**, **à revoir**, filtres cumulables) et par source (**cinéma**, **streaming**).
- Grille principale scindée en deux sections distinctes, chacune avec son en-tête et son compteur : **À voir** puis **À revoir** (une section n'apparaît que si elle contient au moins un film).
- Filtres additionnels par genre, réalisateur et studio (listes déroulantes, valeurs déduites de la liste), par année de sortie (min/max) et par recherche texte libre (titre ou note personnelle).
- Filtre **🕸️ Oubliés** : films « à voir » ajoutés depuis plus de 3 mois (n'apparaît que s'il y en a au moins un).
- Tri au choix : priorité, ajout récent, année, note TMDB, alphabétique.
- Compteurs par statut/source.
- Priorité (Haute/Moyenne/Basse) réglable par film.
- Notation personnelle par étoiles (1 à 5), disponible une fois le film marqué comme vu ou à revoir ; cliquer à nouveau sur l'étoile déjà sélectionnée efface la note.
- Changement de statut et suppression d'un film depuis la liste.
- **Sélection multiple** : case à cocher sur chaque carte (visible au survol/focus, ou en permanence si le film est déjà sélectionné) ; bouton **Tout sélectionner** au-dessus de la grille, qui devient **Tout désélectionner** une fois tous les films visibles cochés (agit comme un interrupteur).
- Barre d'**actions groupées**, affichée dès qu'au moins un film est sélectionné : changement de statut, changement de priorité ou suppression appliqués à toute la sélection en un clic.
- Bouton **🎲 Surprends-moi** : ouvre la fiche d'un film "à voir" pris au hasard dans la liste.
- Les films marqués **déjà vus** sont retirés des grilles principales et regroupés dans une section repliable "Déjà vus" (masquée par défaut) ; ils réapparaissent dans la grille normale si on les sélectionne explicitement via le filtre de statut.

### 🎬 Sorties cinéma (`/a-venir`)
- Sorties en salle en France sur les deux prochains mois (types de sortie « limitée » et « large » TMDB), regroupées par mois.

### 📊 Mon année ciné (`/statistiques`)
- Calculé sur tous les films marqués comme vus (`watched_at` renseigné), qu'ils soient au statut « déjà vu » ou « à revoir ».
- Cartes de synthèse : total de films vus (dont le nombre vu cette année), note personnelle moyenne (sur les films notés), genre favori et réalisateur favori (avec leur nombre d'occurrences).
- Répartition vus au cinéma / vus en streaming.
- Historique chronologique (du plus récent au plus ancien), regroupé par mois, avec affiche, date de visionnage et note personnelle en étoiles si renseignée.

### 🪟 Modale de détails
- Résumé, genre, durée, note, réalisateur, casting.
- Bande-annonce YouTube intégrée, en français si disponible (repli automatique en langue originale sinon).
- **Où regarder (France)** : plateformes disponibles en abonnement, location ou achat (données JustWatch via TMDB), si l'information existe pour le film.
- Films similaires suggérés (recommandations TMDB).
- Si le film appartient à une saga TMDB, proposition d'ajouter toute la collection en un clic.
- Pour un film déjà dans la liste : champ de **note personnelle** (texte libre, enregistrée par un bouton dédié) — c'est aussi ce champ qui est cherché par la recherche texte du tableau de bord.

### 🛠️ Administration (`/admin/...`, réservé aux comptes admin)
- **Codes d'invitation** (`/admin/invitations`) : génère un code (avec expiration facultative), l'envoie directement par e-mail à la personne invitée ou l'affiche à copier-coller, liste tous les codes existants avec leur statut (disponible / utilisé / expiré) et permet de révoquer un code non utilisé.
- **Membres** (`/admin/membres`) : vue d'ensemble de tous les comptes (nombre de films par membre, genres les plus regardés tous comptes confondus), avec un détail dépliable par membre (genres, note moyenne, dernier film vu).
- Accessible uniquement aux comptes marqués `is_admin` (voir [Authentification](#authentification)) ; le lien "Admin" n'apparaît dans la navigation que pour ces comptes.

## Authentification

Chaque personne a son propre compte et sa propre liste, totalement séparée de celle des autres :
tous les films sont automatiquement filtrés par utilisateur au niveau du modèle (un *global
scope* Eloquent sur `WatchlistItem`), donc deux comptes peuvent avoir chacun le même film dans
leur liste sans se marcher dessus.

### Inscription sur invitation

Il n'y a pas d'inscription publique ouverte : créer un compte nécessite un **code
d'invitation** à usage unique, généré soit en CLI soit par un administrateur depuis
`/admin/invitations` (voir [Administration](#🛠️-administration-adminreservé-aux-comptes-admin)).

```bash
php artisan invite:generate                     # code sans expiration
php artisan invite:generate --expires-in-days=7  # code valable 7 jours
```

La commande affiche un code du type `XF3K-9QRT` à partager avec la personne concernée sur
`/inscription`. Un code ne peut servir qu'une seule fois. Depuis `/admin/invitations`, un
administrateur peut en plus renseigner l'e-mail du destinataire : le code lui est alors envoyé
directement (voir [Envoi de mails](#envoi-de-mails) ci-dessous) au lieu d'être simplement affiché.

### Comptes administrateurs

Le rôle admin (colonne `is_admin` sur `users`) donne accès à `/admin/*` et ne peut être positionné
que par un administrateur système, jamais via un formulaire :

```bash
php artisan user:make-admin ton@email.fr           # accorde les droits admin
php artisan user:make-admin ton@email.fr --revoke  # les retire
```

### Double authentification (2FA)

Chaque compte peut activer la 2FA (TOTP, compatible Google Authenticator/Authy et équivalents)
depuis `/parametres/double-authentification`, avec codes de récupération à usage unique. Une fois
activée, la connexion redirige vers un écran de vérification du code (`/deux-facteurs/verification`)
avant l'authentification définitive. La liste des sessions actives est consultable et révocable
individuellement depuis `/parametres/sessions`.

### Envoi de mails

L'e-mail de vérification de compte, la réinitialisation de mot de passe et l'envoi de codes
d'invitation passent tous par le mailer Laravel configuré dans `.env`. **Tant qu'aucun mailer réel
n'est configuré** (`MAIL_MAILER=log` par défaut), ces e-mails atterrissent dans
`storage/logs/laravel.log` — les liens/codes y sont visibles et fonctionnels, mais uniquement en
local. Pour un envoi réel, passer `MAIL_MAILER` à `smtp` (ou un autre transport supporté par
Laravel) et renseigner les identifiants du fournisseur choisi.

L'accès à l'application n'exige **pas** l'e-mail vérifié par défaut (seulement une session
connectée), pour ne pas se retrouver bloqué avant d'avoir configuré un vrai mailer. Une fois un
mailer configuré, passer `REQUIRE_EMAIL_VERIFICATION=true` dans `.env` pour l'exiger (voir
`config/auth.php` — `routes/web.php` en tient compte automatiquement).

### Mot de passe oublié

Fonctionne par e-mail (`/mot-de-passe-oublie`), donc soumis à la même limite que ci-dessus : sans
mailer réel, le lien atterrit dans `storage/logs/laravel.log` plutôt que dans une boîte mail.

### Films ajoutés avant la mise en place des comptes

Si la base contenait déjà des films avant ce système d'auth, ils n'ont pas de propriétaire et
restent invisibles (le scope global les exclut de toute liste tant qu'ils n'appartiennent à
personne). Pour les rattacher à un compte après ta première inscription :

```bash
php artisan watchlist:assign-owner ton@email.fr
# ou par ID : php artisan watchlist:assign-owner 1
```

### Sécurité

- Mots de passe hashés automatiquement (cast `hashed` sur `User::password`, bcrypt).
- Limitation des tentatives de connexion : 5 essais par couple e-mail + IP, verrouillage 60s.
- Limitation des tentatives d'inscription : 10 essais par IP et par minute (les composants
  Livewire ne passant pas par le routeur HTTP classique, cette limite est appliquée manuellement
  dans `App\Livewire\Auth\Register`, pas via un middleware de route).
- Limitation de la génération de codes d'invitation côté admin : 10 par minute et par
  administrateur, pour éviter un abus en cas de compte admin compromis.
- Régénération de la session à la connexion et à l'inscription (protection contre la fixation
  de session).
- Message de confirmation identique qu'un e-mail existe ou non sur "mot de passe oublié"
  (pas d'énumération de comptes).
- Lien de vérification d'e-mail signé et limité en fréquence (`signed`, `throttle:6,1`).
- `user_id` volontairement absent du `$fillable` de `WatchlistItem`, `is_admin` absent de celui
  de `User` : ni l'un ni l'autre n'est jamais modifiable via un payload utilisateur, uniquement en
  base ou via les commandes artisan dédiées.
- Zone `/admin` protégée par trois couches : session connectée (`auth`), ré-authentification par
  mot de passe récente (`password.confirm`, comme les pages de `/parametres`), et rôle admin
  (middleware `admin`, voir `App\Http\Middleware\EnsureUserIsAdmin`).
- En-têtes HTTP de durcissement appliqués à toutes les réponses (`App\Http\Middleware\SetSecurityHeaders`) :
  `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, et
  `Strict-Transport-Security` en HTTPS.
- 2FA disponible par compte (voir ci-dessus).

## Architecture

Le code applicatif est organisé en couches, chacune avec une responsabilité précise, plutôt que
de laisser les composants Livewire mélanger interface et logique métier :

```
app/
├── Livewire/          Composants d'interface (état, validation de saisie, orchestration).
│   ├── Auth/          Connexion, inscription, mot de passe, 2FA...
│   ├── Settings/       Profil, mot de passe, 2FA, sessions
│   ├── Admin/          Invitations, membres (réservé aux admins)
│   ├── Watchlist/       Tableau de bord
│   ├── Search/, Upcoming/, Stats/
│   └── Concerns/        Traits partagés entre plusieurs composants (ex. InteractsWithMovies)
├── Actions/            Logique métier, indépendante de l'UI : une classe = une opération.
│   ├── Watchlist/      Filtrage/tri de la liste, ajout d'un film ou d'une saga, changement
│   │                   de statut, compteurs, statistiques
│   ├── Admin/          Vue d'ensemble des membres, détail par membre
│   ├── InviteCodes/    Génération d'un code d'invitation
│   └── Fortify/        Hooks d'authentification (création de compte, réinitialisation...)
├── Models/              Persistance, relations, scopes globaux (ex. l'isolation par utilisateur
│                        sur WatchlistItem), casts.
├── Services/            Intégrations externes (TmdbClient : appels à l'API TMDB, cache).
├── Support/             Petits utilitaires purs, sans état ni dépendance base de données.
│   └── Movies/          Classification d'un film par fenêtre de sortie (ReleaseWindow).
├── Mail/                Mailables (ex. InviteCodeMail).
└── Http/Middleware/     Middlewares transverses (rôle admin, en-têtes de sécurité).
```

**Principe suivi** : un composant Livewire lit son propre état (propriétés publiques, saisies du
formulaire) et l'UI, mais délègue tout calcul un peu conséquent — construction de requête avec
filtres multiples, agrégations, règles métier (ex. quand horodater `watched_at`) — à une classe
`Action` dédiée, injectée via le conteneur (comme `TmdbClient` l'était déjà) :

```php
// app/Livewire/Watchlist/Dashboard.php
public function with(FilterWatchlistItems $filter): array
{
    return $filter->handle(statusFilter: $this->statusFilter, /* ... */);
}
```

```php
// app/Actions/Watchlist/FilterWatchlistItems.php
class FilterWatchlistItems
{
    public function handle(array $statusFilter = [], /* ... */): array
    {
        // construction de la requête, agrégations, tri...
    }
}
```

Avantages concrets de cette séparation :
- une Action est testable isolément, sans monter un composant Livewire complet ;
- une même Action est réutilisable par plusieurs pages (ex. `WatchlistStatusCounts` sert à la
  fois à l'accueil et au tableau de bord ; `AddMovieToWatchlist`/`AddCollectionToWatchlist`
  servent à la recherche, aux sorties à venir, à l'accueil et au tableau de bord via le trait
  `InteractsWithMovies`) ;
- un composant Livewire reste court et lisible : il orchestre l'UI, il n'implémente pas les
  règles métier.

Les opérations ponctuelles et sans ambiguïté (récupérer/mettre à jour/supprimer un seul
enregistrement en réaction directe à un clic) restent en revanche directement dans le composant :
extraire `WatchlistItem::findOrFail($id)->delete()` dans une Action séparée n'apporterait rien.

## Stack technique

| Composant | Détail |
|---|---|
| Framework | Laravel 13 |
| Frontend réactif | Livewire 4 (pas de SPA JS séparée) |
| Styles | Tailwind CSS 4 (via Vite), Alpine.js (fourni par Livewire) |
| Base de données | SQLite par défaut (`DB_CONNECTION=sqlite`) |
| Source de données films | [The Movie Database (TMDB)](https://www.themoviedb.org/) — API v3/v4 |
| Cache | Pilote Laravel configuré (`database` par défaut) — utilisé pour mettre en cache les résultats de recherche et les fiches films |

## Prérequis

- PHP 8.3 ou supérieur
- Composer
- Node.js + npm (pour compiler les assets Tailwind avec Vite)
- Un jeton d'accès à l'API TMDB (gratuit, voir plus bas)

## Installation

```bash
git clone <url-du-dépôt> watchmovie
cd watchmovie
composer setup
```

La commande `composer setup` (définie dans `composer.json`) enchaîne :
1. `composer install`
2. copie de `.env.example` vers `.env`
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install --ignore-scripts` puis `npm run build`

Il ne reste plus qu'à renseigner le jeton TMDB dans `.env` (voir ci-dessous), puis lancer :

```bash
php artisan serve
```

En développement, `composer dev` lance en parallèle le serveur PHP, la queue, les logs (`pail`) et Vite en mode watch.

Pour créer le tout premier compte admin une fois l'application installée :

```bash
php artisan invite:generate           # génère un code, inscris-toi sur /inscription avec
php artisan user:make-admin toi@email.fr
```

## Configuration TMDB

L'application ne fonctionne pas sans jeton TMDB. Dans `.env` :

```env
TMDB_API_TOKEN=votre-jeton
TMDB_API_URL=https://api.themoviedb.org/3/
```

Un jeton se récupère gratuitement sur [themoviedb.org](https://www.themoviedb.org/settings/api). Les deux formats TMDB sont supportés :
- clé API v3 (chaîne de 32 caractères) ;
- jeton de lecture v4 (JWT) — détecté automatiquement et envoyé en `Bearer`.

Si `TMDB_API_TOKEN` est absent, chaque appel à l'API TMDB renvoie une erreur explicite affichée dans l'interface plutôt que de planter.

## Attribution TMDB

Les [CGU de l'API TMDB](https://www.themoviedb.org/api-terms-of-use) imposent d'afficher, dans l'application elle-même (pas seulement dans cette documentation) :
- le texte *"This product uses the TMDB API but is not endorsed or certified by TMDB."*, à garder tel quel en anglais ;
- le logo officiel TMDB, non modifié.

Les deux sont affichés dans le footer de toutes les pages, factorisé dans `resources/views/components/site-footer.blade.php`. Le logo est chargé directement depuis le CDN de TMDB (pas de fichier à fournir dans le dépôt).

## Routes de l'application

| Route | Composant Livewire | Description |
|---|---|---|
| `/` | `home` | Page d'accueil |
| `/recherche` | `search.index` | Recherche TMDB et ajout à la liste |
| `/tableau-de-bord` | `watchlist.dashboard` | Liste personnelle |
| `/a-venir` | `upcoming.index` | Sorties cinéma à venir |
| `/statistiques` | `stats.index` | Bilan des films vus (« Mon année ciné ») |
| `/connexion` | `auth.login` | Connexion |
| `/inscription` | `auth.register` | Création de compte (code d'invitation requis) |
| `/mot-de-passe-oublie` | `auth.forgot-password` | Demande de réinitialisation |
| `/reinitialiser-mot-de-passe/{token}` | `auth.reset-password` | Choix du nouveau mot de passe |
| `/verifier-email` | `auth.verify-email` | Écran d'attente de vérification d'e-mail |
| `/deux-facteurs/verification` | `auth.two-factor-challenge` | Vérification du code 2FA à la connexion |
| `/parametres/profil` | `settings.profile` | Modification du profil |
| `/parametres/mot-de-passe` | `settings.password` | Changement de mot de passe |
| `/parametres/double-authentification` | `settings.two-factor` | Activation/désactivation de la 2FA |
| `/parametres/sessions` | `settings.sessions` | Sessions actives, révocables individuellement |
| `/admin/invitations` | `admin.invitations` | Génération/envoi/révocation de codes d'invitation (admin) |
| `/admin/membres` | `admin.members` | Vue d'ensemble des comptes et de leurs listes (admin) |

Toutes les routes ci-dessus sauf les quatre routes d'authentification publiques (connexion,
inscription, mot de passe oublié, réinitialisation) nécessitent une session connectée
(middleware `auth`). Les routes `/parametres/*` et `/admin/*` exigent en plus une
ré-authentification récente par mot de passe (`password.confirm`), et `/admin/*` exige un compte
`is_admin` (middleware `admin`). Voir [Authentification](#authentification) pour le détail.

## Modèle de données

Table `watchlist_items` :

| Champ | Type | Détail |
|---|---|---|
| `user_id` | foreign id | Propriétaire du film ; filtré automatiquement par un global scope Eloquent (voir [Authentification](#authentification)) |
| `tmdb_id` | string, unique par utilisateur | Identifiant TMDB du film |
| `title`, `year`, `poster_url`, `type`, `genre`, `runtime`, `plot`, `imdb_rating` | — | Métadonnées récupérées depuis TMDB au moment de l'ajout |
| `director`, `actors`, `studio` | string | Réalisateur, 3 premiers acteurs, studio(s)/société(s) de production (liste séparée par des virgules) |
| `status` | string | `to_watch`, `watched` ou `to_rewatch` |
| `source` | string | `cinema` ou `streaming` |
| `watched_at` | datetime, nullable | Renseigné automatiquement au passage en « déjà vu » ou « à revoir » |
| `priority` | integer | 1 (haute) à 3 (basse), réglable depuis le tableau de bord — pilote le tri par défaut |
| `note` | string, nullable | Note personnelle en texte libre, éditable depuis la modale de détails d'un film déjà dans la liste ; incluse dans la recherche texte du tableau de bord |
| `personal_rating` | integer, nullable | Note personnelle 1 à 5, réglable par étoiles une fois le film vu ou à revoir |

Table `users` : colonnes standards Laravel/Fortify (dont les colonnes 2FA) plus `is_admin`
(boolean, `false` par défaut — voir [Comptes administrateurs](#comptes-administrateurs)).

Table `invite_codes` (voir [Authentification](#authentification)) : `code` (unique), `expires_at`
et `used_at`/`used_by` nullables — un code devient indisponible dès qu'il est utilisé une fois ;
`sent_to` (e-mail du destinataire si le code a été envoyé par mail) et `created_by` (administrateur
à l'origine du code) sont nullables, pour les codes générés en CLI ou plus anciens.

## Détails techniques

- **Recherche multi-champs** : selon le champ principal, l'application interroge `search/movie`, `search/person` (+ `movie_credits`) ou `search/company` (+ `discover/movie`), puis enrichit chaque résultat (réalisateur, casting, studio) via un pool de requêtes HTTP concurrentes (`Http::pool`).
- **Double niveau de cache pour la recherche** :
  - un cache par recherche (requête + mode + année minimale), 6 heures ;
  - un cache par film (réalisateur/casting/studio), 7 jours, partagé entre toutes les recherches — un film déjà rencontré dans une recherche précédente n'est jamais re-téléchargé.
- **Autres caches TMDB** : sorties à venir (`discover/movie`), 12 heures ; fiche complète d'un film (`find()`, utilisée par la modale et l'ajout à la liste), 1 jour ; films similaires et fournisseurs de streaming (« Où regarder »), 3 jours ; saga/collection, 3 jours.
- **Pagination studio parallélisée** : la première page détermine le nombre total de pages, les pages suivantes sont récupérées en une seule vague via `Http::pool` plutôt qu'en séquence.
- **Bande-annonce** : récupérée via `append_to_response=credits,videos` sur l'endpoint `movie/{id}`, avec repli sur un second appel non filtré par langue si aucune vidéo française n'existe.
- **Films similaires & sagas** : les recommandations TMDB (`movie/{id}/recommendations`, 6 films max) alimentent le bloc « Films similaires » ; l'ajout d'une saga entière (`collection/{id}`) ignore les films déjà présents dans la liste.
- **Comptages du tableau de bord** : les compteurs par statut/source et le nombre de films « oubliés » sont calculés via des requêtes SQL groupées (`COUNT`/`GROUP BY`) plutôt qu'en chargeant toute la table en mémoire, pour rester performant même avec une liste volumineuse.
- **Génération de code d'invitation** : garantie unique par une boucle de vérification en base (`App\Actions\InviteCodes\GenerateInviteCode`) plutôt que de compter sur la seule contrainte SQL `unique`.

## Limites connues

- Films uniquement (pas de séries TV).
- Inscription sur invitation uniquement, pas d'auto-inscription publique.
- Pas de vérification d'e-mail obligatoire par défaut (voir [Envoi de mails](#envoi-de-mails) pour l'activer une fois un mailer réel configuré).
- Pas de suite de tests dédiée à l'application (seuls les tests d'exemple par défaut de Laravel sont présents).
- Pas d'intégration continue configurée.
