document.addEventListener('alpine:init', () => {
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
});
