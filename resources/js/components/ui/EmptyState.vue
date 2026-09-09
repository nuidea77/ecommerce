<script setup>
defineProps({ title: { type: String, default: 'Хоосон байна' }, description: String, icon: { type: String, default: '🛍️' } });
</script>
<template>
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-16 text-center">
        <div class="mb-3 text-5xl">{{ icon }}</div>
        <h3 class="text-lg font-semibold text-stone-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-stone-500">{{ description }}</p>
        <div class="mt-5"><slot /></div>
    </div>
</template>
