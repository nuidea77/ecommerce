<script setup>
import { computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';
const props = defineProps({ meta: Object });
const emit = defineEmits(['change']);
const pages = computed(() => {
    const total = props.meta?.last_page || 1;
    const cur = props.meta?.current_page || 1;
    const out = [];
    for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) out.push(i);
    return out;
});
</script>
<template>
    <nav v-if="meta && meta.last_page > 1" class="flex items-center justify-between border-t border-stone-200 pt-4">
        <p class="text-sm text-stone-500">Нийт <span class="font-medium text-stone-800">{{ meta.total }}</span> үр дүн</p>
        <div class="flex items-center gap-1">
            <button class="btn-ghost btn-sm" :disabled="meta.current_page <= 1" @click="emit('change', meta.current_page - 1)"><ChevronLeftIcon class="h-4 w-4" /></button>
            <button v-for="p in pages" :key="p" @click="emit('change', p)" class="h-8 w-8 rounded-lg text-sm font-medium" :class="p === meta.current_page ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100'">{{ p }}</button>
            <button class="btn-ghost btn-sm" :disabled="meta.current_page >= meta.last_page" @click="emit('change', meta.current_page + 1)"><ChevronRightIcon class="h-4 w-4" /></button>
        </div>
    </nav>
</template>
