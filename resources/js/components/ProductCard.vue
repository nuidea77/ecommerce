<script setup>
import { computed } from 'vue';
import { money } from '../lib/format';
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import { errorMessage } from '../lib/api';
import { ShoppingBagIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ product: { type: Object, required: true } });
const cart = useCartStore();
const ui = useUiStore();

const discount = computed(() => {
    const c = props.product.compare_price;
    return c && c > props.product.min_price ? Math.round((1 - props.product.min_price / c) * 100) : 0;
});
const colors = computed(() => {
    const seen = new Map();
    (props.product.variants || []).forEach((v) => v.color && !seen.has(v.color) && seen.set(v.color, v.color_hex));
    return [...seen.entries()].slice(0, 5);
});
const hasOptions = computed(() => (props.product.variants || []).length > 1);
const priceLabel = computed(() => (props.product.min_price === props.product.max_price ? money(props.product.min_price) : `${money(props.product.min_price)} – ${money(props.product.max_price)}`));

async function quickAdd() {
    const v = (props.product.variants || []).find((x) => x.stock > 0) || props.product.variants?.[0];
    if (!v) return;
    try {
        await cart.add(v.id, 1);
        ui.toast(`${props.product.name} сагсанд нэмэгдлээ`);
        ui.cartOpen = true;
    } catch (e) {
        ui.toast(errorMessage(e), 'error');
    }
}
</script>

<template>
    <div class="group card flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
        <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="relative block aspect-square overflow-hidden bg-stone-100">
            <img :src="product.thumbnail" :alt="product.name" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
            <div class="absolute left-3 top-3 flex flex-col gap-1.5">
                <span v-if="discount" class="badge bg-gold-500 text-brand-900">-{{ discount }}%</span>
                <span v-if="!product.in_stock" class="badge bg-brand-900/85 text-cream-100 backdrop-blur">Урьдчилсан захиалга</span>
                <span v-else-if="product.total_stock <= 3" class="badge bg-amber-100 text-amber-800">Цөөн үлдсэн</span>
            </div>
        </router-link>
        <div class="flex flex-1 flex-col p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">{{ product.brand || product.category?.name }}</p>
            <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="mt-1 line-clamp-2 text-sm font-semibold text-stone-900 hover:text-brand-700">{{ product.name }}</router-link>
            <div class="mt-2 flex items-center gap-1.5" v-if="colors.length">
                <span v-for="[name, hex] in colors" :key="name" :title="name" class="h-3.5 w-3.5 rounded-full ring-1 ring-stone-300" :style="{ backgroundColor: hex || '#ddd' }"></span>
                <span v-if="hasOptions" class="text-[11px] text-stone-400">{{ product.variants.length }} сонголт</span>
            </div>
            <div class="mt-auto flex items-end justify-between pt-3">
                <div>
                    <p class="text-base font-bold text-stone-900">{{ priceLabel }}</p>
                    <p v-if="product.compare_price && product.compare_price > product.min_price" class="text-xs text-stone-400 line-through">{{ money(product.compare_price) }}</p>
                </div>
                <button v-if="!hasOptions" @click="quickAdd" class="rounded-xl bg-brand-800 p-2.5 text-white transition hover:bg-gold-500 hover:text-brand-900" title="Сагсанд нэмэх"><ShoppingBagIcon class="h-4 w-4" /></button>
                <router-link v-else :to="{ name: 'product', params: { slug: product.slug } }" class="btn-secondary btn-sm">Сонгох</router-link>
            </div>
        </div>
    </div>
</template>
