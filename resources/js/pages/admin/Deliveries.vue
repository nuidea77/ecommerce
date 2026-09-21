<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { money, dateTime, DELIVERY_STATUS } from '../../lib/format';
import { useUiStore } from '../../stores/ui';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import Pagination from '../../components/ui/Pagination.vue';
import Spinner from '../../components/ui/Spinner.vue';
import { TruckIcon, MapPinIcon, PhoneIcon } from '@heroicons/vue/24/outline';

const route = useRoute(); const ui = useUiStore();
const data = ref(null); const busy = ref(null);
const f = reactive({ tab: route.query.tab || 'active', courier_id: route.query.courier_id || '', delivery_status: '', q: '', page: 1 });
const tabs = [['unassigned', 'Томилох'], ['active', 'Идэвхтэй'], ['failed', 'Амжилтгүй'], ['done', 'Хүргэгдсэн'], ['all', 'Бүгд']];

async function load() { data.value = (await api.get('/admin/deliveries', { params: f })).data; }
async function assign(order, courierId) {
    busy.value = order.id;
    try { await api.patch(`/admin/orders/${order.id}/courier`, { courier_id: courierId || null }); ui.toast(courierId ? 'Хүргэгч томилогдлоо' : 'Томилолт цуцлагдлаа'); await load(); }
    catch (e) { ui.toast(errorMessage(e), 'error'); } finally { busy.value = null; }
}
const addr = (o) => `${o.shipping_city}, ${o.shipping_district || ''}${o.shipping_khoroo ? (o.shipping_city === 'Улаанбаатар' ? `, ${o.shipping_khoroo}-р хороо` : `, ${o.shipping_khoroo} баг`) : ''} — ${o.shipping_address}`;
let t;
watch(() => [f.tab, f.courier_id, f.delivery_status, f.q], () => { f.page = 1; clearTimeout(t); t = setTimeout(load, 200); });
onMounted(load);
</script>
<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold">Хүргэлтүүд</h1><router-link :to="{ name: 'admin.orders' }" class="text-sm text-brand-700 hover:underline">Бүх захиалга →</router-link></div>
        <Spinner v-if="!data" />
        <template v-else>
            <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <button @click="f.tab = 'unassigned'" class="card p-4 text-left hover:ring-brand-300"><p class="text-xs text-stone-500">Томилох шаардлагатай</p><p class="text-2xl font-bold text-amber-600">{{ data.stats.unassigned }}</p></button>
                <div class="card p-4"><p class="text-xs text-stone-500">Томилогдсон</p><p class="text-2xl font-bold text-sky-700">{{ data.stats.assigned }}</p></div>
                <div class="card p-4"><p class="text-xs text-stone-500">Замд яваа</p><p class="text-2xl font-bold text-violet-700">{{ data.stats.in_progress }}</p></div>
                <button @click="f.tab = 'failed'" class="card p-4 text-left hover:ring-brand-300"><p class="text-xs text-stone-500">Амжилтгүй</p><p class="text-2xl font-bold text-red-600">{{ data.stats.failed }}</p></button>
                <div class="card p-4"><p class="text-xs text-stone-500">Өнөөдөр хүргэсэн</p><p class="text-2xl font-bold text-emerald-700">{{ data.stats.delivered_today }}</p></div>
                <div class="card p-4"><p class="text-xs text-stone-500">Нийт хүргэсэн</p><p class="text-2xl font-bold">{{ data.stats.delivered_total }}</p></div>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_300px]">
                <div>
                    <div class="card flex flex-wrap items-center gap-3 p-3">
                        <div class="flex gap-1 rounded-xl bg-cream-100 p-1 text-sm"><button v-for="[k, l] in tabs" :key="k" @click="f.tab = k" class="rounded-lg px-3 py-1.5 font-medium" :class="f.tab === k ? 'bg-white shadow-sm' : 'text-stone-500'">{{ l }}</button></div>
                        <select v-model="f.courier_id" class="input w-auto py-1.5 text-sm"><option value="">Бүх хүргэгч</option><option v-for="c in data.couriers" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                        <select v-if="f.tab === 'all'" v-model="f.delivery_status" class="input w-auto py-1.5 text-sm"><option value="">Бүх төлөв</option><option v-for="(s, k) in DELIVERY_STATUS" :key="k" :value="k">{{ s.label }}</option></select>
                        <input v-model="f.q" placeholder="Дугаар, нэр, утас, хаяг..." class="input max-w-xs py-1.5 text-sm" />
                    </div>

                    <div class="card mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-cream-100 text-left text-xs uppercase text-stone-500"><tr><th class="px-4 py-3">Захиалга</th><th class="px-4 py-3">Хүргэх хаяг</th><th class="px-4 py-3">Хүргэгч</th><th class="px-4 py-3">Төлөв</th><th class="px-4 py-3 text-right">Дүн</th></tr></thead>
                            <tbody class="divide-y divide-stone-100">
                                <tr v-for="o in data.deliveries.data" :key="o.id" class="align-top hover:bg-cream-100/60">
                                    <td class="px-4 py-3"><router-link :to="{ name: 'admin.order', params: { id: o.id } }" class="font-semibold text-brand-700 hover:underline">{{ o.order_number }}</router-link><p class="text-xs text-stone-400">{{ dateTime(o.created_at) }} · {{ o.items_count }} бараа</p><StatusBadge :value="o.payment_status" type="payment" class="mt-1" /></td>
                                    <td class="min-w-[280px] max-w-sm px-4 py-3"><p class="flex items-center gap-1 whitespace-nowrap font-medium"><PhoneIcon class="h-3.5 w-3.5 text-stone-400" /> {{ o.shipping_name }} · <span class="font-mono">{{ o.shipping_phone }}</span></p><p class="mt-0.5 flex items-start gap-1 text-xs text-stone-500"><MapPinIcon class="mt-0.5 h-3.5 w-3.5 shrink-0 text-stone-400" /> {{ addr(o) }}</p><p v-if="o.note" class="mt-1 text-xs text-amber-700">📝 {{ o.note }}</p><p v-if="o.courier_note" class="mt-1 text-xs text-sky-700">🛵 {{ o.courier_note }}</p></td>
                                    <td class="px-4 py-3">
                                        <select :value="o.courier_id || ''" @change="assign(o, $event.target.value)" :disabled="busy === o.id || o.delivery_status === 'delivered'" class="input w-44 py-1.5 text-xs"><option value="">— Томилоогүй —</option><option v-for="c in data.couriers" :key="c.id" :value="c.id">{{ c.name }} ({{ c.active_count }})</option></select>
                                        <p v-if="o.courier" class="mt-1 text-xs text-stone-400">{{ o.courier.phone }}</p>
                                    </td>
                                    <td class="px-4 py-3"><StatusBadge :value="o.delivery_status" type="delivery" /><p class="mt-1 text-xs text-stone-400"><StatusBadge :value="o.status" /></p><p v-if="o.delivered_at" class="mt-1 text-xs text-emerald-700">{{ dateTime(o.delivered_at) }}</p></td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ money(o.total) }}</td>
                                </tr>
                                <tr v-if="!data.deliveries.data.length"><td colspan="5" class="px-4 py-12 text-center text-stone-500"><TruckIcon class="mx-auto mb-2 h-8 w-8 text-stone-300" />Энэ хэсэгт хүргэлт алга</td></tr>
                            </tbody>
                        </table>
                        <div class="p-4"><Pagination :meta="data.deliveries" @change="(p) => { f.page = p; load(); }" /></div>
                    </div>
                </div>

                <div class="card h-fit p-5">
                    <h2 class="font-semibold">Хүргэлтийн ажилтнууд</h2>
                    <ul class="mt-3 divide-y divide-stone-100 text-sm">
                        <li v-for="c in data.couriers" :key="c.id" class="py-3">
                            <button @click="f.courier_id = String(c.id); f.tab = 'active'" class="flex w-full items-center gap-3 text-left hover:text-brand-700">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-100 font-bold text-sky-700">{{ c.name.slice(0, 1) }}</span>
                                <span class="flex-1"><span class="block font-medium">{{ c.name }} <span v-if="!c.is_active" class="badge bg-stone-200 text-stone-600">идэвхгүй</span></span><span class="block font-mono text-xs text-stone-400">{{ c.phone }}</span></span>
                            </button>
                            <div class="mt-2 grid grid-cols-3 gap-1 text-center text-[11px]"><div class="rounded-md bg-sky-50 py-1"><b class="block text-sky-700">{{ c.active_count }}</b>идэвхтэй</div><div class="rounded-md bg-emerald-50 py-1"><b class="block text-emerald-700">{{ c.delivered_today_count }}</b>өнөөдөр</div><div class="rounded-md bg-cream-100 py-1"><b class="block">{{ c.delivered_count }}</b>нийт</div></div>
                        </li>
                    </ul>
                    <router-link :to="{ name: 'admin.users', query: { role: 'courier' } }" class="mt-3 block text-center text-xs text-brand-700 hover:underline">Хүргэгч удирдах →</router-link>
                </div>
            </div>
        </template>
    </div>
</template>
