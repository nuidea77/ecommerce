<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ShoppingCartIcon, MagnifyingGlassIcon, UserIcon, Bars3Icon, XMarkIcon, ClipboardDocumentListIcon, PhoneIcon, TruckIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '../stores/auth';
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import CartDrawer from '../components/CartDrawer.vue';
import api from '../lib/api';

const auth = useAuthStore();
const cart = useCartStore();
const ui = useUiStore();
const router = useRouter();
const route = useRoute();

const search = ref(route.query.q || '');
const mobileOpen = ref(false);
const categories = ref([]);

onMounted(async () => {
    cart.fetch();
    const { data } = await api.get('/categories');
    categories.value = data;
});

function submitSearch() {
    router.push({ name: 'shop', query: { q: search.value || undefined } });
    mobileOpen.value = false;
}

async function logout() {
    await auth.logout();
    cart.reset();
    cart.fetch();
    ui.toast('Системээс гарлаа', 'info');
    router.push({ name: 'home' });
}

const shopName = computed(() => ui.config?.name || 'Чанар Есүй');
const links = [
    { label: 'Бүх бараа', to: { name: 'shop' } },
    { label: 'Хямдралтай', to: { name: 'shop', query: { sort: 'price_asc' } } },
    { label: 'Шинэ бараа', to: { name: 'shop', query: { sort: 'newest' } } },
    { label: 'Бэлэн бараа', to: { name: 'shop', query: { in_stock: '1' } } },
    { label: 'Урьдчилсан захиалга', to: { name: 'shop', query: { in_stock: '0' } } },
];
</script>

<template>
    <div class="flex min-h-full flex-col">
        <!-- Utility bar -->
        <div class="hidden bg-brand-900 text-xs text-cream-200/90 md:block">
            <div class="container-x flex h-8 items-center gap-6">
                <span class="flex items-center gap-1.5"><TruckIcon class="h-4 w-4 text-gold-400" /> 300,000₮-с дээш захиалгад хүргэлт үнэгүй</span>
                <span class="flex items-center gap-1.5"><PhoneIcon class="h-4 w-4 text-gold-400" /> 7700-1122 (09:00–20:00)</span>
                <div class="ml-auto flex items-center gap-4">
                    <router-link :to="{ name: 'orders' }" class="hover:text-white">Захиалга хянах</router-link>
                    <router-link :to="{ name: 'verify' }" class="hover:text-white">Баталгаажуулалт</router-link>
                    <router-link v-if="auth.isAdmin" :to="{ name: 'admin.dashboard' }" class="text-gold-300 hover:text-white">Админ</router-link>
                    <router-link v-if="auth.isCourier || auth.isAdmin" :to="{ name: 'courier.deliveries' }" class="text-gold-300 hover:text-white">Хүргэлт</router-link>
                </div>
            </div>
        </div>

        <header class="sticky top-0 z-40 border-b border-cream-300/70 bg-white/95 backdrop-blur">
            <!-- Main row -->
            <div class="container-x flex h-[68px] items-center gap-3 md:gap-5">
                <button class="-ml-2 p-2 text-brand-900 lg:hidden" @click="mobileOpen = !mobileOpen">
                    <Bars3Icon v-if="!mobileOpen" class="h-6 w-6" /><XMarkIcon v-else class="h-6 w-6" />
                </button>

                <router-link :to="{ name: 'home' }" class="flex shrink-0 items-center" :title="shopName">
                    <img :src="'/images/logo.png'" :alt="shopName" class="h-9 w-auto sm:h-11" />
                </router-link>

                <Menu as="div" class="relative hidden lg:block">
                    <MenuButton class="flex h-11 items-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-semibold text-white hover:bg-brand-800"><Bars3Icon class="h-5 w-5" /> Ангилал <ChevronDownIcon class="h-4 w-4 opacity-70" /></MenuButton>
                    <transition enter-active-class="transition duration-100 ease-out" enter-from-class="-translate-y-1 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <MenuItems class="absolute left-0 mt-2 w-72 rounded-xl bg-paper p-1.5 shadow-xl ring-1 ring-cream-300/70 focus:outline-none">
                            <MenuItem v-for="c in categories" :key="c.id" v-slot="{ active }">
                                <router-link :to="{ name: 'shop', query: { category: c.slug } }" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm" :class="active && 'bg-brand-50 text-brand-800'">
                                    <img :src="c.image" alt="" class="h-8 w-8 rounded-md object-cover" /><span class="flex-1 font-medium">{{ c.name }}</span><span class="text-xs text-stone-400">{{ c.products_count }}</span>
                                </router-link>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>

                <form @submit.prevent="submitSearch" class="hidden flex-1 md:flex">
                    <div class="flex w-full overflow-hidden rounded-xl bg-paper ring-1 ring-brand-700/40 focus-within:ring-2 focus-within:ring-brand-700">
                        <input v-model="search" type="search" placeholder="Бараа хайх..." class="h-11 flex-1 border-0 bg-transparent px-4 text-sm focus:ring-0" />
                        <button class="flex items-center gap-1.5 bg-brand-700 px-5 text-sm font-semibold text-white hover:bg-brand-800"><MagnifyingGlassIcon class="h-5 w-5" /><span class="hidden lg:inline">Хайх</span></button>
                    </div>
                </form>

                <div class="ml-auto flex items-center gap-1 sm:gap-2">
                    <Menu v-if="auth.isLoggedIn" as="div" class="relative">
                        <MenuButton class="flex flex-col items-center rounded-lg px-2 py-1 text-brand-900 hover:bg-brand-50 sm:px-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-cream-100">{{ auth.user.name.slice(0, 1) }}</span>
                            <span class="hidden max-w-[80px] truncate text-[11px] font-medium sm:block">{{ auth.user.name }}</span>
                        </MenuButton>
                        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                            <MenuItems class="absolute right-0 mt-2 w-52 origin-top-right rounded-xl bg-paper p-1.5 shadow-lg ring-1 ring-cream-300/70 focus:outline-none">
                                <MenuItem v-if="auth.isAdmin" v-slot="{ active }"><router-link :to="{ name: 'admin.dashboard' }" class="block rounded-lg px-3 py-2 text-sm font-medium text-brand-700" :class="active && 'bg-cream-100'">⚙️ Админ самбар</router-link></MenuItem>
                                <MenuItem v-if="auth.isCourier || auth.isAdmin" v-slot="{ active }"><router-link :to="{ name: 'courier.deliveries' }" class="block rounded-lg px-3 py-2 text-sm font-medium text-sky-700" :class="active && 'bg-cream-100'">🛵 Хүргэлтүүд</router-link></MenuItem>
                                <MenuItem v-slot="{ active }"><router-link :to="{ name: 'account' }" class="block rounded-lg px-3 py-2 text-sm" :class="active && 'bg-cream-100'">Миний самбар</router-link></MenuItem>
                                <MenuItem v-slot="{ active }"><router-link :to="{ name: 'orders' }" class="block rounded-lg px-3 py-2 text-sm" :class="active && 'bg-cream-100'">Миний захиалгууд</router-link></MenuItem>
                                <MenuItem v-slot="{ active }"><router-link :to="{ name: 'verify' }" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm" :class="active && 'bg-cream-100'">Баталгаажуулалт <span v-if="auth.isVerified" class="text-emerald-600">✓</span><span v-else class="badge bg-amber-100 text-amber-800">Хүлээгдэж буй</span></router-link></MenuItem>
                                <MenuItem v-slot="{ active }"><router-link :to="{ name: 'profile' }" class="block rounded-lg px-3 py-2 text-sm" :class="active && 'bg-cream-100'">Профайл</router-link></MenuItem>
                                <MenuItem v-slot="{ active }"><button @click="logout" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-red-600" :class="active && 'bg-cream-100'">Гарах</button></MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <router-link v-else :to="{ name: 'login' }" class="flex flex-col items-center rounded-lg px-2 py-1 text-brand-900 hover:bg-brand-50 sm:px-3">
                        <UserIcon class="h-6 w-6" /><span class="hidden text-[11px] font-medium sm:block">Нэвтрэх</span>
                    </router-link>
                    <router-link :to="{ name: 'orders' }" class="hidden flex-col items-center rounded-lg px-3 py-1 text-brand-900 hover:bg-brand-50 md:flex">
                        <ClipboardDocumentListIcon class="h-6 w-6" /><span class="text-[11px] font-medium">Захиалга</span>
                    </router-link>
                    <button @click="ui.cartOpen = true" class="relative flex flex-col items-center rounded-lg px-2 py-1 text-brand-900 hover:bg-brand-50 sm:px-3">
                        <ShoppingCartIcon class="h-6 w-6" /><span class="hidden text-[11px] font-medium sm:block">Сагс</span>
                        <span v-if="cart.count" class="absolute right-0.5 top-0 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold text-white">{{ cart.count }}</span>
                    </button>
                </div>
            </div>

            <!-- Link row -->
            <div class="hidden border-t border-cream-300/60 lg:block">
                <div class="container-x flex h-10 items-center gap-1 overflow-x-auto text-[13px] font-medium text-brand-900/80 [scrollbar-width:none]">
                    <router-link v-for="l in links" :key="l.label" :to="l.to" class="whitespace-nowrap rounded-md px-3 py-1.5 hover:bg-brand-50 hover:text-brand-700">{{ l.label }}</router-link>
                    <span class="mx-2 h-4 w-px shrink-0 bg-cream-300"></span>
                    <router-link v-for="c in categories" :key="c.id" :to="{ name: 'shop', query: { category: c.slug } }" class="whitespace-nowrap rounded-md px-3 py-1.5 hover:bg-brand-50 hover:text-brand-700" :class="route.query.category === c.slug && 'bg-brand-50 text-brand-700'">{{ c.name }}</router-link>
                </div>
            </div>

            <!-- Mobile search + nav -->
            <div class="container-x pb-3 md:hidden">
                <form @submit.prevent="submitSearch" class="flex overflow-hidden rounded-xl bg-paper ring-1 ring-brand-700/40"><input v-model="search" type="search" placeholder="Бараа хайх..." class="h-10 flex-1 border-0 bg-transparent px-3 text-sm focus:ring-0" /><button class="bg-brand-700 px-4 text-white"><MagnifyingGlassIcon class="h-5 w-5" /></button></form>
            </div>
            <transition name="fade">
                <div v-if="mobileOpen" class="border-t border-cream-300/60 bg-white lg:hidden">
                    <div class="container-x grid gap-1 py-3 sm:grid-cols-2">
                        <router-link v-for="l in links" :key="l.label" :to="l.to" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-50">{{ l.label }}</router-link>
                        <router-link v-for="c in categories" :key="c.id" :to="{ name: 'shop', query: { category: c.slug } }" @click="mobileOpen = false" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50"><img :src="c.image" alt="" class="h-7 w-7 rounded-md" /> {{ c.name }}</router-link>
                    </div>
                </div>
            </transition>
        </header>

        <div v-if="auth.needsVerification && route.name !== 'verify'" class="border-b border-amber-200 bg-amber-50">
            <div class="container-x flex flex-wrap items-center justify-between gap-2 py-2 text-sm text-amber-900">
                <span>📱 Захиалга өгөхийн тулд утасны дугаараа SMS-ээр (verify.mn) баталгаажуулна уу.</span>
                <router-link :to="{ name: 'verify' }" class="btn-sm btn bg-amber-500 text-white hover:bg-amber-600">Баталгаажуулах</router-link>
            </div>
        </div>

        <main class="flex-1">
            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in"><component :is="Component" /></transition>
            </router-view>
        </main>

        <footer class="mt-14 bg-brand-900 text-cream-200">
            <div class="border-b border-white/10">
                <div class="container-x grid gap-4 py-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="f in [['🚚', 'Шуурхай хүргэлт', 'УБ хотод 24 цагт, орон нутагт 2–5 хоногт'], ['📱', 'QPay төлбөр', 'Бүх банкны аппаар QR уншуулж төлнө'], ['⏳', 'Урьдчилсан захиалга', 'Дууссан барааг захиалж, ирэхэд нь хүргүүлнэ'], ['🛡️', 'Албан ёсны баталгаа', 'Тоног төхөөрөмжид 12–24 сарын баталгаа']]" :key="f[1]" class="flex items-start gap-3">
                        <span class="text-2xl">{{ f[0] }}</span><div><p class="text-sm font-semibold text-white">{{ f[1] }}</p><p class="text-xs text-cream-300/70">{{ f[2] }}</p></div>
                    </div>
                </div>
            </div>
            <div class="container-x grid gap-8 py-12 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <img :src="'/images/logo-light.png'" :alt="shopName" class="h-10 w-auto" />
                    <p class="mt-4 max-w-sm text-sm text-cream-300/80">Гоо сайхны салон, үсчин, косметологичдод зориулсан мэргэжлийн тоног төхөөрөмж, хэрэгслийн нэгдсэн нийлүүлэгч.</p>
                    <div class="mt-5 flex gap-2"><span v-for="b in ['QPay', 'Хаан банк', 'Голомт', 'ХХБ', 'Бэлэн']" :key="b" class="rounded-md bg-white/10 px-2 py-1 text-[11px] font-medium">{{ b }}</span></div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gold-400">Ангилал</h4>
                    <ul class="mt-3 space-y-2 text-sm text-cream-200/80"><li v-for="c in categories" :key="c.id"><router-link :to="{ name: 'shop', query: { category: c.slug } }" class="hover:text-gold-300">{{ c.name }}</router-link></li></ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gold-400">Хэрэглэгчид</h4>
                    <ul class="mt-3 space-y-2 text-sm text-cream-200/80">
                        <li><router-link :to="{ name: 'account' }" class="hover:text-gold-300">Миний самбар</router-link></li>
                        <li><router-link :to="{ name: 'orders' }" class="hover:text-gold-300">Захиалга хянах</router-link></li>
                        <li><router-link :to="{ name: 'verify' }" class="hover:text-gold-300">Баталгаажуулалт</router-link></li>
                        <li><router-link :to="{ name: 'cart' }" class="hover:text-gold-300">Сагс</router-link></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gold-400">Холбоо барих</h4>
                    <ul class="mt-3 space-y-2 text-sm text-cream-200/80">
                        <li>📞 7700-1122</li><li>✉️ info@chanaresui.mn</li><li>📍 Улаанбаатар, Сүхбаатар дүүрэг, Чанар Есүй төв</li><li>🕘 Даваа–Бямба 09:00–20:00</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10 py-4 text-center text-xs text-cream-300/60">© {{ new Date().getFullYear() }} {{ shopName }}. Бүх эрх хуулиар хамгаалагдсан.</div>
        </footer>

        <CartDrawer />
    </div>
</template>
