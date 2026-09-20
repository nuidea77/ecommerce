<script setup>
import { ref } from 'vue';
import { MinusIcon, PlusIcon } from '@heroicons/vue/20/solid';
defineProps({ title: String, count: Number });
const open = ref(true);
</script>
<template>
    <div class="border-b border-cream-300/60 py-3 last:border-0">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
            <span class="text-sm font-semibold text-stone-900">{{ title }} <span v-if="count" class="ml-1 rounded-full bg-brand-700 px-1.5 text-[10px] font-bold text-white">{{ count }}</span></span>
            <component :is="open ? MinusIcon : PlusIcon" class="h-4 w-4 text-stone-400" />
        </button>
        <div v-show="open" class="mt-2.5"><slot /></div>
    </div>
</template>
