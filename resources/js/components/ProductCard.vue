<script setup>
import { computed, ref } from 'vue';
import { money } from '../lib/format';
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import { errorMessage } from '../lib/api';
import { ShoppingBagIcon, ArrowRightIcon, CheckIcon } from '@heroicons/vue/20/solid';

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
const hasRange = computed(() => props.product.min_price !== props.product.max_price);

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
    <article class="group card flex h-full flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-brand-900/10">
        <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="relative block aspect-[4/3] overflow-hidden bg-cream-100 sm:aspect-square">
            <img :src="product.thumbnail" :alt="product.name" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
            <div class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
                <span v-if="discount" class="badge bg-gold-500 text-brand-900 shadow-sm">-{{ discount }}%</span>
                <span v-if="!product.in_stock" class="badge bg-brand-900/85 text-cream-100 backdrop-blur">Урьдчилсан захиалга</span>
                <span v-else-if="product.total_stock <= 3" class="badge bg-amber-100 text-amber-800">Цөөн үлдсэн</span>
            </div>
            <span v-if="product.is_featured" class="absolute right-3 top-3 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-gold-600 shadow-sm">★ Онцлох</span>
        </router-link>

        <div class="flex flex-1 flex-col p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-600">{{ product.brand || product.category?.name }}</p>
            <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="mt-1 line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-5 text-stone-900 hover:text-brand-700">{{ product.name }}</router-link>

            <div class="mt-2 flex h-5 items-center gap-1.5">
                <template v-if="colors.length">
                    <span v-for="[name, hex] in colors.slice(0, 5)" :key="name" :title="name" class="h-3.5 w-3.5 rounded-full ring-1 ring-black/10" :style="{ backgroundColor: hex || '#ddd' }"></span>
                    <span v-if="colors.length > 5" class="text-[11px] text-stone-400">+{{ colors.length - 5 }}</span>
                </template>
                <span v-if="hasOptions" class="ml-auto text-[11px] text-stone-400">{{ product.variants.length }} сонголт</span>
            </div>

            <div class="mt-auto pt-3">
                <p class="h-4 text-[11px] text-stone-400">{{ hasRange ? 'эхлэх үнэ' : '' }}</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold tracking-tight text-stone-900">{{ money(product.min_price) }}</span>
                    <span v-if="product.compare_price && product.compare_price > product.min_price" class="text-xs text-stone-400 line-through">{{ money(product.compare_price) }}</span>
                </div>
                <button v-if="!hasOptions" @click="quickAdd" :disabled="adding" class="btn-secondary mt-3 w-full group-hover:bg-brand-700 group-hover:text-white group-hover:ring-brand-700">
                    <CheckIcon v-if="adding" class="h-4 w-4" /><ShoppingBagIcon v-else class="h-4 w-4" /> {{ product.in_stock ? 'Сагсанд нэмэх' : 'Урьдчилан захиалах' }}
                </button>
                <router-link v-else :to="{ name: 'product', params: { slug: product.slug } }" class="btn-secondary mt-3 w-full group-hover:bg-brand-700 group-hover:text-white group-hover:ring-brand-700">
                    Сонголт хийх <ArrowRightIcon class="h-4 w-4" />
                </router-link>
            </div>
        </div>
    </article>
</template>
