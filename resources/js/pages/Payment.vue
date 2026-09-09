<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../lib/api';
import { money } from '../lib/format';
import { useUiStore } from '../stores/ui';
import QrCode from '../components/ui/QrCode.vue';
import Spinner from '../components/ui/Spinner.vue';
import { CheckCircleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const ui = useUiStore();

const order = ref(null);
const payment = ref(null);
const paid = ref(false);
const mock = ref(false);
const loading = ref(true);
const checking = ref(false);
const simulating = ref(false);
let timer = null;

const number = computed(() => route.params.number);
const expiresIn = ref('');

async function load() {
    try {
        const o = await api.get(`/orders/${number.value}`);
        order.value = o.data.order;
        mock.value = o.data.qpay_mock;
        if (order.value.payment_status === 'paid') { paid.value = true; return; }
        if (order.value.payment_method !== 'qpay' || order.value.status === 'cancelled') return;
        const p = await api.post(`/orders/${number.value}/payment/invoice`);
        payment.value = p.data.payment;
        paid.value = p.data.paid;
        mock.value = p.data.mock ?? mock.value;
    } catch (e) {
        ui.toast(errorMessage(e), 'error');
    } finally {
        loading.value = false;
    }
}

async function check(manual = false) {
    if (paid.value) return;
    checking.value = manual;
    try {
        const { data } = await api.get(`/orders/${number.value}/payment/check`);
        if (data.paid) {
            paid.value = true;
            order.value = data.order;
            ui.toast('Төлбөр амжилттай төлөгдлөө! 🎉');
            clearInterval(timer);
        } else if (manual) {
            ui.toast('Төлбөр хараахан баталгаажаагүй байна', 'info');
        }
    } finally {
        checking.value = false;
    }
}

async function simulate() {
    simulating.value = true;
    try {
        await api.post(`/orders/${number.value}/payment/simulate`);
        await check();
    } catch (e) {
        ui.toast(errorMessage(e), 'error');
    } finally {
        simulating.value = false;
    }
}

function tick() {
    if (!payment.value?.expires_at) return;
    const diff = Math.max(0, new Date(payment.value.expires_at) - Date.now());
    const m = Math.floor(diff / 60000), s = Math.floor((diff % 60000) / 1000);
    expiresIn.value = `${m}:${String(s).padStart(2, '0')}`;
}

onMounted(async () => {
    await load();
    tick();
    timer = setInterval(() => { tick(); check(); }, 5000);
});
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div class="container-x py-10">
        <Spinner v-if="loading" />
        <div v-else-if="!order" class="text-center"><p>Захиалга олдсонгүй.</p></div>

        <!-- Paid -->
        <div v-else-if="paid" class="mx-auto max-w-lg text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100"><CheckCircleIcon class="h-12 w-12 text-emerald-600" /></div>
            <h1 class="mt-6 font-display text-3xl font-bold">Төлбөр амжилттай!</h1>
            <p class="mt-2 text-stone-600">Захиалга <b>{{ order.order_number }}</b> баталгаажлаа. Бид бараагаа бэлтгээд хүргэлтийн ажилтанд хүлээлгэн өгнө.</p>
            <div class="mt-8 flex justify-center gap-3">
                <router-link :to="{ name: 'order', params: { number: order.order_number } }" class="btn-brand">Захиалга харах</router-link>
                <router-link :to="{ name: 'shop' }" class="btn-secondary">Дэлгүүр рүү</router-link>
            </div>
        </div>

        <!-- Awaiting payment -->
        <div v-else-if="payment" class="mx-auto grid max-w-4xl gap-8 lg:grid-cols-[1fr_320px]">
            <div class="card p-6 sm:p-8">
                <div class="flex items-center justify-between">
                    <div><p class="text-sm text-stone-500">Захиалга {{ order.order_number }}</p><h1 class="font-display text-2xl font-bold">QPay-ээр төлөх</h1></div>
                    <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 40'><rect width='120' height='40' rx='8' fill='%230b3d91'/><text x='60' y='27' text-anchor='middle' font-family='Arial' font-weight='bold' font-size='20' fill='white'>qPay</text></svg>" class="h-10" alt="qPay" />
                </div>
                <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                    <div class="rounded-2xl bg-white p-3 ring-1 ring-stone-200 shadow-sm">
                        <QrCode :text="payment.qr_text" :image="payment.qr_image" :size="220" />
                    </div>
                    <div class="flex-1 text-sm text-stone-600">
                        <p class="text-3xl font-bold text-stone-900">{{ money(payment.amount) }}</p>
                        <ol class="mt-4 list-decimal space-y-1.5 pl-4">
                            <li>Банкныхаа аппликейшнийг нээнэ</li>
                            <li>QR код уншуулах хэсгээс энэ кодыг уншуулна</li>
                            <li>Дүнг шалгаад төлбөрөө баталгаажуулна</li>
                            <li>Төлбөр төлөгдмөгц энэ хуудас автоматаар шинэчлэгдэнэ</li>
                        </ol>
                        <p v-if="expiresIn" class="mt-4 text-xs">Нэхэмжлэх хүчинтэй хугацаа: <b class="text-stone-900">{{ expiresIn }}</b></p>
                        <button @click="check(true)" :disabled="checking" class="btn-secondary btn-sm mt-3"><ArrowPathIcon class="h-4 w-4" :class="checking && 'animate-spin'" /> Төлбөр шалгах</button>
                    </div>
                </div>

                <div class="mt-8">
                    <p class="mb-3 text-sm font-semibold">Гар утаснаас банкны аппаар төлөх</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <a v-for="u in payment.urls" :key="u.name" :href="u.link" class="flex items-center gap-2 rounded-xl p-2 ring-1 ring-stone-200 hover:bg-stone-50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-stone-900 text-[10px] font-bold text-white">{{ u.description?.slice(0, 2) }}</span>
                            <span class="truncate text-xs font-medium">{{ u.description }}</span>
                        </a>
                    </div>
                </div>

                <div v-if="mock" class="mt-8 rounded-2xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">🧪 Туршилтын горим (QPAY_MOCK=true)</p>
                    <p class="mt-1">QPay мерчант эрх тохируулаагүй тул нэхэмжлэх локал үүсгэгдсэн. Бодит банкны апп энэ QR-ыг уншихгүй. Төлбөрийг доорх товчоор симуляц хийнэ үү.</p>
                    <button @click="simulate" :disabled="simulating" class="btn mt-3 bg-amber-500 text-white hover:bg-amber-600">{{ simulating ? 'Түр хүлээнэ үү...' : 'Төлбөр төлөгдсөнөөр симуляц хийх' }}</button>
                </div>
            </div>

            <aside class="card h-fit p-6">
                <h2 class="font-semibold">Захиалгын дүн</h2>
                <ul class="mt-3 divide-y divide-stone-100 text-sm">
                    <li v-for="i in order.items" :key="i.id" class="flex justify-between gap-2 py-2"><span class="text-stone-600">{{ i.product_name }} <span class="text-xs text-stone-400">× {{ i.quantity }}</span></span><span class="font-medium">{{ money(i.line_total) }}</span></li>
                </ul>
                <dl class="mt-3 space-y-1.5 border-t border-stone-200 pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-500">Хүргэлт</dt><dd>{{ order.shipping_fee ? money(order.shipping_fee) : 'Үнэгүй' }}</dd></div>
                    <div class="flex justify-between text-base font-bold"><dt>Нийт</dt><dd>{{ money(order.total) }}</dd></div>
                </dl>
                <router-link :to="{ name: 'order', params: { number: order.order_number } }" class="btn-ghost mt-4 w-full">Дараа төлөх</router-link>
            </aside>
        </div>

        <div v-else class="mx-auto max-w-lg text-center">
            <p class="text-stone-600">Энэ захиалга QPay төлбөр шаардахгүй эсвэл цуцлагдсан байна.</p>
            <router-link :to="{ name: 'order', params: { number: order.order_number } }" class="btn-brand mt-4">Захиалга харах</router-link>
        </div>
    </div>
</template>
