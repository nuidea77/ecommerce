import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

const CART_KEY = 'bp_cart_token';
export const getCartToken = () => localStorage.getItem(CART_KEY);
export const setCartToken = (t) => (t ? localStorage.setItem(CART_KEY, t) : localStorage.removeItem(CART_KEY));

api.interceptors.request.use((config) => {
    const token = getCartToken();
    if (token) config.headers['X-Cart-Token'] = token;
    return config;
});

let csrfReady = false;
export async function ensureCsrf() {
    if (csrfReady) return;
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    csrfReady = true;
}

api.interceptors.request.use(async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes((config.method || '').toLowerCase())) {
        await ensureCsrf();
    }
    return config;
});

api.interceptors.response.use(
    (r) => r,
    async (error) => {
        if (error.response?.status === 419 && !error.config.__retried) {
            csrfReady = false;
            await ensureCsrf();
            error.config.__retried = true;
            return api.request(error.config);
        }
        return Promise.reject(error);
    }
);

export function errorMessage(error, fallback = 'Алдаа гарлаа. Дахин оролдоно уу.') {
    const data = error?.response?.data;
    if (data?.errors) {
        const first = Object.values(data.errors)[0];
        return Array.isArray(first) ? first[0] : String(first);
    }
    return data?.message || fallback;
}

export default api;
