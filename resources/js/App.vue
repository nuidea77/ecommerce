<script setup>
import { onMounted } from 'vue';
import { useUiStore } from './stores/ui';
import Toasts from './components/ui/Toasts.vue';

const ui = useUiStore();
onMounted(() => ui.loadConfig());
</script>

<template>
    <router-view />
    <Toasts />
</template>
