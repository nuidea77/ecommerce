<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { money, dateTime, ORDER_STATUS, PAYMENT_STATUS, PAYMENT_METHOD } from '../../lib/format';
import { useUiStore } from '../../stores/ui';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import OrderTimeline from '../../components/OrderTimeline.vue';
import Spinner from '../../components/ui/Spinner.vue';

const route = useRoute(); const ui = useUiStore();
const order = ref(null); const couriers = ref([]);
const status = reactive({ status: '', comment: '' });
const courierId = ref('');
const busy = ref(false);

function apply(data) { order.value = data.order; couriers.value = data.couriers; status.status = data.order.status; courierId.value = data.order.courier_id || ''; }
async function load() { apply((await api.get(`/admin/orders/${route.params.id}`)).data); }
async function run(fn, msg) { busy.value = true; try { apply((await fn()).data); ui.toast(msg); status.comment = ''; } catch (e) { ui.toast(errorMessage(e), 'error'); } finally { busy.value = false; } }
const setStatus = () => run(() => api.patch(`/admin/orders/${order.value.id}/status`, status), 'Төлөв шинэчлэгдлээ');
const setPayment = (p) => run(() => api.patch(`/admin/orders/${order.value.id}/payment`, { payment_status: p }), 'Төлбөрийн төлөв шинэчлэгдлээ');
const assign = () => run(() => api.patch(`/admin/orders/${order.value.id}/courier`, { courier_id: courierId.value || null }), 'Хүргэлтийн ажилтан шинэчлэгдлээ');
onMounted(load);
</script>
<template>
    <div>
        <Spinner v-if="!order" />
        <template v-else>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-sm text-stone-500"><router-link :to="{ name: 'admin.orders' }" class="hover:text-brand-700">Захиалгууд</router-link> / {{ order.order_number }}</p><h1 class="text-2xl font-bold">{{ order.order_number }}</h1><p class="text-sm text-stone-500">{{ dateTime(order.created_at) }}</p></div>
                <div class="flex flex-wrap gap-2"><StatusBadge :value="order.status" /><StatusBadge :value="order.payment_status" type="payment" /><StatusBadge :value="order.delivery_status" type="delivery" /><span v-if="order.has_backorder" class="badge bg-amber-100 text-amber-800">Урьдчилсан захиалга</span></div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
                <div class="space-y-6">
                    <div class="card">
                        <div class="border-b border-stone-100 px-5 py-4 font-semibold">Бараанууд</div>
                        <ul class="divide-y divide-stone-100">
                            <li v-for="i in order.items" :key="i.id" class="flex gap-4 px-5 py-4 text-sm">
                                <img :src="i.image" class="h-14 w-14 rounded-lg bg-cream-200/60 object-cover" alt="" />
                                <div class="flex-1"><p class="font-medium">{{ i.product_name }}</p><p class="text-stone-500">{{ i.variant_label }} · {{ i.sku }}</p><span v-if="i.is_backorder" class="badge mt-1 bg-amber-100 text-amber-800">Урьдчилсан — бараа ирэхийг хүлээж буй</span></div>
                                <div class="text-right"><p class="font-semibold">{{ money(i.line_total) }}</p><p class="text-xs text-stone-400">{{ money(i.price) }} × {{ i.quantity }}</p></div>
                            </li>
                        </ul>
                        <dl class="space-y-1 border-t border-stone-100 px-5 py-4 text-sm">
                            <div class="flex justify-between"><dt class="text-stone-500">Барааны дүн</dt><dd>{{ money(order.subtotal) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-stone-500">Хүргэлт</dt><dd>{{ money(order.shipping_fee) }}</dd></div>
                            <div class="flex justify-between text-base font-bold"><dt>Нийт</dt><dd>{{ money(order.total) }}</dd></div>
                        </dl>
                    </div>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="card p-5 text-sm"><h3 class="mb-3 font-semibold">Хэрэглэгч</h3><p class="font-medium">{{ order.user?.name }}</p><p class="text-stone-500">{{ order.user?.email }}</p><p class="text-stone-500">{{ order.user?.phone }}</p></div>
                        <div class="card p-5 text-sm"><h3 class="mb-3 font-semibold">Хүргэлтийн хаяг</h3><p class="font-medium">{{ order.shipping_name }} · {{ order.shipping_phone }}</p><p class="text-stone-600">{{ order.shipping_city }}<span v-if="order.shipping_district">, {{ order.shipping_district }}</span></p><p class="text-stone-600">{{ order.shipping_address }}</p><p v-if="order.note" class="mt-2 rounded-lg bg-amber-50 p-2 text-xs text-amber-800">📝 {{ order.note }}</p><p v-if="order.courier_note" class="mt-2 rounded-lg bg-sky-50 p-2 text-xs text-sky-800">🛵 {{ order.courier_note }}</p></div>
                    </div>
                    <div class="card p-5"><h3 class="mb-4 font-semibold">Түүх</h3><OrderTimeline :histories="order.histories" /></div>
                </div>

                <div class="space-y-6">
                    <div class="card p-5">
                        <h3 class="font-semibold">Захиалгын төлөв</h3>
                        <select v-model="status.status" class="input mt-3"><option v-for="(s, k) in ORDER_STATUS" :key="k" :value="k">{{ s.label }}</option></select>
                        <input v-model="status.comment" class="input mt-2" placeholder="Тайлбар (заавал биш)" />
                        <button @click="setStatus" :disabled="busy" class="btn-primary mt-3 w-full">Төлөв хадгалах</button>
                    </div>
                    <div class="card p-5">
                        <h3 class="font-semibold">Хүргэлтийн ажилтан</h3>
                        <select v-model="courierId" class="input mt-3"><option value="">— Томилоогүй —</option><option v-for="c in couriers" :key="c.id" :value="c.id">{{ c.name }} ({{ c.active_deliveries_count }} идэвхтэй)</option></select>
                        <button @click="assign" :disabled="busy" class="btn-primary mt-3 w-full">Томилох</button>
                        <p class="mt-2 text-xs text-stone-500">Томилсноор захиалга "Бэлтгэж буй" төлөвт шилжинэ.</p>
                    </div>
                    <div class="card p-5">
                        <h3 class="font-semibold">Төлбөр</h3>
                        <p class="mt-2 text-sm text-stone-600">{{ PAYMENT_METHOD[order.payment_method] }} · <StatusBadge :value="order.payment_status" type="payment" /></p>
                        <p v-if="order.paid_at" class="text-xs text-stone-400">Төлсөн: {{ dateTime(order.paid_at) }}</p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button v-for="(s, k) in PAYMENT_STATUS" :key="k" @click="setPayment(k)" :disabled="busy || order.payment_status === k" class="btn-secondary btn-sm">{{ s.label }}</button>
                        </div>
                        <ul v-if="order.payments?.length" class="mt-4 divide-y divide-stone-100 text-xs">
                            <li v-for="p in order.payments" :key="p.id" class="flex justify-between py-2"><span>{{ p.provider }} · {{ p.status }}</span><span class="text-stone-500">{{ money(p.amount) }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
