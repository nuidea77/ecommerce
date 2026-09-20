<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ShoppingBagIcon, MagnifyingGlassIcon, UserCircleIcon, Bars3Icon, XMarkIcon, ChevronDownIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
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

const search = ref('');
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
</script>

<template>
    <div class="flex min-h-full flex-col">
        <!-- Top bar -->
        <div class="bg-brand-800 text-center text-xs text-cream-200">
            <div class="container-x py-2">👑 300,000₮-с дээш захиалгад хүргэлт үнэгүй · QPay-ээр аюулгүй төлбөр · Дууссан барааг урьдчилан захиалах боломжтой</div>
        </div>

        <header class="sticky top-0 z-40 border-b border-cream-300/60 bg-cream-100/90 backdrop-blur">
            <div class="container-x flex h-16 items-center gap-4">
                <button class="lg:hidden -ml-2 p-2 text-stone-600" @click="mobileOpen = !mobileOpen">
                    <Bars3Icon v-if="!mobileOpen" class="h-6 w-6" /><XMarkIcon v-else class="h-6 w-6" />
                </button>

                <router-link :to="{ name: 'home' }" class="flex shrink-0 items-center" :title="shopName">
                    <img :src="'/images/logo.png'" :alt="shopName" class="h-9 w-auto sm:h-10" />
                </router-link>

                <nav class="ml-8 hidden items-center gap-1 text-sm font-medium text-brand-900/80 lg:flex">
                    <Menu as="div" class="relative">
                        <MenuButton class="flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-2 hover:bg-brand-50 hover:text-brand-700" :class="route.query.category && 'text-brand-700'"><Squares2X2Icon class="h-4 w-4" /> Ангилал <ChevronDownIcon class="h-3.5 w-3.5" /></MenuButton>
                        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="-translate-y-1 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <MenuItems class="absolute left-0 mt-2 grid w-[560px] grid-cols-2 gap-1 rounded-2xl bg-paper p-2 shadow-xl ring-1 ring-cream-300/70 focus:outline-none">
                                <MenuItem v-for="c in categories" :key="c.id" v-slot="{ active }">
                                    <router-link :to="{ name: 'shop', query: { category: c.slug } }" class="flex items-center gap-3 rounded-xl p-2.5" :class="active && 'bg-brand-50'">
                                        <img :src="c.image" alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover" />
                                        <span class="min-w-0"><span class="block truncate text-sm font-semibold text-stone-900">{{ c.name }}</span><span class="block truncate text-xs text-stone-500">{{ c.description }}</span></span>
                                    </router-link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }"><router-link :to="{ name: 'shop' }" class="col-span-2 mt-1 flex items-center justify-center rounded-xl border-t border-cream-200 p-2.5 text-sm font-semibold text-brand-700" :class="active && 'bg-brand-50'">Бүх бараа үзэх →</router-link></MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <router-link :to="{ name: 'shop' }" class="whitespace-nowrap rounded-full px-3 py-2 hover:bg-brand-50 hover:text-brand-700" :class="route.name === 'shop' && !route.query.category && 'text-brand-700'">Бүх бараа</router-link>
                    <router-link :to="{ name: 'shop', query: { sort: 'newest' } }" class="whitespace-nowrap rounded-full px-3 py-2 hover:bg-brand-50 hover:text-brand-700">Шинэ</router-link>
                    <router-link :to="{ name: 'shop', query: { in_stock: '1' } }" class="whitespace-nowrap rounded-full px-3 py-2 hover:bg-brand-50 hover:text-brand-700">Бэлэн бараа</router-link>
                </nav>

                <form @submit.prevent="submitSearch" class="ml-auto hidden w-64 shrink-0 md:block">
                    <div class="relative">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-stone-400" />
                        <input v-model="search" type="search" placeholder="Бараа хайх..." class="input pl-9 py-2 rounded-full" />
                    </div>
                </form>

                <div class="ml-auto flex items-center gap-1 md:ml-2">
                    <Menu v-if="auth.isLoggedIn" as="div" class="relative">
                        <MenuButton class="flex items-center gap-2 rounded-full p-1.5 pr-3 hover:bg-cream-200/60">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-cream-100">{{ auth.user.name.slice(0, 1) }}</span>
                            <span class="hidden text-sm font-medium sm:block">{{ auth.user.name }}</span>
                        </MenuButton>
                        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                            <MenuItems class="absolute right-0 mt-2 w-52 origin-top-right rounded-xl bg-paper p-1.5 shadow-lg ring-1 ring-stone-200 focus:outline-none">
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
                    <router-link v-else :to="{ name: 'login' }" class="flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-stone-700 hover:bg-cream-200/60">
                        <UserCircleIcon class="h-5 w-5" /><span class="hidden sm:block">Нэвтрэх</span>
                    </router-link>

                    <button @click="ui.cartOpen = true" class="relative rounded-full p-2 text-stone-700 hover:bg-cream-200/60">
                        <ShoppingBagIcon class="h-6 w-6" />
                        <span v-if="cart.count" class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-gold-500 px-1 text-[11px] font-bold text-brand-900">{{ cart.count }}</span>
                    </button>
                </div>
            </div>

            <!-- Mobile nav -->
            <transition name="fade">
                <div v-if="mobileOpen" class="border-t border-cream-300/60 bg-cream-100 lg:hidden">
                    <div class="container-x space-y-1 py-3">
                        <form @submit.prevent="submitSearch" class="mb-2"><input v-model="search" type="search" placeholder="Бараа хайх..." class="input rounded-full" /></form>
                        <router-link :to="{ name: 'shop' }" @click="mobileOpen = false" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-cream-100">Бүх бараа</router-link>
                        <router-link v-for="c in categories" :key="c.id" :to="{ name: 'shop', query: { category: c.slug } }" @click="mobileOpen = false" class="block rounded-lg px-3 py-2 text-sm hover:bg-cream-100">{{ c.icon }} {{ c.name }}</router-link>
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

        <footer class="mt-16 bg-brand-900 text-cream-200">
            <div class="container-x grid gap-8 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <img :src="'/images/logo-light.png'" :alt="shopName" class="h-10 w-auto" />
                    <p class="mt-4 text-sm text-cream-300/80">Гоо сайхны салон, үсчин, косметологичдод зориулсан мэргэжлийн тоног төхөөрөмж, хэрэгслийн нэгдсэн нийлүүлэгч.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gold-400">Ангилал</h4>
                    <ul class="mt-3 space-y-2 text-sm text-cream-200/80">
                        <li v-for="c in categories" :key="c.id"><router-link :to="{ name: 'shop', query: { category: c.slug } }" class="hover:text-gold-300">{{ c.name }}</router-link></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gold-400">Үйлчилгээ</h4>
                    <ul class="mt-3 space-y-2 text-sm text-cream-200/80">
                        <li>Улаанбаатар хотод 24 цагт хүргэнэ</li>
                        <li>Орон нутагт 2–5 хоногт</li>
                        <li>QPay болон бэлэн төлбөр</li>
                        <li>Дууссан барааг урьдчилан захиалах</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gold-400">Холбоо барих</h4>
                    <ul class="mt-3 space-y-2 text-sm text-cream-200/80">
                        <li>📞 7700-1122</li>
                        <li>✉️ info@chanaresui.mn</li>
                        <li>📍 Улаанбаатар, Сүхбаатар дүүрэг, Чанар Есүй төв</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10 py-4 text-center text-xs text-cream-300/60">© {{ new Date().getFullYear() }} {{ shopName }}. Бүх эрх хуулиар хамгаалагдсан.</div>
        </footer>

        <CartDrawer />
    </div>
</template>
