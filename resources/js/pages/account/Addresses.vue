<script setup>
import { ref, reactive, onMounted } from 'vue';
import api, { errorMessage } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';
import AccountShell from '../../components/account/AccountShell.vue';
import AddressForm from '../../components/address/AddressForm.vue';
import Modal from '../../components/ui/Modal.vue';
import Spinner from '../../components/ui/Spinner.vue';
import { PlusIcon, PencilSquareIcon, TrashIcon, MapPinIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';

const auth = useAuthStore(); const ui = useUiStore();
const items = ref(null); const open = ref(false); const editing = ref(null); const saving = ref(false); const errors = ref({});
const blank = () => ({ label: 'Салон', recipient_name: auth.user.name, phone: auth.user.phone, province: 'Улаанбаатар', district: '', khoroo: '', address: '', is_default: false });
const form = ref(blank());

async function load() { items.value = (await api.get('/addresses')).data; }
function create() { editing.value = null; form.value = blank(); errors.value = {}; open.value = true; }
function edit(a) { editing.value = a; form.value = { ...a }; errors.value = {}; open.value = true; }
async function save() {
    saving.value = true; errors.value = {};
    try { editing.value ? await api.put(`/addresses/${editing.value.id}`, form.value) : await api.post('/addresses', form.value); ui.toast('Хаяг хадгалагдлаа'); open.value = false; load(); }
    catch (e) { errors.value = e.response?.data?.errors || {}; ui.toast(errorMessage(e), 'error'); }
    finally { saving.value = false; }
}
async function remove(a) { if (!confirm('Энэ хаягийг устгах уу?')) return; await api.delete(`/addresses/${a.id}`); ui.toast('Хаяг устгагдлаа', 'info'); load(); }
async function makeDefault(a) { await api.patch(`/addresses/${a.id}/default`); load(); }
onMounted(load);
</script>
<template>
    <AccountShell title="Хүргэлтийн хаягууд">
        <div class="mb-4 flex items-center justify-between"><p class="text-sm text-stone-500">Захиалга хийхдээ хадгалсан хаягаасаа сонгоно. Үндсэн хаяг автоматаар сонгогдоно.</p><button @click="create" class="btn-brand"><PlusIcon class="h-5 w-5" /> Шинэ хаяг</button></div>
        <Spinner v-if="!items" />
        <div v-else-if="!items.length" class="card p-10 text-center"><MapPinIcon class="mx-auto h-10 w-10 text-stone-300" /><p class="mt-3 font-medium">Хадгалсан хаяг байхгүй</p><p class="mt-1 text-sm text-stone-500">Хүргэлтийн хаягаа нэмээд захиалга хийхэд дахин бичих шаардлагагүй.</p><button @click="create" class="btn-brand mt-4">Хаяг нэмэх</button></div>
        <div v-else class="grid gap-4 md:grid-cols-2">
            <div v-for="a in items" :key="a.id" class="card relative p-5" :class="a.is_default && 'ring-2 ring-brand-600'">
                <div class="flex items-start justify-between gap-2">
                    <div><span class="badge bg-cream-100 text-stone-700">{{ a.label || 'Хаяг' }}</span><span v-if="a.is_default" class="badge ml-1 bg-brand-700 text-white"><CheckBadgeIcon class="h-3.5 w-3.5" /> Үндсэн</span></div>
                    <div class="flex gap-1"><button @click="edit(a)" class="btn-ghost btn-sm"><PencilSquareIcon class="h-4 w-4" /></button><button @click="remove(a)" class="btn-ghost btn-sm text-red-600"><TrashIcon class="h-4 w-4" /></button></div>
                </div>
                <p class="mt-3 font-semibold">{{ a.recipient_name }} · <span class="font-mono font-normal">{{ a.phone }}</span></p>
                <p class="mt-1 text-sm text-stone-600">{{ a.full }}</p>
                <button v-if="!a.is_default" @click="makeDefault(a)" class="mt-3 text-xs font-medium text-brand-700 hover:underline">Үндсэн болгох</button>
            </div>
        </div>
        <Modal :open="open" :title="editing ? 'Хаяг засах' : 'Шинэ хаяг'" size="max-w-2xl" @close="open = false">
            <form @submit.prevent="save" class="space-y-4">
                <AddressForm v-model="form" :errors="errors" />
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_default" class="rounded border-stone-300 text-brand-600" /> Үндсэн хаяг болгох</label>
                <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Болих</button><button :disabled="saving" class="btn-brand">Хадгалах</button></div>
            </form>
        </Modal>
    </AccountShell>
</template>
