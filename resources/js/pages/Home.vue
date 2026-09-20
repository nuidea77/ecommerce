<script setup>
import { ref, onMounted } from 'vue';
import api from '../lib/api';
import ProductCarousel from '../components/ProductCarousel.vue';
import Spinner from '../components/ui/Spinner.vue';
import { ArrowRightIcon, TruckIcon, ArrowPathIcon, LockClosedIcon, SparklesIcon, HeartIcon, ShieldCheckIcon, GiftIcon, StarIcon, BoltIcon } from '@heroicons/vue/24/outline';

const data = ref(null);
onMounted(async () => { data.value = (await api.get('/home')).data; });

const values = [
    { icon: SparklesIcon, t: 'Мэргэжлийн', s: 'брэндүүд' },
    { icon: HeartIcon, t: 'Салонд', s: 'зориулсан' },
    { icon: ShieldCheckIcon, t: 'Албан ёсны', s: 'баталгаа' },
    { icon: ArrowPathIcon, t: 'Урьдчилсан', s: 'захиалга' },
];
</script>

<template>
    <div>
        <!-- Hero -->
        <section class="bg-cream-100">
            <div class="container-x grid items-center gap-10 py-14 lg:grid-cols-2 lg:py-20">
                <div class="max-w-xl">
                    <h1 class="font-display text-5xl leading-[1.08] text-stone-900 sm:text-6xl">Таны салонд.<br />Таны гарт төгс.</h1>
                    <p class="mt-6 max-w-md text-base text-stone-600">Гоо сайхны салон, үсчин, косметологичдод зориулсан мэргэжлийн тоног төхөөрөмж, хэрэгслийг нэг дороос.</p>
                    <div class="mt-8 flex flex-wrap items-center gap-6">
                        <router-link :to="{ name: 'shop' }" class="rounded-xl bg-brand-700 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-800">Дэлгүүр үзэх</router-link>
                        <router-link :to="{ name: 'shop', query: { status: 'sale' } }" class="flex items-center gap-2 text-sm font-medium text-stone-800 hover:text-brand-700">Хямдралтай бараа <ArrowRightIcon class="h-4 w-4" /></router-link>
                    </div>
                    <div class="mt-12 grid grid-cols-4 divide-x divide-stone-300/60">
                        <div v-for="v in values" :key="v.t" class="flex flex-col items-center px-2 text-center first:items-start first:pl-0 first:text-left">
                            <component :is="v.icon" class="h-7 w-7 text-brand-800" stroke-width="1.3" />
                            <p class="mt-2 text-xs leading-tight text-stone-700">{{ v.t }}<br />{{ v.s }}</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <img :src="'/images/photos/hero.jpg'" alt="Чанар Есүй салоны тоног төхөөрөмж" class="aspect-[4/3] w-full rounded-3xl object-cover" />
                </div>
            </div>
        </section>

        <!-- Perks band -->
        <section class="bg-brand-700 text-cream-50">
            <div class="container-x grid gap-6 py-6 sm:grid-cols-3 sm:divide-x sm:divide-white/15">
                <div v-for="p in [[TruckIcon, 'Хүргэлт үнэгүй', '300,000₮-с дээш захиалгад'], [ArrowPathIcon, 'Урьдчилсан захиалга', 'Дууссан барааг ч захиална'], [LockClosedIcon, 'Аюулгүй төлбөр', 'QPay болон бэлэн төлбөр']]" :key="p[1]" class="flex items-center gap-4 sm:justify-center">
                    <component :is="p[0]" class="h-8 w-8 shrink-0" stroke-width="1.3" />
                    <div><p class="text-sm font-semibold">{{ p[1] }}</p><p class="text-xs text-cream-200/80">{{ p[2] }}</p></div>
                </div>
            </div>
        </section>

        <Spinner v-if="!data" />
        <template v-else>
            <!-- Shop by category -->
            <section class="container-x grid gap-8 py-14 lg:grid-cols-[260px_1fr]">
                <div>
                    <h2 class="font-display text-4xl text-stone-900">Ангиллаар үзэх</h2>
                    <p class="mt-4 text-sm text-stone-600">Салоны өдөр тутмын ажилд хэрэгтэй бүх зүйл.</p>
                    <router-link :to="{ name: 'shop' }" class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-brand-800 hover:text-brand-700">Бүх бараа <ArrowRightIcon class="h-4 w-4" /></router-link>
                </div>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <router-link v-for="c in data.categories" :key="c.id" :to="{ name: 'shop', query: { category: c.slug } }" class="group overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200/70 transition hover:shadow-lg hover:shadow-stone-900/5">
                        <div class="aspect-square overflow-hidden bg-cream-100"><img :src="c.image" :alt="c.name" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" /></div>
                        <div class="px-4 py-4 text-center"><p class="font-display text-lg text-stone-900">{{ c.name }}</p><p class="mt-0.5 flex items-center justify-center gap-1 text-xs text-stone-500">Үзэх <ArrowRightIcon class="h-3 w-3" /></p></div>
                    </router-link>
                </div>
            </section>

            <!-- Loyalty / preorder block -->
            <section class="container-x pb-6">
                <div class="grid overflow-hidden rounded-2xl lg:grid-cols-[400px_1fr]">
                    <div class="bg-brand-900 p-8 text-cream-50 sm:p-10">
                        <p class="text-xs text-cream-300/70">Салоны хамтрагч хөтөлбөр</p>
                        <h3 class="mt-3 font-display text-3xl leading-tight">Сайн сонголт<br />урамшуулал авах ёстой.</h3>
                        <p class="mt-4 text-sm text-cream-200/80">Бүртгүүлж, дугаараа баталгаажуулснаар салоны хэрэглэгчийн хямдрал, эрт мэдэгдэл, урьдчилсан захиалгын давуу эрх эдэлнэ.</p>
                        <router-link :to="{ name: 'register' }" class="mt-6 inline-block rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">Бүртгүүлэх</router-link>
                    </div>
                    <div class="grid gap-6 bg-cream-100 p-8 sm:grid-cols-3 sm:divide-x sm:divide-stone-300/60 sm:p-10">
                        <div v-for="b in [[StarIcon, 'Салоны хямдрал', 'Багц захиалгад тусгай үнэ'], [GiftIcon, 'Урамшуулал', 'Онцгой хямдрал, бэлэг'], [BoltIcon, 'Эрт хандалт', 'Шинэ бараа ирэхэд эхэлж авна']]" :key="b[1]" class="flex flex-col items-center text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-700 text-cream-50"><component :is="b[0]" class="h-6 w-6" stroke-width="1.5" /></span>
                            <p class="mt-3 text-sm font-semibold text-stone-900">{{ b[1] }}</p><p class="mt-1 text-xs text-stone-600">{{ b[2] }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <ProductCarousel title="Их зарагддаг" :products="data.bestsellers" :to="{ name: 'shop', query: { sort: 'popular' } }" />

            <!-- Editorial banner -->
            <section class="container-x py-6">
                <div class="grid items-center gap-8 rounded-2xl bg-cream-100 p-6 lg:grid-cols-2 lg:p-10">
                    <img :src="'/images/photos/lifestyle.jpg'" alt="" class="aspect-[4/3] w-full rounded-2xl object-cover" />
                    <div class="max-w-md">
                        <p class="text-xs uppercase tracking-widest text-brand-800">Мэргэжлийн хэрэглээнд</p>
                        <h3 class="mt-3 font-display text-4xl leading-tight text-stone-900">Өдөр бүр олон цагийн ажилд зориулсан тоног төхөөрөмж.</h3>
                        <p class="mt-4 text-sm text-stone-600">Салонд өдөр бүр ашиглагдах хүчин чадал, баталгаа, сэлбэгийн хангамж. Дууссан бараа ч урьдчилан захиалж, дараагийн ачилтаар эхэлж авах боломжтой.</p>
                        <router-link :to="{ name: 'shop', query: { category: 'hair' } }" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-800">Үсний тоног төхөөрөмж <ArrowRightIcon class="h-4 w-4" /></router-link>
                    </div>
                </div>
            </section>

            <ProductCarousel title="Хямдралтай" :products="data.sale" :to="{ name: 'shop', query: { status: 'sale' } }" />
            <ProductCarousel title="Шинээр ирсэн" :products="data.newest" :to="{ name: 'shop', query: { sort: 'newest' } }" />
        </template>
    </div>
</template>
