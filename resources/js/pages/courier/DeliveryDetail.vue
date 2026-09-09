<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { money, dateTime, PAYMENT_METHOD } from '../../lib/format';
import { useUiStore } from '../../stores/ui';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import OrderTimeline from '../../components/OrderTimeline.vue';
import Spinner from '../../components/ui/Spinner.vue';

const route = useRoute(); const ui = useUiStore();
const order = ref(null); const busy = ref(false);
const form = reactive({ courier_note: '', cash_collected: true });

const next = computed(() => ({
    assigned: [{ status: 'picked_up', label: 'Барааг хүлээн авлаа', cls: 'btn-primary' }],
    picked_up: [{ status: 'in_transit', label: 'Хүргэлтэнд гарлаа', cls: 'btn-primary' }, { status: 'delivered', label: 'Хүргэж өглөө ✓', cls: 'btn bg-emerald-600 text-white hover:bg-emerald-700' }],
    in_transit: [{ status: 'delivered', label: 'Хүргэж өглөө ✓', cls: 'btn bg-emerald-600 text-white hover:bg-emerald-700' }, { status: 'failed', label: 'Амжилтгүй', cls: 'btn-danger' }],
    failed: [{ status: 'in_transit', label: 'Дахин хүргэлтэнд', cls: 'btn-primary' }],
}[order.value?.delivery_status] || []));

async function load() { order.value = (await api.get(`/courier/deliveries/${route.params.id}`)).data; }
async function set(status) {
    if (status === 'failed' && !form.courier_note) return ui.toast('Амжилтгүй болсон шалтгаанаа бичнэ үү', 'error');
    busy.value = true;
    try { order.value = (await api.patch(`/courier/deliveries/${order.value.id}/status`, { delivery_status: status, ...form })).data; ui.toast('Төлөв шинэчлэгдлээ'); form.courier_note = ''; }
    catch (e) { ui.toast(errorMessage(e), 'error'); } finally { busy.value = false; }
}
onMounted(load);
</script>
<template>
    <div>
        <Spinner v-if="!order" />
        <template v-else>
            <p class="text-sm text-stone-500"><router-link :to="{ name: 'courier.deliveries' }" class="hover:text-sky-700">Хүргэлтүүд</router-link> / {{ order.order_number }}</p>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold">{{ order.order_number }}</h1><StatusBadge :value="order.delivery_status" type="delivery" /></div>

            <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_360px]">
                <div class="space-y-5">
                    <div class="card p-5">
                        <h3 class="font-semibold">Хүлээн авагч</h3>
                        <p class="mt-2 text-lg font-bold">{{ order.shipping_name }}</p>
                        <a :href="`tel:${order.shipping_phone}`" class="btn-secondary btn-sm mt-1">📞 {{ order.shipping_phone }}</a>
                        <p class="mt-3 text-stone-700">📍 {{ order.shipping_city }}<span v-if="order.shipping_district">, {{ order.shipping_district }}</span></p>
                        <p class="text-stone-700">{{ order.shipping_address }}</p>
                        <a :href="`https://maps.google.com/?q=${encodeURIComponent(order.shipping_city + ' ' + (order.shipping_district || '') + ' ' + order.shipping_address)}`" target="_blank" class="mt-2 inline-block text-sm text-sky-700 hover:underline">Газрын зураг дээр харах →</a>
                        <p v-if="order.note" class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">📝 Хэрэглэгчийн тэмдэглэл: {{ order.note }}</p>
                    </div>
                    <div class="card">
                        <div class="border-b border-stone-100 px-5 py-3 font-semibold">Бараанууд ({{ order.items.length }})</div>
                        <ul class="divide-y divide-stone-100">
                            <li v-for="i in order.items" :key="i.id" class="flex items-center gap-3 px-5 py-3 text-sm"><img :src="i.image" class="h-12 w-12 rounded-lg bg-stone-100 object-cover" alt="" /><div class="flex-1"><p class="font-medium">{{ i.product_name }}</p><p class="text-xs text-stone-500">{{ i.variant_label }}</p></div><span class="font-semibold">× {{ i.quantity }}</span></li>
                        </ul>
                    </div>
                    <div class="card p-5"><h3 class="mb-3 font-semibold">Түүх</h3><OrderTimeline :histories="order.histories" /></div>
                </div>
                <div class="space-y-5">
                    <div class="card p-5" :class="order.payment_status !== 'paid' && 'ring-2 ring-amber-400'">
                        <h3 class="font-semibold">Төлбөр</h3>
                        <p class="mt-2 text-sm text-stone-600">{{ PAYMENT_METHOD[order.payment_method] }}</p>
                        <p v-if="order.payment_status === 'paid'" class="mt-1 text-emerald-700 font-semibold">✓ Төлөгдсөн</p>
                        <div v-else class="mt-1"><p class="text-2xl font-bold text-amber-700">{{ money(order.total) }}</p><p class="text-xs text-amber-700">Хүргэлт дээр бэлнээр авна</p></div>
                    </div>
                    <div v-if="next.length" class="card p-5">
                        <h3 class="font-semibold">Төлөв шинэчлэх</h3>
                        <textarea v-model="form.courier_note" rows="2" class="input mt-3" placeholder="Тэмдэглэл (амжилтгүй бол шалтгаан)"></textarea>
                        <label v-if="order.payment_method === 'cash' && order.payment_status !== 'paid'" class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.cash_collected" class="rounded border-stone-300 text-emerald-600" /> Бэлэн мөнгө хүлээн авсан</label>
                        <div class="mt-3 flex flex-col gap-2"><button v-for="n in next" :key="n.status" @click="set(n.status)" :disabled="busy" :class="n.cls" class="w-full py-3">{{ n.label }}</button></div>
                    </div>
                    <div v-else class="card p-5 text-center text-sm text-stone-500">Хүргэлт {{ dateTime(order.delivered_at) }}-д дууссан ✓</div>
                </div>
            </div>
        </template>
    </div>
</template>
