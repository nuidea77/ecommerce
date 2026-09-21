<script setup>
import { ref } from 'vue';
import api, { errorMessage } from '../lib/api';
import { useUiStore } from '../stores/ui';
import { CreditCardIcon, XMarkIcon, ArrowPathIcon } from '@heroicons/vue/20/solid';

const props = defineProps({ order: { type: Object, required: true }, size: { type: String, default: 'sm' } });
const emit = defineEmits(['updated']);
const ui = useUiStore();
const busy = ref(false);
const cancellable = ['pending', 'awaiting_payment', 'confirmed'];

async function cancel() {
    if (!confirm('Захиалгаа цуцлахдаа итгэлтэй байна уу?')) return;
    busy.value = true;
    try { const { data } = await api.post(`/orders/${props.order.order_number}/cancel`); ui.toast('Захиалга цуцлагдлаа', 'info'); emit('updated', data.order); }
    catch (e) { ui.toast(errorMessage(e), 'error'); } finally { busy.value = false; }
}
</script>
<template>
    <div class="flex flex-wrap items-center gap-2" @click.stop>
        <router-link v-if="order.status === 'awaiting_payment'" :to="{ name: 'pay', params: { number: order.order_number } }" class="btn-brand" :class="size === 'sm' && 'btn-sm'"><CreditCardIcon class="h-4 w-4" /> Дахин төлөх</router-link>
        <router-link v-if="order.status === 'cancelled' || order.status === 'delivered'" :to="{ name: 'shop' }" class="btn-secondary" :class="size === 'sm' && 'btn-sm'"><ArrowPathIcon class="h-4 w-4" /> Дахин захиалах</router-link>
        <button v-if="cancellable.includes(order.status)" @click="cancel" :disabled="busy" class="btn-secondary text-red-600" :class="size === 'sm' && 'btn-sm'"><XMarkIcon class="h-4 w-4" /> Цуцлах</button>
    </div>
</template>
