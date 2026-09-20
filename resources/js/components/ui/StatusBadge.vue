<script setup>
import { computed } from 'vue';
import { ORDER_STATUS, PAYMENT_STATUS, DELIVERY_STATUS, ROLE } from '../../lib/format';
const props = defineProps({ value: String, type: { type: String, default: 'order' } });
const map = { order: ORDER_STATUS, payment: PAYMENT_STATUS, delivery: DELIVERY_STATUS, role: ROLE };
const item = computed(() => map[props.type]?.[props.value] || { label: props.value, cls: 'bg-cream-200/60 text-stone-700' });
</script>
<template>
    <span class="badge" :class="item.cls">{{ item.label }}</span>
</template>
