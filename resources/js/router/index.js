import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/',
        component: () => import('../layouts/StoreLayout.vue'),
        children: [
            { path: '', name: 'home', component: () => import('../pages/Home.vue') },
            { path: 'shop', name: 'shop', component: () => import('../pages/Shop.vue') },
            { path: 'products/:slug', name: 'product', component: () => import('../pages/ProductDetail.vue') },
            { path: 'cart', name: 'cart', component: () => import('../pages/Cart.vue') },
            { path: 'checkout', name: 'checkout', component: () => import('../pages/Checkout.vue'), meta: { auth: true } },
            { path: 'orders', name: 'orders', component: () => import('../pages/Orders.vue'), meta: { auth: true } },
            { path: 'orders/:number', name: 'order', component: () => import('../pages/OrderDetail.vue'), meta: { auth: true } },
            { path: 'orders/:number/pay', name: 'pay', component: () => import('../pages/Payment.vue'), meta: { auth: true } },
            { path: 'profile', name: 'profile', component: () => import('../pages/Profile.vue'), meta: { auth: true } },
            { path: 'login', name: 'login', component: () => import('../pages/auth/Login.vue'), meta: { guest: true } },
            { path: 'register', name: 'register', component: () => import('../pages/auth/Register.vue'), meta: { guest: true } },
        ],
    },
    {
        path: '/admin',
        component: () => import('../layouts/AdminLayout.vue'),
        meta: { auth: true, roles: ['admin'] },
        children: [
            { path: '', name: 'admin.dashboard', component: () => import('../pages/admin/Dashboard.vue') },
            { path: 'products', name: 'admin.products', component: () => import('../pages/admin/Products.vue') },
            { path: 'products/new', name: 'admin.products.new', component: () => import('../pages/admin/ProductForm.vue') },
            { path: 'products/:id/edit', name: 'admin.products.edit', component: () => import('../pages/admin/ProductForm.vue') },
            { path: 'categories', name: 'admin.categories', component: () => import('../pages/admin/Categories.vue') },
            { path: 'orders', name: 'admin.orders', component: () => import('../pages/admin/Orders.vue') },
            { path: 'orders/:id', name: 'admin.order', component: () => import('../pages/admin/OrderDetail.vue') },
            { path: 'users', name: 'admin.users', component: () => import('../pages/admin/Users.vue') },
        ],
    },
    {
        path: '/courier',
        component: () => import('../layouts/CourierLayout.vue'),
        meta: { auth: true, roles: ['courier', 'admin'] },
        children: [
            { path: '', name: 'courier.deliveries', component: () => import('../pages/courier/Deliveries.vue') },
            { path: 'deliveries/:id', name: 'courier.delivery', component: () => import('../pages/courier/DeliveryDetail.vue') },
        ],
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../pages/NotFound.vue') },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, saved) {
        if (saved) return saved;
        if (to.hash) return { el: to.hash };
        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (!auth.loaded) await auth.fetch();

    const needsAuth = to.matched.some((r) => r.meta.auth);
    const roles = to.matched.flatMap((r) => r.meta.roles || []);

    if (needsAuth && !auth.isLoggedIn) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }
    if (roles.length && !roles.includes(auth.user?.role)) {
        return { name: 'home' };
    }
    if (to.meta.guest && auth.isLoggedIn) {
        return auth.isAdmin ? { name: 'admin.dashboard' } : auth.isCourier ? { name: 'courier.deliveries' } : { name: 'home' };
    }
    return true;
});

export default router;
