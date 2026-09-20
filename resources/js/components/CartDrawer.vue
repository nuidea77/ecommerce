<script setup>
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import { money } from '../lib/format';
import QuantityInput from './ui/QuantityInput.vue';
import { errorMessage } from '../lib/api';

const cart = useCartStore();
const ui = useUiStore();

async function setQty(item, q) {
    try { await cart.update(item.id, q); } catch (e) { ui.toast(errorMessage(e), 'error'); }
}
</script>

<template>
    <TransitionRoot :show="ui.cartOpen" as="template">
        <Dialog class="relative z-50" @close="ui.cartOpen = false">
            <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-stone-900/40 backdrop-blur-sm" />
            </TransitionChild>
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <TransitionChild as="template" enter="transform transition ease-in-out duration-300" enter-from="translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-300" leave-from="translate-x-0" leave-to="translate-x-full">
                            <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                <div class="flex h-full flex-col bg-paper shadow-xl">
                                    <div class="flex items-center justify-between border-b border-stone-200 px-5 py-4">
                                        <h2 class="text-lg font-semibold">Сагс <span class="text-sm font-normal text-stone-500">({{ cart.count }})</span></h2>
                                        <button @click="ui.cartOpen = false" class="rounded-lg p-1 text-stone-400 hover:bg-cream-200/60"><XMarkIcon class="h-5 w-5" /></button>
                                    </div>
                                    <div class="flex-1 overflow-y-auto px-5 py-4">
                                        <div v-if="!cart.items.length" class="flex h-full flex-col items-center justify-center text-center">
                                            <div class="text-5xl">🛒</div>
                                            <p class="mt-3 font-medium">Сагс хоосон байна</p>
                                            <router-link :to="{ name: 'shop' }" @click="ui.cartOpen = false" class="btn-brand mt-4">Дэлгүүр үзэх</router-link>
                                        </div>
                                        <ul v-else class="divide-y divide-stone-100">
                                            <li v-for="item in cart.items" :key="item.id" class="flex gap-4 py-4">
                                                <img :src="item.image" :alt="item.name" class="h-20 w-20 shrink-0 rounded-xl bg-cream-200/60 object-cover" />
                                                <div class="flex flex-1 flex-col">
                                                    <div class="flex justify-between gap-2">
                                                        <router-link :to="{ name: 'product', params: { slug: item.slug } }" @click="ui.cartOpen = false" class="text-sm font-semibold leading-tight hover:text-brand-700">{{ item.name }}</router-link>
                                                        <button @click="cart.remove(item.id)" class="text-stone-400 hover:text-red-500"><TrashIcon class="h-4 w-4" /></button>
                                                    </div>
                                                    <p class="mt-0.5 text-xs text-stone-500">{{ item.variant_label }}</p>
                                                    <p v-if="item.is_backorder" class="mt-0.5 text-xs font-medium text-amber-600">Урьдчилсан захиалга · ~{{ item.backorder_days }} хоног</p>
                                                    <div class="mt-auto flex items-center justify-between pt-2">
                                                        <QuantityInput :model-value="item.quantity" @update:model-value="setQty(item, $event)" size="sm" />
                                                        <span class="text-sm font-semibold">{{ money(item.line_total) }}</span>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div v-if="cart.items.length" class="border-t border-stone-200 px-5 py-4">
                                        <div class="flex justify-between text-sm text-stone-600"><span>Барааны дүн</span><span>{{ money(cart.subtotal) }}</span></div>
                                        <div class="mt-1 flex justify-between text-sm text-stone-600"><span>Хүргэлт</span><span>{{ cart.shipping_fee ? money(cart.shipping_fee) : 'Үнэгүй' }}</span></div>
                                        <div class="mt-2 flex justify-between text-base font-bold"><span>Нийт</span><span>{{ money(cart.total) }}</span></div>
                                        <router-link :to="{ name: 'checkout' }" @click="ui.cartOpen = false" class="btn-brand mt-4 w-full">Захиалга хийх</router-link>
                                        <router-link :to="{ name: 'cart' }" @click="ui.cartOpen = false" class="btn-ghost mt-2 w-full">Сагс дэлгэрэнгүй</router-link>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
