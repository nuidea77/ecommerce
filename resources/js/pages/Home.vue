<script setup>
import { ref, onMounted } from 'vue';
import api from '../lib/api';
import ProductCard from '../components/ProductCard.vue';
import Spinner from '../components/ui/Spinner.vue';
import { ArrowRightIcon, TruckIcon, ShieldCheckIcon, CreditCardIcon, ClockIcon } from '@heroicons/vue/24/outline';

const data = ref(null);
onMounted(async () => {
    const res = await api.get('/home');
    data.value = res.data;
});

const perks = [
    { icon: TruckIcon, title: 'Шуурхай хүргэлт', text: 'УБ хотод 24 цагт, орон нутагт 2–5 хоногт' },
    { icon: CreditCardIcon, title: 'QPay төлбөр', text: 'Бүх банкны аппаар QR уншуулж төлнө' },
    { icon: ClockIcon, title: 'Урьдчилсан захиалга', text: 'Дууссан барааг захиалж, ирэхэд нь хүргүүлнэ' },
    { icon: ShieldCheckIcon, title: 'Албан ёсны баталгаа', text: 'Тоног төхөөрөмжид 12–24 сарын баталгаа' },
];
</script>

<template>
    <div>
        <!-- Hero -->
        <section class="relative overflow-hidden bg-stone-900 text-white">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(236,72,153,0.35),_transparent_55%),radial-gradient(ellipse_at_bottom_left,_rgba(217,70,239,0.2),_transparent_50%)]"></div>
            <div class="container-x relative grid items-center gap-10 py-16 lg:grid-cols-2 lg:py-24">
                <div>
                    <span class="badge bg-white/10 text-brand-200 ring-1 ring-white/20">Мэргэжлийн салоны нийлүүлэгч</span>
                    <h1 class="mt-5 font-display text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">Таны салоныг <span class="text-brand-400">төгс</span> тоноглоно</h1>
                    <p class="mt-5 max-w-lg text-lg text-stone-300">Үс хатаагчаас косметологийн аппарат хүртэл — гоо сайхны салон, үсчин, хумсны мастеруудад зориулсан мэргэжлийн тоног төхөөрөмж, хэрэглээний материал нэг дороос.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <router-link :to="{ name: 'shop' }" class="btn-brand px-6 py-3 text-base">Дэлгүүр үзэх <ArrowRightIcon class="h-5 w-5" /></router-link>
                        <router-link :to="{ name: 'shop', query: { category: 'skin' } }" class="btn px-6 py-3 text-base bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/20">Косметологийн аппарат</router-link>
                    </div>
                    <dl class="mt-10 grid grid-cols-3 gap-6 border-t border-white/10 pt-8">
                        <div><dt class="text-2xl font-bold">500+</dt><dd class="text-sm text-stone-400">Бүтээгдэхүүн</dd></div>
                        <div><dt class="text-2xl font-bold">1,200+</dt><dd class="text-sm text-stone-400">Салон хамтрагч</dd></div>
                        <div><dt class="text-2xl font-bold">24ц</dt><dd class="text-sm text-stone-400">Хүргэлт УБ хотод</dd></div>
                    </dl>
                </div>
                <div class="relative hidden lg:block">
                    <div class="grid grid-cols-2 gap-4">
                        <img :src="'/images/products/professional-hair-dryer-2200w.svg'" class="rounded-3xl shadow-2xl shadow-brand-900/40 rotate-[-3deg]" alt="" />
                        <img :src="'/images/products/hydraulic-salon-chair.svg'" class="mt-10 rounded-3xl shadow-2xl shadow-brand-900/40 rotate-[3deg]" alt="" />
                        <img :src="'/images/products/uv-led-nail-lamp-120w.svg'" class="-mt-6 rounded-3xl shadow-2xl shadow-brand-900/40 rotate-[2deg]" alt="" />
                        <img :src="'/images/products/hydrafacial-machine-7in1.svg'" class="mt-4 rounded-3xl shadow-2xl shadow-brand-900/40 rotate-[-2deg]" alt="" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Perks -->
        <section class="border-b border-stone-200 bg-white">
            <div class="container-x grid gap-6 py-8 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="p in perks" :key="p.title" class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><component :is="p.icon" class="h-5 w-5" /></span>
                    <div><p class="text-sm font-semibold">{{ p.title }}</p><p class="text-xs text-stone-500">{{ p.text }}</p></div>
                </div>
            </div>
        </section>

        <Spinner v-if="!data" />
        <template v-else>
            <!-- Categories -->
            <section class="container-x py-14">
                <div class="mb-6 flex items-end justify-between">
                    <div><h2 class="font-display text-2xl font-bold sm:text-3xl">Ангиллаар үзэх</h2><p class="mt-1 text-sm text-stone-500">Салоны бүх хэрэгцээг хангах 6 ангилал</p></div>
                    <router-link :to="{ name: 'shop' }" class="hidden text-sm font-medium text-brand-700 hover:underline sm:block">Бүгдийг үзэх →</router-link>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <router-link v-for="c in data.categories" :key="c.id" :to="{ name: 'shop', query: { category: c.slug } }" class="group card flex flex-col items-center p-5 text-center transition hover:-translate-y-0.5 hover:shadow-md">
                        <img :src="c.image" :alt="c.name" class="h-20 w-20 rounded-2xl object-cover transition group-hover:scale-105" />
                        <p class="mt-3 text-sm font-semibold leading-tight">{{ c.name }}</p>
                        <p class="mt-1 text-xs text-stone-400">{{ c.products_count }} бараа</p>
                    </router-link>
                </div>
            </section>

            <!-- Featured -->
            <section class="bg-white py-14">
                <div class="container-x">
                    <div class="mb-6 flex items-end justify-between">
                        <div><h2 class="font-display text-2xl font-bold sm:text-3xl">Онцлох бүтээгдэхүүн</h2><p class="mt-1 text-sm text-stone-500">Салонуудын хамгийн их сонгодог бараа</p></div>
                        <router-link :to="{ name: 'shop' }" class="hidden text-sm font-medium text-brand-700 hover:underline sm:block">Бүгдийг үзэх →</router-link>
                    </div>
                    <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                        <ProductCard v-for="p in data.featured" :key="p.id" :product="p" />
                    </div>
                </div>
            </section>

            <!-- Promo -->
            <section class="container-x py-14">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 to-fuchsia-700 p-8 text-white lg:col-span-2">
                        <div class="relative z-10 max-w-md">
                            <span class="badge bg-white/20 text-white">Урьдчилсан захиалга</span>
                            <h3 class="mt-4 font-display text-3xl font-bold">Дууссан бараа ч захиалах боломжтой</h3>
                            <p class="mt-3 text-brand-100">Дуусчихсан барааг урьдчилан захиалснаар дараагийн ачилтаар танд эхэлж хүргэнэ. Төлбөрөө хүргэлт дээр эсвэл QPay-ээр хийнэ.</p>
                            <router-link :to="{ name: 'shop' }" class="btn mt-6 bg-white text-brand-700 hover:bg-brand-50">Захиалах</router-link>
                        </div>
                        <img :src="'/images/products/barber-chair-classic.svg'" class="absolute -bottom-10 -right-10 hidden w-72 rotate-6 rounded-3xl opacity-90 sm:block" alt="" />
                    </div>
                    <div class="rounded-3xl bg-stone-900 p-8 text-white">
                        <span class="badge bg-white/10 text-stone-200">Багцаар хямд</span>
                        <h3 class="mt-4 font-display text-2xl font-bold">Хэрэглээний материал 6, 12, 50-аар багцалж авбал хямдарна</h3>
                        <router-link :to="{ name: 'shop', query: { category: 'supplies' } }" class="btn-brand mt-6">Материал үзэх</router-link>
                    </div>
                </div>
            </section>

            <!-- Newest -->
            <section class="container-x pb-14">
                <div class="mb-6"><h2 class="font-display text-2xl font-bold sm:text-3xl">Шинээр ирсэн</h2></div>
                <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                    <ProductCard v-for="p in data.newest" :key="p.id" :product="p" />
                </div>
            </section>
        </template>
    </div>
</template>
