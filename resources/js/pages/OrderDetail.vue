<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../lib/api';
import { money, dateTime, PAYMENT_METHOD } from '../lib/format';
import { useUiStore } from '../stores/ui';
import StatusBadge from '../components/ui/StatusBadge.vue';
import OrderTimeline from '../components/OrderTimeline.vue';
import Spinner from '../components/ui/Spinner.vue';

const route = useRoute();
const ui = useUiStore();
const order = ref(null);
const cancelling = ref(false);

const steps = [
    { key: 'pending', label: 'Захиалсан' }, { key: 'confirmed', label: 'Баталгаажсан' },
    { key: 'processing', label: 'Бэлтгэж буй' }, { key: 'shipped', label: 'Хүргэлтэнд' }, { key: 'delivered', label: 'Хүргэгдсэн' },
];
const stepIndex = computed(() => steps.findIndex((s) => s.key === order.value?.status));

async function load() {
    const { data } = await api.get(`/orders/${route.params.number}`);
    order.value = data.order;
}
async function cancel() {
    if (!confirm('Захиалгаа цуцлахдаа итгэлтэй байна уу?')) return;
    cancelling.value = true;
    try { const { data } = await api.post(`/orders/${route.params.number}/cancel`); order.value = data.order; ui.toast('Захиалга цуцлагдлаа', 'info'); }
    catch (e) { ui.toast(errorMessage(e), 'error'); }
    finally { cancelling.value = false; }
}
onMounted(load);
</script>

<template>
    <div class="container-x py-8">
        <Spinner v-if="!order" />
        <template v-else>
            <div v-if="route.query.created" class="mb-6 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">🎉 Захиалга амжилттай үүслээ! Хүргэлтийн ажилтан тантай удахгүй холбогдоно. Төлбөрийг барааг хүлээн авахдаа төлнө үү.</div>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-stone-500"><router-link :to="{ name: 'orders' }" class="hover:text-brand-700">Захиалгууд</router-link> / {{ order.order_number }}</p>
                    <h1 class="mt-1 font-display text-3xl font-bold">Захиалга {{ order.order_number }}</h1>
                    <p class="text-sm text-stone-500">{{ dateTime(order.created_at) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :value="order.status" type="order" /><StatusBadge :value="order.payment_status" type="payment" />
                    <router-link v-if="order.payment_method === 'qpay' && order.payment_status === 'unpaid' && order.status !== 'cancelled'" :to="{ name: 'pay', params: { number: order.order_number } }" class="btn-brand btn-sm">QPay-ээр төлөх</router-link>
                    <button v-if="['pending', 'confirmed'].includes(order.status)" @click="cancel" :disabled="cancelling" class="btn-secondary btn-sm text-red-600">Цуцлах</button>
                </div>
            </div>

            <!-- Progress -->
            <div v-if="order.status !== 'cancelled'" class="card mt-6 p-6">
                <ol class="flex items-center">
                    <li v-for="(s, i) in steps" :key="s.key" class="flex flex-1 items-center">
                        <div class="flex flex-col items-center text-center">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ring-2" :class="i <= stepIndex ? 'bg-brand-600 text-white ring-brand-600' : 'bg-paper text-stone-400 ring-stone-200'">{{ i < stepIndex ? '✓' : i + 1 }}</span>
                            <span class="mt-2 hidden text-xs font-medium sm:block" :class="i <= stepIndex ? 'text-stone-900' : 'text-stone-400'">{{ s.label }}</span>
                        </div>
                        <div v-if="i < steps.length - 1" class="mx-2 h-0.5 flex-1" :class="i < stepIndex ? 'bg-brand-600' : 'bg-stone-200'"></div>
                    </li>
                </ol>
                <p class="mt-4 text-center text-sm text-stone-600 sm:hidden">{{ steps[stepIndex]?.label }}</p>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="space-y-6">
                    <div class="card">
                        <div class="border-b border-stone-100 px-5 py-4 font-semibold">Бараанууд</div>
                        <ul class="divide-y divide-stone-100">
                            <li v-for="i in order.items" :key="i.id" class="flex gap-4 px-5 py-4">
                                <img :src="i.image" class="h-16 w-16 rounded-xl bg-cream-200/60 object-cover" alt="" />
                                <div class="flex-1"><p class="font-medium">{{ i.product_name }}</p><p class="text-sm text-stone-500">{{ i.variant_label }} · SKU {{ i.sku }}</p><span v-if="i.is_backorder" class="badge mt-1 bg-amber-100 text-amber-800">Урьдчилсан захиалга</span></div>
                                <div class="text-right"><p class="font-semibold">{{ money(i.line_total) }}</p><p class="text-xs text-stone-400">{{ money(i.price) }} × {{ i.quantity }}</p></div>
                            </li>
                        </ul>
                        <dl class="space-y-1.5 border-t border-stone-100 px-5 py-4 text-sm">
                            <div class="flex justify-between"><dt class="text-stone-500">Барааны дүн</dt><dd>{{ money(order.subtotal) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-stone-500">Хүргэлт</dt><dd>{{ order.shipping_fee ? money(order.shipping_fee) : 'Үнэгүй' }}</dd></div>
                            <div class="flex justify-between text-base font-bold"><dt>Нийт</dt><dd>{{ money(order.total) }}</dd></div>
                        </dl>
                    </div>
                    <div class="card p-5">
                        <h3 class="mb-4 font-semibold">Захиалгын түүх</h3>
                        <OrderTimeline :histories="order.histories" />
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="card p-5 text-sm">
                        <h3 class="mb-3 font-semibold">Хүргэлтийн хаяг</h3>
                        <p class="font-medium">{{ order.shipping_name }}</p><p>{{ order.shipping_phone }}</p>
                        <p class="mt-1 text-stone-600">{{ order.shipping_city }}<span v-if="order.shipping_district">, {{ order.shipping_district }}</span><br />{{ order.shipping_address }}</p>
                        <p v-if="order.note" class="mt-2 rounded-lg bg-cream-100 p-2 text-xs text-stone-500">Тэмдэглэл: {{ order.note }}</p>
                    </div>
                    <div class="card p-5 text-sm">
                        <h3 class="mb-3 font-semibold">Төлбөр</h3>
                        <p>{{ PAYMENT_METHOD[order.payment_method] }}</p>
                        <p class="mt-1"><StatusBadge :value="order.payment_status" type="payment" /></p>
                        <p v-if="order.paid_at" class="mt-1 text-xs text-stone-500">Төлсөн: {{ dateTime(order.paid_at) }}</p>
                    </div>
                    <div class="card p-5 text-sm">
                        <h3 class="mb-3 font-semibold">Хүргэлт</h3>
                        <StatusBadge :value="order.delivery_status" type="delivery" />
                        <div v-if="order.courier" class="mt-3 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 font-bold text-sky-700">{{ order.courier.name.slice(0, 1) }}</span>
                            <div><p class="font-medium">{{ order.courier.name }}</p><a :href="`tel:${order.courier.phone}`" class="text-xs text-brand-700">{{ order.courier.phone }}</a></div>
                        </div>
                        <p v-else class="mt-2 text-xs text-stone-500">Хүргэлтийн ажилтан хараахан томилогдоогүй.</p>
                        <p v-if="order.courier_note" class="mt-2 rounded-lg bg-cream-100 p-2 text-xs text-stone-600">{{ order.courier_note }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
