// Composants Alpine de l'éditeur de documents (devis, puis factures).
// Les calculs ne sont jamais faits ici : le serveur (DocumentCalculator) renvoie les montants.

/** Agrandit une zone de texte à la hauteur de son contenu. */
export function autosize(el) {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
}

/** Minuscules sans accents, pour chercher « societe » dans « Société ». */
const normalize = (text) => (text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

export default function registerDocumentEditor(Alpine) {
    /**
     * Sauvegarde automatique : 2 secondes après la dernière modification.
     * États : idle, dirty (modifié), saving, saved, error (saisie à corriger).
     */
    Alpine.data('documentAutosave', (delay = 2000) => ({
        state: 'idle',
        version: 0,
        layoutPanel: false, // panneau « Mise en page »
        timer: null,

        init() {
            const touched = (event) => {
                if (event?.target?.closest?.('[data-no-autosave]')) return;
                this.touch();
            };
            this.$el.addEventListener('input', touched);
            this.$el.addEventListener('change', touched);
            window.addEventListener('document-changed', () => this.touch());

            // Page fermée ou onglet caché : on enregistre tout de suite, et le navigateur demande confirmation.
            window.addEventListener('beforeunload', (event) => {
                if (this.state === 'dirty' || this.state === 'saving') {
                    this.saveNow();
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden' && this.state === 'dirty') this.saveNow();
            });
        },

        touch() {
            this.version++;
            this.state = 'dirty';
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.saveNow(), delay);
        },

        async saveNow() {
            clearTimeout(this.timer);
            const version = this.version;
            this.state = 'saving';
            const ok = await this.$wire.save();
            // Une modification arrivée pendant l'enregistrement reste à enregistrer.
            if (this.version !== version) {
                this.touch();
                return;
            }
            this.state = ok ? 'saved' : 'error';
        },

        /** Ouvre un lien (PDF, Word) après avoir enregistré les dernières modifications. */
        async openAfterSave(url, newTab = true) {
            const win = newTab ? window.open('', '_blank') : null;
            if (this.state !== 'saved' && this.state !== 'idle') await this.saveNow();
            if (win) win.location = url;
            else window.location = url;
        },
    }));

    /** Auto-complétion de la désignation à partir des lignes déjà saisies (remplit aussi le prix). */
    Alpine.data('lineSuggest', () => ({
        open: false,
        items: [],
        active: -1,

        async lookup(term) {
            if ((term || '').trim().length < 2) {
                this.open = false;
                return;
            }
            this.items = await this.$wire.suggest(term);
            this.active = -1;
            this.open = this.items.length > 0;
        },

        keydown(event) {
            if (!this.open) return;
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.active = (this.active + 1) % this.items.length;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.active = (this.active - 1 + this.items.length) % this.items.length;
            } else if (event.key === 'Enter' && this.active >= 0) {
                event.preventDefault();
                this.pick(this.active);
            } else if (event.key === 'Escape') {
                this.open = false;
            }
        },

        /** Remplit la désignation et le prix comme si l'utilisateur les avait tapés. */
        pick(index) {
            const item = this.items[index];
            this.open = false;
            if (!item) return;

            const line = this.$root.closest('[data-line]');
            const description = line.querySelector('[data-field="description"]');
            const price = line.querySelector('[data-field="unit_price"]');

            description.value = item.description;
            description.dispatchEvent(new Event('input', { bubbles: true }));
            autosize(description);

            if (item.unit_price !== '') {
                price.value = item.unit_price;
                price.dispatchEvent(new Event('input', { bubbles: true }));
            }
            description.focus();
        },
    }));

    /** Choix du client dans une liste avec recherche (les options sont rendues par le serveur). */
    Alpine.data('clientPicker', () => ({
        open: false,
        query: '',

        matches(text) {
            return normalize(text).includes(normalize(this.query.trim()));
        },

        toggle() {
            this.open = !this.open;
            if (this.open) this.$nextTick(() => this.$refs.search?.focus());
        },

        firstVisible() {
            return [...this.$root.querySelectorAll('[data-client-option]')].find((el) => el.offsetParent !== null);
        },
    }));
}
