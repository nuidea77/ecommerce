<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../../lib/api';
import { money, dateTime, PAYMENT_METHOD } from '../../lib/format';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import Spinner from '../../components/ui/Spinner.vue';
import { MapPinIcon, PhoneIcon } from '@heroicons/vue/24/outline';

const tab = ref('active'); const data = ref(null);
async function load() { data.value = (await api.get('/courier/deliveries', { params: { tab: tab.value } })).data; }
watch(tab, load); onMounted(load);
</script>
<template>
    <div>
        <h1 class="text-2xl font-bold">Миний хүргэлтүүд</h1>
        <div v-if="data" class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="card p-4"><p class="text-xs text-stone-500">Томилогдсон</p><p class="text-2xl font-bold text-sky-700">{{ data.stats.assigned }}</p></div>
            <div class="card p-4"><p class="text-xs text-stone-500">Замд яваа</p><p class="text-2xl font-bold text-violet-700">{{ data.stats.in_progress }}</p></div>
            <div class="card p-4"><p class="text-xs text-stone-500">Өнөөдөр хүргэсэн</p><p class="text-2xl font-bold text-emerald-700">{{ data.stats.delivered_today }}</p></div>
            <div class="card p-4"><p class="text-xs text-stone-500">Нийт хүргэсэн</p><p class="text-2xl font-bold">{{ data.stats.delivered_total }}</p></div>
        </div>
        <div class="mt-5 flex gap-1 rounded-xl bg-white p-1 text-sm ring-1 ring-stone-200 w-fit">
            <button v-for="t in [['active', 'Идэвхтэй'], ['done', 'Дууссан'], ['all', 'Бүгд']]" :key="t[0]" @click="tab = t[0]" class="rounded-lg px-4 py-1.5 font-medium" :class="tab === t[0] ? 'bg-brand-700 text-white' : 'text-stone-600'">{{ t[1] }}</button>
        </div>
        <Spinner v-if="!data" />
        <EmptyState v-else-if="!data.deliveries.data.length" class="mt-5" title="Хүргэлт байхгүй" description="Танд томилогдсон хүргэлт одоогоор алга." icon="🛵" />
        <div v-else class="mt-5 grid gap-4 md:grid-cols-2">
            <router-link v-for="o in data.deliveries.data" :key="o.id" :to="{ name: 'courier.delivery', params: { id: o.id } }" class="card block p-5 transition hover:shadow-md">
                <div class="flex items-start justify-between gap-2">
                    <div><p class="font-semibold">{{ o.order_number }}</p><p class="text-xs text-stone-400">{{ dateTime(o.updated_at) }} · {{ o.items_count }} бараа</p></div>
                    <StatusBadge :value="o.delivery_status" type="delivery" />
                </div>
                <div class="mt-3 space-y-1 text-sm text-stone-600">
                    <p class="flex items-start gap-2"><MapPinIcon class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" /><span>{{ o.shipping_city }}<span v-if="o.shipping_district">, {{ o.shipping_district }}</span> — {{ o.shipping_address }}</span></p>
                    <p class="flex items-center gap-2"><PhoneIcon class="h-4 w-4 text-sky-600" />{{ o.shipping_name }} · {{ o.shipping_phone }}</p>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-stone-100 pt-3 text-sm">
                    <span class="badge" :class="o.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">{{ o.payment_status === 'paid' ? 'Төлбөр төлөгдсөн' : `Бэлнээр авах: ${money(o.total)}` }}</span>
                    <span class="text-xs text-stone-400">{{ PAYMENT_METHOD[o.payment_method] }}</span>
                </div>
            </router-link>
        </div>
    </div>
</template>
