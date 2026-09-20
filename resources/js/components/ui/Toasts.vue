<script setup>
import { useUiStore } from '../../stores/ui';
import { CheckCircleIcon, ExclamationCircleIcon, InformationCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const ui = useUiStore();
const icons = { success: CheckCircleIcon, error: ExclamationCircleIcon, info: InformationCircleIcon };
const colors = { success: 'text-emerald-500', error: 'text-red-500', info: 'text-sky-500' };
</script>

<template>
    <div aria-live="polite" class="pointer-events-none fixed inset-0 z-[100] flex flex-col items-end gap-2 px-4 py-6 sm:p-6">
        <transition-group name="fade">
            <div v-for="t in ui.toasts" :key="t.id" class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-paper p-4 shadow-lg ring-1 ring-stone-200">
                <component :is="icons[t.type] || icons.info" class="h-5 w-5 shrink-0" :class="colors[t.type] || colors.info" />
                <p class="flex-1 text-sm text-stone-800">{{ t.message }}</p>
                <button @click="ui.dismiss(t.id)" class="text-stone-400 hover:text-stone-600"><XMarkIcon class="h-4 w-4" /></button>
            </div>
        </transition-group>
    </div>
</template>
