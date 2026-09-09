import { defineStore } from 'pinia';
import api, { setCartToken } from '../lib/api';

const empty = { items: [], count: 0, subtotal: 0, shipping_fee: 0, total: 0, has_backorder: false, free_shipping_threshold: 0 };

export const useCartStore = defineStore('cart', {
    state: () => ({ ...empty, loading: false, loaded: false }),
    actions: {
        apply(data) {
            Object.assign(this, data);
            if (data.token) setCartToken(data.token);
            this.loaded = true;
        },
        async fetch() {
            this.loading = true;
            try {
                const { data } = await api.get('/cart');
                this.apply(data);
            } finally {
                this.loading = false;
            }
        },
        async add(variantId, quantity = 1) {
            const { data } = await api.post('/cart/items', { variant_id: variantId, quantity });
            this.apply(data);
        },
        async update(itemId, quantity) {
            const { data } = await api.patch(`/cart/items/${itemId}`, { quantity });
            this.apply(data);
        },
        async remove(itemId) {
            const { data } = await api.delete(`/cart/items/${itemId}`);
            this.apply(data);
        },
        async clear() {
            const { data } = await api.delete('/cart');
            this.apply(data);
        },
        reset() {
            Object.assign(this, empty);
        },
    },
});
