import { defineStore } from 'pinia';

let id = 0;

export const useUiStore = defineStore('ui', {
    state: () => ({ toasts: [], cartOpen: false, config: null }),
    actions: {
        toast(message, type = 'success', timeout = 3500) {
            const t = { id: ++id, message, type };
            this.toasts.push(t);
            setTimeout(() => this.dismiss(t.id), timeout);
        },
        dismiss(tid) {
            this.toasts = this.toasts.filter((t) => t.id !== tid);
        },
        async loadConfig() {
            if (this.config) return this.config;
            const { default: api } = await import('../lib/api');
            const { data } = await api.get('/config');
            this.config = data;
            return data;
        },
    },
});
