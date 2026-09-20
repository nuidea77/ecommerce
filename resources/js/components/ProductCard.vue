<script setup>
import { computed, ref } from 'vue';
import { money } from '../lib/format';
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import { errorMessage } from '../lib/api';
import { ShoppingCartIcon } from '@heroicons/vue/20/solid';

const props = defineProps({ product: { type: Object, required: true } });
const cart = useCartStore();
const ui = useUiStore();
const adding = ref(false);

const discount = computed(() => {
    const c = props.product.compare_price;
    return c && c > props.product.min_price ? Math.round((1 - props.product.min_price / c) * 100) : 0;
});
const colors = computed(() => {
    const seen = new Map();
    (props.product.variants || []).forEach((v) => v.color && !seen.has(v.color) && seen.set(v.color, v.color_hex));
    return [...seen.entries()];
});
const hasOptions = computed(() => (props.product.variants || []).length > 1);

async function quickAdd() {
    const v = (props.product.variants || []).find((x) => x.stock > 0) || props.product.variants?.[0];
    if (!v) return;
    adding.value = true;
    try {
        await cart.add(v.id, 1);
        ui.toast(`${props.product.name} сагсанд нэмэгдлээ`);
        ui.cartOpen = true;
    } catch (e) {
        ui.toast(errorMessage(e), 'error');
    } finally {
        adding.value = false;
    }
}
</script>

<template>
    <article class="group flex h-full flex-col overflow-hidden rounded-xl bg-paper ring-1 ring-cream-300/70 transition hover:shadow-lg hover:shadow-brand-900/10 hover:ring-brand-300">
        <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="relative block aspect-square overflow-hidden bg-paper">
            <img :src="product.thumbnail" :alt="product.name" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
            <span v-if="discount" class="absolute left-2 top-2 rounded-md bg-red-600 px-1.5 py-0.5 text-[11px] font-bold text-white shadow-sm">-{{ discount }}%</span>
            <span v-if="!product.in_stock" class="absolute bottom-2 left-2 rounded-md bg-brand-900/85 px-1.5 py-0.5 text-[10px] font-semibold text-cream-100">Урьдчилсан захиалга</span>
            <span v-else-if="product.total_stock <= 3" class="absolute bottom-2 left-2 rounded-md bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">Цөөн үлдсэн</span>
        </router-link>

        <div class="flex flex-1 flex-col p-3">
            <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="line-clamp-2 min-h-[2.5rem] text-[13px] font-medium leading-5 text-stone-800 hover:text-brand-700">{{ product.name }}</router-link>
            <div class="mt-1.5 flex h-4 items-center gap-1">
                <span v-for="[name, hex] in colors.slice(0, 5)" :key="name" :title="name" class="h-3 w-3 rounded-full ring-1 ring-black/10" :style="{ backgroundColor: hex || '#ddd' }"></span>
                <span v-if="hasOptions" class="ml-auto text-[11px] text-stone-400">{{ product.variants.length }} сонголт</span>
            </div>
            <div class="mt-2 flex flex-wrap items-baseline gap-x-2">
                <span class="text-base font-bold text-brand-800">{{ money(product.min_price) }}</span>
                <span v-if="product.compare_price && product.compare_price > product.min_price" class="text-xs text-stone-400 line-through">{{ money(product.compare_price) }}</span>
            </div>
            <button v-if="!hasOptions" @click="quickAdd" :disabled="adding" class="mt-2.5 flex w-full items-center justify-center gap-1.5 rounded-lg border border-brand-700 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-700 hover:text-white disabled:opacity-50">
                <ShoppingCartIcon class="h-4 w-4" /> {{ product.in_stock ? 'Сагслах' : 'Урьдчилан захиалах' }}
            </button>
            <router-link v-else :to="{ name: 'product', params: { slug: product.slug } }" class="mt-2.5 flex w-full items-center justify-center gap-1.5 rounded-lg border border-brand-700 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-700 hover:text-white">
                <ShoppingCartIcon class="h-4 w-4" /> Сагслах
            </router-link>
        </div>
    </article>
</template>
