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
  Laravel 13 + Livewire 4 — mono-utilisateur, sans compte, à héberger soi-même.
</p>

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
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

Aucune authentification n'est mise en place : l'application est prévue pour un usage personnel, en local ou sur un hébergement privé.

## Modèle de données

Table unique `watchlist_items` :

| Champ | Type | Détail |
|---|---|---|
| `tmdb_id` | string, unique | Identifiant TMDB du film |
| `title`, `year`, `poster_url`, `type`, `genre`, `runtime`, `plot`, `imdb_rating` | — | Métadonnées récupérées depuis TMDB au moment de l'ajout |
| `director`, `actors`, `studio` | string | Réalisateur, 3 premiers acteurs, studio(s)/société(s) de production (liste séparée par des virgules) |
| `status` | string | `to_watch`, `watched` ou `to_rewatch` |
| `source` | string | `cinema` ou `streaming` |
| `watched_at` | datetime, nullable | Renseigné automatiquement au passage en « déjà vu » ou « à revoir » |
| `priority` | integer | 1 (haute) à 3 (basse), réglable depuis le tableau de bord — pilote le tri par défaut |
| `note` | string, nullable | Note personnelle en texte libre, éditable depuis la modale de détails d'un film déjà dans la liste ; incluse dans la recherche texte du tableau de bord |
| `personal_rating` | integer, nullable | Note personnelle 1 à 5, réglable par étoiles une fois le film vu ou à revoir |

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

## Limites connues

- Films uniquement (pas de séries TV).
- Mono-utilisateur, sans compte ni partage de liste.
- Pas de suite de tests dédiée à l'application (seuls les tests d'exemple par défaut de Laravel sont présents).
- Pas d'intégration continue configurée.
