<script setup>
import { computed, ref } from 'vue';
import { money } from '../lib/format';
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import { errorMessage } from '../lib/api';
import { PlusIcon, StarIcon } from '@heroicons/vue/20/solid';

const props = defineProps({ product: { type: Object, required: true } });
const cart = useCartStore();
const ui = useUiStore();
const adding = ref(false);

const discount = computed(() => {
    const c = props.product.compare_price;
    return c && c > props.product.min_price ? Math.round((1 - props.product.min_price / c) * 100) : 0;
});
const hasOptions = computed(() => (props.product.variants || []).length > 1);
const isNew = computed(() => Date.now() - new Date(props.product.created_at) < 30 * 86400000);
const stars = computed(() => Math.round(Number(props.product.rating || 0)));

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
    <article class="group flex h-full flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200/70 transition hover:shadow-lg hover:shadow-stone-900/5">
        <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="relative block aspect-[4/5] overflow-hidden bg-cream-100">
            <img :src="product.thumbnail" :alt="product.name" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
            <div class="absolute right-3 top-3 flex flex-col items-end gap-1.5">
                <span v-if="product.is_featured" class="rounded-md bg-white/90 px-2 py-0.5 text-[11px] font-medium text-stone-800 shadow-sm">Bestseller</span>
                <span v-else-if="isNew" class="rounded-md bg-white/90 px-2 py-0.5 text-[11px] font-medium text-stone-800 shadow-sm">Шинэ</span>
                <span v-if="discount" class="rounded-md bg-brand-700 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">-{{ discount }}%</span>
            </div>
            <span v-if="!product.in_stock" class="absolute bottom-3 left-3 rounded-md bg-stone-900/80 px-2 py-0.5 text-[11px] font-medium text-white backdrop-blur">Урьдчилсан захиалга</span>
        </router-link>

        <div class="flex flex-1 flex-col px-4 pb-4 pt-3">
            <router-link :to="{ name: 'product', params: { slug: product.slug } }" class="line-clamp-2 min-h-[2.5rem] text-sm font-medium leading-5 text-stone-900 hover:text-brand-700">{{ product.name }}</router-link>
            <div class="mt-1.5 flex items-center gap-1">
                <span class="flex text-gold-500"><StarIcon v-for="i in 5" :key="i" class="h-3.5 w-3.5" :class="i > stars && 'text-stone-200'" /></span>
                <span class="text-[11px] text-stone-400">({{ product.reviews_count || 0 }})</span>
                <span v-if="hasOptions" class="ml-auto text-[11px] text-stone-400">{{ product.variants.length }} сонголт</span>
            </div>
            <div class="mt-auto flex items-end justify-between pt-3">
                <div>
                    <p class="text-base font-semibold text-stone-900">{{ money(product.min_price) }}</p>
                    <p v-if="product.compare_price && product.compare_price > product.min_price" class="text-xs text-stone-400 line-through">{{ money(product.compare_price) }}</p>
                </div>
                <button v-if="!hasOptions" @click="quickAdd" :disabled="adding" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-700 text-white transition hover:bg-brand-800 disabled:opacity-50" :title="product.in_stock ? 'Сагслах' : 'Урьдчилан захиалах'"><PlusIcon class="h-5 w-5" /></button>
                <router-link v-else :to="{ name: 'product', params: { slug: product.slug } }" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-700 text-white transition hover:bg-brand-800" title="Сонголт хийх"><PlusIcon class="h-5 w-5" /></router-link>
            </div>
        </div>
    </article>
</template>
