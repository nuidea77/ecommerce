<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../lib/api';
import { money } from '../lib/format';
import ProductCard from '../components/ProductCard.vue';
import Pagination from '../components/ui/Pagination.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import Spinner from '../components/ui/Spinner.vue';
import { AdjustmentsHorizontalIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();

const categories = ref([]);
const filters = ref({ colors: [], sizes: [], brands: [], price: { min: 0, max: 0 } });
const result = ref(null);
const loading = ref(false);
const mobileFilters = ref(false);

const state = reactive({
    q: route.query.q || '',
    category: route.query.category || '',
    color: route.query.color || '',
    size: route.query.size || '',
    brand: route.query.brand || '',
    min_price: route.query.min_price || '',
    max_price: route.query.max_price || '',
    in_stock: route.query.in_stock === '1',
    sort: route.query.sort || '',
    page: Number(route.query.page) || 1,
});

const sorts = [
    { value: '', label: 'Эрэмбэлэх: Онцлох' },
    { value: 'newest', label: 'Шинэ нь эхэндээ' },
    { value: 'price_asc', label: 'Үнэ: багаас их' },
    { value: 'price_desc', label: 'Үнэ: ихээс бага' },
    { value: 'name', label: 'Нэрээр' },
];

const activeCount = computed(() => ['category', 'color', 'size', 'brand', 'min_price', 'max_price'].filter((k) => state[k]).length + (state.in_stock ? 1 : 0));
const currentCategory = computed(() => categories.value.find((c) => c.slug === state.category));

function queryObject() {
    const q = {};
    Object.entries(state).forEach(([k, v]) => {
        if (k === 'in_stock') { if (v) q[k] = '1'; }
        else if (k === 'page') { if (v > 1) q[k] = v; }
        else if (v) q[k] = v;
    });
    return q;
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/products', { params: { ...queryObject(), per_page: 12 } });
        result.value = data;
    } finally {
        loading.value = false;
    }
}

function apply(resetPage = true) {
    if (resetPage) state.page = 1;
    router.replace({ query: queryObject() });
}

function reset() {
    Object.assign(state, { q: '', category: '', color: '', size: '', brand: '', min_price: '', max_price: '', in_stock: false, sort: '', page: 1 });
    router.replace({ query: {} });
}

watch(() => route.query, (q) => {
    Object.assign(state, {
        q: q.q || '', category: q.category || '', color: q.color || '', size: q.size || '', brand: q.brand || '',
        min_price: q.min_price || '', max_price: q.max_price || '', in_stock: q.in_stock === '1', sort: q.sort || '', page: Number(q.page) || 1,
    });
    load();
});

onMounted(async () => {
    const [c, f] = await Promise.all([api.get('/categories'), api.get('/products/filters')]);
    categories.value = c.data;
    filters.value = f.data;
    load();
});
</script>

<template>
    <div class="container-x py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-stone-500"><router-link :to="{ name: 'home' }" class="hover:text-brand-700">Нүүр</router-link> / Дэлгүүр<span v-if="currentCategory"> / {{ currentCategory.name }}</span></p>
                <h1 class="mt-1 font-display text-3xl font-bold">{{ currentCategory?.name || (state.q ? `"${state.q}" хайлт` : 'Бүх бараа') }}</h1>
                <p v-if="currentCategory?.description" class="mt-1 text-sm text-stone-500">{{ currentCategory.description }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button class="btn-secondary lg:hidden" @click="mobileFilters = true"><AdjustmentsHorizontalIcon class="h-5 w-5" /> Шүүлтүүр <span v-if="activeCount" class="badge bg-brand-600 text-white">{{ activeCount }}</span></button>
                <select v-model="state.sort" @change="apply()" class="input w-auto py-2">
                    <option v-for="s in sorts" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[260px_1fr]">
            <!-- Filters -->
            <aside class="fixed inset-0 z-50 overflow-y-auto bg-white p-6 lg:static lg:z-auto lg:block lg:bg-transparent lg:p-0" :class="mobileFilters ? 'block' : 'hidden'">
                <div class="mb-4 flex items-center justify-between lg:hidden"><h3 class="text-lg font-semibold">Шүүлтүүр</h3><button @click="mobileFilters = false"><XMarkIcon class="h-6 w-6" /></button></div>
                <div class="space-y-6">
                    <div class="card p-5">
                        <h4 class="mb-3 text-sm font-semibold">Ангилал</h4>
                        <ul class="space-y-1.5 text-sm">
                            <li><button @click="state.category = ''; apply()" class="w-full rounded-lg px-2 py-1.5 text-left hover:bg-stone-50" :class="!state.category && 'bg-brand-50 font-semibold text-brand-700'">Бүгд</button></li>
                            <li v-for="c in categories" :key="c.id"><button @click="state.category = c.slug; apply()" class="flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left hover:bg-stone-50" :class="state.category === c.slug && 'bg-brand-50 font-semibold text-brand-700'"><span>{{ c.icon }} {{ c.name }}</span><span class="text-xs text-stone-400">{{ c.products_count }}</span></button></li>
                        </ul>
                    </div>

                    <div class="card p-5">
                        <h4 class="mb-3 text-sm font-semibold">Өнгө</h4>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="c in filters.colors" :key="c.color" @click="state.color = state.color === c.color ? '' : c.color; apply()" :title="c.color" class="flex items-center gap-1.5 rounded-full py-1 pl-1 pr-2.5 text-xs ring-1 transition" :class="state.color === c.color ? 'bg-brand-800 text-white ring-brand-800' : 'bg-white text-stone-700 ring-stone-200 hover:ring-stone-400'">
                                <span class="h-4 w-4 rounded-full ring-1 ring-stone-300" :style="{ backgroundColor: c.color_hex || '#ddd' }"></span>{{ c.color }}
                            </button>
                        </div>
                    </div>

                    <div class="card p-5" v-if="filters.sizes.length">
                        <h4 class="mb-3 text-sm font-semibold">Хэмжээ</h4>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="s in filters.sizes" :key="s" @click="state.size = state.size === s ? '' : s; apply()" class="rounded-lg px-2.5 py-1 text-xs ring-1 transition" :class="state.size === s ? 'bg-brand-800 text-white ring-brand-800' : 'bg-white ring-stone-200 hover:ring-stone-400'">{{ s }}</button>
                        </div>
                    </div>

                    <div class="card p-5">
                        <h4 class="mb-3 text-sm font-semibold">Брэнд</h4>
                        <select v-model="state.brand" @change="apply()" class="input py-2"><option value="">Бүх брэнд</option><option v-for="b in filters.brands" :key="b" :value="b">{{ b }}</option></select>
                    </div>

                    <div class="card p-5">
                        <h4 class="mb-3 text-sm font-semibold">Үнэ (₮)</h4>
                        <div class="flex items-center gap-2">
                            <input v-model="state.min_price" type="number" :placeholder="String(filters.price.min)" class="input py-2" @change="apply()" />
                            <span class="text-stone-400">–</span>
                            <input v-model="state.max_price" type="number" :placeholder="String(filters.price.max)" class="input py-2" @change="apply()" />
                        </div>
                        <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" v-model="state.in_stock" @change="apply()" class="rounded border-stone-300 text-brand-600 focus:ring-brand-500" /> Зөвхөн бэлэн байгаа</label>
                    </div>

                    <button v-if="activeCount || state.q" @click="reset" class="btn-ghost w-full">Шүүлтүүр цэвэрлэх</button>
                    <button class="btn-brand w-full lg:hidden" @click="mobileFilters = false">Үр дүн харах</button>
                </div>
            </aside>

            <!-- Results -->
            <div>
                <Spinner v-if="loading && !result" />
                <template v-else-if="result">
                    <div v-if="activeCount || state.q" class="mb-4 flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-stone-500">Шүүлт:</span>
                        <span v-if="state.q" class="badge bg-stone-100 text-stone-700">"{{ state.q }}" <button @click="state.q = ''; apply()">×</button></span>
                        <span v-if="state.color" class="badge bg-stone-100 text-stone-700">{{ state.color }} <button @click="state.color = ''; apply()">×</button></span>
                        <span v-if="state.size" class="badge bg-stone-100 text-stone-700">{{ state.size }} <button @click="state.size = ''; apply()">×</button></span>
                        <span v-if="state.brand" class="badge bg-stone-100 text-stone-700">{{ state.brand }} <button @click="state.brand = ''; apply()">×</button></span>
                        <span v-if="state.min_price || state.max_price" class="badge bg-stone-100 text-stone-700">{{ state.min_price ? money(state.min_price) : '0₮' }} – {{ state.max_price ? money(state.max_price) : '∞' }} <button @click="state.min_price = state.max_price = ''; apply()">×</button></span>
                        <span v-if="state.in_stock" class="badge bg-stone-100 text-stone-700">Бэлэн байгаа <button @click="state.in_stock = false; apply()">×</button></span>
                    </div>
                    <p class="mb-4 text-sm text-stone-500">{{ result.total }} бараа олдлоо</p>
                    <div v-if="result.data.length" class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3" :class="loading && 'opacity-50'">
                        <ProductCard v-for="p in result.data" :key="p.id" :product="p" />
                    </div>
                    <EmptyState v-else title="Бараа олдсонгүй" description="Шүүлтүүрээ өөрчилж эсвэл өөр түлхүүр үгээр хайж үзнэ үү." icon="🔍"><button @click="reset" class="btn-secondary">Шүүлтүүр цэвэрлэх</button></EmptyState>
                    <div class="mt-8"><Pagination :meta="result" @change="(p) => { state.page = p; apply(false); }" /></div>
                </template>
            </div>
        </div>
    </div>
</template>
