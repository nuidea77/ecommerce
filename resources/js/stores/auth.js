import { defineStore } from 'pinia';
import api, { ensureCsrf } from '../lib/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({ user: null, loaded: false }),
    getters: {
        isLoggedIn: (s) => !!s.user,
        isAdmin: (s) => s.user?.role === 'admin',
        isCourier: (s) => s.user?.role === 'courier',
        isCustomer: (s) => s.user?.role === 'customer',
    },
    actions: {
        async fetch() {
            try {
                const { data } = await api.get('/auth/me');
                this.user = data.user;
            } catch {
                this.user = null;
            } finally {
                this.loaded = true;
            }
            return this.user;
        },
        async login(payload) {
            await ensureCsrf();
            const { data } = await api.post('/auth/login', payload);
            this.user = data.user;
            return data.user;
        },
        async register(payload) {
            await ensureCsrf();
            const { data } = await api.post('/auth/register', payload);
            this.user = data.user;
            return data.user;
        },
        async logout() {
            await api.post('/auth/logout');
            this.user = null;
        },
        async updateProfile(payload) {
            const { data } = await api.put('/auth/profile', payload);
            this.user = data.user;
            return data.user;
        },
    },
});
