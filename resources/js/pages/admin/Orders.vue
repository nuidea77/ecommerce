<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import api from '../../lib/api';
import { money, dateTime, ORDER_STATUS, PAYMENT_STATUS, DELIVERY_STATUS, PAYMENT_METHOD } from '../../lib/format';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import Pagination from '../../components/ui/Pagination.vue';
import Spinner from '../../components/ui/Spinner.vue';

const result = ref(null);
const f = reactive({ q: '', status: '', payment_status: '', delivery_status: '', payment_method: '', backorder: false, page: 1 });
async function load() { result.value = (await api.get('/admin/orders', { params: { ...f, backorder: f.backorder ? 1 : undefined } })).data; }
let t;
watch(() => [f.q, f.status, f.payment_status, f.delivery_status, f.payment_method, f.backorder], () => { f.page = 1; clearTimeout(t); t = setTimeout(load, 250); });
onMounted(load);
</script>
<template>
    <div>
        <h1 class="text-2xl font-bold">Захиалгууд</h1>
        <div class="card mt-5 flex flex-wrap items-center gap-3 p-4">
            <input v-model="f.q" placeholder="Дугаар, нэр, утас..." class="input max-w-xs py-2" />
            <select v-model="f.status" class="input w-auto py-2"><option value="">Бүх төлөв</option><option v-for="(s, k) in ORDER_STATUS" :key="k" :value="k">{{ s.label }}</option></select>
            <select v-model="f.payment_status" class="input w-auto py-2"><option value="">Төлбөр: бүгд</option><option v-for="(s, k) in PAYMENT_STATUS" :key="k" :value="k">{{ s.label }}</option></select>
            <select v-model="f.delivery_status" class="input w-auto py-2"><option value="">Хүргэлт: бүгд</option><option v-for="(s, k) in DELIVERY_STATUS" :key="k" :value="k">{{ s.label }}</option></select>
            <select v-model="f.payment_method" class="input w-auto py-2"><option value="">Хэлбэр: бүгд</option><option v-for="(s, k) in PAYMENT_METHOD" :key="k" :value="k">{{ s }}</option></select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="f.backorder" class="rounded border-stone-300 text-brand-600" /> Урьдчилсан захиалга</label>
        </div>
        <Spinner v-if="!result" />
        <div v-else class="card mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500"><tr><th class="px-4 py-3">Захиалга</th><th class="px-4 py-3">Хэрэглэгч</th><th class="px-4 py-3">Төлөв</th><th class="px-4 py-3">Төлбөр</th><th class="px-4 py-3">Хүргэлт</th><th class="px-4 py-3 text-right">Дүн</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    <tr v-for="o in result.data" :key="o.id" class="hover:bg-stone-50">
                        <td class="px-4 py-3"><router-link :to="{ name: 'admin.order', params: { id: o.id } }" class="font-semibold text-brand-700 hover:underline">{{ o.order_number }}</router-link><p class="text-xs text-stone-400">{{ dateTime(o.created_at) }} · {{ o.items_count }} бараа</p><span v-if="o.has_backorder" class="badge mt-1 bg-amber-100 text-amber-800">Урьдчилсан</span></td>
                        <td class="px-4 py-3"><p class="font-medium">{{ o.shipping_name }}</p><p class="text-xs text-stone-400">{{ o.shipping_phone }} · {{ o.shipping_city }}</p></td>
                        <td class="px-4 py-3"><StatusBadge :value="o.status" /></td>
                        <td class="px-4 py-3"><StatusBadge :value="o.payment_status" type="payment" /><p class="mt-1 text-xs text-stone-400">{{ PAYMENT_METHOD[o.payment_method] }}</p></td>
                        <td class="px-4 py-3"><StatusBadge :value="o.delivery_status" type="delivery" /><p v-if="o.courier" class="mt-1 text-xs text-stone-400">{{ o.courier.name }}</p></td>
                        <td class="px-4 py-3 text-right font-semibold">{{ money(o.total) }}</td>
                    </tr>
                    <tr v-if="!result.data.length"><td colspan="6" class="px-4 py-10 text-center text-stone-500">Захиалга олдсонгүй</td></tr>
                </tbody>
            </table>
            <div class="p-4"><Pagination :meta="result" @change="(p) => { f.page = p; load(); }" /></div>
        </div>
    </div>
</template>
