<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ShoppingBagIcon, MagnifyingGlassIcon, UserIcon, Bars3Icon, XMarkIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
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
const searchOpen = ref(false);
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
</script>

<template>
    <div class="flex min-h-full flex-col">
        <!-- Value strip -->
        <div class="hidden border-b border-stone-200/70 bg-white md:block">
            <div class="container-x flex h-9 items-center justify-center gap-8 text-[12px] text-stone-600">
                <span v-for="v in ['✦ Мэргэжлийн бүтээгдэхүүн', '✦ Албан ёсны баталгаа', '✦ 24 цагт хүргэлт', '✦ QPay төлбөр']" :key="v">{{ v }}</span>
            </div>
        </div>

        <header class="sticky top-0 z-40 border-b border-stone-200/70 bg-white/95 backdrop-blur">
            <div class="container-x flex h-[72px] items-center gap-4">
                <button class="-ml-2 p-2 text-stone-800 lg:hidden" @click="mobileOpen = !mobileOpen">
                    <Bars3Icon v-if="!mobileOpen" class="h-6 w-6" /><XMarkIcon v-else class="h-6 w-6" />
                </button>
                <router-link :to="{ name: 'home' }" class="flex shrink-0 items-center" :title="shopName">
                    <img :src="'/images/logo.png'" :alt="shopName" class="h-9 w-auto sm:h-11" />
                </router-link>

                <nav class="mx-auto hidden items-center gap-8 text-[13.5px] font-medium text-stone-700 lg:flex">
                    <router-link :to="{ name: 'shop' }" class="hover:text-brand-700" :class="route.name === 'shop' && !route.query.category && 'text-brand-700'">Дэлгүүр</router-link>
                    <router-link :to="{ name: 'shop', query: { sort: 'popular' } }" class="hover:text-brand-700">Их зарагддаг</router-link>
                    <Menu as="div" class="relative">
                        <MenuButton class="flex items-center gap-1 hover:text-brand-700">Ангилал <ChevronDownIcon class="h-3.5 w-3.5" /></MenuButton>
                        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="-translate-y-1 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <MenuItems class="absolute left-1/2 mt-4 grid w-[520px] -translate-x-1/2 grid-cols-2 gap-1 rounded-2xl bg-white p-2 shadow-xl ring-1 ring-stone-200/70 focus:outline-none">
                                <MenuItem v-for="c in categories" :key="c.id" v-slot="{ active }">
                                    <router-link :to="{ name: 'shop', query: { category: c.slug } }" class="flex items-center gap-3 rounded-xl p-2" :class="active && 'bg-cream-100'">
                                        <img :src="c.image" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover" /><span class="min-w-0"><span class="block truncate text-sm font-medium text-stone-900">{{ c.name }}</span><span class="block truncate text-xs text-stone-500">{{ c.products_count }} бараа</span></span>
                                    </router-link>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <router-link :to="{ name: 'shop', query: { status: 'sale' } }" class="hover:text-brand-700">Хямдрал</router-link>
                    <router-link :to="{ name: 'shop', query: { sort: 'newest' } }" class="hover:text-brand-700">Шинэ</router-link>
                    <router-link :to="{ name: 'shop', query: { status: 'preorder' } }" class="hover:text-brand-700">Урьдчилсан захиалга</router-link>
                </nav>

                <div class="ml-auto flex items-center gap-1 lg:ml-0">
                    <button @click="searchOpen = !searchOpen" class="rounded-full p-2 text-stone-800 hover:bg-cream-100" title="Хайх"><MagnifyingGlassIcon class="h-5 w-5" stroke-width="1.6" /></button>
                    <Menu v-if="auth.isLoggedIn" as="div" class="relative">
                        <MenuButton class="rounded-full p-2 text-stone-800 hover:bg-cream-100"><UserIcon class="h-5 w-5" stroke-width="1.6" /></MenuButton>
                        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                            <MenuItems class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-stone-200/70 focus:outline-none">
                                <div class="px-3 py-2 text-xs text-stone-500">{{ auth.user.name }}</div>
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
                    <router-link v-else :to="{ name: 'login' }" class="rounded-full p-2 text-stone-800 hover:bg-cream-100" title="Нэвтрэх"><UserIcon class="h-5 w-5" stroke-width="1.6" /></router-link>
                    <button @click="ui.cartOpen = true" class="relative rounded-full p-2 text-stone-800 hover:bg-cream-100" title="Сагс">
                        <ShoppingBagIcon class="h-5 w-5" stroke-width="1.6" />
                        <span v-if="cart.count" class="absolute -right-0.5 -top-0.5 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-brand-700 px-1 text-[10px] font-bold text-white">{{ cart.count }}</span>
                    </button>
                </div>
            </div>

            <!-- Search row (toggle) -->
            <transition name="fade">
                <div v-if="searchOpen" class="border-t border-stone-200/70 bg-white">
                    <form @submit.prevent="submitSearch(); searchOpen = false" class="container-x flex items-center gap-3 py-3">
                        <MagnifyingGlassIcon class="h-5 w-5 text-stone-400" />
                        <input v-model="search" type="search" autofocus placeholder="Бараа хайх..." class="h-10 flex-1 border-0 bg-transparent text-sm focus:ring-0" />
                        <button class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Хайх</button>
                    </form>
                </div>
            </transition>

            <!-- Mobile nav -->
            <transition name="fade">
                <div v-if="mobileOpen" class="border-t border-stone-200/70 bg-white lg:hidden">
                    <div class="container-x grid gap-1 py-3 sm:grid-cols-2">
                        <router-link :to="{ name: 'shop' }" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-cream-100">Дэлгүүр</router-link>
                        <router-link :to="{ name: 'shop', query: { status: 'sale' } }" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-cream-100">Хямдрал</router-link>
                        <router-link :to="{ name: 'shop', query: { sort: 'newest' } }" @click="mobileOpen = false" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-cream-100">Шинэ</router-link>
                        <router-link v-for="c in categories" :key="c.id" :to="{ name: 'shop', query: { category: c.slug } }" @click="mobileOpen = false" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-cream-100"><img :src="c.image" alt="" class="h-7 w-7 rounded-md object-cover" /> {{ c.name }}</router-link>
                    </div>
                </div>
            </transition>
        </header>

        <div v-if="auth.needsVerification && route.name !== 'verify'" class="border-b border-amber-200 bg-amber-50">
            <div class="container-x flex flex-wrap items-center justify-between gap-2 py-2 text-sm text-amber-900">
                <span>📱 Захиалга өгөхийн тулд утасны дугаараа SMS-ээр баталгаажуулна уу.</span>
                <router-link :to="{ name: 'verify' }" class="btn-sm btn bg-amber-500 text-white hover:bg-amber-600">Баталгаажуулах</router-link>
            </div>
        </div>

        <main class="flex-1">
            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in"><component :is="Component" /></transition>
            </router-view>
        </main>

        <footer class="mt-16 border-t border-stone-200/70 bg-cream-100">
            <div class="container-x grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <img :src="'/images/logo.png'" :alt="shopName" class="h-10 w-auto" />
                    <p class="mt-4 max-w-sm text-sm text-stone-600">Гоо сайхны салон, үсчин, косметологичдод зориулсан мэргэжлийн тоног төхөөрөмж, хэрэгслийн нэгдсэн нийлүүлэгч.</p>
                    <div class="mt-5 flex gap-2"><span v-for="b in ['QPay', 'Хаан банк', 'Голомт', 'ХХБ', 'Хас банк']" :key="b" class="rounded-md bg-white px-2 py-1 text-[11px] font-medium text-stone-600 ring-1 ring-stone-200/70">{{ b }}</span></div>
                </div>
                <div>
                    <h4 class="font-display text-lg text-stone-900">Ангилал</h4>
                    <ul class="mt-4 space-y-2 text-sm text-stone-600"><li v-for="c in categories" :key="c.id"><router-link :to="{ name: 'shop', query: { category: c.slug } }" class="hover:text-brand-700">{{ c.name }}</router-link></li></ul>
                </div>
                <div>
                    <h4 class="font-display text-lg text-stone-900">Хэрэглэгч</h4>
                    <ul class="mt-4 space-y-2 text-sm text-stone-600">
                        <li><router-link :to="{ name: 'account' }" class="hover:text-brand-700">Миний самбар</router-link></li>
                        <li><router-link :to="{ name: 'orders' }" class="hover:text-brand-700">Захиалга хянах</router-link></li>
                        <li><router-link :to="{ name: 'addresses' }" class="hover:text-brand-700">Хүргэлтийн хаягууд</router-link></li>
                        <li><router-link :to="{ name: 'verify' }" class="hover:text-brand-700">Баталгаажуулалт</router-link></li>
                        <li><router-link :to="{ name: 'cart' }" class="hover:text-brand-700">Сагс</router-link></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-display text-lg text-stone-900">Холбоо барих</h4>
                    <ul class="mt-4 space-y-2 text-sm text-stone-600"><li>7700-1122</li><li>info@chanaresui.mn</li><li>Улаанбаатар, Сүхбаатар дүүрэг, Чанар Есүй төв</li><li>Даваа–Бямба 09:00–20:00</li></ul>
                </div>
            </div>
            <div class="border-t border-stone-200/70 py-5 text-center text-xs text-stone-500">© {{ new Date().getFullYear() }} {{ shopName }}. Бүх эрх хуулиар хамгаалагдсан.</div>
        </footer>

        <CartDrawer />
    </div>
</template>
