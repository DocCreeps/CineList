// Action en attente de confirmation (voir le store confirmModal) : volontairement hors de l'état réactif.
let pendingAction = null;

document.addEventListener('alpine:init', () => {
    /**
     * Notifications ("toasts") globales, empilées en haut à droite de l'écran (en haut, centrées, sur mobile).
     *
     * Deux façons de les déclencher :
     *  - depuis une action Livewire sur la page courante : $this->dispatch('toast', message: '…', type: 'success')
     *    (Livewire relaie tout dispatch() en événement navigateur sur `window`, capté ci-dessous) ;
     *  - après une redirection plein-page (ex. réinitialisation du mot de passe → connexion) : le
     *    layout flashe encore `session('notice')`, mais l'affiche via <x-toast-bridge> plutôt qu'un
     *    encart dans la page — ce composant appelle directement $store.toast.push(...) en x-init.
     *
     * Chaque toast se ferme seul après sa durée, sauf en cas de survol (pause()/resume(), avec une
     * échéance en Date.now() plutôt qu'un compteur décrémenté, pour rester juste même si l'onglet
     * est mis en arrière-plan).
     */
    Alpine.store('toast', {
        items: [],
        _uid: 0,

        push(message, type = 'success', duration = 5000) {
            if (! message) {
                return;
            }

            const id = ++this._uid;
            this.items.push({ id, message, type, duration, deadline: Date.now() + duration, timer: null });
            this._arm(id);
        },

        _arm(id) {
            const item = this.items.find((item) => item.id === id);

            if (! item) {
                return;
            }

            clearTimeout(item.timer);
            item.timer = setTimeout(() => this.dismiss(id), Math.max(0, item.deadline - Date.now()));
        },

        pause(id) {
            const item = this.items.find((item) => item.id === id);

            if (! item) {
                return;
            }

            clearTimeout(item.timer);
            item.timer = null;
            item.remaining = item.deadline - Date.now();
        },

        resume(id) {
            const item = this.items.find((item) => item.id === id);

            if (! item || item.timer) {
                return;
            }

            item.deadline = Date.now() + Math.max(500, item.remaining ?? item.duration);
            this._arm(id);
        },

        dismiss(id) {
            const item = this.items.find((item) => item.id === id);

            if (item?.timer) {
                clearTimeout(item.timer);
            }

            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    window.addEventListener('toast', (event) => {
        Alpine.store('toast').push(event.detail?.message, event.detail?.type ?? 'success');
    });

    Alpine.store('confirmModal', {
        show: false,
        title: 'Confirmation',
        message: '',
        confirmLabel: 'Confirmer',
        cancelLabel: 'Annuler',
        danger: true,

        /**
         * Ouvre la modale de confirmation.
         *
         * L'action n'est PAS passée sous forme de fonction : la politique CSP de l'application interdit
         * à Alpine d'évaluer du JavaScript arbitraire (donc pas de `() => …` dans un attribut). On
         * décrit l'appel à la place, et c'est ce fichier — du vrai code — qui l'exécute.
         *
         * Exemple dans une vue :
         *   x-on:click="$store.confirmModal.open('Supprimer ?', $wire, 'remove', [12])"
         *
         * @param {string} message
         * @param {object} wire Composant Livewire ($wire) dont on appelle une méthode.
         * @param {string} method Nom de la méthode du composant à appeler si l'utilisateur confirme.
         * @param {Array} args Arguments de cette méthode.
         * @param {object} options { title, confirmLabel, cancelLabel, danger }
         */
        open(message, wire, method, args = [], options = {}) {
            this.message = message;
            // Gardé hors de l'état réactif d'Alpine : envelopper $wire dans un proxy réactif le casserait.
            pendingAction = { wire, method, args };
            this.title = options.title ?? 'Confirmation';
            this.confirmLabel = options.confirmLabel ?? 'Confirmer';
            this.cancelLabel = options.cancelLabel ?? 'Annuler';
            this.danger = options.danger ?? true;
            this.show = true;
        },

        confirm() {
            const action = pendingAction;
            this.close();
            if (action?.wire && action.method) {
                action.wire[action.method](...action.args);
            }
        },

        close() {
            this.show = false;
            pendingAction = null;
        },
    });

    /**
     * Carrousel à flèches (films similaires de la modale) : fait défiler l'élément x-ref="track".
     * A poser sur le conteneur : x-data="scrollTrack", puis x-on:click="scrollBy(-300)".
     */
    Alpine.data('scrollTrack', () => ({
        scrollBy(amount) {
            this.$refs.track.scrollBy({ left: amount, behavior: 'smooth' });
        },
    }));

    /**
     * Bouton « Copier » : copie un texte dans le presse-papiers puis affiche « Copié ! » 2 secondes.
     * x-data="copyButton", x-on:click="copy(@js($url))", et x-show="copied" / x-show="! copied" pour les libellés.
     * (Les `;`, `navigator` et `setTimeout` ne sont pas utilisables dans un attribut sous CSP : voir ce fichier.)
     */
    Alpine.data('copyButton', () => ({
        copied: false,

        async copy(text) {
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                return; // presse-papiers indisponible (page non sécurisée) : on n'affiche pas un faux « Copié ! »
            }

            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        },
    }));

    /**
     * Sélecteur de mois (historique du bilan) : ouverture, mois courant, flèches et balayage tactile.
     * x-data="monthSlider(<nombre de mois>)" ; les mois sont ordonnés du plus ancien au plus récent
     * et l'on démarre sur le dernier. Chaque pastille porte x-ref="chip<index>".
     */
    Alpine.data('monthSlider', (count) => ({
        open: false,
        i: count - 1,
        count,
        startX: null,

        toggle() {
            this.open = ! this.open;
            if (this.open) this.go(this.i);
        },

        go(n) {
            this.i = Math.max(0, Math.min(this.count - 1, n));
            this.$nextTick(() => this.$refs['chip' + this.i]?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' }));
        },

        swipeStart(e) {
            this.startX = e.changedTouches[0].clientX;
        },

        swipeEnd(e) {
            if (this.startX === null) return;
            const dx = e.changedTouches[0].clientX - this.startX;
            this.startX = null;
            if (Math.abs(dx) > 50) this.go(this.i + (dx < 0 ? 1 : -1));
        },
    }));

    /**
     * Indicateur de force du mot de passe : évalué côté client, sans requête réseau. Les règles
     * reflètent la politique de App\Providers\AppServiceProvider
     * (Password::min(12)->mixedCase()->numbers()->symbols()).
     * x-data="passwordStrength('<id du champ mot de passe>')".
     */
    Alpine.data('passwordStrength', (target) => ({
        value: '',

        get rules() {
            return [
                { label: '12 caractères minimum', valid: this.value.length >= 12 },
                { label: 'Une minuscule', valid: /[a-z]/.test(this.value) },
                { label: 'Une majuscule', valid: /[A-Z]/.test(this.value) },
                { label: 'Un chiffre', valid: /[0-9]/.test(this.value) },
                { label: 'Un caractère spécial', valid: /[^A-Za-z0-9]/.test(this.value) },
            ];
        },

        get score() {
            return this.rules.filter(r => r.valid).length;
        },

        get scoreLabel() {
            if (this.value.length === 0) return '';
            if (this.score <= 2) return 'Faible';
            if (this.score <= 4) return 'Moyen';
            return 'Fort';
        },

        get scoreColor() {
            if (this.score <= 2) return 'bg-red-500';
            if (this.score <= 4) return 'bg-amber-500';
            return 'bg-emerald-500';
        },

        /** Largeur de la barre (calculée ici : un gabarit `${…}` n'est pas évaluable sous CSP). */
        get barStyle() {
            return `width: ${(this.score / 5) * 100}%`;
        },

        init() {
            const field = document.getElementById(target);
            if (!field) return;
            this.value = field.value;
            field.addEventListener('input', (e) => { this.value = e.target.value; });
        },
    }));

    /**
     * Compte à rebours automatique après un "Trop de tentatives".
     *
     * A poser sur le <form> concerné : x-data="throttleCountdown". Le composant Livewire envoie
     * l'événement "throttled" ({ seconds }) via le trait ThrottlesWithCountdown ; on décompte alors
     * en direct, sans aucune requête réseau, et on réactive le formulaire à zéro.
     *
     * Dans le formulaire :
     *   - <x-throttle-notice /> affiche le message et les secondes restantes ;
     *   - :disabled="remaining > 0" sur le bouton d'envoi le bloque pendant l'attente.
     *
     * L'échéance est calculée avec Date.now() (et non en décrémentant un compteur) : le décompte
     * reste juste même si l'onglet est en arrière-plan et que le navigateur ralentit les timers.
     */
    Alpine.data('throttleCountdown', () => {
        let timer = null;
        let deadline = 0;
        let listener = null;

        return {
            remaining: 0,

            init() {
                listener = (event) => this.start(event.detail?.seconds);
                window.addEventListener('throttled', listener);
            },

            destroy() {
                this.stop();
                window.removeEventListener('throttled', listener);
            },

            /** (Re)lance le décompte ; appelé à chaque réponse "trop de tentatives" du serveur. */
            start(seconds) {
                this.stop();

                seconds = Math.ceil(Number(seconds));

                if (! (seconds > 0)) {
                    this.remaining = 0;

                    return;
                }

                deadline = Date.now() + seconds * 1000;
                this.remaining = seconds;
                timer = setInterval(() => this.tick(), 250);
            },

            tick() {
                this.remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));

                if (this.remaining === 0) {
                    this.stop();
                }
            },

            stop() {
                clearInterval(timer);
                timer = null;
            },

            get label() {
                return `${this.remaining} seconde${this.remaining > 1 ? 's' : ''}`;
            },
        };
    });

    /**
     * Carrousel de la carte « Film préféré » (vue d'ensemble admin) : une diapositive toutes les
     * 4 secondes, en boucle, que l'on peut aussi parcourir à la main :
     *  - flèches précédent / suivant et points cliquables (voir le HTML) ;
     *  - flèches gauche / droite du clavier ;
     *  - glissement du doigt (swipe) sur écran tactile.
     *
     * Le défilement automatique se met en pause au survol de la souris et au focus clavier (variable
     * `paused`, pilotée depuis le HTML), et ne démarre pas si l'utilisateur a demandé moins
     * d'animations (prefers-reduced-motion) : la navigation manuelle reste alors disponible.
     * Chaque action manuelle relance le minuteur, pour ne pas enchaîner juste après un clic.
     *
     * Chaque diapositive porte l'attribut data-slide ; leur nombre est relu dans le DOM à chaque
     * action plutôt que figé à la création, car Livewire peut en ajouter ou en retirer lors d'un
     * rafraîchissement (ex. un premier film noté qui fait apparaître « le mieux noté »).
     */
    Alpine.data('favoriteCarousel', (interval = 4000) => {
        let timer = null;
        let touchStartX = null;

        return {
            active: 0,
            paused: false,

            init() {
                this.play();
            },

            destroy() {
                this.stop();
            },

            get total() {
                return this.$root.querySelectorAll('[data-slide]').length;
            },

            play() {
                this.stop();

                if (this.total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                timer = setInterval(() => {
                    if (! this.paused) {
                        this.step(1, false);
                    }
                }, interval);
            },

            stop() {
                clearInterval(timer);
                timer = null;
            },

            /** Avance (+1) ou recule (-1) d'une diapositive, en boucle. */
            step(direction, manual = true) {
                const total = Math.max(1, this.total);

                // `active % total` : la diapositive affichée a pu disparaître lors d'un rafraîchissement.
                this.active = ((this.active % total) + direction + total) % total;

                if (manual) {
                    this.play();
                }
            },

            go(index) {
                this.active = index;
                this.play();
            },

            swipeStart(event) {
                touchStartX = event.changedTouches[0].clientX;
            },

            /** Un glissement horizontal d'au moins 40 px change de diapositive (gauche = suivante). */
            swipeEnd(event) {
                if (touchStartX === null) {
                    return;
                }

                const distance = event.changedTouches[0].clientX - touchStartX;
                touchStartX = null;

                if (Math.abs(distance) >= 40) {
                    this.step(distance < 0 ? 1 : -1);
                }
            },
        };
    });
});
