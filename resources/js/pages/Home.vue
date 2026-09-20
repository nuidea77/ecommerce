<script setup>
import { ref, onMounted } from 'vue';
import api from '../lib/api';
import HeroCarousel from '../components/HeroCarousel.vue';
import ProductSection from '../components/ProductSection.vue';
import Spinner from '../components/ui/Spinner.vue';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';

const data = ref(null);
onMounted(async () => { data.value = (await api.get('/home')).data; });
</script>

<template>
    <div>
        <!-- Hero: categories sidebar + carousel + promo tiles -->
        <section class="container-x pt-4">
            <div class="grid gap-4 lg:grid-cols-[240px_1fr_240px]">
                <aside class="hidden rounded-2xl bg-paper ring-1 ring-cream-300/70 lg:block">
                    <p class="border-b border-cream-300/60 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-stone-500">Ангилал</p>
                    <ul class="py-1">
                        <li v-for="c in data?.categories || []" :key="c.id">
                            <router-link :to="{ name: 'shop', query: { category: c.slug } }" class="group flex items-center gap-3 px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-800">
                                <img :src="c.image" alt="" class="h-8 w-8 rounded-md object-cover" /><span class="flex-1 font-medium">{{ c.name }}</span><ChevronRightIcon class="h-4 w-4 text-stone-300 group-hover:text-brand-600" />
                            </router-link>
                        </li>
                    </ul>
                </aside>
                <HeroCarousel />
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-1">
                    <router-link :to="{ name: 'shop', query: { category: 'skin' } }" class="flex items-center gap-3 rounded-2xl bg-paper p-4 ring-1 ring-cream-300/70 transition hover:ring-brand-300">
                        <img :src="'/images/products/hydrafacial-machine-7in1.svg'" alt="" class="h-16 w-16 rounded-xl" /><div><p class="text-xs text-stone-500">Косметологи</p><p class="text-sm font-bold">Гидрафейшл 7-in-1</p><p class="text-xs font-semibold text-brand-700">Үзэх →</p></div>
                    </router-link>
                    <router-link :to="{ name: 'shop', query: { category: 'supplies' } }" class="flex items-center gap-3 rounded-2xl bg-gold-500 p-4 text-brand-900 transition hover:bg-gold-400">
                        <img :src="'/images/products/disposable-towels-pack.svg'" alt="" class="h-16 w-16 rounded-xl" /><div><p class="text-xs opacity-80">Багцаар хямд</p><p class="text-sm font-bold">Хэрэглээний материал</p><p class="text-xs font-semibold">50 / 100 / 500 ш →</p></div>
                    </router-link>
                </div>
            </div>
        </section>

        <Spinner v-if="!data" />
        <template v-else>
            <!-- Category icon row -->
            <section class="container-x py-8">
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-6">
                    <router-link v-for="c in data.categories" :key="c.id" :to="{ name: 'shop', query: { category: c.slug } }" class="group flex flex-col items-center gap-2 rounded-2xl bg-paper p-3 ring-1 ring-cream-300/70 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-brand-300">
                        <img :src="c.image" :alt="c.name" class="h-16 w-16 rounded-full object-cover ring-2 ring-cream-200 transition group-hover:ring-brand-300" />
                        <span class="text-center text-xs font-semibold leading-tight">{{ c.name }}</span>
                        <span class="text-[11px] text-stone-400">{{ c.products_count }} бараа</span>
                    </router-link>
                </div>
            </section>

            <ProductSection title="Хямдралтай бараа" subtitle="Хязгаарлагдмал хугацаанд" :products="data.sale" :to="{ name: 'shop', query: { sort: 'price_asc' } }" accent="bg-red-500" />
            <ProductSection title="Онцлох бүтээгдэхүүн" subtitle="Салонуудын хамгийн их сонгодог" :products="data.featured" accent="bg-gold-500" />

            <!-- Wide promo -->
            <section class="container-x py-6">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-brand-800 to-brand-900 px-6 py-8 text-cream-50 sm:px-10">
                    <div class="relative z-10 max-w-xl">
                        <span class="rounded-full bg-gold-500/20 px-3 py-1 text-xs font-semibold text-gold-300">Урьдчилсан захиалга</span>
                        <h3 class="mt-3 font-display text-2xl font-bold sm:text-3xl">Дууссан бараа ч захиалах боломжтой</h3>
                        <p class="mt-2 text-sm text-cream-200/80">Дуусчихсан барааг урьдчилан захиалснаар дараагийн ачилтаар танд эхэлж хүргэнэ. Төлбөрөө хүргэлт дээр эсвэл QPay-ээр хийнэ.</p>
                        <router-link :to="{ name: 'shop', query: { in_stock: '0' } }" class="btn-gold mt-5">Захиалах</router-link>
                    </div>
                    <img :src="'/images/products/barber-chair-classic.svg'" class="absolute -bottom-8 right-6 hidden w-56 rotate-6 rounded-2xl opacity-90 sm:block" alt="" />
                </div>
            </section>

            <ProductSection title="Шинээр ирсэн" :products="data.newest" :to="{ name: 'shop', query: { sort: 'newest' } }" />
            <ProductSection title="Их зарагддаг" :products="data.bestsellers" accent="bg-brand-500" />

        </template>
    </div>
</template>
