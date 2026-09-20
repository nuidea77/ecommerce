<script setup>
import ProductCard from './ProductCard.vue';
defineProps({ title: String, subtitle: String, products: { type: Array, default: () => [] }, to: { type: Object, default: () => ({ name: 'shop' }) }, accent: { type: String, default: 'bg-brand-700' } });
</script>
<template>
    <section v-if="products.length" class="container-x py-6">
        <div class="mb-4 flex items-end justify-between gap-3">
            <div class="flex items-center gap-3"><span class="h-7 w-1.5 rounded-full" :class="accent"></span><div><h2 class="text-xl font-bold sm:text-2xl">{{ title }}</h2><p v-if="subtitle" class="text-xs text-stone-500">{{ subtitle }}</p></div></div>
            <router-link :to="to" class="whitespace-nowrap text-sm font-medium text-brand-700 hover:underline">Бүгдийг үзэх →</router-link>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5">
            <ProductCard v-for="p in products" :key="p.id" :product="p" />
        </div>
    </section>
</template>
