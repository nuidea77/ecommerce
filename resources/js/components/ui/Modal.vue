<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
defineProps({ open: Boolean, title: String, size: { type: String, default: 'max-w-lg' } });
const emit = defineEmits(['close']);
</script>
<template>
    <TransitionRoot :show="open" as="template">
        <Dialog class="relative z-50" @close="emit('close')">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-stone-900/50 backdrop-blur-sm" />
            </TransitionChild>
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
                    <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:scale-95">
                        <DialogPanel class="w-full rounded-2xl bg-paper p-6 shadow-xl" :class="size">
                            <div class="mb-4 flex items-start justify-between">
                                <DialogTitle class="text-lg font-semibold text-stone-900">{{ title }}</DialogTitle>
                                <button @click="emit('close')" class="rounded-lg p-1 text-stone-400 hover:bg-cream-200/60 hover:text-stone-600"><XMarkIcon class="h-5 w-5" /></button>
                            </div>
                            <slot />
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
