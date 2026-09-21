<script setup>
import { ref, onMounted } from 'vue';
import api from '../../lib/api';
import { money, dateTime, PAYMENT_METHOD } from '../../lib/format';
import { useAuthStore } from '../../stores/auth';
import AccountShell from '../../components/account/AccountShell.vue';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import ProductCard from '../../components/ProductCard.vue';
import Spinner from '../../components/ui/Spinner.vue';
import { ShieldCheckIcon, TruckIcon, CreditCardIcon, ClockIcon } from '@heroicons/vue/24/outline';
import OrderActions from '../../components/OrderActions.vue';

const auth = useAuthStore(); const d = ref(null);
async function load() { d.value = (await api.get('/account/dashboard')).data; }
onMounted(load);
const greet = () => { const h = new Date().getHours(); return h < 12 ? 'Өглөөний мэнд' : h < 18 ? 'Өдрийн мэнд' : 'Оройн мэнд'; };
const stepIdx = (s) => ['unassigned', 'assigned', 'picked_up', 'in_transit', 'delivered'].indexOf(s);
</script>
<template>
    <AccountShell>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div><p class="text-sm text-stone-500">{{ greet() }},</p><h1 class="font-display text-3xl font-bold">{{ auth.user.name }} 👋</h1></div>
            <router-link :to="{ name: 'shop' }" class="btn-brand">Дэлгүүр үзэх</router-link>
        </div>
        <Spinner v-if="!d" />
        <div v-else class="space-y-6">
            <!-- Verification -->
            <div v-if="!auth.isVerified" class="flex flex-col items-start gap-4 rounded-2xl bg-gradient-to-r from-gold-500 to-gold-600 p-5 text-brand-900 sm:flex-row sm:items-center">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/20"><ShieldCheckIcon class="h-7 w-7" /></span>
                <div class="flex-1"><p class="font-bold">Бүртгэлээ баталгаажуулна уу</p><p class="text-sm text-brand-900/80">Захиалга өгөхийн өмнө утасны дугаараа SMS-ээр нэг удаа баталгаажуулна.</p></div>
                <router-link :to="{ name: 'verify' }" class="btn-primary">Баталгаажуулах</router-link>
            </div>
            <div v-else class="flex items-center gap-3 rounded-2xl bg-emerald-50 px-5 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200"><ShieldCheckIcon class="h-5 w-5" /> Утасны дугаар SMS-ээр баталгаажсан · {{ auth.user.verified_phone }}</div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="card p-5"><p class="text-xs text-stone-500">Нийт захиалга</p><p class="mt-1 text-2xl font-bold">{{ d.stats.orders_total }}</p><p class="text-xs text-stone-400">{{ d.stats.delivered }} хүргэгдсэн</p></div>
                <div class="card p-5"><p class="text-xs text-stone-500">Идэвхтэй захиалга</p><p class="mt-1 text-2xl font-bold text-brand-700">{{ d.stats.orders_active }}</p><p class="text-xs text-stone-400">{{ d.stats.backorders }} урьдчилсан</p></div>
                <div class="card p-5"><p class="text-xs text-stone-500">Төлбөр хүлээгдэж буй</p><p class="mt-1 text-2xl font-bold" :class="d.stats.unpaid ? 'text-amber-600' : ''">{{ d.stats.unpaid }}</p><p class="text-xs text-stone-400">QPay нэхэмжлэх</p></div>
                <div class="card p-5"><p class="text-xs text-stone-500">Нийт худалдан авалт</p><p class="mt-1 text-2xl font-bold">{{ money(d.stats.spent_total) }}</p><p class="text-xs text-stone-400">төлөгдсөн дүн</p></div>
            </div>

            <!-- Unpaid -->
            <div v-if="d.unpaid_orders.length" class="card p-5">
                <h3 class="flex items-center gap-2 font-semibold"><CreditCardIcon class="h-5 w-5 text-amber-600" /> Төлбөр хүлээгдэж буй захиалга</h3>
                <ul class="mt-3 divide-y divide-stone-100">
                    <li v-for="o in d.unpaid_orders" :key="o.id" class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"><div><p class="font-medium">{{ o.order_number }}</p><p class="text-xs text-stone-500">{{ dateTime(o.created_at) }}</p></div><div class="flex items-center gap-3"><span class="font-semibold">{{ money(o.total) }}</span><OrderActions :order="o" @updated="load" /></div></li>
                </ul>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <!-- Active deliveries -->
                <div class="card p-5">
                    <h3 class="flex items-center gap-2 font-semibold"><TruckIcon class="h-5 w-5 text-sky-600" /> Хүргэлт хянах</h3>
                    <p v-if="!d.active_deliveries.length" class="mt-3 text-sm text-stone-500">Одоогоор замд яваа хүргэлт байхгүй.</p>
                    <ul v-else class="mt-3 space-y-4">
                        <li v-for="o in d.active_deliveries" :key="o.id" class="rounded-xl bg-cream-100 p-4">
                            <div class="flex items-center justify-between text-sm"><router-link :to="{ name: 'order', params: { number: o.order_number } }" class="font-semibold hover:text-brand-700">{{ o.order_number }}</router-link><StatusBadge :value="o.delivery_status" type="delivery" /></div>
                            <div class="mt-3 flex items-center gap-1"><template v-for="(s, i) in ['Томилогдсон', 'Хүлээн авсан', 'Замд', 'Хүргэгдсэн']" :key="s"><div class="h-1.5 flex-1 rounded-full" :class="i < stepIdx(o.delivery_status) ? 'bg-sky-600' : 'bg-stone-200'"></div></template></div>
                            <p v-if="o.courier" class="mt-3 text-xs text-stone-600">🛵 {{ o.courier.name }} · <a :href="`tel:${o.courier.phone}`" class="text-brand-700">{{ o.courier.phone }}</a></p>
                        </li>
                    </ul>
                </div>
                <!-- Backorders -->
                <div class="card p-5">
                    <h3 class="flex items-center gap-2 font-semibold"><ClockIcon class="h-5 w-5 text-amber-600" /> Урьдчилсан захиалгын бараа</h3>
                    <p v-if="!d.backorder_items.length" class="mt-3 text-sm text-stone-500">Хүлээгдэж буй урьдчилсан захиалга байхгүй.</p>
                    <ul v-else class="mt-3 divide-y divide-stone-100 text-sm">
                        <li v-for="i in d.backorder_items" :key="i.id" class="flex items-center gap-3 py-2.5"><img :src="i.image" class="h-10 w-10 rounded-lg bg-cream-200/60 object-cover" alt="" /><div class="flex-1"><p class="font-medium">{{ i.product_name }}</p><p class="text-xs text-stone-500">{{ i.variant_label }} × {{ i.quantity }} · {{ i.order?.order_number }}</p></div><span class="badge bg-amber-100 text-amber-800">Хүлээгдэж буй</span></li>
                    </ul>
                </div>
            </div>

            <!-- Recent orders -->
            <div class="card">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4"><h3 class="font-semibold">Сүүлийн захиалгууд</h3><router-link :to="{ name: 'orders' }" class="text-sm text-brand-700 hover:underline">Бүгд →</router-link></div>
                <p v-if="!d.recent_orders.length" class="p-5 text-sm text-stone-500">Та одоогоор захиалга хийгээгүй байна.</p>
                <ul v-else class="divide-y divide-stone-100">
                    <li v-for="o in d.recent_orders" :key="o.id"><router-link :to="{ name: 'order', params: { number: o.order_number } }" class="flex flex-wrap items-center gap-4 px-5 py-3 hover:bg-cream-100">
                        <div class="flex -space-x-2"><img v-for="i in o.items.slice(0, 3)" :key="i.id" :src="i.image" class="h-9 w-9 rounded-lg bg-cream-200/60 object-cover ring-2 ring-white" alt="" /></div>
                        <div class="min-w-0 flex-1"><p class="text-sm font-semibold">{{ o.order_number }}</p><p class="text-xs text-stone-500">{{ dateTime(o.created_at) }} · {{ o.items.length }} бараа · {{ PAYMENT_METHOD[o.payment_method] }}</p></div>
                        <div class="flex items-center gap-2"><StatusBadge :value="o.status" /><StatusBadge :value="o.payment_status" type="payment" /></div>
                        <p class="text-sm font-bold">{{ money(o.total) }}</p>
                    </router-link></li>
                </ul>
            </div>

            <!-- Reorder / recommended -->
            <div v-if="d.reorder.length"><h3 class="mb-4 font-semibold">Дахин захиалах</h3><div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4"><ProductCard v-for="p in d.reorder" :key="p.id" :product="p" /></div></div>
            <div v-else-if="d.recommended.length"><h3 class="mb-4 font-semibold">Танд санал болгох</h3><div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4"><ProductCard v-for="p in d.recommended" :key="p.id" :product="p" /></div></div>
        </div>
    </AccountShell>
</template>
