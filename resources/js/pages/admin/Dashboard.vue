<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../lib/api';
import { money, dateTime, ORDER_STATUS } from '../../lib/format';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import Spinner from '../../components/ui/Spinner.vue';

const d = ref(null);
onMounted(async () => { d.value = (await api.get('/admin/dashboard')).data; });

const tiles = computed(() => d.value ? [
    { label: 'Өнөөдрийн захиалга', value: d.value.stats.orders_today, sub: `Нийт ${d.value.stats.orders_total}`, icon: '🧾' },
    { label: 'Энэ сарын орлого', value: money(d.value.stats.revenue_month), sub: `Нийт ${money(d.value.stats.revenue_total)}`, icon: '💰' },
    { label: 'Хүлээгдэж буй', value: d.value.stats.orders_pending, sub: 'Баталгаажуулах шаардлагатай', icon: '⏳' },
    { label: 'Идэвхтэй хүргэлт', value: d.value.stats.deliveries_active, sub: `${d.value.stats.couriers} хүргэлтийн ажилтан`, icon: '🛵' },
    { label: 'Хэрэглэгчид', value: d.value.stats.customers, sub: 'Бүртгэлтэй салон', icon: '👥' },
    { label: 'Бүтээгдэхүүн', value: d.value.stats.products, sub: `${d.value.stats.low_stock} сонголт дуусаж буй`, icon: '📦' },
    { label: 'Урьдчилсан захиалга', value: d.value.stats.backorders, sub: 'Бараа ирэхийг хүлээж буй', icon: '🔔' },
] : []);

const chart = computed(() => {
    if (!d.value) return { bars: [], max: 0 };
    const days = [];
    for (let i = 13; i >= 0; i--) {
        const date = new Date(); date.setDate(date.getDate() - i);
        const key = date.toISOString().slice(0, 10);
        const row = d.value.revenue_by_day.find((r) => String(r.day).slice(0, 10) === key);
        days.push({ key, label: `${date.getMonth() + 1}/${date.getDate()}`, total: Number(row?.total || 0), orders: Number(row?.orders || 0) });
    }
    return { bars: days, max: Math.max(1, ...days.map((x) => x.total)) };
});
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold">Хянах самбар</h1>
        <Spinner v-if="!d" />
        <template v-else>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="t in tiles" :key="t.label" class="card p-5">
                    <div class="flex items-center justify-between"><p class="text-sm text-stone-500">{{ t.label }}</p><span class="text-xl">{{ t.icon }}</span></div>
                    <p class="mt-2 text-2xl font-bold">{{ t.value }}</p>
                    <p class="text-xs text-stone-400">{{ t.sub }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-3">
                <div class="card p-5 xl:col-span-2">
                    <div class="flex items-center justify-between"><h2 class="font-semibold">Орлого (сүүлийн 14 хоног)</h2><span class="text-xs text-stone-400">Төлөгдсөн захиалгаар</span></div>
                    <div class="mt-6 flex h-48 items-end gap-1.5">
                        <div v-for="b in chart.bars" :key="b.key" class="group relative flex h-full flex-1 flex-col items-center justify-end">
                            <div class="w-full rounded-t-md bg-brand-500 transition group-hover:bg-brand-600" :style="{ height: `${Math.max(2, (b.total / chart.max) * 100)}%` }"></div>
                            <span class="mt-1 text-[10px] text-stone-400">{{ b.label }}</span>
                            <div class="pointer-events-none absolute -top-9 hidden whitespace-nowrap rounded bg-stone-900 px-2 py-1 text-[10px] text-white group-hover:block">{{ money(b.total) }} · {{ b.orders }} захиалга</div>
                        </div>
                    </div>
                </div>
                <div class="card p-5">
                    <h2 class="font-semibold">Захиалгын төлөв</h2>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="(cfg, key) in ORDER_STATUS" :key="key" class="flex items-center justify-between text-sm">
                            <StatusBadge :value="key" type="order" /><span class="font-semibold">{{ d.status_breakdown[key] || 0 }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-3">
                <div class="card xl:col-span-2">
                    <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4"><h2 class="font-semibold">Сүүлийн захиалгууд</h2><router-link :to="{ name: 'admin.orders' }" class="text-sm text-brand-700 hover:underline">Бүгд →</router-link></div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500"><tr><th class="px-5 py-3">Дугаар</th><th class="px-5 py-3">Хэрэглэгч</th><th class="px-5 py-3">Төлөв</th><th class="px-5 py-3">Төлбөр</th><th class="px-5 py-3 text-right">Дүн</th></tr></thead>
                            <tbody class="divide-y divide-stone-100">
                                <tr v-for="o in d.recent_orders" :key="o.id" class="hover:bg-stone-50">
                                    <td class="px-5 py-3"><router-link :to="{ name: 'admin.order', params: { id: o.id } }" class="font-medium text-brand-700">{{ o.order_number }}</router-link><p class="text-xs text-stone-400">{{ dateTime(o.created_at) }}</p></td>
                                    <td class="px-5 py-3">{{ o.user?.name }}</td>
                                    <td class="px-5 py-3"><StatusBadge :value="o.status" /></td>
                                    <td class="px-5 py-3"><StatusBadge :value="o.payment_status" type="payment" /></td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ money(o.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="card p-5">
                        <h2 class="font-semibold">Үлдэгдэл дуусаж буй</h2>
                        <ul class="mt-3 divide-y divide-stone-100 text-sm">
                            <li v-for="v in d.low_stock_variants" :key="v.id" class="flex items-center justify-between py-2">
                                <div><router-link :to="{ name: 'admin.products.edit', params: { id: v.product_id } }" class="font-medium hover:text-brand-700">{{ v.product?.name }}</router-link><p class="text-xs text-stone-400">{{ v.label }}</p></div>
                                <span class="badge" :class="v.stock <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'">{{ v.stock < 0 ? `${-v.stock} урьдчилсан` : `${v.stock} ш` }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="card p-5">
                        <h2 class="font-semibold">Хамгийн их зарагдсан</h2>
                        <ul class="mt-3 space-y-2.5 text-sm">
                            <li v-for="p in d.top_products" :key="p.id" class="flex items-center gap-3"><img :src="p.thumbnail" class="h-9 w-9 rounded-lg bg-stone-100 object-cover" alt="" /><span class="flex-1 truncate font-medium">{{ p.name }}</span><span class="text-xs text-stone-500">{{ p.sold_count }} ш</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
