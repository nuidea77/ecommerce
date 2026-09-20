<script setup>
import { ref } from 'vue';
import ProductCard from './ProductCard.vue';
import { ChevronLeftIcon, ChevronRightIcon, ArrowRightIcon } from '@heroicons/vue/20/solid';
defineProps({ title: String, products: { type: Array, default: () => [] }, to: { type: Object, default: () => ({ name: 'shop' }) } });
const track = ref(null);
const scroll = (dir) => track.value?.scrollBy({ left: dir * (track.value.clientWidth * 0.8), behavior: 'smooth' });
</script>
<template>
    <section v-if="products.length" class="container-x py-10">
        <div class="mb-5 flex items-end justify-between">
            <h2 class="font-display text-3xl text-stone-900">{{ title }}</h2>
            <router-link :to="to" class="flex items-center gap-1.5 text-sm font-medium text-stone-700 hover:text-brand-700">Бүгдийг үзэх <ArrowRightIcon class="h-4 w-4" /></router-link>
        </div>
        <div class="relative">
            <button @click="scroll(-1)" class="absolute -left-4 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md ring-1 ring-stone-200 hover:bg-stone-50 lg:flex"><ChevronLeftIcon class="h-5 w-5" /></button>
            <div ref="track" class="flex snap-x gap-4 overflow-x-auto pb-2 [scrollbar-width:none]">
                <div v-for="p in products" :key="p.id" class="w-[68%] shrink-0 snap-start sm:w-[42%] md:w-[30%] lg:w-[23%] xl:w-[19%]"><ProductCard :product="p" /></div>
            </div>
            <button @click="scroll(1)" class="absolute -right-4 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md ring-1 ring-stone-200 hover:bg-stone-50 lg:flex"><ChevronRightIcon class="h-5 w-5" /></button>
        </div>
    </section>
</template>
