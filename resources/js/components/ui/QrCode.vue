<script setup>
import { ref, watch, onMounted } from 'vue';
import QRCode from 'qrcode';
const props = defineProps({ text: String, image: String, size: { type: Number, default: 220 } });
const canvas = ref(null);
const render = async () => {
    if (!canvas.value || !props.text || props.image) return;
    await QRCode.toCanvas(canvas.value, props.text, { width: props.size, margin: 1, color: { dark: '#1c1917', light: '#ffffff' } });
};
onMounted(render);
watch(() => props.text, render);
</script>
<template>
    <img v-if="image" :src="image.startsWith('data:') ? image : `data:image/png;base64,${image}`" :width="size" :height="size" class="rounded-lg" alt="QPay QR" />
    <canvas v-else ref="canvas" class="rounded-lg"></canvas>
</template>
