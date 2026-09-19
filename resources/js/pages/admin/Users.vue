<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { dateOnly, ROLE } from '../../lib/format';
import { useUiStore } from '../../stores/ui';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import Pagination from '../../components/ui/Pagination.vue';
import Modal from '../../components/ui/Modal.vue';
import Spinner from '../../components/ui/Spinner.vue';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const ui = useUiStore();
const result = ref(null);
const route = useRoute();
const f = reactive({ q: '', role: route.query.role || '', verified: '', page: 1 });
const open = ref(false); const editing = ref(null); const saving = ref(false);
const form = reactive({ name: '', email: '', phone: '', role: 'customer', password: '', is_active: true });

async function load() { result.value = (await api.get('/admin/users', { params: f })).data; }
function create(role = 'customer') { editing.value = null; Object.assign(form, { name: '', email: '', phone: '', role, password: '', is_active: true }); open.value = true; }
function edit(u) { editing.value = u; Object.assign(form, { name: u.name, email: u.email, phone: u.phone || '', role: u.role, password: '', is_active: u.is_active }); open.value = true; }
async function save() {
    saving.value = true;
    try { editing.value ? await api.put(`/admin/users/${editing.value.id}`, form) : await api.post('/admin/users', form); ui.toast('Хадгалагдлаа'); open.value = false; load(); }
    catch (e) { ui.toast(errorMessage(e), 'error'); } finally { saving.value = false; }
}
async function remove(u) {
    if (!confirm(`${u.name} хэрэглэгчийг устгах уу?`)) return;
    try { await api.delete(`/admin/users/${u.id}`); ui.toast('Устгагдлаа'); load(); } catch (e) { ui.toast(errorMessage(e), 'error'); }
}
let t;
watch(() => [f.q, f.role, f.verified], () => { f.page = 1; clearTimeout(t); t = setTimeout(load, 250); });
onMounted(load);
</script>
<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Хэрэглэгчид</h1>
            <div class="flex gap-2"><button @click="create('courier')" class="btn-secondary"><PlusIcon class="h-5 w-5" /> Хүргэлтийн ажилтан</button><button @click="create()" class="btn-brand"><PlusIcon class="h-5 w-5" /> Хэрэглэгч</button></div>
        </div>
        <div class="card mt-5 flex flex-wrap items-center gap-3 p-4">
            <input v-model="f.q" placeholder="Нэр, имэйл, утас..." class="input max-w-xs py-2" />
            <div class="flex gap-1 rounded-xl bg-stone-100 p-1 text-sm">
                <button v-for="(r, k) in { '': 'Бүгд', ...Object.fromEntries(Object.entries(ROLE).map(([k, v]) => [k, v.label])) }" :key="k" @click="f.role = k" class="rounded-lg px-3 py-1.5 font-medium" :class="f.role === k ? 'bg-white shadow-sm' : 'text-stone-500'">{{ r }}</button>
            </div>
            <select v-model="f.verified" class="input w-auto py-2"><option value="">Баталгаажуулалт: бүгд</option><option value="1">Баталгаажсан</option><option value="0">Баталгаажаагүй</option></select>
        </div>
        <Spinner v-if="!result" />
        <div v-else class="card mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500"><tr><th class="px-4 py-3">Хэрэглэгч</th><th class="px-4 py-3">Эрх</th><th class="px-4 py-3">Баталгаажуулалт</th><th class="px-4 py-3">Утас</th><th class="px-4 py-3">Захиалга / Хүргэлт</th><th class="px-4 py-3">Төлөв</th><th class="px-4 py-3">Бүртгүүлсэн</th><th></th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    <tr v-for="u in result.data" :key="u.id" class="hover:bg-stone-50">
                        <td class="px-4 py-3"><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-700">{{ u.name.slice(0, 1) }}</span><div><p class="font-medium">{{ u.name }}</p><p class="text-xs text-stone-400">{{ u.email }}</p></div></div></td>
                        <td class="px-4 py-3"><StatusBadge :value="u.role" type="role" /></td>
                        <td class="px-4 py-3"><template v-if="u.is_verified"><span class="badge bg-emerald-100 text-emerald-800">✓ verify.mn</span><p class="mt-0.5 font-mono text-xs text-stone-400">{{ u.verified_phone }}</p></template><span v-else-if="u.role === 'customer'" class="badge bg-amber-100 text-amber-800">Баталгаажаагүй</span><span v-else class="text-xs text-stone-400">—</span></td>
                        <td class="px-4 py-3 text-stone-600">{{ u.phone || '—' }}</td>
                        <td class="px-4 py-3 text-stone-600"><span v-if="u.role === 'courier'">{{ u.active_deliveries_count }} идэвхтэй · {{ u.delivered_count }} хүргэсэн</span><span v-else>{{ u.orders_count }} захиалга</span></td>
                        <td class="px-4 py-3"><span class="badge" :class="u.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'">{{ u.is_active ? 'Идэвхтэй' : 'Идэвхгүй' }}</span></td>
                        <td class="px-4 py-3 text-stone-500">{{ dateOnly(u.created_at) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap"><button @click="edit(u)" class="btn-ghost btn-sm"><PencilSquareIcon class="h-4 w-4" /></button><button @click="remove(u)" class="btn-ghost btn-sm text-red-600"><TrashIcon class="h-4 w-4" /></button></td>
                    </tr>
                </tbody>
            </table>
            <div class="p-4"><Pagination :meta="result" @change="(p) => { f.page = p; load(); }" /></div>
        </div>
        <Modal :open="open" :title="editing ? 'Хэрэглэгч засах' : 'Шинэ хэрэглэгч'" @close="open = false">
            <form @submit.prevent="save" class="space-y-4">
                <div><label class="label">Нэр *</label><input v-model="form.name" class="input" required /></div>
                <div><label class="label">И-мэйл *</label><input v-model="form.email" type="email" class="input" required /></div>
                <div class="grid grid-cols-2 gap-3"><div><label class="label">Утас</label><input v-model="form.phone" class="input" /></div><div><label class="label">Эрх *</label><select v-model="form.role" class="input"><option v-for="(r, k) in ROLE" :key="k" :value="k">{{ r.label }}</option></select></div></div>
                <div><label class="label">Нууц үг {{ editing ? '(өөрчлөхгүй бол хоосон)' : '*' }}</label><input v-model="form.password" type="password" class="input" :required="!editing" /></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded border-stone-300 text-brand-600" /> Идэвхтэй</label>
                <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Болих</button><button :disabled="saving" class="btn-brand">Хадгалах</button></div>
            </form>
        </Modal>
    </div>
</template>
