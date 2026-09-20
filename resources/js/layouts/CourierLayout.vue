<script setup>
import { useRouter } from 'vue-router';
import { ArrowLeftStartOnRectangleIcon, BuildingStorefrontIcon, TruckIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '../stores/auth';
const auth = useAuthStore();
const router = useRouter();
async function logout() { await auth.logout(); router.push({ name: 'login' }); }
</script>

<template>
    <div class="flex min-h-full flex-col bg-cream-100">
        <header class="sticky top-0 z-20 bg-brand-800 text-white shadow">
            <div class="container-x flex h-16 items-center gap-3">
                <router-link :to="{ name: 'courier.deliveries' }" class="flex items-center gap-3 font-semibold"><img :src="'/images/logo-mark-light.png'" alt="" class="h-8 w-8" /><span class="flex items-center gap-2"><TruckIcon class="h-5 w-5 text-gold-400" /> Хүргэлтийн систем</span></router-link>
                <div class="ml-auto flex items-center gap-2 text-sm">
                    <span class="hidden sm:block">{{ auth.user?.name }}</span>
                    <router-link v-if="auth.isAdmin" :to="{ name: 'admin.dashboard' }" class="rounded-lg bg-white/10 px-2.5 py-1.5 text-xs hover:bg-white/20">Админ</router-link>
                    <router-link :to="{ name: 'home' }" class="rounded-lg p-2 hover:bg-white/10" title="Дэлгүүр"><BuildingStorefrontIcon class="h-5 w-5" /></router-link>
                    <button @click="logout" class="rounded-lg p-2 hover:bg-white/10" title="Гарах"><ArrowLeftStartOnRectangleIcon class="h-5 w-5" /></button>
                </div>
            </div>
        </header>
        <main class="container-x flex-1 py-6"><router-view /></main>
    </div>
</template>
