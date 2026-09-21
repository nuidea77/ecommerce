<script setup>
import { useAuthStore } from '../../stores/auth';
import { Squares2X2Icon, ClipboardDocumentListIcon, UserCircleIcon, ShieldCheckIcon, MapPinIcon } from '@heroicons/vue/24/outline';
defineProps({ title: String });
const auth = useAuthStore();
const nav = [
    { name: 'account', label: 'Самбар', icon: Squares2X2Icon, exact: true },
    { name: 'orders', label: 'Захиалгууд', icon: ClipboardDocumentListIcon },
    { name: 'addresses', label: 'Хаягууд', icon: MapPinIcon },
    { name: 'verify', label: 'Баталгаажуулалт', icon: ShieldCheckIcon },
    { name: 'profile', label: 'Профайл', icon: UserCircleIcon },
];
</script>
<template>
    <div class="container-x py-8">
        <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="card p-4">
                    <div class="flex items-center gap-3 border-b border-stone-100 pb-4">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-700 text-lg font-bold text-cream-100">{{ auth.user.name.slice(0, 1) }}</span>
                        <div class="min-w-0"><p class="truncate font-semibold">{{ auth.user.name }}</p><p class="truncate font-mono text-xs text-stone-500">{{ auth.user.phone }}</p></div>
                    </div>
                    <nav class="mt-3 flex gap-1 overflow-x-auto lg:flex-col">
                        <router-link v-for="n in nav" :key="n.name" :to="{ name: n.name }" class="flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-stone-600 hover:bg-cream-100 hover:text-stone-900" :exact-active-class="n.exact ? 'bg-brand-50 text-brand-700' : ''" :active-class="n.exact ? '' : 'bg-brand-50 text-brand-700'">
                            <component :is="n.icon" class="h-5 w-5" />{{ n.label }}
                            <span v-if="n.name === 'verify'" class="ml-auto h-2 w-2 rounded-full" :class="auth.isVerified ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                        </router-link>
                    </nav>
                </div>
            </aside>
            <div class="min-w-0">
                <h1 v-if="title" class="mb-6 font-display text-3xl font-bold">{{ title }}</h1>
                <slot />
            </div>
        </div>
    </div>
</template>
