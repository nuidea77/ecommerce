<script setup>
import { ref, reactive, onMounted } from 'vue';
import api, { errorMessage } from '../../lib/api';
import { useUiStore } from '../../stores/ui';
import Modal from '../../components/ui/Modal.vue';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const ui = useUiStore();
const items = ref([]);
const open = ref(false);
const editing = ref(null);
const form = reactive({ name: '', icon: '', image: '', description: '', sort_order: 0, is_active: true });
const saving = ref(false);

async function load() { items.value = (await api.get('/admin/categories')).data; }
function create() { editing.value = null; Object.assign(form, { name: '', icon: '', image: '', description: '', sort_order: items.value.length, is_active: true }); open.value = true; }
function edit(c) { editing.value = c; Object.assign(form, { name: c.name, icon: c.icon || '', image: c.image || '', description: c.description || '', sort_order: c.sort_order, is_active: c.is_active }); open.value = true; }
async function save() {
    saving.value = true;
    try { editing.value ? await api.put(`/admin/categories/${editing.value.id}`, form) : await api.post('/admin/categories', form); ui.toast('Хадгалагдлаа'); open.value = false; load(); }
    catch (e) { ui.toast(errorMessage(e), 'error'); } finally { saving.value = false; }
}
async function remove(c) {
    if (!confirm(`"${c.name}" ангиллыг устгах уу?`)) return;
    try { await api.delete(`/admin/categories/${c.id}`); ui.toast('Устгагдлаа'); load(); } catch (e) { ui.toast(errorMessage(e), 'error'); }
}
onMounted(load);
</script>
<template>
    <div>
        <div class="flex items-center justify-between"><h1 class="text-2xl font-bold">Ангилал</h1><button @click="create" class="btn-brand"><PlusIcon class="h-5 w-5" /> Шинэ ангилал</button></div>
        <div class="card mt-5 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Ангилал</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">Бараа</th><th class="px-4 py-3">Төлөв</th><th></th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    <tr v-for="c in items" :key="c.id" class="hover:bg-stone-50">
                        <td class="px-4 py-3 text-stone-400">{{ c.sort_order }}</td>
                        <td class="px-4 py-3"><div class="flex items-center gap-3"><img v-if="c.image" :src="c.image" class="h-10 w-10 rounded-lg object-cover" alt="" /><span v-else class="text-xl">{{ c.icon }}</span><div><p class="font-medium">{{ c.icon }} {{ c.name }}</p><p class="text-xs text-stone-400">{{ c.description }}</p></div></div></td>
                        <td class="px-4 py-3 text-stone-500">{{ c.slug }}</td>
                        <td class="px-4 py-3">{{ c.products_count }}</td>
                        <td class="px-4 py-3"><span class="badge" :class="c.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'">{{ c.is_active ? 'Идэвхтэй' : 'Идэвхгүй' }}</span></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap"><button @click="edit(c)" class="btn-ghost btn-sm"><PencilSquareIcon class="h-4 w-4" /></button><button @click="remove(c)" class="btn-ghost btn-sm text-red-600"><TrashIcon class="h-4 w-4" /></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Modal :open="open" :title="editing ? 'Ангилал засах' : 'Шинэ ангилал'" @close="open = false">
            <form @submit.prevent="save" class="space-y-4">
                <div><label class="label">Нэр *</label><input v-model="form.name" class="input" required /></div>
                <div class="grid grid-cols-2 gap-3"><div><label class="label">Icon (emoji)</label><input v-model="form.icon" class="input" /></div><div><label class="label">Эрэмбэ</label><input v-model.number="form.sort_order" type="number" class="input" /></div></div>
                <div><label class="label">Зургийн URL</label><input v-model="form.image" class="input" /></div>
                <div><label class="label">Тайлбар</label><textarea v-model="form.description" rows="2" class="input"></textarea></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded border-stone-300 text-brand-600" /> Идэвхтэй</label>
                <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Болих</button><button :disabled="saving" class="btn-brand">Хадгалах</button></div>
            </form>
        </Modal>
    </div>
</template>
