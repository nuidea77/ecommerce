<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-stone-50 text-center">
        <p class="font-display text-7xl font-bold text-brand-600">404</p>
        <h1 class="mt-4 text-2xl font-bold">Хуудас олдсонгүй</h1>
        <router-link :to="{ name: 'home' }" class="btn-brand mt-6">Нүүр хуудас руу</router-link>
    </div>
</template>
