import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('adminShell', () => ({
    drawer: false,
    confirmOpen: false,
    confirmMessage: '',
    pendingForm: null,

    init() {
        this.$watch('drawer', (open) => {
            document.documentElement.classList.toggle('drawer-open', open);
        });
    },

    confirmSubmit(event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();
        this.pendingForm = form;
        this.confirmMessage = form.dataset.confirm;
        this.confirmOpen = true;
        this.$nextTick(() => this.$refs.confirmAccept?.focus());
    },

    cancelConfirmation() {
        this.confirmOpen = false;
        this.pendingForm = null;
    },

    proceedConfirmation() {
        if (!this.pendingForm) return;

        const form = this.pendingForm;
        form.dataset.confirmed = 'true';
        this.confirmOpen = false;
        form.requestSubmit();
    },
}));

Alpine.start();
