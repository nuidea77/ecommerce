<script setup>
import { HISTORY_LABEL, dateTime } from '../lib/format';
defineProps({ histories: { type: Array, default: () => [] } });
const dot = (h) => ({ payment: 'bg-emerald-500', delivery: 'bg-sky-500' }[h.type] || (h.status === 'cancelled' ? 'bg-red-500' : 'bg-brand-500'));
</script>
<template>
    <ol class="relative space-y-5 border-l border-stone-200 pl-5">
        <li v-for="h in histories" :key="h.id" class="relative">
            <span class="absolute -left-[26px] top-1 h-3 w-3 rounded-full ring-4 ring-white" :class="dot(h)"></span>
            <p class="text-sm font-semibold text-stone-900">{{ HISTORY_LABEL[h.status] || h.status }}</p>
            <p v-if="h.comment" class="text-sm text-stone-600">{{ h.comment }}</p>
            <p class="mt-0.5 text-xs text-stone-400">{{ dateTime(h.created_at) }}<span v-if="h.user"> · {{ h.user.name }}</span></p>
        </li>
    </ol>
</template>
