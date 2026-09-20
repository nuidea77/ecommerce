<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../lib/api';
import { money } from '../lib/format';
import ProductCard from '../components/ProductCard.vue';
import Pagination from '../components/ui/Pagination.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import Spinner from '../components/ui/Spinner.vue';
import FilterSection from '../components/shop/FilterSection.vue';
import CheckList from '../components/shop/CheckList.vue';
import PriceRange from '../components/shop/PriceRange.vue';
import { AdjustmentsHorizontalIcon, XMarkIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();

const facets = ref(null);
const result = ref(null);
const loading = ref(false);
const mobileFilters = ref(false);

const MULTI = ['category', 'color', 'size', 'pack', 'discount', 'status'];
const toArr = (v) => (v == null || v === '' ? [] : Array.isArray(v) ? v : String(v).split(','));
const fromQuery = (q) => ({
    q: q.q || '', sort: q.sort || '', min_price: q.min_price || '', max_price: q.max_price || '', page: Number(q.page) || 1,
    ...Object.fromEntries(MULTI.map((k) => [k, toArr(q[k])])),
    status: [...toArr(q.status), ...(q.in_stock === '1' ? ['in_stock'] : q.in_stock === '0' ? ['preorder'] : [])],
});
const state = reactive(fromQuery(route.query));

const sorts = [
    { value: '', label: 'Онцлох эхэндээ' }, { value: 'newest', label: 'Шинэ эхэндээ' }, { value: 'popular', label: 'Их зарагдсан' },
    { value: 'discount', label: 'Хямдрал ихтэй' }, { value: 'price_asc', label: 'Үнэ: багаас их' }, { value: 'price_desc', label: 'Үнэ: ихээс бага' }, { value: 'name', label: 'Нэрээр' },
];

const activeCount = computed(() => MULTI.reduce((n, k) => n + state[k].length, 0) + (state.min_price || state.max_price ? 1 : 0));
const currentCategory = computed(() => state.category.length === 1 ? facets.value?.categories.find((c) => c.slug === state.category[0]) : null);
const chips = computed(() => {
    const out = [];
    const label = (list, key, valueKey, labelKey) => (v) => list?.find((i) => String(i[valueKey]) === String(v))?.[labelKey] ?? v;
    state.category.forEach((v) => out.push({ k: 'category', v, l: label(facets.value?.categories, 'category', 'slug', 'name')(v) }));
    state.color.forEach((v) => out.push({ k: 'color', v, l: v }));
    state.size.forEach((v) => out.push({ k: 'size', v, l: v }));
    state.pack.forEach((v) => out.push({ k: 'pack', v, l: label(facets.value?.packs, 'pack', 'pack_size', 'label')(v) }));
    state.discount.forEach((v) => out.push({ k: 'discount', v, l: label(facets.value?.discounts, 'discount', 'value', 'label')(v) }));
    state.status.forEach((v) => out.push({ k: 'status', v, l: label(facets.value?.statuses, 'status', 'value', 'label')(v) }));
    if (state.min_price || state.max_price) out.push({ k: 'price', l: `${state.min_price ? money(state.min_price) : '0₮'} – ${state.max_price ? money(state.max_price) : '∞'}` });
    return out;
});

function queryObject() {
    const q = {};
    if (state.q) q.q = state.q;
    if (state.sort) q.sort = state.sort;
    if (state.min_price) q.min_price = state.min_price;
    if (state.max_price) q.max_price = state.max_price;
    if (state.page > 1) q.page = state.page;
    MULTI.forEach((k) => { if (state[k].length) q[k] = state[k].join(','); });
    return q;
}

async function load() {
    loading.value = true;
    try {
        const params = { ...queryObject(), per_page: 16 };
        const [p, f] = await Promise.all([api.get('/products', { params }), api.get('/products/filters', { params })]);
        result.value = p.data;
        facets.value = f.data;
    } finally {
        loading.value = false;
    }
}

function apply(resetPage = true) {
    if (resetPage) state.page = 1;
    router.replace({ query: queryObject() });
}
function toggle(key, value) {
    const v = String(value);
    state[key] = state[key].includes(v) ? state[key].filter((x) => x !== v) : [...state[key], v];
    apply();
}
function removeChip(c) {
    if (c.k === 'price') { state.min_price = state.max_price = ''; } else { state[c.k] = state[c.k].filter((x) => x !== c.v); }
    apply();
}
function reset() {
    Object.assign(state, fromQuery({}));
    router.replace({ query: {} });
}

watch(() => route.query, (q) => { Object.assign(state, fromQuery(q)); load(); });
onMounted(load);
</script>

<template>
    <div class="container-x py-5">
        <p class="mb-3 text-xs text-stone-500"><router-link :to="{ name: 'home' }" class="hover:text-brand-700">Нүүр</router-link> <ChevronRightIcon class="inline h-3 w-3" /> Бүтээгдэхүүн<template v-if="currentCategory"> <ChevronRightIcon class="inline h-3 w-3" /> {{ currentCategory.name }}</template></p>

        <div class="grid gap-6 lg:grid-cols-[250px_1fr]">
            <!-- Filters -->
            <aside class="fixed inset-0 z-50 overflow-y-auto bg-paper p-5 lg:static lg:z-auto lg:block lg:overflow-visible lg:rounded-xl lg:p-4 lg:ring-1 lg:ring-cream-300/70" :class="mobileFilters ? 'block' : 'hidden'">
                <div class="mb-2 flex items-center justify-between"><h3 class="text-base font-bold">Шүүлтүүр</h3><button class="lg:hidden" @click="mobileFilters = false"><XMarkIcon class="h-6 w-6" /></button><button v-if="activeCount" @click="reset" class="hidden text-xs text-brand-700 hover:underline lg:block">Цэвэрлэх</button></div>

                <template v-if="facets">
                    <FilterSection title="Ангилал" :count="state.category.length">
                        <ul class="space-y-0.5">
                            <li v-for="c in facets.categories" :key="c.id">
                                <button type="button" @click="toggle('category', c.slug)" class="flex w-full items-center gap-1.5 rounded-md px-1 py-1 text-left text-[13px] hover:bg-cream-100" :class="state.category.includes(c.slug) ? 'font-semibold text-brand-700' : 'text-stone-700'">
                                    <ChevronRightIcon class="h-3 w-3 shrink-0 text-stone-400 transition" :class="state.category.includes(c.slug) && 'rotate-90 text-brand-600'" /><span class="flex-1 truncate">{{ c.name }}</span><span class="text-[11px] tabular-nums text-stone-400">{{ c.products_count }}</span>
                                </button>
                            </li>
                        </ul>
                    </FilterSection>

                    <FilterSection title="Хямдрал" :count="state.discount.length">
                        <CheckList :items="facets.discounts" :model-value="state.discount" @update:model-value="state.discount = $event; apply()" />
                    </FilterSection>

                    <FilterSection title="Төлөв" :count="state.status.length">
                        <CheckList :items="facets.statuses" :model-value="state.status" @update:model-value="state.status = $event; apply()" />
                    </FilterSection>

                    <FilterSection title="Өнгө" :count="state.color.length">
                        <div class="grid grid-cols-6 gap-2">
                            <button v-for="c in facets.colors" :key="c.color" type="button" @click="toggle('color', c.color)" :title="`${c.color} (${c.count})`" class="relative aspect-square rounded-md ring-1 ring-black/10 transition hover:scale-110" :class="[state.color.includes(c.color) ? 'ring-2 ring-brand-700 ring-offset-2' : '', !c.count && !state.color.includes(c.color) && 'opacity-30']" :style="{ backgroundColor: c.color_hex || '#ddd' }">
                                <span v-if="state.color.includes(c.color)" class="absolute inset-0 flex items-center justify-center text-xs font-bold drop-shadow" :class="['#f5f5f4', '#e0f2fe', '#e7d5c0', '#c0c0c0'].includes(c.color_hex) ? 'text-stone-900' : 'text-white'">✓</span>
                            </button>
                        </div>
                        <p v-if="state.color.length" class="mt-2 text-[11px] text-stone-500">{{ state.color.join(', ') }}</p>
                    </FilterSection>

                    <FilterSection v-if="facets.sizes.length" title="Хэмжээ" :count="state.size.length">
                        <div class="grid grid-cols-4 gap-1.5">
                            <button v-for="s in facets.sizes" :key="s.size" type="button" @click="toggle('size', s.size)" class="truncate rounded-md px-1 py-1.5 text-xs font-medium ring-1 transition" :class="[state.size.includes(s.size) ? 'bg-brand-700 text-white ring-brand-700' : 'bg-paper text-stone-700 ring-cream-300 hover:ring-brand-400', !s.count && !state.size.includes(s.size) && 'opacity-30']" :title="`${s.size} (${s.count})`">{{ s.size }}</button>
                        </div>
                    </FilterSection>

                    <FilterSection v-if="facets.packs.length > 1" title="Багц" :count="state.pack.length">
                        <div class="grid grid-cols-3 gap-1.5">
                            <button v-for="p in facets.packs" :key="p.pack_size" type="button" @click="toggle('pack', p.pack_size)" class="truncate rounded-md px-1 py-1.5 text-xs font-medium ring-1 transition" :class="[state.pack.includes(String(p.pack_size)) ? 'bg-brand-700 text-white ring-brand-700' : 'bg-paper text-stone-700 ring-cream-300 hover:ring-brand-400', !p.count && !state.pack.includes(String(p.pack_size)) && 'opacity-30']">{{ p.label || `${p.pack_size} ш` }}</button>
                        </div>
                    </FilterSection>

                    <FilterSection title="Үнэ" :count="state.min_price || state.max_price ? 1 : 0">
                        <PriceRange :min="Math.floor(facets.price.min)" :max="Math.ceil(facets.price.max)" :from="state.min_price" :to="state.max_price" @change="({ min, max }) => { state.min_price = min; state.max_price = max; apply(); }" />
                    </FilterSection>
                </template>
                <Spinner v-else />

                <button class="btn-brand mt-4 w-full lg:hidden" @click="mobileFilters = false">Үр дүн харах</button>
            </aside>

            <!-- Results -->
            <div class="min-w-0">
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <h1 class="text-xl font-bold">{{ currentCategory?.name || (state.q ? `"${state.q}"` : 'Бүтээгдэхүүн') }} <span v-if="result" class="text-sm font-normal text-stone-500">· {{ result.total }} бүтээгдэхүүн</span></h1>
                    <div class="ml-auto flex items-center gap-2">
                        <button class="btn-secondary btn-sm lg:hidden" @click="mobileFilters = true"><AdjustmentsHorizontalIcon class="h-4 w-4" /> Шүүлтүүр <span v-if="activeCount" class="rounded-full bg-brand-700 px-1.5 text-[10px] text-white">{{ activeCount }}</span></button>
                        <select v-model="state.sort" @change="apply()" class="input w-auto py-1.5 text-[13px]"><option v-for="s in sorts" :key="s.value" :value="s.value">{{ s.label }}</option></select>
                    </div>
                </div>


                <div v-if="chips.length" class="mb-4 flex flex-wrap items-center gap-1.5 text-xs">
                    <span v-for="c in chips" :key="c.k + c.v" class="inline-flex items-center gap-1 rounded-full bg-brand-700 py-1 pl-2.5 pr-1.5 text-white"><span>{{ c.l }}</span><button @click="removeChip(c)" class="rounded-full p-0.5 hover:bg-white/20"><XMarkIcon class="h-3 w-3" /></button></span>
                    <button @click="reset" class="ml-1 text-brand-700 hover:underline">Бүгдийг цэвэрлэх</button>
                </div>

                <Spinner v-if="loading && !result" />
                <template v-else-if="result">
                    <div v-if="result.data.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4" :class="loading && 'opacity-50'">
                        <ProductCard v-for="p in result.data" :key="p.id" :product="p" />
                    </div>
                    <EmptyState v-else title="Бараа олдсонгүй" description="Шүүлтүүрээ өөрчилж эсвэл өөр түлхүүр үгээр хайж үзнэ үү." icon="🔍"><button @click="reset" class="btn-secondary">Шүүлтүүр цэвэрлэх</button></EmptyState>
                    <div class="mt-6"><Pagination :meta="result" @change="(p) => { state.page = p; apply(false); }" /></div>
                </template>
            </div>
        </div>
    </div>
</template>
