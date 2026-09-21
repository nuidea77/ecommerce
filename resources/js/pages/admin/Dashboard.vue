<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import api from '../../lib/api';
import { money, dateTime, ORDER_STATUS, PAYMENT_METHOD } from '../../lib/format';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import Spinner from '../../components/ui/Spinner.vue';
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon, ExclamationTriangleIcon, ShieldCheckIcon, TableCellsIcon, ChartBarIcon } from '@heroicons/vue/24/outline';

const d = ref(null);
const days = ref(14);
const loading = ref(false);
const chartView = ref('chart');
const hover = ref(null);

async function load() {
    loading.value = true;
    try { d.value = (await api.get('/admin/dashboard', { params: { days: days.value } })).data; }
    finally { loading.value = false; }
}
watch(days, load);
onMounted(load);

const fmtDay = (iso) => { const [, m, day] = iso.split('-'); return `${Number(m)}/${Number(day)}`; };
const compact = (v) => (v >= 1e6 ? `${(v / 1e6).toFixed(1)}сая` : v >= 1e3 ? `${Math.round(v / 1e3)}м` : String(Math.round(v)));

// Revenue chart geometry (single series, one axis)
const W = 760, H = 220, PL = 48, PB = 26, PT = 12;
const chart = computed(() => {
    if (!d.value) return null;
    const s = d.value.series;
    const max = Math.max(1, ...s.map((x) => x.revenue));
    const niceMax = Math.ceil(max / Math.pow(10, Math.floor(Math.log10(max)))) * Math.pow(10, Math.floor(Math.log10(max)));
    const iw = W - PL - 8, ih = H - PB - PT;
    const slot = iw / s.length;
    const bw = Math.min(28, slot * 0.62);
    const bars = s.map((x, i) => ({ ...x, x: PL + i * slot + (slot - bw) / 2, w: bw, h: (x.revenue / niceMax) * ih, y: PT + ih - (x.revenue / niceMax) * ih, cx: PL + i * slot + slot / 2 }));
    const ticks = [0, 0.25, 0.5, 0.75, 1].map((t) => ({ v: niceMax * t, y: PT + ih - ih * t }));
    const labelEvery = s.length > 20 ? 5 : s.length > 10 ? 2 : 1;
    return { bars, ticks, ih, labelEvery, baseline: PT + ih };
});

const kpiCards = computed(() => d.value ? [
    { label: 'Орлого', value: money(d.value.kpis.revenue.value), change: d.value.kpis.revenue.change, sub: `өмнөх үе ${money(d.value.kpis.revenue.prev)}` },
    { label: 'Захиалга', value: d.value.kpis.orders.value, change: d.value.kpis.orders.change, sub: `өмнөх үе ${d.value.kpis.orders.prev}` },
    { label: 'Дундаж захиалга', value: money(d.value.kpis.avg_order.value), sub: 'төлөгдсөн захиалгаар' },
    { label: 'Шинэ хэрэглэгч', value: d.value.kpis.customers_new.value, change: d.value.kpis.customers_new.change, sub: `өмнөх үе ${d.value.kpis.customers_new.prev}` },
] : []);

const payTotal = computed(() => d.value ? Math.max(1, d.value.payment_methods.reduce((a, p) => a + Number(p.orders), 0)) : 1);
const payColor = { qpay: '#2a78d6', cash: '#eb6834' };
const catMax = computed(() => d.value ? Math.max(1, ...d.value.category_sales.map((c) => Number(c.revenue))) : 1);
const statusTotal = computed(() => d.value ? Math.max(1, Object.values(d.value.status_breakdown).reduce((a, b) => a + Number(b), 0)) : 1);
const statusColor = { pending: '#f59e0b', confirmed: '#0ea5e9', processing: '#6366f1', shipped: '#8b5cf6', delivered: '#10b981', cancelled: '#a8a29e' };
const attentionReason = (o) => o.delivery_status === 'failed' ? { t: 'Хүргэлт амжилтгүй', c: 'bg-red-100 text-red-700' } : o.status === 'pending' ? { t: 'Баталгаажуулах', c: 'bg-amber-100 text-amber-800' } : o.delivery_status === 'unassigned' ? { t: 'Хүргэгч томилох', c: 'bg-sky-100 text-sky-800' } : { t: 'Шалгах', c: 'bg-cream-200/60 text-stone-700' };
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold">Хянах самбар</h1><p v-if="d" class="text-sm text-stone-500">{{ d.period.from }} — {{ d.period.to }}</p></div>
            <div class="flex gap-1 rounded-xl bg-paper p-1 text-sm ring-1 ring-stone-200">
                <button v-for="n in [7, 14, 30, 90]" :key="n" @click="days = n" class="rounded-lg px-3 py-1.5 font-medium transition" :class="days === n ? 'bg-brand-800 text-white' : 'text-stone-600 hover:bg-cream-200/60'">{{ n }} хоног</button>
            </div>
        </div>

        <Spinner v-if="!d" />
        <div v-else class="mt-6 space-y-6" :class="loading && 'opacity-60'">
            <!-- KPIs -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="k in kpiCards" :key="k.label" class="card p-5">
                    <p class="text-sm text-stone-500">{{ k.label }}</p>
                    <div class="mt-2 flex items-end justify-between gap-2">
                        <p class="text-2xl font-bold tracking-tight">{{ k.value }}</p>
                        <span v-if="k.change !== undefined" class="badge" :class="k.change >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'"><component :is="k.change >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="h-3.5 w-3.5" />{{ k.change > 0 ? '+' : '' }}{{ k.change }}%</span>
                    </div>
                    <p class="mt-1 text-xs text-stone-400">{{ k.sub }}</p>
                </div>
            </div>

            <!-- Operational strip -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <router-link :to="{ name: 'admin.orders', query: { status: 'pending' } }" class="card p-4 hover:ring-brand-300"><p class="text-xs text-stone-500">Баталгаажуулах</p><p class="text-xl font-bold text-amber-600">{{ d.stats.orders_pending }}</p></router-link>
                <router-link :to="{ name: 'admin.orders', query: { payment_status: 'unpaid' } }" class="card p-4 hover:ring-brand-300"><p class="text-xs text-stone-500">Төлбөр хүлээж буй</p><p class="text-xl font-bold">{{ d.stats.awaiting_payment }}</p></router-link>
                <router-link :to="{ name: 'admin.orders', query: { delivery_status: 'unassigned' } }" class="card p-4 hover:ring-brand-300"><p class="text-xs text-stone-500">Хүргэгч томилох</p><p class="text-xl font-bold text-sky-700">{{ d.stats.unassigned }}</p></router-link>
                <router-link :to="{ name: 'admin.orders', query: { delivery_status: 'in_transit' } }" class="card p-4 hover:ring-brand-300"><p class="text-xs text-stone-500">Идэвхтэй хүргэлт</p><p class="text-xl font-bold">{{ d.stats.deliveries_active }}</p></router-link>
                <router-link :to="{ name: 'admin.orders', query: { backorder: 1 } }" class="card p-4 hover:ring-brand-300"><p class="text-xs text-stone-500">Урьдчилсан захиалга</p><p class="text-xl font-bold text-amber-600">{{ d.stats.backorders }}</p></router-link>
                <router-link :to="{ name: 'admin.products', query: { low_stock: 1 } }" class="card p-4 hover:ring-brand-300"><p class="text-xs text-stone-500">Дууссан сонголт</p><p class="text-xl font-bold text-red-600">{{ d.stats.out_of_stock }}</p></router-link>
            </div>

            <!-- Revenue chart + status -->
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="card p-5 xl:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div><h2 class="font-semibold">Өдрийн орлого</h2><p class="text-xs text-stone-400">Төлөгдсөн захиалгын дүн, ₮ · hover хийж дэлгэрэнгүй</p></div>
                        <div class="flex gap-1 rounded-lg bg-cream-200/60 p-0.5"><button @click="chartView = 'chart'" class="rounded-md p-1.5" :class="chartView === 'chart' ? 'bg-paper shadow-sm' : 'text-stone-500'" title="График"><ChartBarIcon class="h-4 w-4" /></button><button @click="chartView = 'table'" class="rounded-md p-1.5" :class="chartView === 'table' ? 'bg-paper shadow-sm' : 'text-stone-500'" title="Хүснэгт"><TableCellsIcon class="h-4 w-4" /></button></div>
                    </div>
                    <div v-if="chartView === 'chart'" class="relative mt-4">
                        <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" role="img" aria-label="Өдрийн орлогын график">
                            <g v-for="t in chart.ticks" :key="t.v"><line :x1="PL" :x2="W - 8" :y1="t.y" :y2="t.y" stroke="#e7e5e4" stroke-width="1" /><text :x="PL - 6" :y="t.y + 4" text-anchor="end" font-size="10" fill="#a8a29e">{{ compact(t.v) }}</text></g>
                            <line :x1="PL" :x2="W - 8" :y1="chart.baseline" :y2="chart.baseline" stroke="#d6d3d1" />
                            <g v-for="(b, i) in chart.bars" :key="b.day" @mouseenter="hover = b" @mouseleave="hover = null">
                                <rect :x="b.cx - 14" :y="PT" width="28" :height="chart.ih" fill="transparent" />
                                <rect :x="b.x" :y="b.y" :width="b.w" :height="Math.max(b.h, b.revenue ? 2 : 0)" rx="3" :fill="hover?.day === b.day ? '#0f3227' : '#24654a'" :opacity="hover && hover.day !== b.day ? 0.55 : 1" />
                                <text v-if="i % chart.labelEvery === 0" :x="b.cx" :y="H - 8" text-anchor="middle" font-size="10" fill="#78716c">{{ fmtDay(b.day) }}</text>
                            </g>
                        </svg>
                        <div v-if="hover" class="pointer-events-none absolute -top-2 rounded-lg bg-stone-900 px-3 py-2 text-xs text-white shadow-lg" :style="{ left: `${(hover.cx / W) * 100}%`, transform: 'translateX(-50%)' }">
                            <p class="font-semibold">{{ hover.day }}</p><p>Орлого: {{ money(hover.revenue) }}</p><p>Захиалга: {{ hover.orders }}</p>
                        </div>
                    </div>
                    <div v-else class="mt-4 max-h-64 overflow-auto rounded-xl ring-1 ring-stone-200">
                        <table class="min-w-full text-sm"><thead class="sticky top-0 bg-cream-100 text-left text-xs uppercase text-stone-500"><tr><th class="px-3 py-2">Өдөр</th><th class="px-3 py-2 text-right">Захиалга</th><th class="px-3 py-2 text-right">Орлого</th></tr></thead>
                        <tbody class="divide-y divide-stone-100"><tr v-for="s in d.series" :key="s.day"><td class="px-3 py-1.5">{{ s.day }}</td><td class="px-3 py-1.5 text-right">{{ s.orders }}</td><td class="px-3 py-1.5 text-right font-medium">{{ money(s.revenue) }}</td></tr></tbody></table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="card p-5">
                        <h2 class="font-semibold">Захиалгын төлөв <span class="text-xs font-normal text-stone-400">(бүх цаг)</span></h2>
                        <div class="mt-4 flex h-3 w-full gap-0.5 overflow-hidden rounded-full"><div v-for="(cfg, key) in ORDER_STATUS" :key="key" :style="{ width: `${((d.status_breakdown[key] || 0) / statusTotal) * 100}%`, backgroundColor: statusColor[key] }" :title="cfg.label"></div></div>
                        <ul class="mt-4 space-y-2">
                            <li v-for="(cfg, key) in ORDER_STATUS" :key="key" class="flex items-center justify-between text-sm"><span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: statusColor[key] }"></span>{{ cfg.label }}</span><span class="font-semibold">{{ d.status_breakdown[key] || 0 }}</span></li>
                        </ul>
                    </div>
                    <div class="card p-5">
                        <h2 class="font-semibold">Төлбөрийн хэлбэр</h2>
                        <div class="mt-4 flex h-3 w-full gap-0.5 overflow-hidden rounded-full"><div v-for="p in d.payment_methods" :key="p.payment_method" :style="{ width: `${(p.orders / payTotal) * 100}%`, backgroundColor: payColor[p.payment_method] }"></div></div>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li v-for="p in d.payment_methods" :key="p.payment_method" class="flex items-center justify-between"><span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: payColor[p.payment_method] }"></span>{{ PAYMENT_METHOD[p.payment_method] }}</span><span><b>{{ p.orders }}</b> <span class="text-stone-400">· {{ money(p.revenue) }}</span></span></li>
                            <li v-if="!d.payment_methods.length" class="text-stone-500">Энэ хугацаанд захиалга байхгүй</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Attention + categories -->
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="card xl:col-span-2">
                    <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4"><h2 class="flex items-center gap-2 font-semibold"><ExclamationTriangleIcon class="h-5 w-5 text-amber-500" /> Анхаарал шаардсан захиалга</h2><router-link :to="{ name: 'admin.orders' }" class="text-sm text-brand-700 hover:underline">Бүгд →</router-link></div>
                    <p v-if="!d.attention_orders.length" class="p-5 text-sm text-stone-500">Бүх захиалга хэвийн ✓</p>
                    <ul v-else class="divide-y divide-stone-100">
                        <li v-for="o in d.attention_orders" :key="o.id"><router-link :to="{ name: 'admin.order', params: { id: o.id } }" class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm hover:bg-cream-100">
                            <div class="min-w-0 flex-1"><p class="font-semibold text-brand-700">{{ o.order_number }}</p><p class="text-xs text-stone-500">{{ o.user?.name }} · {{ dateTime(o.created_at) }}</p></div>
                            <span class="badge" :class="attentionReason(o).c">{{ attentionReason(o).t }}</span><StatusBadge :value="o.status" /><span class="w-24 text-right font-semibold">{{ money(o.total) }}</span>
                        </router-link></li>
                    </ul>
                </div>
                <div class="card p-5">
                    <h2 class="font-semibold">Ангиллын борлуулалт</h2>
                    <ul class="mt-4 space-y-3">
                        <li v-for="c in d.category_sales" :key="c.name">
                            <div class="flex justify-between text-sm"><span class="truncate">{{ c.name }}</span><span class="ml-2 shrink-0 font-medium">{{ money(c.revenue) }}</span></div>
                            <div class="mt-1 h-2 w-full rounded-full bg-cream-200/60"><div class="h-2 rounded-full bg-brand-600" :style="{ width: `${(c.revenue / catMax) * 100}%` }"></div></div>
                            <p class="mt-0.5 text-[11px] text-stone-400">{{ c.qty }} ширхэг</p>
                        </li>
                        <li v-if="!d.category_sales.length" class="text-sm text-stone-500">Мэдээлэл алга</li>
                    </ul>
                </div>
            </div>

            <!-- Top products / backorders / low stock -->
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="card p-5">
                    <h2 class="font-semibold">Шилдэг бүтээгдэхүүн</h2>
                    <ul class="mt-3 divide-y divide-stone-100 text-sm">
                        <li v-for="(p, i) in d.top_products" :key="p.product_id" class="flex items-center gap-3 py-2.5"><span class="w-4 text-xs text-stone-400">{{ i + 1 }}</span><img :src="p.image" class="h-9 w-9 rounded-lg bg-cream-200/60 object-cover" alt="" /><div class="min-w-0 flex-1"><p class="truncate font-medium">{{ p.product_name }}</p><p class="text-xs text-stone-400">{{ p.qty }} ш</p></div><span class="font-semibold">{{ money(p.revenue) }}</span></li>
                        <li v-if="!d.top_products.length" class="py-2 text-stone-500">Мэдээлэл алга</li>
                    </ul>
                </div>
                <div class="card p-5">
                    <h2 class="font-semibold">Нийлүүлэх шаардлагатай <span class="text-xs font-normal text-stone-400">(урьдчилсан захиалга)</span></h2>
                    <ul class="mt-3 divide-y divide-stone-100 text-sm">
                        <li v-for="b in d.backorder_items" :key="b.product_variant_id" class="flex items-center justify-between py-2.5"><div class="min-w-0"><p class="truncate font-medium">{{ b.product_name }}</p><p class="text-xs text-stone-400">{{ b.variant_label }} · {{ b.orders }} захиалга</p></div><span class="badge bg-amber-100 text-amber-800">{{ b.qty }} ш</span></li>
                        <li v-if="!d.backorder_items.length" class="py-2 text-stone-500">Хүлээгдэж буй урьдчилсан захиалга алга ✓</li>
                    </ul>
                </div>
                <div class="card p-5">
                    <h2 class="font-semibold">Үлдэгдэл дуусаж буй</h2>
                    <ul class="mt-3 divide-y divide-stone-100 text-sm">
                        <li v-for="v in d.low_stock_variants" :key="v.id" class="flex items-center justify-between py-2.5"><div class="min-w-0"><router-link :to="{ name: 'admin.products.edit', params: { id: v.product_id } }" class="truncate font-medium hover:text-brand-700">{{ v.product?.name }}</router-link><p class="text-xs text-stone-400">{{ v.label }}</p></div><span class="badge" :class="v.stock <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'">{{ v.stock < 0 ? `${-v.stock} урьдчилсан` : `${v.stock} ш` }}</span></li>
                    </ul>
                </div>
            </div>

            <!-- Couriers + customers + recent orders -->
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="card p-5">
                    <h2 class="font-semibold">Хүргэлтийн ажилтнууд</h2>
                    <ul class="mt-3 divide-y divide-stone-100 text-sm">
                        <li v-for="c in d.couriers" :key="c.id" class="flex items-center gap-3 py-2.5"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-100 font-bold text-sky-700">{{ c.name.slice(0, 1) }}</span><div class="flex-1"><p class="font-medium">{{ c.name }}</p><p class="text-xs text-stone-400">{{ c.phone }}</p></div><div class="text-right text-xs"><p><b class="text-sky-700">{{ c.active_count }}</b> идэвхтэй</p><p><b class="text-emerald-700">{{ c.delivered_count }}</b> хүргэсэн<span v-if="c.failed_count" class="text-red-600"> · {{ c.failed_count }} амжилтгүй</span></p></div></li>
                    </ul>
                </div>
                <div class="card p-5">
                    <div class="flex items-center justify-between"><h2 class="font-semibold">Хэрэглэгчид</h2><router-link :to="{ name: 'admin.users', query: { role: 'customer' } }" class="text-sm text-brand-700 hover:underline">Бүгд →</router-link></div>
                    <div class="mt-3 flex items-center gap-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800"><ShieldCheckIcon class="h-5 w-5" /><span><b>{{ d.stats.customers_verified }}</b> / {{ d.stats.customers }} SMS-ээр баталгаажсан</span></div>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-cream-200/60"><div class="h-1.5 rounded-full bg-emerald-500" :style="{ width: `${d.stats.customers ? (d.stats.customers_verified / d.stats.customers) * 100 : 0}%` }"></div></div>
                    <ul class="mt-3 divide-y divide-stone-100 text-sm">
                        <li v-for="u in d.recent_customers" :key="u.id" class="flex items-center gap-3 py-2.5"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">{{ u.name.slice(0, 1) }}</span><div class="min-w-0 flex-1"><p class="truncate font-medium">{{ u.name }} <span v-if="u.is_verified" class="text-emerald-600" title="Баталгаажсан">✓</span></p><p class="truncate text-xs text-stone-400">{{ u.email }}</p></div><span class="text-xs text-stone-500">{{ u.orders_count }} захиалга</span></li>
                    </ul>
                </div>
                <div class="card">
                    <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4"><h2 class="font-semibold">Сүүлийн захиалгууд</h2><router-link :to="{ name: 'admin.orders' }" class="text-sm text-brand-700 hover:underline">Бүгд →</router-link></div>
                    <ul class="divide-y divide-stone-100 text-sm">
                        <li v-for="o in d.recent_orders" :key="o.id"><router-link :to="{ name: 'admin.order', params: { id: o.id } }" class="flex items-center gap-3 px-5 py-2.5 hover:bg-cream-100"><div class="min-w-0 flex-1"><p class="font-medium text-brand-700">{{ o.order_number }}</p><p class="truncate text-xs text-stone-400">{{ o.user?.name }} · {{ dateTime(o.created_at) }}</p></div><StatusBadge :value="o.payment_status" type="payment" /><span class="w-20 text-right font-semibold">{{ money(o.total) }}</span></router-link></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
