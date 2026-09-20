<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const slides = [
    { title: 'Салоны тоног төхөөрөмж', sub: 'Үс хатаагч, шулуутгагч, сандал — мэргэжлийн брэндүүд', cta: 'Дэлгүүр үзэх', to: { name: 'shop' }, img: '/images/products/professional-hair-dryer-2200w.svg', bg: 'from-brand-800 to-brand-900', tag: '👑 Албан ёсны нийлүүлэгч' },
    { title: 'Хүргэлт үнэгүй', sub: '300,000₮-с дээш захиалгад Улаанбаатар хотод 24 цагт', cta: 'Захиалах', to: { name: 'shop', query: { in_stock: '1' } }, img: '/images/products/hydraulic-salon-chair.svg', bg: 'from-gold-500 to-gold-600', tag: '🚚 24 цагт хүргэнэ', dark: true },
    { title: 'Хямдралтай бараа', sub: 'Хумсны лампа, хайч, нөмрөг 20% хүртэл хямдарлаа', cta: 'Хямдрал үзэх', to: { name: 'shop', query: { sort: 'price_asc' } }, img: '/images/products/uv-led-nail-lamp-120w.svg', bg: 'from-brand-600 to-brand-800', tag: '🔥 Хязгаартай' },
];
const i = ref(0);
let timer;
const go = (n) => (i.value = (n + slides.length) % slides.length);
const restart = () => { clearInterval(timer); timer = setInterval(() => go(i.value + 1), 5000); };
onMounted(restart);
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div class="group relative h-full min-h-[280px] overflow-hidden rounded-2xl">
        <transition-group name="fade">
            <div v-for="(s, idx) in slides" :key="idx" v-show="idx === i" class="absolute inset-0 bg-gradient-to-r p-7 sm:p-10" :class="[s.bg, s.dark ? 'text-brand-900' : 'text-cream-50']">
                <div class="flex h-full items-center gap-6">
                    <div class="max-w-md">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="s.dark ? 'bg-brand-900/10' : 'bg-white/15'">{{ s.tag }}</span>
                        <h2 class="mt-4 font-display text-3xl font-bold leading-tight sm:text-4xl">{{ s.title }}</h2>
                        <p class="mt-2 text-sm sm:text-base" :class="s.dark ? 'text-brand-900/80' : 'text-cream-200/85'">{{ s.sub }}</p>
                        <router-link :to="s.to" class="mt-6 inline-flex items-center rounded-xl px-5 py-2.5 text-sm font-semibold transition" :class="s.dark ? 'bg-brand-900 text-cream-50 hover:bg-brand-800' : 'bg-gold-500 text-brand-900 hover:bg-gold-400'">{{ s.cta }}</router-link>
                    </div>
                    <img :src="s.img" alt="" class="ml-auto hidden h-52 w-52 rounded-2xl object-cover shadow-2xl shadow-black/30 sm:block lg:h-60 lg:w-60" />
                </div>
            </div>
        </transition-group>
        <button @click="go(i - 1); restart()" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-1.5 text-brand-900 opacity-0 shadow transition group-hover:opacity-100"><ChevronLeftIcon class="h-5 w-5" /></button>
        <button @click="go(i + 1); restart()" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-1.5 text-brand-900 opacity-0 shadow transition group-hover:opacity-100"><ChevronRightIcon class="h-5 w-5" /></button>
        <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-1.5">
            <button v-for="(s, idx) in slides" :key="idx" @click="go(idx); restart()" class="h-1.5 rounded-full transition-all" :class="idx === i ? 'w-6 bg-white' : 'w-1.5 bg-white/50'"></button>
        </div>
    </div>
</template>
