<script setup>
import { ref, computed } from 'vue';
const props = defineProps({ items: { type: Array, default: () => [] }, modelValue: { type: Array, default: () => [] }, valueKey: { type: String, default: 'value' }, labelKey: { type: String, default: 'label' }, searchable: Boolean, limit: { type: Number, default: 8 } });
const emit = defineEmits(['update:modelValue']);
const q = ref(''); const showAll = ref(false);
const filtered = computed(() => {
    const list = props.items.filter((i) => !q.value || String(i[props.labelKey]).toLowerCase().includes(q.value.toLowerCase()));
    return showAll.value || q.value ? list : list.slice(0, props.limit);
});
const toggle = (v) => emit('update:modelValue', props.modelValue.includes(v) ? props.modelValue.filter((x) => x !== v) : [...props.modelValue, v]);
</script>
<template>
    <div>
        <input v-if="searchable" v-model="q" type="search" placeholder="Хайх..." class="mb-2 h-8 w-full rounded-lg border-0 bg-cream-100 px-2.5 text-xs ring-1 ring-cream-300 focus:ring-brand-500" />
        <ul class="space-y-1">
            <li v-for="i in filtered" :key="i[valueKey]">
                <label class="flex cursor-pointer items-center gap-2 rounded-md px-1 py-0.5 text-[13px] hover:bg-cream-100" :class="!i.count && !modelValue.includes(i[valueKey]) && 'opacity-40'">
                    <input type="checkbox" :checked="modelValue.includes(i[valueKey])" @change="toggle(i[valueKey])" class="h-3.5 w-3.5 rounded border-stone-300 text-brand-700 focus:ring-brand-500" />
                    <span class="flex-1 truncate text-stone-700">{{ i[labelKey] }}</span>
                    <span class="text-[11px] tabular-nums text-stone-400">{{ i.count ?? '' }}</span>
                </label>
            </li>
        </ul>
        <button v-if="!q && items.length > limit" type="button" @click="showAll = !showAll" class="mt-1.5 text-xs font-medium text-brand-700 hover:underline">{{ showAll ? 'Хураах' : `Бүгд (${items.length})` }}</button>
    </div>
</template>
