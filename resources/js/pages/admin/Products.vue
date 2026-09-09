<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import api, { errorMessage } from '../../lib/api';
import { money } from '../../lib/format';
import { useUiStore } from '../../stores/ui';
import Pagination from '../../components/ui/Pagination.vue';
import Spinner from '../../components/ui/Spinner.vue';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const ui = useUiStore();
const result = ref(null);
const categories = ref([]);
const f = reactive({ q: '', category_id: '', status: '', low_stock: false, page: 1 });

async function load() {
    const { data } = await api.get('/admin/products', { params: { ...f, low_stock: f.low_stock ? 1 : undefined } });
    result.value = data;
}
async function remove(p) {
    if (!confirm(`"${p.name}" бүтээгдэхүүнийг устгах уу?`)) return;
    try { await api.delete(`/admin/products/${p.id}`); ui.toast('Устгагдлаа'); load(); } catch (e) { ui.toast(errorMessage(e), 'error'); }
}
async function toggle(p) {
    try { await api.put(`/admin/products/${p.id}`, { ...p, is_active: !p.is_active, variants: undefined }); load(); } catch (e) { ui.toast(errorMessage(e), 'error'); }
}
let t;
watch(() => [f.q, f.category_id, f.status, f.low_stock], () => { f.page = 1; clearTimeout(t); t = setTimeout(load, 250); });
onMounted(async () => { categories.value = (await api.get('/admin/categories')).data; load(); });
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Бүтээгдэхүүн</h1>
            <router-link :to="{ name: 'admin.products.new' }" class="btn-brand"><PlusIcon class="h-5 w-5" /> Шинэ бүтээгдэхүүн</router-link>
        </div>
        <div class="card mt-5 flex flex-wrap items-center gap-3 p-4">
            <input v-model="f.q" placeholder="Нэр, брэндээр хайх..." class="input max-w-xs py-2" />
            <select v-model="f.category_id" class="input w-auto py-2"><option value="">Бүх ангилал</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            <select v-model="f.status" class="input w-auto py-2"><option value="">Бүх төлөв</option><option value="active">Идэвхтэй</option><option value="inactive">Идэвхгүй</option></select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="f.low_stock" class="rounded border-stone-300 text-brand-600" /> Дуусаж буй</label>
        </div>
        <Spinner v-if="!result" />
        <div v-else class="card mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500"><tr><th class="px-4 py-3">Бүтээгдэхүүн</th><th class="px-4 py-3">Ангилал</th><th class="px-4 py-3">Үнэ</th><th class="px-4 py-3">Сонголт / Үлдэгдэл</th><th class="px-4 py-3">Зарагдсан</th><th class="px-4 py-3">Төлөв</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    <tr v-for="p in result.data" :key="p.id" class="hover:bg-stone-50">
                        <td class="px-4 py-3"><div class="flex items-center gap-3"><img :src="p.thumbnail" class="h-11 w-11 rounded-lg bg-stone-100 object-cover" alt="" /><div><p class="font-medium">{{ p.name }}</p><p class="text-xs text-stone-400">{{ p.brand }}<span v-if="p.is_featured" class="ml-1 text-brand-600">★ онцлох</span></p></div></div></td>
                        <td class="px-4 py-3 text-stone-600">{{ p.category?.name }}</td>
                        <td class="px-4 py-3 font-medium">{{ p.min_price === p.max_price ? money(p.min_price) : `${money(p.min_price)} – ${money(p.max_price)}` }}</td>
                        <td class="px-4 py-3"><span class="text-stone-600">{{ p.variants.length }} сонголт</span> · <span class="badge" :class="p.total_stock <= 0 ? 'bg-red-100 text-red-700' : p.total_stock <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">{{ p.total_stock }} ш</span></td>
                        <td class="px-4 py-3 text-stone-600">{{ p.sold_count }}</td>
                        <td class="px-4 py-3"><button @click="toggle(p)" class="badge" :class="p.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'">{{ p.is_active ? 'Идэвхтэй' : 'Идэвхгүй' }}</button></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap"><router-link :to="{ name: 'admin.products.edit', params: { id: p.id } }" class="btn-ghost btn-sm"><PencilSquareIcon class="h-4 w-4" /></router-link><button @click="remove(p)" class="btn-ghost btn-sm text-red-600"><TrashIcon class="h-4 w-4" /></button></td>
                    </tr>
                    <tr v-if="!result.data.length"><td colspan="7" class="px-4 py-10 text-center text-stone-500">Бүтээгдэхүүн олдсонгүй</td></tr>
                </tbody>
            </table>
            <div class="p-4"><Pagination :meta="result" @change="(p) => { f.page = p; load(); }" /></div>
        </div>
    </div>
</template>
