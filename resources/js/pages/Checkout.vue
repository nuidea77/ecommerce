<script setup>
import { reactive, ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api, { errorMessage } from '../lib/api';
import { money } from '../lib/format';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import AddressForm from '../components/address/AddressForm.vue';
import { CheckCircleIcon, PlusIcon, MapPinIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const cart = useCartStore();
const auth = useAuthStore();
const ui = useUiStore();

const addresses = ref([]);
const selectedId = ref(null);
const addingNew = ref(false);
const newAddress = ref({ label: 'Салон', recipient_name: auth.user?.name || '', phone: auth.user?.phone || '', province: 'Улаанбаатар', district: '', khoroo: '', address: '' });
const saveAddress = ref(true);
const note = ref('');
const errors = ref({});
const submitting = ref(false);

const selected = computed(() => addresses.value.find((a) => a.id === selectedId.value));

onMounted(async () => {
    cart.fetch();
    addresses.value = (await api.get('/addresses')).data;
    const def = addresses.value.find((a) => a.is_default) || addresses.value[0];
    if (def) selectedId.value = def.id; else addingNew.value = true;
});

async function submit() {
    errors.value = {};
    if (!addingNew.value && !selectedId.value) return ui.toast('Хүргэлтийн хаягаа сонгоно уу', 'error');
    submitting.value = true;
    try {
        const payload = addingNew.value ? { ...newAddress.value, save_address: saveAddress.value, note: note.value } : { address_id: selectedId.value, note: note.value };
        const { data } = await api.post('/orders', payload);
        await cart.fetch();
        ui.toast(`Захиалга ${data.order.order_number} үүслээ`);
        router.push({ name: 'pay', params: { number: data.order.order_number } });
    } catch (e) {
        if (e.response?.data?.verification_required) return router.push({ name: 'verify', query: { redirect: '/checkout' } });
        errors.value = e.response?.data?.errors || {};
        ui.toast(errorMessage(e), 'error');
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="container-x py-8">
        <h1 class="font-display text-3xl font-bold">Захиалга хийх</h1>
        <form @submit.prevent="submit" novalidate class="mt-6 grid gap-8 lg:grid-cols-[1fr_380px]">
            <div class="space-y-6">
                <section class="card p-6">
                    <div class="flex items-center justify-between"><h2 class="text-lg font-semibold">1. Хүргэлтийн хаяг</h2><router-link :to="{ name: 'addresses' }" class="text-xs text-brand-700 hover:underline">Хаягуудаа удирдах</router-link></div>

                    <div v-if="addresses.length" class="mt-4 grid gap-3 sm:grid-cols-2">
                        <button v-for="a in addresses" :key="a.id" type="button" @click="selectedId = a.id; addingNew = false" class="relative rounded-2xl p-4 text-left ring-1 transition" :class="!addingNew && selectedId === a.id ? 'bg-brand-50 ring-2 ring-brand-600' : 'bg-white ring-stone-200 hover:ring-stone-400'">
                            <div class="flex items-center gap-2"><MapPinIcon class="h-4 w-4 text-brand-700" /><span class="text-sm font-semibold">{{ a.label || 'Хаяг' }}</span><span v-if="a.is_default" class="badge bg-brand-700 text-white">Үндсэн</span></div>
                            <p class="mt-1.5 text-sm">{{ a.recipient_name }} · <span class="font-mono">{{ a.phone }}</span></p>
                            <p class="mt-0.5 text-xs text-stone-500">{{ a.full }}</p>
                            <CheckCircleIcon v-if="!addingNew && selectedId === a.id" class="absolute right-3 top-3 h-5 w-5 text-brand-600" />
                        </button>
                        <button type="button" @click="addingNew = true" class="flex min-h-[110px] flex-col items-center justify-center rounded-2xl border-2 border-dashed p-4 text-sm font-medium transition" :class="addingNew ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-stone-300 text-stone-500 hover:border-brand-400 hover:text-brand-700'"><PlusIcon class="h-6 w-6" /> Өөр газар хүргүүлэх</button>
                    </div>

                    <div v-if="addingNew" class="mt-5 rounded-2xl bg-cream-100/60 p-5 ring-1 ring-cream-300/70">
                        <p class="mb-4 text-sm font-semibold">{{ addresses.length ? 'Шинэ хаяг' : 'Хүргэлтийн хаягаа оруулна уу' }}</p>
                        <AddressForm v-model="newAddress" :errors="errors" />
                        <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" v-model="saveAddress" class="rounded border-stone-300 text-brand-600" /> Энэ хаягийг дараагийн захиалгад хадгалах</label>
                    </div>

                    <div class="mt-5"><label class="label">Нэмэлт тэмдэглэл</label><textarea v-model="note" rows="2" class="input" placeholder="Хүргэлтийн цаг, тусгай заавар..."></textarea></div>
                </section>

                <section class="card p-6">
                    <h2 class="text-lg font-semibold">2. Төлбөр</h2>
                    <div class="mt-4 flex items-start gap-4 rounded-2xl bg-brand-50 p-4 ring-2 ring-brand-600">
                        <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 40'><rect width='120' height='40' rx='8' fill='%230b3d91'/><text x='60' y='27' text-anchor='middle' font-family='Arial' font-weight='bold' font-size='20' fill='white'>qPay</text></svg>" class="h-10" alt="qPay" />
                        <div class="flex-1"><p class="font-semibold">QPay-ээр төлөх</p><p class="text-sm text-stone-600">Захиалга үүсмэгц QR код гарна. Хаан, Голомт, ХХБ, Хас, Төрийн банк болон бусад банкны аппаар уншуулж шууд төлнө. Төлбөр баталгаажсаны дараа захиалга бэлтгэгдэнэ.</p></div>
                        <CheckCircleIcon class="h-5 w-5 shrink-0 text-brand-600" />
                    </div>
                </section>
            </div>

            <aside class="card h-fit p-6">
                <h2 class="text-lg font-semibold">Таны захиалга</h2>
                <ul class="mt-4 divide-y divide-stone-100">
                    <li v-for="item in cart.items" :key="item.id" class="flex gap-3 py-3">
                        <img :src="item.image" class="h-14 w-14 rounded-lg bg-white object-contain ring-1 ring-stone-200/70" alt="" />
                        <div class="flex-1 text-sm"><p class="font-medium leading-tight">{{ item.name }}</p><p class="text-xs text-stone-500">{{ item.variant_label }} × {{ item.quantity }}</p><span v-if="item.is_backorder" class="text-xs font-medium text-amber-600">Урьдчилсан захиалга</span></div>
                        <p class="text-sm font-semibold">{{ money(item.line_total) }}</p>
                    </li>
                </ul>
                <div v-if="selected && !addingNew" class="mt-3 rounded-xl bg-cream-100 p-3 text-xs text-stone-600"><p class="font-semibold text-stone-800">Хүргэх хаяг</p><p>{{ selected.recipient_name }} · {{ selected.phone }}</p><p>{{ selected.full }}</p></div>
                <dl class="mt-3 space-y-2 border-t border-stone-200 pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-500">Барааны дүн</dt><dd>{{ money(cart.subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-stone-500">Хүргэлт</dt><dd>{{ cart.shipping_fee ? money(cart.shipping_fee) : 'Үнэгүй' }}</dd></div>
                    <div class="flex justify-between border-t border-stone-200 pt-3 text-base font-bold"><dt>Нийт төлөх</dt><dd>{{ money(cart.total) }}</dd></div>
                </dl>
                <p v-if="errors.cart" class="mt-3 text-sm text-red-600">{{ errors.cart[0] }}</p>
                <button type="submit" :disabled="submitting || !cart.items.length" class="btn-brand mt-5 w-full py-3 text-base">{{ submitting ? 'Үүсгэж байна...' : 'QPay-ээр төлөх' }}</button>
                <p class="mt-3 text-center text-xs text-stone-400">Захиалга өгснөөр үйлчилгээний нөхцөлийг зөвшөөрч байна.</p>
            </aside>
        </form>
    </div>
</template>
