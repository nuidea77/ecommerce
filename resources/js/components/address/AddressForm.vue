<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import api from '../../lib/api';
import { rules } from '../../lib/validate';

const props = defineProps({ modelValue: { type: Object, required: true }, errors: { type: Object, default: () => ({}) }, showLabel: { type: Boolean, default: true } });
const emit = defineEmits(['update:modelValue']);
const form = computed({ get: () => props.modelValue, set: (v) => emit('update:modelValue', v) });
const set = (k, v) => emit('update:modelValue', { ...props.modelValue, [k]: v });

const locations = ref([]);
onMounted(async () => { locations.value = (await api.get('/locations')).data.provinces; });

const province = computed(() => locations.value.find((p) => p.name === form.value.province));
const isCity = computed(() => province.value?.type === 'city');
const districts = computed(() => (isCity.value ? province.value.districts.map((d) => d.name) : province.value?.soums || []));
const khoroos = computed(() => { const d = province.value?.districts?.find((x) => x.name === form.value.district); return d ? Array.from({ length: d.khoroos }, (_, i) => String(i + 1)) : []; });

watch(() => form.value.province, () => emit('update:modelValue', { ...props.modelValue, district: '', khoroo: '' }));
watch(() => form.value.district, (v, old) => { if (old !== undefined && isCity.value) emit('update:modelValue', { ...props.modelValue, khoroo: '' }); });

const touched = ref({});
const err = (f) => props.errors[f]?.[0] || (touched.value[f] ? (f === 'recipient_name' ? rules.name(form.value.recipient_name) : f === 'phone' ? rules.phone(form.value.phone) : '') : '');
const labels = ['Салон', 'Гэр', 'Ажил', 'Бусад'];
</script>
<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div v-if="showLabel" class="sm:col-span-2">
            <label class="label">Хаягийн нэр</label>
            <div class="flex flex-wrap gap-2"><button v-for="l in labels" :key="l" type="button" @click="set('label', l)" class="rounded-full px-3 py-1.5 text-sm ring-1 transition" :class="form.label === l ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white ring-stone-200 hover:ring-brand-400'">{{ l }}</button></div>
        </div>
        <div><label class="label">Хүлээн авагчийн нэр *</label><input :value="form.recipient_name" @input="set('recipient_name', $event.target.value)" @blur="touched.recipient_name = true" class="input" :class="err('recipient_name') && 'ring-red-400'" /><p v-if="err('recipient_name')" class="mt-1 text-xs text-red-600">{{ err('recipient_name') }}</p></div>
        <div><label class="label">Утас *</label><input :value="form.phone" @input="set('phone', $event.target.value)" @blur="touched.phone = true" class="input font-mono" inputmode="tel" placeholder="99001122" :class="err('phone') && 'ring-red-400'" /><p v-if="err('phone')" class="mt-1 text-xs text-red-600">{{ err('phone') }}</p></div>
        <div>
            <label class="label">Хот / Аймаг *</label>
            <select :value="form.province" @change="set('province', $event.target.value)" class="input"><option value="" disabled>Сонгох</option><optgroup label="Хот"><option v-for="p in locations.filter((x) => x.type === 'city')" :key="p.name" :value="p.name">{{ p.name }}</option></optgroup><optgroup label="Аймаг"><option v-for="p in locations.filter((x) => x.type === 'aimag')" :key="p.name" :value="p.name">{{ p.name }}</option></optgroup></select>
        </div>
        <div>
            <label class="label">{{ isCity ? 'Дүүрэг' : 'Сум' }} *</label>
            <select :value="form.district" @change="set('district', $event.target.value)" class="input" :disabled="!form.province"><option value="" disabled>{{ form.province ? 'Сонгох' : 'Эхлээд хот/аймаг сонгоно' }}</option><option v-for="d in districts" :key="d" :value="d">{{ d }}</option></select>
            <p v-if="errors.district" class="mt-1 text-xs text-red-600">{{ errors.district[0] }}</p>
        </div>
        <div>
            <label class="label">{{ isCity ? 'Хороо' : 'Баг' }}</label>
            <select v-if="isCity" :value="form.khoroo" @change="set('khoroo', $event.target.value)" class="input" :disabled="!form.district"><option value="">Сонгох</option><option v-for="k in khoroos" :key="k" :value="k">{{ k }}-р хороо</option></select>
            <input v-else :value="form.khoroo" @input="set('khoroo', $event.target.value)" class="input" placeholder="Багийн нэр (заавал биш)" :disabled="!form.province" />
        </div>
        <div class="sm:col-span-2"><label class="label">Дэлгэрэнгүй хаяг *</label><textarea :value="form.address" @input="set('address', $event.target.value)" rows="2" class="input" placeholder="Гудамж, байр, орц, тоот, салоны нэр, орох заавар"></textarea><p v-if="errors.address" class="mt-1 text-xs text-red-600">{{ errors.address[0] }}</p></div>
    </div>
</template>
