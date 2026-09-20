<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../lib/api';
import { money } from '../lib/format';
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import ProductCard from '../components/ProductCard.vue';
import QuantityInput from '../components/ui/QuantityInput.vue';
import Spinner from '../components/ui/Spinner.vue';
import { ShoppingBagIcon, CheckIcon, ClockIcon, TruckIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const cart = useCartStore();
const ui = useUiStore();

const product = ref(null);
const related = ref([]);
const loading = ref(true);
const notFound = ref(false);
const activeImage = ref(0);
const qty = ref(1);
const adding = ref(false);
const tab = ref('desc');

const sel = ref({ color: null, size: null, pack: null });

const variants = computed(() => product.value?.variants || []);
const uniq = (key) => [...new Map(variants.value.filter((v) => v[key] != null && v[key] !== '').map((v) => [String(v[key]), v])).values()];
const colors = computed(() => uniq('color'));
const sizes = computed(() => [...new Set(variants.value.map((v) => v.size).filter(Boolean))]);
const packs = computed(() => [...new Map(variants.value.map((v) => [v.pack_size, v])).values()].sort((a, b) => a.pack_size - b.pack_size));
const hasPacks = computed(() => packs.value.length > 1);

const matches = (v, s) => (s.color == null || v.color === s.color) && (s.size == null || v.size === s.size) && (s.pack == null || v.pack_size === s.pack);
const available = (key, value) => variants.value.some((v) => matches(v, { ...sel.value, [key]: value }));
const anyStock = (key, value) => variants.value.some((v) => matches(v, { ...sel.value, [key]: value }) && v.stock > 0);

const selected = computed(() => {
    const m = variants.value.filter((v) => matches(v, sel.value));
    const complete = (!colors.value.length || sel.value.color != null) && (!sizes.value.length || sel.value.size != null) && (!hasPacks.value || sel.value.pack != null);
    return complete && m.length ? m[0] : null;
});

const price = computed(() => selected.value?.price ?? product.value?.min_price);
const isBackorder = computed(() => selected.value && selected.value.stock < qty.value);
const canBuy = computed(() => selected.value && (selected.value.stock >= qty.value || product.value.allow_backorder));
const discount = computed(() => (product.value?.compare_price && price.value < product.value.compare_price ? Math.round((1 - price.value / product.value.compare_price) * 100) : 0));
const images = computed(() => {
    const base = product.value?.images || [];
    return selected.value?.image ? [selected.value.image, ...base] : base;
});

function pick(key, value) {
    sel.value[key] = sel.value[key] === value ? null : value;
    // Drop selections that no longer combine with this choice.
    ['color', 'size', 'pack'].forEach((k) => {
        if (k !== key && sel.value[k] != null && !variants.value.some((v) => matches(v, sel.value))) sel.value[k] = null;
    });
}

function autoSelect() {
    const first = variants.value.find((v) => v.stock > 0) || variants.value[0];
    if (!first) return;
    sel.value = { color: colors.value.length ? first.color : null, size: sizes.value.length ? first.size : null, pack: hasPacks.value ? first.pack_size : null };
}

async function load() {
    loading.value = true;
    notFound.value = false;
    try {
        const { data } = await api.get(`/products/${route.params.slug}`);
        product.value = data.product;
        related.value = data.related;
        activeImage.value = 0;
        qty.value = 1;
        autoSelect();
    } catch {
        notFound.value = true;
    } finally {
        loading.value = false;
    }
}

async function addToCart() {
    if (!selected.value) return ui.toast('Сонголтоо бүрэн хийнэ үү', 'error');
    adding.value = true;
    try {
        await cart.add(selected.value.id, qty.value);
        ui.toast(isBackorder.value ? 'Урьдчилсан захиалга сагсанд нэмэгдлээ' : 'Сагсанд нэмэгдлээ');
        ui.cartOpen = true;
    } catch (e) {
        ui.toast(errorMessage(e), 'error');
    } finally {
        adding.value = false;
    }
}

watch(() => route.params.slug, load);
onMounted(load);
</script>

<template>
    <div class="container-x py-8">
        <Spinner v-if="loading" />
        <div v-else-if="notFound" class="py-24 text-center"><p class="text-5xl">😕</p><h1 class="mt-4 text-2xl font-bold">Бараа олдсонгүй</h1><router-link :to="{ name: 'shop' }" class="btn-brand mt-6">Дэлгүүр рүү буцах</router-link></div>
        <template v-else>
            <p class="mb-6 text-sm text-stone-500">
                <router-link :to="{ name: 'home' }" class="hover:text-brand-700">Нүүр</router-link> /
                <router-link :to="{ name: 'shop' }" class="hover:text-brand-700">Дэлгүүр</router-link> /
                <router-link :to="{ name: 'shop', query: { category: product.category.slug } }" class="hover:text-brand-700">{{ product.category.name }}</router-link> /
                <span class="text-stone-800">{{ product.name }}</span>
            </p>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:gap-12">
                <!-- Gallery -->
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div class="relative aspect-square overflow-hidden rounded-3xl bg-cream-200/60 ring-1 ring-stone-200">
                        <img :src="images[activeImage] || images[0]" :alt="product.name" class="h-full w-full object-cover" />
                        <span v-if="discount" class="badge absolute left-4 top-4 bg-gold-500 text-brand-900">-{{ discount }}%</span>
                    </div>
                    <div v-if="images.length > 1" class="mt-4 flex gap-3">
                        <button v-for="(img, i) in images" :key="img + i" @click="activeImage = i" class="h-20 w-20 overflow-hidden rounded-xl ring-2 transition" :class="activeImage === i ? 'ring-brand-600' : 'ring-transparent hover:ring-stone-300'"><img :src="img" class="h-full w-full object-cover" alt="" /></button>
                    </div>
                </div>

                <!-- Info -->
                <div>
                    <p class="text-sm font-medium uppercase tracking-wide text-brand-700">{{ product.category.name }}</p>
                    <h1 class="mt-1 font-display text-3xl font-bold leading-tight sm:text-4xl">{{ product.name }}</h1>
                    <p class="mt-3 text-stone-600">{{ product.short_description }}</p>

                    <div class="mt-5 flex items-end gap-3">
                        <span class="text-3xl font-bold">{{ money(price) }}</span>
                        <span v-if="product.compare_price && product.compare_price > price" class="pb-1 text-lg text-stone-400 line-through">{{ money(product.compare_price) }}</span>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                        <template v-if="selected">
                            <span v-if="selected.stock > 0" class="badge bg-emerald-100 text-emerald-800"><CheckIcon class="h-3.5 w-3.5" /> Бэлэн байгаа ({{ selected.stock }} ш)</span>
                            <span v-else class="badge bg-amber-100 text-amber-800"><ClockIcon class="h-3.5 w-3.5" /> Дууссан — урьдчилан захиалах боломжтой</span>
                            <span class="text-xs text-stone-400">SKU: {{ selected.sku }}</span>
                        </template>
                        <span v-else class="text-stone-500">Сонголтоо хийнэ үү</span>
                    </div>

                    <!-- Options -->
                    <div class="mt-6 space-y-5">
                        <div v-if="colors.length">
                            <p class="mb-2 text-sm font-semibold">Өнгө: <span class="font-normal text-stone-500">{{ sel.color || '—' }}</span></p>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="c in colors" :key="c.color" @click="pick('color', c.color)" :disabled="!available('color', c.color)" class="flex items-center gap-2 rounded-full py-1.5 pl-1.5 pr-3 text-sm ring-1 transition disabled:cursor-not-allowed disabled:opacity-30" :class="sel.color === c.color ? 'bg-brand-800 text-white ring-brand-800' : 'bg-paper ring-stone-200 hover:ring-stone-400'">
                                    <span class="h-5 w-5 rounded-full ring-1 ring-black/10" :style="{ backgroundColor: c.color_hex || '#ddd' }"></span>{{ c.color }}
                                    <span v-if="!anyStock('color', c.color)" class="text-[10px] opacity-70">(захиалгаар)</span>
                                </button>
                            </div>
                        </div>
                        <div v-if="sizes.length">
                            <p class="mb-2 text-sm font-semibold">Хэмжээ: <span class="font-normal text-stone-500">{{ sel.size || '—' }}</span></p>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="s in sizes" :key="s" @click="pick('size', s)" :disabled="!available('size', s)" class="min-w-12 rounded-xl px-3 py-2 text-sm font-medium ring-1 transition disabled:cursor-not-allowed disabled:opacity-30" :class="sel.size === s ? 'bg-brand-800 text-white ring-brand-800' : 'bg-paper ring-stone-200 hover:ring-stone-400'">{{ s }}<span v-if="!anyStock('size', s)" class="ml-1 text-[10px] opacity-70">•</span></button>
                            </div>
                        </div>
                        <div v-if="hasPacks">
                            <p class="mb-2 text-sm font-semibold">Багц: <span class="font-normal text-stone-500">{{ packs.find((p) => p.pack_size === sel.pack)?.pack_label || '—' }}</span></p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <button v-for="p in packs" :key="p.pack_size" @click="pick('pack', p.pack_size)" :disabled="!available('pack', p.pack_size)" class="rounded-xl p-3 text-left ring-1 transition disabled:cursor-not-allowed disabled:opacity-30" :class="sel.pack === p.pack_size ? 'bg-brand-50 ring-2 ring-brand-600' : 'bg-paper ring-stone-200 hover:ring-stone-400'">
                                    <p class="text-sm font-semibold">{{ p.pack_label || `${p.pack_size} ширхэг` }}</p>
                                    <p class="text-xs text-stone-500">{{ money(variants.filter((v) => matches(v, { ...sel, pack: p.pack_size }))[0]?.price ?? p.price) }}<span v-if="p.pack_size > 1"> · {{ money((variants.filter((v) => matches(v, { ...sel, pack: p.pack_size }))[0]?.price ?? p.price) / p.pack_size) }}/ш</span></p>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Backorder notice -->
                    <div v-if="selected && isBackorder" class="mt-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                        <p class="font-semibold">⏳ Урьдчилсан захиалга</p>
                        <p class="mt-1">Энэ сонголт одоогоор дууссан байна. Захиалга өгснөөр дараагийн ачилтаар (ойролцоогоор <b>{{ product.backorder_days }} хоногт</b>) танд эхэлж хүргэнэ.<span v-if="selected.stock > 0"> {{ selected.stock }} ширхэг нь бэлэн, үлдсэн нь захиалгаар ирнэ.</span></p>
                    </div>

                    <!-- Add to cart -->
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <QuantityInput v-model="qty" />
                        <button @click="addToCart" :disabled="!canBuy || adding" class="flex-1 py-3 text-base" :class="isBackorder ? 'btn bg-amber-500 text-white hover:bg-amber-600' : 'btn-brand'">
                            <ShoppingBagIcon class="h-5 w-5" /> {{ isBackorder ? 'Урьдчилан захиалах' : 'Сагсанд нэмэх' }} · {{ money(price * qty) }}
                        </button>
                    </div>

                    <ul class="mt-6 grid gap-3 text-sm text-stone-600 sm:grid-cols-3">
                        <li class="flex items-center gap-2"><TruckIcon class="h-5 w-5 text-brand-600" /> УБ хотод 24 цагт</li>
                        <li class="flex items-center gap-2"><ShieldCheckIcon class="h-5 w-5 text-brand-600" /> Албан ёсны баталгаа</li>
                        <li class="flex items-center gap-2"><CheckIcon class="h-5 w-5 text-brand-600" /> QPay / бэлэн төлбөр</li>
                    </ul>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mt-14">
                <div class="flex gap-6 border-b border-stone-200 text-sm font-medium">
                    <button @click="tab = 'desc'" class="-mb-px border-b-2 pb-3" :class="tab === 'desc' ? 'border-brand-600 text-brand-700' : 'border-transparent text-stone-500 hover:text-stone-800'">Дэлгэрэнгүй</button>
                    <button @click="tab = 'specs'" class="-mb-px border-b-2 pb-3" :class="tab === 'specs' ? 'border-brand-600 text-brand-700' : 'border-transparent text-stone-500 hover:text-stone-800'">Үзүүлэлт</button>
                    <button @click="tab = 'variants'" class="-mb-px border-b-2 pb-3" :class="tab === 'variants' ? 'border-brand-600 text-brand-700' : 'border-transparent text-stone-500 hover:text-stone-800'">Сонголтууд ({{ variants.length }})</button>
                </div>
                <div class="py-6">
                    <div v-if="tab === 'desc'" class="prose prose-stone max-w-3xl whitespace-pre-line text-stone-700">{{ product.description }}</div>
                    <dl v-else-if="tab === 'specs'" class="grid max-w-2xl gap-y-3 sm:grid-cols-2">
                        <template v-for="(v, k) in product.specs" :key="k"><dt class="text-sm text-stone-500">{{ k }}</dt><dd class="text-sm font-medium">{{ v }}</dd></template>
                        <p v-if="!product.specs || !Object.keys(product.specs).length" class="text-sm text-stone-500">Үзүүлэлт оруулаагүй.</p>
                    </dl>
                    <div v-else class="card overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-cream-100 text-left text-xs uppercase text-stone-500"><tr><th class="px-4 py-3">SKU</th><th class="px-4 py-3">Сонголт</th><th class="px-4 py-3">Үнэ</th><th class="px-4 py-3">Үлдэгдэл</th></tr></thead>
                            <tbody class="divide-y divide-stone-100">
                                <tr v-for="v in variants" :key="v.id" class="hover:bg-cream-100"><td class="px-4 py-2.5 text-stone-500">{{ v.sku }}</td><td class="px-4 py-2.5 font-medium">{{ v.label }}</td><td class="px-4 py-2.5">{{ money(v.price) }}</td><td class="px-4 py-2.5"><span class="badge" :class="v.stock > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">{{ v.stock > 0 ? `${v.stock} ш` : 'Захиалгаар' }}</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <section v-if="related.length" class="mt-10">
                <h2 class="mb-6 font-display text-2xl font-bold">Төстэй бараа</h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-4"><ProductCard v-for="p in related" :key="p.id" :product="p" /></div>
            </section>
        </template>
    </div>
</template>
