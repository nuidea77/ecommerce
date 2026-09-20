<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Squares2X2Icon, CubeIcon, TagIcon, ClipboardDocumentListIcon, UsersIcon, ArrowLeftStartOnRectangleIcon, Bars3Icon, XMarkIcon, BuildingStorefrontIcon, TruckIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const open = ref(false);

const nav = [
    { name: 'admin.dashboard', label: 'Хянах самбар', icon: Squares2X2Icon, exact: true },
    { name: 'admin.orders', label: 'Захиалгууд', icon: ClipboardDocumentListIcon },
    { name: 'admin.products', label: 'Бүтээгдэхүүн', icon: CubeIcon },
    { name: 'admin.categories', label: 'Ангилал', icon: TagIcon },
    { name: 'admin.users', label: 'Хэрэглэгчид', icon: UsersIcon },
    { name: 'courier.deliveries', label: 'Хүргэлт (courier view)', icon: TruckIcon },
];

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="flex min-h-full bg-cream-100">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-40 w-64 transform bg-brand-900 text-cream-200/80 transition-transform lg:static lg:translate-x-0" :class="open ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center gap-2 px-5">
                <img :src="'/images/logo-light.png'" alt="Чанар Есүй" class="h-8 w-auto" />
                <span class="rounded-md bg-gold-500/20 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-gold-300">Админ</span>
                <button class="ml-auto lg:hidden" @click="open = false"><XMarkIcon class="h-5 w-5" /></button>
            </div>
            <nav class="mt-4 space-y-1 px-3">
                <router-link v-for="n in nav" :key="n.name" :to="{ name: n.name }" @click="open = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-brand-800 hover:text-white" :exact-active-class="n.exact ? 'bg-brand-800 text-white' : ''" :active-class="n.exact ? '' : 'bg-brand-800 text-white'">
                    <component :is="n.icon" class="h-5 w-5" />{{ n.label }}
                </router-link>
            </nav>
            <div class="absolute bottom-0 w-full space-y-1 border-t border-white/10 p-3">
                <router-link :to="{ name: 'home' }" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-brand-800 hover:text-white"><BuildingStorefrontIcon class="h-5 w-5" />Дэлгүүр рүү</router-link>
                <button @click="logout" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-brand-800 hover:text-white"><ArrowLeftStartOnRectangleIcon class="h-5 w-5" />Гарах</button>
            </div>
        </aside>
        <div v-if="open" class="fixed inset-0 z-30 bg-stone-900/50 lg:hidden" @click="open = false"></div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-cream-300/60 bg-cream-50 px-4 sm:px-6">
                <button class="lg:hidden" @click="open = true"><Bars3Icon class="h-6 w-6 text-stone-600" /></button>
                <p class="text-sm text-stone-500">Удирдлагын систем</p>
                <div class="ml-auto flex items-center gap-2 text-sm">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-700 font-bold text-cream-100">{{ auth.user?.name?.slice(0, 1) }}</span>
                    <span class="hidden font-medium sm:block">{{ auth.user?.name }}</span>
                </div>
            </header>
            <main class="flex-1 p-4 sm:p-6 lg:p-8"><router-view /></main>
        </div>
    </div>
</template>
