<script setup>
import { ref, onMounted } from 'vue';
import api from '../lib/api';
import { money, dateTime, PAYMENT_METHOD } from '../lib/format';
import StatusBadge from '../components/ui/StatusBadge.vue';
import Pagination from '../components/ui/Pagination.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import Spinner from '../components/ui/Spinner.vue';

const result = ref(null);
async function load(page = 1) {
    const { data } = await api.get('/orders', { params: { page } });
    result.value = data;
}
onMounted(load);
</script>

<template>
    <div class="container-x py-8">
        <h1 class="font-display text-3xl font-bold">Миний захиалгууд</h1>
        <Spinner v-if="!result" />
        <EmptyState v-else-if="!result.data.length" class="mt-6" title="Захиалга байхгүй" description="Та одоогоор захиалга хийгээгүй байна." icon="📦"><router-link :to="{ name: 'shop' }" class="btn-brand">Дэлгүүр үзэх</router-link></EmptyState>
        <div v-else class="mt-6 space-y-4">
            <router-link v-for="o in result.data" :key="o.id" :to="{ name: 'order', params: { number: o.order_number } }" class="card block p-5 transition hover:shadow-md">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ o.order_number }} <span v-if="o.has_backorder" class="badge ml-1 bg-amber-100 text-amber-800">Урьдчилсан</span></p>
                        <p class="text-xs text-stone-500">{{ dateTime(o.created_at) }} · {{ PAYMENT_METHOD[o.payment_method] }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge :value="o.status" type="order" /><StatusBadge :value="o.payment_status" type="payment" />
                        <StatusBadge v-if="o.delivery_status !== 'unassigned'" :value="o.delivery_status" type="delivery" />
                    </div>
                    <p class="text-lg font-bold">{{ money(o.total) }}</p>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <img v-for="i in o.items.slice(0, 5)" :key="i.id" :src="i.image" class="h-12 w-12 rounded-lg bg-stone-100 object-cover ring-1 ring-stone-200" :alt="i.product_name" />
                    <span v-if="o.items.length > 5" class="text-xs text-stone-500">+{{ o.items.length - 5 }}</span>
                    <span class="ml-auto text-sm text-stone-500">{{ o.items.length }} бараа</span>
                </div>
                <div v-if="o.payment_method === 'qpay' && o.payment_status === 'unpaid' && o.status !== 'cancelled'" class="mt-3 rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-800">Төлбөр хүлээгдэж байна — дарж QPay-ээр төлнө үү</div>
            </router-link>
            <Pagination :meta="result" @change="load" />
        </div>
    </div>
</template>
