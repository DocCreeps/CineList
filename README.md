<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.4+">
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
- [Variables d'environnement](#variables-denvironnement)
- [Attribution TMDB](#attribution-tmdb)
- [Routes de l'application](#routes-de-lapplication)
- [Modèle de données](#modèle-de-données)
- [Détails techniques](#détails-techniques)
- [Limites connues](#limites-connues)

## Fonctionnalités

### 🏠 Accueil (`/`)
- Bandeau d'introduction en forme de **ticket d'entrée** (« Cinélist · Séance personnelle ») : accès rapide à la recherche, au tableau de bord et au bouton **🎲 Surprends-moi** (affiché si au moins un film est « à voir »). La souche du ticket porte les compteurs personnels : dans la liste, à voir, déjà vus, à revoir.
- Pastilles de raccourci vers le tableau de bord, la recherche, les sorties à venir et « Mon année ciné ».
- Bloc **Votre liste** : les 6 films « à voir » les plus prioritaires (à priorité égale, les plus récemment ajoutés) ; un clic ouvre la fiche du film.
- Bloc **Prochainement** : **tous** les films de la liste au statut « à voir » et à la source « cinéma », avec leur date de sortie **en France** (vérifiée film par film, voir [Détails techniques](#détails-techniques)). Les films déjà à l'affiche passent en premier (badge **En salles**), puis les autres par date croissante ; un film dont aucune sortie salle française n'est encore annoncée affiche la date de sortie générale, marquée « date à confirmer ». Les films sortis depuis plus de ~2 mois n'apparaissent plus (ni « prochainement », ni encore à l'affiche).

### 🔎 Recherche (`/recherche`)
- Recherche de films via l'API TMDB, sur 4 champs combinables : **titre**, **réalisateur**, **acteur** et **studio**.
- Un champ pilote la requête TMDB (priorité titre > réalisateur > acteur > studio), les autres champs remplis affinent le résultat côté application.
- Déclenchement automatique de la recherche à partir de 2 caractères saisis (500 ms après la dernière frappe).
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
- Notation personnelle par étoiles (1 à 5), sur la carte du film ou dans la modale de détails, disponible une fois le film marqué comme vu ou à revoir ; cliquer à nouveau sur l'étoile déjà sélectionnée efface la note.
- Changement de statut et suppression d'un film depuis la liste.
- **Sélection multiple** : case à cocher sur chaque carte (visible au survol/focus, ou en permanence si le film est déjà sélectionné) ; bouton **Tout sélectionner** au-dessus de la grille, qui devient **Tout désélectionner** une fois tous les films visibles cochés (agit comme un interrupteur).
- Barre d'**actions groupées**, affichée dès qu'au moins un film est sélectionné : changement de statut, changement de priorité ou suppression appliqués à toute la sélection en un clic.
- Bouton **🎲 Surprends-moi** : ouvre la fiche d'un film "à voir" pris au hasard dans la liste.
- Les films marqués **déjà vus** sont retirés des grilles principales et regroupés dans une section repliable "Déjà vus" (masquée par défaut) ; ils réapparaissent dans la grille normale si on les sélectionne explicitement via le filtre de statut.

### 🎬 Sorties cinéma (`/a-venir`)
- Sorties en salle en France (types de sortie « limitée » et « large » TMDB), présentées **semaine par semaine** — la semaine cinéma va du mercredi au mardi, jour de sortie des films en France :
  - **Cette semaine** : bloc fixe en haut de page, toujours la semaine en cours ;
  - **Les autres semaines** : en dessous, une seule semaine à la fois, de **3 mois en arrière à 3 mois en avant**. Flèches précédent/suivant et sélecteur « Aller à » (semaines passées / prochaines semaines) ; la semaine affichée est reflétée dans l'URL (`?semaine=2026-09-23`) pour pouvoir être partagée. La semaine en cours est absente de cette navigation, puisqu'elle est déjà affichée au-dessus. Les semaines des extrémités sont tronquées à la limite des 3 mois.
- Dans une même journée de sortie, les films les plus populaires passent en premier. Dans « Cette semaine », qui mêle sorties passées et à venir, un badge **Aujourd'hui**, **Demain** ou **En salles** précise où en est chaque film.
- Chaque semaine est **une requête TMDB courte**, mise en cache séparément : « Cette semaine » s'affiche d'abord, l'explorateur se charge juste après (sans bloquer la page), et revenir sur une semaine déjà consultée est immédiat. Récupérer 3 mois d'un coup représenterait plusieurs centaines d'appels à froid.
- Un film déjà présent dans la liste personnelle est affiché en grisé, avec son statut actuel à la place des boutons d'ajout (comme sur la recherche).
- Boutons d'ajout selon la date : **+ Cinéma** (et **Déjà vue** pour un film déjà à l'affiche, enregistré comme vu au cinéma) ; au-delà de ~2 mois après la sortie, comme sur la recherche : **Déjà vue** / **+ Streaming** / **Revoir**. Une ressortie d'un film ancien propose aussi **+ Streaming**.
- Pour chaque film candidat, la date de sortie française réelle est vérifiée individuellement (voir [Détails techniques](#détails-techniques)) : un film déjà sorti ailleurs dans le monde mais faisant l'objet d'une ressortie/reprise en salle en France n'apparaît qu'avec sa date de ressortie française, jamais avec sa date de sortie d'origine.

### 📊 Mon année ciné (`/statistiques`)
- Calculé sur les films au statut « déjà vu » (`watched_at` renseigné). Les films « à revoir » ne comptent **pas** dans les films vus, les genres ou la note moyenne : ils ont leur propre compteur et leur propre frise.
- Cartes de synthèse : total de films vus (dont le nombre vu cette année), note personnelle moyenne (sur les films notés), genre favori, réalisateur favori et **studio favori** (les plus fréquents, avec leur nombre de films), et **film préféré** : le film vu le mieux noté, à note égale celui qui a la meilleure note TMDB, puis le visionnage le plus récent. Tant qu'aucun film n'est noté, la carte invite à en noter un.
- Répartition vus au cinéma / vus en streaming, et nombre de films à revoir.
- **Historique** chronologique (du plus récent au plus ancien), regroupé par mois, avec affiche, date de visionnage et note personnelle en étoiles si renseignée ; puis, dans une frise à part, les **Films à revoir**.
- Malgré son nom, le bilan porte sur **tous** les films vus, toutes années confondues : seul le compteur « dont N cette année » est annuel.
- Ce bilan est strictement personnel. La vue d'ensemble de tous les membres (réalisateur, studio et film préférés collectifs) se trouve dans l'administration.

### 🪟 Modale de détails
- Résumé, genre, durée, note, slogan, réalisateur, scénaristes, **studio(s) de production** et les trois premiers acteurs.
- **Fiche technique** (données TMDB, chaque ligne n'apparaît que si TMDB la connaît) : titre original (s'il diffère du titre français), date de sortie, langue originale, pays de production, budget et recettes. Pays et langue sont traduits en français via l'extension PHP `intl` (repli sur le nom fourni par TMDB si elle est absente).
- **Casting complet** : le bouton « Voir le casting complet » ouvre, par-dessus la modale, un panneau avec l'équipe technique principale (réalisation, scénario, photographie, montage, musique) et la distribution avec photo et rôle (100 acteurs au maximum ; le nombre réel est indiqué). Les données ne sont chargées qu'à l'ouverture du panneau. La touche Échap ferme d'abord le panneau, puis la modale.
- Bande-annonce YouTube intégrée, en français si disponible (repli automatique en langue originale sinon).
- **Où regarder (France)** : plateformes disponibles en abonnement, location ou achat (données JustWatch via TMDB), si l'information existe pour le film.
- Films similaires suggérés (recommandations TMDB).
- Si le film appartient à une saga TMDB, proposition d'ajouter toute la collection en un clic.
- Pour un film déjà dans la liste : champ de **note personnelle** (texte libre, 2 000 caractères maximum, enregistrée par un bouton dédié) — c'est aussi ce champ qui est cherché par la recherche texte du tableau de bord. Pour un film vu ou à revoir, les **étoiles** de notation (1 à 5) y sont aussi disponibles.

### 🛠️ Administration (`/admin/...`, réservé aux comptes admin)
- **Codes d'invitation** (`/admin/invitations`) : génère un code avec expiration facultative (1 à 365 jours) et **nombre d'utilisations facultatif** (usage unique par défaut, un nombre précis, ou illimité), l'envoie directement par e-mail à la personne invitée ou l'affiche à copier-coller, liste tous les codes existants avec leur statut (disponible, épuisé ou expiré) et l'historique de qui l'a utilisé. Un code jamais utilisé peut être révoqué (supprimé) ; un code multi-usage déjà partiellement utilisé peut être désactivé (ses utilisations restantes sont coupées, mais l'historique des inscriptions déjà faites avec ce code est conservé).
- **Membres & catégories** (`/admin/membres`) : vue d'ensemble de tous les comptes.
  - Chiffres clés : nombre de membres (dont administrateurs), films au total, films vus, genre n° 1.
  - Favoris de la communauté : **top 3 des réalisateurs** et **top 3 des studios** (sur les films déjà vus, tous membres confondus) et une carte **Film préféré** sous forme de carrousel : chaque diapositive montre le n° 1 de son angle avec les n° 2 et 3 à côté, voir [Films préférés de la communauté](#films-préférés-de-la-communauté).
  - Une carte par membre : films, vus, à voir, 3 genres les plus présents, date d'inscription et dernière activité (déduite de sa session la plus récente), lien vers sa fiche et bouton de **suppression définitive** du compte — supprime aussi tous ses films (`cascadeOnDelete`). Deux garde-fous : impossible de se supprimer soi-même depuis cette page, et impossible de supprimer le dernier compte administrateur restant.
  - Répartition des films par catégorie (genre), tous membres confondus.
- **Fiche d'un membre** (`/admin/membres/{membre}`), en lecture seule : répartition par statut et par source, note moyenne, genres (avec la part déjà vue de chacun), 5 réalisateurs les plus vus, distribution des notes, temps de visionnage cumulé, films ajoutés sur les 30 derniers jours et dernière activité ; puis la liste de ses films, filtrable (statut, source, genre, recherche titre/réalisateur), triable et paginée par « Afficher plus » (24 par 24). Les filtres sont reflétés dans l'URL. Les **notes personnelles en texte libre des membres ne sont jamais chargées** par ces pages : un administrateur voit la liste et les statistiques d'un membre, pas ses annotations.
- Accessible uniquement aux comptes marqués `is_admin` (voir [Authentification](#authentification)) ; le lien « Admin » n'apparaît dans la navigation que pour ces comptes.

## Authentification

Chaque personne a son propre compte et sa propre liste, totalement séparée de celle des autres :
tous les films sont automatiquement filtrés par utilisateur au niveau du modèle (un *global
scope* Eloquent sur `WatchlistItem`), donc deux comptes peuvent avoir chacun le même film dans
leur liste sans se marcher dessus.

L'authentification repose sur [Laravel Fortify](https://laravel.com/docs/fortify) pour ses
*Actions* (création de compte, réinitialisation et changement de mot de passe, mise à jour du
profil), sa politique de mot de passe et la logique de double authentification. Ses routes et ses
vues sont volontairement désactivées (`Fortify::ignoreRoutes()`, `'views' => false`) : l'application
garde ses propres routes en français et ses composants Livewire (`App\Livewire\Auth\*`,
`App\Livewire\Settings\*`), qui appellent ces Actions.

### Inscription sur invitation

Il n'y a pas d'inscription publique ouverte : créer un compte nécessite un **code
d'invitation**, généré soit en CLI soit par un administrateur depuis `/admin/invitations`
(voir la section *Administration* des [Fonctionnalités](#fonctionnalités)).

```bash
php artisan invite:generate                       # usage unique, sans expiration
php artisan invite:generate --expires-in-days=7    # code valable 7 jours
php artisan invite:generate --uses=5               # jusqu'à 5 inscriptions avec ce code
php artisan invite:generate --uses=illimite        # aucune limite d'utilisations
```

La commande affiche un code du type `XF3K-9QRT` à partager avec la ou les personnes concernées
sur `/inscription`. Par défaut un code est à usage unique ; un administrateur peut, depuis
`/admin/invitations` ou via `--uses`, générer un code réutilisable un nombre de fois précis ou
sans limite (utile par ex. pour un « code famille » évolutif). Depuis `/admin/invitations`, un
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
avant l'authentification définitive. La liste des sessions actives est consultable depuis
`/parametres/sessions`, avec un bouton pour **déconnecter tous les autres appareils** (elle lit la
table `sessions` : `SESSION_DRIVER=database` est donc nécessaire, comme dans `.env.example`).

### Politique de mot de passe

Un mot de passe doit comporter **au moins 12 caractères**, dont une minuscule, une majuscule, un
chiffre et un caractère spécial. La règle est définie une seule fois (`Password::defaults()` dans
`App\Providers\AppServiceProvider`) et s'applique à l'inscription, à la réinitialisation et au
changement de mot de passe. Les formulaires affichent en direct les critères déjà remplis
(`<x-password-strength>`). Les messages de validation sont traduits en français
(`lang/fr/validation.php`).

### Confirmation du mot de passe

Les pages sensibles (`/parametres/*` et `/admin/*`) exigent une ré-authentification par mot de
passe (`/confirmer-mot-de-passe`), valable 3 heures par défaut (`AUTH_PASSWORD_TIMEOUT`, en
secondes).

### Profil et suppression du compte

`/parametres/profil` permet de modifier son nom et son adresse e-mail. Une « zone de danger » permet
de **supprimer son propre compte en libre-service** : le mot de passe est redemandé (5 essais par
minute au maximum), puis le compte, tous ses films (`cascadeOnDelete`) et ses sessions sont
supprimés. Comme pour la suppression d'un membre par un administrateur, il est impossible de fermer
le **dernier compte administrateur** restant : il faut d'abord en nommer un autre.

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
  Le même mécanisme protège la confirmation de mot de passe, la double authentification, la
  suppression du compte et la déconnexion des autres appareils.
- Quand une limite est atteinte, le navigateur affiche un **compte à rebours automatique**
  (Alpine.js, sans requête réseau) et désactive le bouton d'envoi jusqu'à la fin de l'attente.
  Le composant Livewire n'écrit plus le nombre de secondes dans une erreur figée : il appelle
  `isThrottled()` (trait `App\Livewire\Concerns\ThrottlesWithCountdown`), qui déclenche
  l'événement navigateur `throttled`. La limite reste appliquée côté serveur par le
  `RateLimiter` : le décompte n'est qu'un affichage. Pour l'ajouter à un nouveau formulaire :
  `x-data="throttleCountdown"` sur le `<form>`, `<x-throttle-notice />` dans le formulaire et
  `:disabled="remaining > 0"` sur le bouton d'envoi.
- Tous les champs mot de passe utilisent `<x-password-input>` (bouton afficher / masquer en
  Alpine.js, état propre à chaque champ) au lieu d'un `<input type="password">` brut.
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
- Suppression d'un membre (`/admin/membres`) impossible sur son propre compte, et impossible sur
  le dernier compte administrateur restant, pour ne jamais se retrouver sans accès à
  l'administration.
- En-têtes HTTP de durcissement appliqués à toutes les réponses (`App\Http\Middleware\SetSecurityHeaders`) :
  `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, et
  `Strict-Transport-Security` en HTTPS.
- 2FA disponible par compte (voir ci-dessus).
- Politique de mot de passe forte (12 caractères, casse mixte, chiffre, caractère spécial), voir
  [Politique de mot de passe](#politique-de-mot-de-passe).
- Consommation d'un code d'invitation **atomique** : la ligne du code est verrouillée
  (`lockForUpdate`) dans une transaction (`App\Actions\Fortify\CreateNewUser`), si bien que deux
  inscriptions simultanées ne peuvent pas dépasser le nombre d'utilisations autorisé.
- Identifiants TMDB validés côté serveur (numériques uniquement) avant tout appel à l'API ou
  ajout à la liste ; note personnelle limitée à 2 000 caractères.
- Déconnexion par requête `POST` (`/deconnexion`), avec invalidation de la session et
  régénération du jeton CSRF.
- En-tête `X-Forwarded-Host` ignoré, sauf en environnement `local` et uniquement pour les hôtes
  listés dans `TUNNEL_HOSTS` (tunnels de développement) : un client ne peut pas faire passer un
  hôte arbitraire pour l'URL racine de l'application (URLs générées par Laravel).
- Les administrateurs ne voient jamais les notes personnelles des membres (colonnes explicitement
  listées dans les requêtes de l'administration).

## Architecture

Le code applicatif est organisé en couches, chacune avec une responsabilité précise, plutôt que
de laisser les composants Livewire mélanger interface et logique métier :

```
app/
├── Livewire/          Composants d'interface (état, validation de saisie, orchestration).
│   ├── Home.php       Page d'accueil
│   ├── Auth/          Connexion, inscription, mot de passe, confirmation du mot de passe, 2FA...
│   ├── Settings/      Profil (dont suppression du compte), mot de passe, 2FA, sessions
│   ├── Admin/         Invitations, membres, fiche d'un membre (réservé aux admins)
│   ├── Watchlist/     Tableau de bord
│   ├── Search/, Upcoming/, Stats/
│   └── Concerns/      Traits partagés entre plusieurs composants (ex. InteractsWithMovies,
│                      ThrottlesWithCountdown)
├── Actions/           Logique métier, indépendante de l'UI : une classe = une opération.
│   ├── Watchlist/     Filtrage/tri de la liste, ajout d'un film ou d'une saga, changement
│   │                  de statut, compteurs, statistiques (ComputeWatchlistStats)
│   ├── Admin/         Vue d'ensemble des membres (ComputeMembersOverview), détail par membre
│   │                  (ComputeMemberDetail)
│   ├── InviteCodes/   Génération d'un code d'invitation
│   └── Fortify/       Actions Fortify (création de compte, réinitialisation et changement de
│                      mot de passe, profil) et règles de mot de passe
├── Console/Commands/  invite:generate, user:make-admin, watchlist:assign-owner
├── Http/
│   ├── Controllers/Auth/   LogoutController (déconnexion)
│   └── Middleware/         Rôle admin, en-têtes de sécurité
├── Models/            Persistance, relations, scopes globaux (ex. l'isolation par utilisateur
│                      sur WatchlistItem), casts : User, WatchlistItem, InviteCode,
│                      InviteCodeRedemption.
├── Providers/         AppServiceProvider (politique de mot de passe, hôtes de tunnel),
│                      FortifyServiceProvider (Actions Fortify, limiteurs de débit)
├── Services/          Intégrations externes (TmdbClient : appels à l'API TMDB, cache).
├── Support/           Petits utilitaires purs, sans état ni dépendance base de données.
│   └── Movies/        ReleaseWindow (classification d'un film par fenêtre de sortie), Genres
│                      (comptage par genre), Favorites (réalisateur, studio et films préférés).
└── Mail/              Mailables (ex. InviteCodeMail).
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
| Cache | Pilote Laravel configuré (`database` par défaut) — utilisé pour mettre en cache les appels à l'API TMDB (recherches, fiches films, sorties, fournisseurs de streaming) |
| Authentification | [Laravel Fortify](https://laravel.com/docs/fortify) (Actions, politique de mot de passe, 2FA) avec une interface 100 % Livewire |

## Prérequis

- PHP 8.4.1 ou supérieur, avec l'extension `pdo_sqlite` (les dépendances verrouillées dans `composer.lock`, notamment Symfony 8.1, l'exigent)
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

En développement, `composer dev` (qui lance `php artisan dev`) démarre en parallèle le serveur PHP, la queue et Vite en mode watch, ainsi que les logs (`pail`) lorsque l'extension PHP `pcntl` est disponible.

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

## Variables d'environnement

`.env.example` documente toutes les variables ; voici celles qui comptent pour cette application :

| Variable | Valeur par défaut | Rôle |
|---|---|---|
| `TMDB_API_TOKEN`, `TMDB_API_URL` | *(à renseigner)* / `https://api.themoviedb.org/3/` | Accès à l'API TMDB (voir [Configuration TMDB](#configuration-tmdb)) |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | `fr`, `fr`, `fr_FR` | Langue des messages de validation (`lang/fr/validation.php`). Les dates affichées sont de toute façon forcées en français. |
| `SESSION_DRIVER` | `database` | Requis pour la page des sessions actives et pour la « dernière activité » des membres dans l'administration : toutes deux lisent la table `sessions`. |
| `CACHE_STORE`, `QUEUE_CONNECTION` | `database` | Cache des appels TMDB ; file de tâches (`composer dev` lance un `queue:listen`). |
| `MAIL_MAILER` (+ `MAIL_*`) | `log` | Transport des e-mails (vérification, mot de passe oublié, codes d'invitation). Voir [Envoi de mails](#envoi-de-mails). |
| `REQUIRE_EMAIL_VERIFICATION` | `false` | Exige l'e-mail vérifié pour accéder à l'application. À activer une fois un vrai mailer configuré. |
| `AUTH_PASSWORD_TIMEOUT` | `10800` | Durée (en secondes) pendant laquelle une confirmation de mot de passe reste valable. |
| `TUNNEL_HOSTS` | *(vide)* | Liste blanche, séparée par des virgules, d'hôtes de tunnel de développement (Cloudflare Tunnel, Localtunnel, Ngrok) autorisés à devenir l'URL racine via `X-Forwarded-Host`. Ignorée hors environnement `local`. |

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
| `/a-venir` | `upcoming.index` | Sorties cinéma : semaine en cours, puis navigation semaine par semaine sur ±3 mois (`?semaine=AAAA-MM-JJ`) |
| `/statistiques` | `stats.index` | Bilan des films vus (« Mon année ciné ») |
| `/connexion` | `auth.login` | Connexion |
| `/inscription` | `auth.register` | Création de compte (code d'invitation requis) |
| `/mot-de-passe-oublie` | `auth.forgot-password` | Demande de réinitialisation |
| `/reinitialiser-mot-de-passe/{token}` | `auth.reset-password` | Choix du nouveau mot de passe |
| `/verifier-email` | `auth.verify-email` | Écran d'attente de vérification d'e-mail |
| `/confirmer-mot-de-passe` | `auth.confirm-password` | Ré-authentification avant une page sensible |
| `/deux-facteurs/verification` | `auth.two-factor-challenge` | Vérification du code 2FA à la connexion |
| `/parametres/profil` | `settings.profile` | Modification du profil et suppression du compte |
| `/parametres/mot-de-passe` | `settings.password` | Changement de mot de passe |
| `/parametres/double-authentification` | `settings.two-factor` | Activation/désactivation de la 2FA |
| `/parametres/sessions` | `settings.sessions` | Sessions actives, révocables individuellement |
| `/admin/invitations` | `admin.invitations` | Génération/envoi/révocation de codes d'invitation (admin) |
| `/admin/membres` | `admin.members` | Vue d'ensemble des comptes, favoris de la communauté, suppression d'un membre (admin) |
| `/admin/membres/{member}` | `admin.member-show` | Fiche d'un membre : statistiques et liste de ses films, en lecture seule (admin) |

Toutes les routes ci-dessus sauf les routes réservées aux visiteurs non connectés (connexion,
inscription, mot de passe oublié, réinitialisation, vérification du code 2FA) nécessitent une
session connectée (middleware `auth`). Les routes `/parametres/*` et `/admin/*` exigent en plus une
ré-authentification récente par mot de passe (`password.confirm`), et `/admin/*` exige un compte
`is_admin` (middleware `admin`). Voir [Authentification](#authentification) pour le détail.

En plus de ces pages : `POST /deconnexion` (déconnexion), `GET /email/verifier/{id}/{hash}` (lien signé de
vérification d'e-mail) et `GET /up` (contrôle de santé de Laravel).

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
| `note` | string, nullable | Note personnelle en texte libre (2 000 caractères maximum), éditable depuis la modale de détails d'un film déjà dans la liste ; incluse dans la recherche texte du tableau de bord, jamais visible des administrateurs |
| `personal_rating` | integer, nullable | Note personnelle 1 à 5, réglable par étoiles une fois le film vu ou à revoir |

Table `users` : colonnes standards Laravel/Fortify (dont les colonnes 2FA) plus `is_admin`
(boolean, `false` par défaut — voir [Comptes administrateurs](#comptes-administrateurs)).

Table `invite_codes` (voir [Authentification](#authentification)) : `code` (unique), `expires_at`
nullable, `max_uses` (nullable = illimité, sinon nombre d'inscriptions autorisées — `1` par
défaut) et `uses_count` (nombre d'inscriptions déjà réalisées, incrémenté à chaque usage) ; un
code devient indisponible dès que `uses_count` atteint `max_uses`. `sent_to` (e-mail du
destinataire si le code a été envoyé par mail) et `created_by` (administrateur à l'origine du
code) sont nullables, pour les codes générés en CLI ou plus anciens. `used_at`/`used_by`
(première utilisation) sont conservés pour compatibilité avec l'historique antérieur au
multi-usage ; l'historique complet (potentiellement plusieurs comptes pour un même code) vit dans
la table `invite_code_redemptions` (`invite_code_id`, `user_id`, horodatage).

Tables techniques de Laravel : `sessions` (pilote de session `database`), `password_reset_tokens`, `cache` / `cache_locks`, `jobs` / `job_batches` / `failed_jobs`.

## Détails techniques

- **Recherche multi-champs** : selon le champ principal, l'application interroge `search/movie`, `search/person` (+ `movie_credits`) ou `search/company` (+ `discover/movie`), puis enrichit chaque résultat (réalisateur, casting, studio) via un pool de requêtes HTTP concurrentes (`Http::pool`).
- **Double niveau de cache pour la recherche** :
  - un cache par recherche (requête + mode + année minimale), 6 heures ;
  - un cache par film (réalisateur/casting/studio), 7 jours, partagé entre toutes les recherches — un film déjà rencontré dans une recherche précédente n'est jamais re-téléchargé.
- **Autres caches TMDB** : sorties à venir, 12 heures (une semaine cinéma par entrée pour la page « À venir », si bien que chaque semaine parcourue n'est téléchargée qu'une fois) ; dates de sortie françaises de chaque film candidat (page « À venir » et bloc « Prochainement » de l'accueil), 12 heures, par film ; fiche complète d'un film (`find()`, utilisée par la modale et l'ajout à la liste, clé versionnée `tmdb.movie.v4.*`), 1 jour ; casting complet (`credits()`, panneau « Casting complet » de la modale), 3 jours, un échec n'étant jamais mis en cache ; films similaires et fournisseurs de streaming (« Où regarder »), 3 jours ; saga/collection, 3 jours.
- **Bloc « Prochainement » de l'accueil** : plutôt que de croiser la liste avec une fenêtre de sorties (qui aurait manqué les films annoncés à plus de deux mois ou déjà à l'affiche), `TmdbClient::cinemaReleaseDates()` interroge chaque film « cinéma / à voir » de la liste. Date retenue : la prochaine sortie salle française (types limitée/large) ou, à défaut, la plus récente déjà passée ; si TMDB n'en connaît aucune, la date de sortie générale de la fiche, marquée « date à confirmer ». Le résultat est mis en cache par film (12 heures) : recharger l'accueil ne rappelle pas TMDB.
- **Fiabilité des sorties « France »** : `discover/movie` filtre bien côté serveur par date de sortie régionale (`region=FR` + `release_date.gte/lte` + `with_release_type`), mais le champ `release_date` qu'il renvoie sur chaque résultat n'est pas cette date régionale — il peut s'agir de la toute première sortie du film n'importe où dans le monde, parfois des années plus tôt (cas typique : une ressortie/reprise en salle française d'un film déjà ancien). Pour éviter d'afficher cette date trompeuse, chaque film candidat fait l'objet d'un appel individuel à `movie/{id}/release_dates` (en pool, comme l'enrichissement de la recherche) : seule sa date de sortie France de type sortie limitée/large réellement comprise dans la période demandée est conservée, tout film sans une telle date étant écarté des résultats. Ces appels sont envoyés par lots de 30 (pour rester sous la limite de débit de TMDB) et leur résultat est mis en cache par film, si bien que les plages voisines, ou le lendemain, ne les refont pas.
- **Pagination studio parallélisée** : la première page détermine le nombre total de pages, les pages suivantes sont récupérées en une seule vague via `Http::pool` plutôt qu'en séquence.
- **Bande-annonce** : récupérée via `append_to_response=credits,videos` sur l'endpoint `movie/{id}`, avec repli sur un second appel non filtré par langue si aucune vidéo française n'existe.
- **Casting complet** : `TmdbClient::credits()` interroge `movie/{id}/credits` uniquement à l'ouverture du panneau. Côté Livewire, le résultat est exposé par une propriété calculée (`fullCredits`, trait `InteractsWithMovies`) et non par une propriété publique : il n'est donc pas sérialisé dans l'état de la page à chaque requête.
- **Films similaires & sagas** : les recommandations TMDB (`movie/{id}/recommendations`, 6 films max) alimentent le bloc « Films similaires » ; l'ajout d'une saga entière (`collection/{id}`) ignore les films déjà présents dans la liste.
- **Comptages du tableau de bord** : les compteurs par statut/source et le nombre de films « oubliés » sont calculés via des requêtes SQL groupées (`COUNT`/`GROUP BY`) plutôt qu'en chargeant toute la table en mémoire, pour rester performant même avec une liste volumineuse.
- **Génération de code d'invitation** : garantie unique par une boucle de vérification en base (`App\Actions\InviteCodes\GenerateInviteCode`) plutôt que de compter sur la seule contrainte SQL `unique`.

### Films préférés de la communauté

La carte « Film préféré » de `/admin/membres` (`App\Support\Movies\Favorites::filmsAcrossMembers()`) regroupe les films de **tous les membres et de tous les statuts** (à voir, vu, à revoir) par identifiant TMDB, et les classe sous trois angles, présentés dans un carrousel. Chaque angle est un classement de 3 films : le n° 1 occupe la diapositive, les n° 2 et 3 sont affichés à côté (sous le n° 1 sur mobile).

| Diapositive | Critère |
|---|---|
| **Film préféré** | Score de 0 à 100 : 60 % de note lissée + 40 % de popularité (voir ci-dessous). |
| **Le plus ajouté** | Nombre de membres qui ont le film dans leur liste, quel que soit le statut (un membre ne compte qu'une fois). À égalité : meilleure moyenne, puis nombre de notes. |
| **Le mieux noté** | Moyenne des notes personnelles, sans tenir compte des ajouts. À égalité : plus de notes, puis plus d'ajouts. Les films sans note sont ignorés. |

- **Note lissée** : `(somme des notes + 2 × moyenne générale) / (nombre de notes + 2)`. Un 5/5 donné par un seul membre ne bat donc pas automatiquement un film noté 4,8 par plusieurs ; un film sans note reçoit la moyenne générale (le milieu de l'échelle, 2,5/5, si personne n'a rien noté).
- **Popularité** : nombre d'ajouts rapporté à celui du film le plus ajouté.
- Les poids et le lissage sont des constantes en tête de `Favorites` (`SCORE_WEIGHT_RATING`, `SCORE_WEIGHT_POPULARITY`, `RATING_PRIOR_WEIGHT`).
- **Sans doublon** (`Favorites::withoutDuplicates()`) : seuls les n° 1 sont comparés. Si le film « général » n° 1 est aussi celui qui est en tête des ajouts ou des notes, la diapositive « Film préféré » n'est pas affichée (avec son classement) ; si les trois angles désignent le même n° 1, seule la diapositive « Film préféré » reste. La diapositive « Le mieux noté » disparaît aussi tant qu'aucun film n'est noté.
- **Carrousel** (composant Alpine `favoriteCarousel`, `resources/js/app.js`) : une diapositive toutes les 4 secondes, en pause au survol de la souris et au focus clavier ; navigation manuelle par flèches, points, touches ← / → du clavier ou glissement du doigt ; aucun défilement automatique si le navigateur demande moins d'animations (`prefers-reduced-motion`). Les titres longs passent sur deux lignes au maximum, sans dépasser de la carte.

## Limites connues

- Films uniquement (pas de séries TV).
- Inscription sur invitation uniquement, pas d'auto-inscription publique.
- Pas de vérification d'e-mail obligatoire par défaut (voir [Envoi de mails](#envoi-de-mails) pour l'activer une fois un mailer réel configuré).
- Pas de suite de tests dédiée à l'application (seuls les tests d'exemple par défaut de Laravel sont présents).
- Pas d'intégration continue configurée.
