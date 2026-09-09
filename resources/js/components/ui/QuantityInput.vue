<script setup>
import { MinusIcon, PlusIcon } from '@heroicons/vue/20/solid';
const props = defineProps({ modelValue: { type: Number, default: 1 }, min: { type: Number, default: 1 }, max: { type: Number, default: 999 }, size: { type: String, default: 'md' } });
const emit = defineEmits(['update:modelValue']);
const set = (v) => emit('update:modelValue', Math.min(props.max, Math.max(props.min, Number(v) || props.min)));
</script>
<template>
    <div class="inline-flex items-center rounded-xl ring-1 ring-stone-200 bg-white" :class="size === 'sm' ? 'h-8' : 'h-11'">
        <button type="button" @click="set(modelValue - 1)" :disabled="modelValue <= min" class="flex h-full items-center px-2.5 text-stone-600 hover:text-brand-600 disabled:opacity-30"><MinusIcon class="h-4 w-4" /></button>
        <input type="number" :value="modelValue" @change="set($event.target.value)" class="w-10 border-0 bg-transparent p-0 text-center text-sm font-semibold focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" />
        <button type="button" @click="set(modelValue + 1)" :disabled="modelValue >= max" class="flex h-full items-center px-2.5 text-stone-600 hover:text-brand-600 disabled:opacity-30"><PlusIcon class="h-4 w-4" /></button>
    </div>
</template>
