<script setup>
import { ref, computed } from 'vue';
import api, { errorMessage } from '../../lib/api';
import { useUiStore } from '../../stores/ui';
import { CloudArrowUpIcon, XMarkIcon, StarIcon, LinkIcon, ArrowsPointingOutIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ modelValue: { type: Array, default: () => [] }, multiple: { type: Boolean, default: true }, compact: Boolean });
const emit = defineEmits(['update:modelValue']);
const ui = useUiStore();
const images = computed(() => props.modelValue || []);
const set = (arr) => emit('update:modelValue', arr);

const fileInput = ref(null);
const dragging = ref(false);
const uploading = ref(false);
const progress = ref(0);
const url = ref('');
const dragIndex = ref(null);

const ACCEPT = ['image/jpeg', 'image/png', 'image/webp'];

async function upload(fileList) {
    const files = [...fileList].filter((f) => ACCEPT.includes(f.type));
    if (!files.length) return ui.toast('Зөвхөн JPG, PNG, WEBP зураг хуулна', 'error');
    if (files.some((f) => f.size > 6 * 1024 * 1024)) return ui.toast('Зураг 6MB-аас бага байх ёстой', 'error');
    const chosen = props.multiple ? files.slice(0, 12) : files.slice(0, 1);
    uploading.value = true; progress.value = 0;
    try {
        const fd = new FormData();
        chosen.forEach((f) => fd.append('images[]', f));
        const { data } = await api.post('/admin/products/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' }, onUploadProgress: (e) => (progress.value = e.total ? Math.round((e.loaded / e.total) * 100) : 0) });
        set(props.multiple ? [...images.value, ...data.urls] : [data.urls[0]]);
        ui.toast(`${data.urls.length} зураг хуулагдлаа`);
    } catch (e) { ui.toast(errorMessage(e), 'error'); }
    finally { uploading.value = false; }
}
function onDrop(e) {
    dragging.value = false;
    if (dragIndex.value !== null) return; // internal reorder, not a file drop
    if (e.dataTransfer?.files?.length) return upload(e.dataTransfer.files);
    const text = e.dataTransfer?.getData('text/uri-list') || e.dataTransfer?.getData('text/plain');
    if (text && /^https?:\/\//.test(text)) addUrl(text);
}
function addUrl(v = url.value) {
    const u = String(v || '').trim();
    if (!/^(https?:\/\/|\/)/.test(u)) return ui.toast('Зургийн URL буруу байна', 'error');
    set(props.multiple ? [...images.value, u] : [u]);
    url.value = '';
}
function remove(i) { set(images.value.filter((_, idx) => idx !== i)); }
function makeMain(i) { const arr = [...images.value]; const [x] = arr.splice(i, 1); set([x, ...arr]); }
function onDragStart(i) { dragIndex.value = i; }
function onDropOn(i) {
    if (dragIndex.value === null || dragIndex.value === i) { dragIndex.value = null; return; }
    const arr = [...images.value]; const [x] = arr.splice(dragIndex.value, 1); arr.splice(i, 0, x); set(arr); dragIndex.value = null;
}
</script>

<template>
    <div>
        <div
            @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop"
            @click="fileInput.click()"
            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed text-center transition"
            :class="[dragging ? 'border-brand-600 bg-brand-50' : 'border-stone-300 bg-cream-100/50 hover:border-brand-400 hover:bg-brand-50/40', compact ? 'px-3 py-4' : 'px-4 py-8']"
        >
            <CloudArrowUpIcon class="h-8 w-8 text-brand-700" />
            <p class="mt-2 text-sm font-medium text-stone-800">{{ dragging ? 'Энд тавина уу' : 'Зургаа энд чирж тавих эсвэл дарж сонгох' }}</p>
            <p v-if="!compact" class="mt-1 text-xs text-stone-500">JPG, PNG, WEBP · тус бүр 6MB хүртэл{{ multiple ? ' · нэг дор 12 хүртэл' : '' }}</p>
            <div v-if="uploading" class="mt-3 h-1.5 w-48 overflow-hidden rounded-full bg-stone-200"><div class="h-full bg-brand-600 transition-all" :style="{ width: progress + '%' }"></div></div>
        </div>
        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" :multiple="multiple" class="hidden" @change="upload($event.target.files); $event.target.value = ''" />

        <div class="mt-2 flex gap-2">
            <div class="relative flex-1"><LinkIcon class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-stone-400" /><input v-model="url" class="input py-2 pl-8 text-xs" placeholder="эсвэл зургийн URL буулгаад Enter" @keydown.enter.prevent="addUrl()" /></div>
            <button type="button" @click="addUrl()" class="btn-secondary btn-sm">Нэмэх</button>
        </div>

        <div v-if="images.length" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
            <div v-for="(img, i) in images" :key="img + i" draggable="true" @dragstart="onDragStart(i)" @dragover.prevent @drop.prevent.stop="onDropOn(i)" @dragend="dragIndex = null"
                class="group relative aspect-square cursor-grab overflow-hidden rounded-xl bg-white ring-1 ring-stone-200 active:cursor-grabbing" :class="dragIndex === i && 'opacity-40'">
                <img :src="img" class="h-full w-full object-contain p-1" alt="" />
                <span v-if="i === 0 && multiple" class="absolute bottom-1 left-1 rounded bg-brand-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">Үндсэн</span>
                <div class="absolute inset-x-0 top-0 flex justify-end gap-1 p-1 opacity-0 transition group-hover:opacity-100">
                    <button v-if="multiple && i !== 0" type="button" @click.stop="makeMain(i)" title="Үндсэн болгох" class="rounded-md bg-white/90 p-1 text-gold-600 shadow hover:bg-white"><StarIcon class="h-4 w-4" /></button>
                    <button type="button" @click.stop="remove(i)" title="Устгах" class="rounded-md bg-white/90 p-1 text-red-600 shadow hover:bg-white"><XMarkIcon class="h-4 w-4" /></button>
                </div>
                <ArrowsPointingOutIcon v-if="multiple" class="absolute bottom-1 right-1 h-3.5 w-3.5 text-stone-400 opacity-0 group-hover:opacity-100" />
            </div>
        </div>
        <p v-if="images.length > 1" class="mt-1.5 text-[11px] text-stone-400">Зургийг чирж дарааллыг нь солино. Эхний зураг үндсэн зураг болно.</p>
    </div>
</template>
