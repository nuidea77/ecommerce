<script setup>
import { ref, watch, computed } from 'vue';
import { money } from '../../lib/format';
const props = defineProps({ min: { type: Number, default: 0 }, max: { type: Number, default: 1000000 }, from: [Number, String], to: [Number, String] });
const emit = defineEmits(['change']);
const lo = ref(Number(props.from) || props.min); const hi = ref(Number(props.to) || props.max);
watch(() => [props.min, props.max, props.from, props.to], () => { lo.value = Number(props.from) || props.min; hi.value = Number(props.to) || props.max; });
const step = computed(() => Math.max(1000, Math.round((props.max - props.min) / 200 / 1000) * 1000));
const pct = (v) => ((v - props.min) / Math.max(1, props.max - props.min)) * 100;
function onLo(e) { lo.value = Math.min(Number(e.target.value), hi.value - step.value); }
function onHi(e) { hi.value = Math.max(Number(e.target.value), lo.value + step.value); }
function commit() { emit('change', { min: lo.value > props.min ? lo.value : '', max: hi.value < props.max ? hi.value : '' }); }
</script>
<template>
    <div class="px-1">
        <div class="mb-3 flex items-center justify-between text-xs font-semibold text-stone-700"><span>{{ money(lo) }}</span><span>{{ money(hi) }}</span></div>
        <div class="relative h-5">
            <div class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-cream-300"></div>
            <div class="absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-brand-600" :style="{ left: pct(lo) + '%', right: 100 - pct(hi) + '%' }"></div>
            <input type="range" :min="min" :max="max" :step="step" :value="lo" @input="onLo" @change="commit" class="range-thumb pointer-events-none absolute inset-0 w-full appearance-none bg-transparent" />
            <input type="range" :min="min" :max="max" :step="step" :value="hi" @input="onHi" @change="commit" class="range-thumb pointer-events-none absolute inset-0 w-full appearance-none bg-transparent" />
        </div>
        <div class="mt-3 flex items-center gap-2">
            <input v-model.number="lo" type="number" class="h-8 w-full rounded-lg border-0 bg-cream-100 px-2 text-xs ring-1 ring-cream-300 focus:ring-brand-500" @change="commit" />
            <span class="text-stone-400">–</span>
            <input v-model.number="hi" type="number" class="h-8 w-full rounded-lg border-0 bg-cream-100 px-2 text-xs ring-1 ring-cream-300 focus:ring-brand-500" @change="commit" />
        </div>
    </div>
</template>
<style scoped>
.range-thumb::-webkit-slider-thumb { pointer-events: auto; appearance: none; height: 16px; width: 16px; border-radius: 9999px; background: #fff; border: 3px solid #184d3b; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,.25); }
.range-thumb::-moz-range-thumb { pointer-events: auto; height: 16px; width: 16px; border-radius: 9999px; background: #fff; border: 3px solid #184d3b; cursor: pointer; }
</style>
