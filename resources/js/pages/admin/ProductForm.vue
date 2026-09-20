<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { useUiStore } from '../../stores/ui';
import Spinner from '../../components/ui/Spinner.vue';
import { PlusIcon, TrashIcon, PhotoIcon, XMarkIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/outline';

const route = useRoute(); const router = useRouter(); const ui = useUiStore();
const id = computed(() => route.params.id);
const categories = ref([]);
const loading = ref(!!id.value);
const saving = ref(false);
const errors = ref({});
const uploading = ref(false);
const fileInput = ref(null);

const form = reactive({
    category_id: '', name: '', brand: '', short_description: '', description: '', base_price: 0, compare_price: null,
    images: [], specs: [], is_active: true, is_featured: false, allow_backorder: true, backorder_days: 14, variants: [],
});
const newImageUrl = ref('');

const COLOR_PRESETS = { 'Хар': '#1c1917', 'Цагаан': '#f5f5f4', 'Ягаан': '#f472b6', 'Алтан': '#d4a017', 'Мөнгөлөг': '#c0c0c0', 'Улаан': '#dc2626', 'Хөх': '#2563eb', 'Ногоон': '#16a34a', 'Бор': '#78350f', 'Саарал': '#6b7280' };

function blankVariant(from = null) {
    return { id: null, sku: '', color: from?.color || '', color_hex: from?.color_hex || '', size: from?.size || '', pack_size: from?.pack_size || 1, pack_label: from?.pack_label || '', price: from?.price || form.base_price || 0, stock: 0, image: '', is_active: true };
}
function addVariant(from = null) { form.variants.push(blankVariant(from)); }
function onColorInput(v) { if (COLOR_PRESETS[v.color]) v.color_hex = COLOR_PRESETS[v.color]; }

async function upload(e) {
    const file = e.target.files?.[0]; if (!file) return;
    uploading.value = true;
    try { const fd = new FormData(); fd.append('image', file); const { data } = await api.post('/admin/products/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } }); form.images.push(data.url); ui.toast('Зураг хуулагдлаа'); }
    catch (err) { ui.toast(errorMessage(err), 'error'); }
    finally { uploading.value = false; e.target.value = ''; }
}
function addImageUrl() { if (newImageUrl.value.trim()) { form.images.push(newImageUrl.value.trim()); newImageUrl.value = ''; } }

async function save() {
    saving.value = true; errors.value = {};
    const payload = { ...form, specs: Object.fromEntries(form.specs.filter((s) => s.key).map((s) => [s.key, s.value])), compare_price: form.compare_price || null };
    try {
        const res = id.value ? await api.put(`/admin/products/${id.value}`, payload) : await api.post('/admin/products', payload);
        ui.toast('Хадгалагдлаа');
        if (!id.value) router.replace({ name: 'admin.products.edit', params: { id: res.data.id } });
        hydrate(res.data);
    } catch (e) { errors.value = e.response?.data?.errors || {}; ui.toast(errorMessage(e), 'error'); }
    finally { saving.value = false; }
}
function hydrate(p) {
    Object.assign(form, {
        category_id: p.category_id, name: p.name, brand: p.brand || '', short_description: p.short_description || '', description: p.description || '',
        base_price: p.base_price, compare_price: p.compare_price, images: p.images || [], specs: Object.entries(p.specs || {}).map(([key, value]) => ({ key, value })),
        is_active: p.is_active, is_featured: p.is_featured, allow_backorder: p.allow_backorder, backorder_days: p.backorder_days,
        variants: (p.variants || []).map((v) => ({ ...v, color: v.color || '', color_hex: v.color_hex || '', size: v.size || '', pack_label: v.pack_label || '', image: v.image || '' })),
    });
}
onMounted(async () => {
    categories.value = (await api.get('/admin/categories')).data;
    if (id.value) { hydrate((await api.get(`/admin/products/${id.value}`)).data); loading.value = false; }
    else { form.category_id = categories.value[0]?.id || ''; addVariant(); }
});
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-sm text-stone-500"><router-link :to="{ name: 'admin.products' }" class="hover:text-brand-700">Бүтээгдэхүүн</router-link> / {{ id ? 'Засах' : 'Шинэ' }}</p><h1 class="text-2xl font-bold">{{ id ? form.name || 'Засах' : 'Шинэ бүтээгдэхүүн' }}</h1></div>
            <div class="flex gap-2"><router-link :to="{ name: 'admin.products' }" class="btn-secondary">Буцах</router-link><button @click="save" :disabled="saving" class="btn-brand">{{ saving ? 'Хадгалж байна...' : 'Хадгалах' }}</button></div>
        </div>
        <Spinner v-if="loading" />
        <form v-else @submit.prevent="save" class="mt-6 grid gap-6 xl:grid-cols-[1fr_340px]">
            <div class="space-y-6">
                <section class="card p-6">
                    <h2 class="font-semibold">Үндсэн мэдээлэл</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label class="label">Нэр *</label><input v-model="form.name" class="input" required /><p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p></div>
                        <div><label class="label">Ангилал *</label><select v-model="form.category_id" class="input" required><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                        <div><label class="label">Брэнд</label><input v-model="form.brand" class="input" /></div>
                        <div><label class="label">Үндсэн үнэ (₮) *</label><input v-model.number="form.base_price" type="number" min="0" class="input" required /><p class="mt-1 text-xs text-stone-400">Сонголтуудын хамгийн бага үнээр автоматаар шинэчлэгдэнэ</p></div>
                        <div><label class="label">Хямдралын өмнөх үнэ (₮)</label><input v-model.number="form.compare_price" type="number" min="0" class="input" /></div>
                        <div class="sm:col-span-2"><label class="label">Богино тайлбар</label><input v-model="form.short_description" class="input" maxlength="500" /></div>
                        <div class="sm:col-span-2"><label class="label">Дэлгэрэнгүй тайлбар</label><textarea v-model="form.description" rows="6" class="input"></textarea></div>
                    </div>
                </section>

                <section class="card p-6">
                    <div class="flex items-center justify-between"><div><h2 class="font-semibold">Сонголтууд (өнгө / хэмжээ / багц)</h2><p class="text-xs text-stone-500">Сонголт бүр өөрийн SKU, үнэ, үлдэгдэлтэй. Үлдэгдэл 0 бол урьдчилсан захиалгаар зарагдана.</p></div><button type="button" @click="addVariant()" class="btn-secondary btn-sm"><PlusIcon class="h-4 w-4" /> Нэмэх</button></div>
                    <p v-if="errors.variants" class="mt-2 text-xs text-red-600">{{ errors.variants[0] }}</p>
                    <div class="mt-4 space-y-3">
                        <div v-for="(v, i) in form.variants" :key="i" class="rounded-xl bg-cream-100 p-4 ring-1 ring-stone-200">
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div><label class="label text-xs">Өнгө</label><div class="flex gap-1.5"><input v-model="v.color" @input="onColorInput(v)" list="colors" class="input py-2" placeholder="Хар" /><input v-model="v.color_hex" type="color" class="h-10 w-10 shrink-0 cursor-pointer rounded-lg border-0 bg-transparent p-0" /></div></div>
                                <div><label class="label text-xs">Хэмжээ</label><input v-model="v.size" class="input py-2" placeholder="M / 25мм / 6.0&quot;" /></div>
                                <div><label class="label text-xs">Багцын тоо</label><input v-model.number="v.pack_size" type="number" min="1" class="input py-2" /></div>
                                <div><label class="label text-xs">Багцын нэр</label><input v-model="v.pack_label" class="input py-2" placeholder="6-н багц" /></div>
                                <div><label class="label text-xs">Үнэ (₮) *</label><input v-model.number="v.price" type="number" min="0" class="input py-2" required /></div>
                                <div><label class="label text-xs">Үлдэгдэл *</label><input v-model.number="v.stock" type="number" class="input py-2" required /></div>
                                <div><label class="label text-xs">SKU</label><input v-model="v.sku" class="input py-2" placeholder="Автомат" /></div>
                                <div><label class="label text-xs">Зураг URL</label><input v-model="v.image" class="input py-2" placeholder="Сонголтын зураг" /></div>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <label class="flex items-center gap-2 text-xs"><input type="checkbox" v-model="v.is_active" class="rounded border-stone-300 text-brand-600" /> Идэвхтэй</label>
                                <div class="flex gap-1"><button type="button" @click="addVariant(v)" class="btn-ghost btn-sm" title="Хуулах"><DocumentDuplicateIcon class="h-4 w-4" /></button><button type="button" @click="form.variants.splice(i, 1)" class="btn-ghost btn-sm text-red-600"><TrashIcon class="h-4 w-4" /></button></div>
                            </div>
                        </div>
                    </div>
                    <datalist id="colors"><option v-for="(hex, name) in COLOR_PRESETS" :key="name" :value="name" /></datalist>
                </section>

                <section class="card p-6">
                    <div class="flex items-center justify-between"><h2 class="font-semibold">Техник үзүүлэлт</h2><button type="button" @click="form.specs.push({ key: '', value: '' })" class="btn-secondary btn-sm"><PlusIcon class="h-4 w-4" /> Нэмэх</button></div>
                    <div class="mt-4 space-y-2">
                        <div v-for="(s, i) in form.specs" :key="i" class="flex gap-2"><input v-model="s.key" class="input py-2" placeholder="Чадал" /><input v-model="s.value" class="input py-2" placeholder="2200W" /><button type="button" @click="form.specs.splice(i, 1)" class="btn-ghost btn-sm text-red-600"><TrashIcon class="h-4 w-4" /></button></div>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="card p-6">
                    <h2 class="font-semibold">Зураг</h2>
                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <div v-for="(img, i) in form.images" :key="i" class="group relative aspect-square overflow-hidden rounded-lg bg-cream-200/60 ring-1 ring-stone-200"><img :src="img" class="h-full w-full object-cover" alt="" /><button type="button" @click="form.images.splice(i, 1)" class="absolute right-1 top-1 hidden rounded-full bg-white/90 p-0.5 text-red-600 group-hover:block"><XMarkIcon class="h-4 w-4" /></button><span v-if="i === 0" class="absolute bottom-1 left-1 rounded bg-stone-900/70 px-1 text-[10px] text-white">Үндсэн</span></div>
                        <button type="button" @click="fileInput.click()" :disabled="uploading" class="flex aspect-square flex-col items-center justify-center rounded-lg border-2 border-dashed border-stone-300 text-stone-400 hover:border-brand-400 hover:text-brand-600"><PhotoIcon class="h-6 w-6" /><span class="text-[10px]">{{ uploading ? '...' : 'Хуулах' }}</span></button>
                    </div>
                    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="upload" />
                    <div class="mt-3 flex gap-2"><input v-model="newImageUrl" class="input py-2 text-xs" placeholder="эсвэл зургийн URL" @keydown.enter.prevent="addImageUrl" /><button type="button" @click="addImageUrl" class="btn-secondary btn-sm">+</button></div>
                </section>
                <section class="card space-y-3 p-6">
                    <h2 class="font-semibold">Тохиргоо</h2>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded border-stone-300 text-brand-600" /> Дэлгүүрт харагдана</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_featured" class="rounded border-stone-300 text-brand-600" /> Онцлох бүтээгдэхүүн</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.allow_backorder" class="rounded border-stone-300 text-brand-600" /> Дууссан үед урьдчилан захиалах боломжтой</label>
                    <div v-if="form.allow_backorder"><label class="label">Урьдчилсан захиалгын хугацаа (хоног)</label><input v-model.number="form.backorder_days" type="number" min="1" class="input py-2" /></div>
                </section>
                <button type="submit" :disabled="saving" class="btn-brand w-full py-3">{{ saving ? 'Хадгалж байна...' : 'Хадгалах' }}</button>
            </div>
        </form>
    </div>
</template>
