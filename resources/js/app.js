document.addEventListener('alpine:init', () => {
    /**
     * Notifications ("toasts") globales, empilées en bas de l'écran.
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
        _callback: null,

        /**
         * Ouvre la modale de confirmation.
         * @param {string} message
         * @param {Function} callback Exécutée si l'utilisateur confirme.
         * @param {object} options { title, confirmLabel, cancelLabel, danger }
         */
        open(message, callback, options = {}) {
            this.message = message;
            this._callback = callback;
            this.title = options.title ?? 'Confirmation';
            this.confirmLabel = options.confirmLabel ?? 'Confirmer';
            this.cancelLabel = options.cancelLabel ?? 'Annuler';
            this.danger = options.danger ?? true;
            this.show = true;
        },

        confirm() {
            const callback = this._callback;
            this.close();
            if (typeof callback === 'function') {
                callback();
            }
        },

        close() {
            this.show = false;
            this._callback = null;
        },
    });

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
});
