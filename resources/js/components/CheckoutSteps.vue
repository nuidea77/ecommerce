<script setup>
import { CheckIcon } from '@heroicons/vue/20/solid';
defineProps({ current: { type: Number, required: true } });
const steps = ['Миний сагс', 'Захиалгын хаяг', 'Баталгаажуулах', 'Захиалгын мэдээлэл'];
</script>
<template>
    <ol class="mb-8 flex items-center">
        <li v-for="(s, i) in steps" :key="s" class="flex flex-1 items-center last:flex-none">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-2 transition" :class="i + 1 < current ? 'bg-brand-700 text-white ring-brand-700' : i + 1 === current ? 'bg-white text-brand-700 ring-brand-700' : 'bg-white text-stone-400 ring-stone-200'"><CheckIcon v-if="i + 1 < current" class="h-4 w-4" /><template v-else>{{ i + 1 }}</template></span>
                <span class="hidden text-sm font-medium sm:block" :class="i + 1 <= current ? 'text-stone-900' : 'text-stone-400'">{{ s }}</span>
            </div>
            <div v-if="i < steps.length - 1" class="mx-3 h-px flex-1" :class="i + 1 < current ? 'bg-brand-700' : 'bg-stone-200'"></div>
        </li>
    </ol>
</template>
