// Fenêtre de confirmation de l'application (remplace la boîte confirm() du navigateur, petite et peu claire).
//
// Formulaire : <form data-confirm="Message" data-confirm-title="Titre" data-confirm-label="Oui, …" data-confirm-danger>
// Depuis Alpine : $dispatch('confirm-action', { title, message, confirmLabel, danger, onConfirm: () => … })

export default function registerConfirmDialog(Alpine) {
    // Avant tout autre gestionnaire : un formulaire à confirmer n'est envoyé qu'après « Oui ».
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        window.dispatchEvent(new CustomEvent('confirm-action', {
            detail: {
                title: form.dataset.confirmTitle,
                message: form.dataset.confirm,
                confirmLabel: form.dataset.confirmLabel,
                danger: 'confirmDanger' in form.dataset,
                onConfirm: () => {
                    form.dataset.confirmed = '1';
                    form.requestSubmit();
                },
            },
        }));
    }, true);

    Alpine.data('confirmDialog', () => ({
        open: false,
        title: '',
        message: '',
        confirmLabel: '',
        danger: false,
        onConfirm: null,
        returnFocus: null,

        show(detail) {
            // Typographie française : espace insécable avant « ? », « ! », « : » (jamais seuls en début de ligne).
            const french = (text) => (text || '').replace(/ ([?!:;])/g, (m, sign) => String.fromCharCode(160) + sign);
            this.title = french(detail.title) || 'Confirmer';
            this.message = french(detail.message);
            this.confirmLabel = detail.confirmLabel || 'Oui, continuer';
            this.danger = !!detail.danger;
            this.onConfirm = detail.onConfirm || null;
            this.returnFocus = document.activeElement;
            this.open = true;
            // Action risquée : le focus va sur « Non », sinon sur le bouton principal.
            this.$nextTick(() => (this.danger ? this.$refs.cancel : this.$refs.ok).focus());
        },

        cancel() {
            this.open = false;
            this.returnFocus?.focus?.();
        },

        confirm() {
            this.open = false;
            const action = this.onConfirm;
            this.onConfirm = null;
            action?.();
        },
    }));
}
