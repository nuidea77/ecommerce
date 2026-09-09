<script setup>
import { useCartStore } from '../stores/cart';
import { useUiStore } from '../stores/ui';
import { money } from '../lib/format';
import { errorMessage } from '../lib/api';
import QuantityInput from '../components/ui/QuantityInput.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';

const cart = useCartStore();
const ui = useUiStore();
async function setQty(item, q) { try { await cart.update(item.id, q); } catch (e) { ui.toast(errorMessage(e), 'error'); } }
</script>

<template>
    <div class="container-x py-8">
        <h1 class="font-display text-3xl font-bold">Сагс</h1>
        <EmptyState v-if="cart.loaded && !cart.items.length" class="mt-6" title="Сагс хоосон байна" description="Дэлгүүрээс бараагаа сонгоод сагсандаа нэмнэ үү." icon="🛒"><router-link :to="{ name: 'shop' }" class="btn-brand">Дэлгүүр үзэх</router-link></EmptyState>
        <div v-else class="mt-6 grid gap-8 lg:grid-cols-[1fr_360px]">
            <div class="card divide-y divide-stone-100">
                <div v-for="item in cart.items" :key="item.id" class="flex gap-4 p-4 sm:p-5">
                    <img :src="item.image" class="h-24 w-24 shrink-0 rounded-xl bg-stone-100 object-cover" :alt="item.name" />
                    <div class="flex flex-1 flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <router-link :to="{ name: 'product', params: { slug: item.slug } }" class="font-semibold hover:text-brand-700">{{ item.name }}</router-link>
                                <p class="mt-0.5 text-sm text-stone-500">{{ item.variant_label }}</p>
                                <span v-if="item.is_backorder" class="badge mt-1 bg-amber-100 text-amber-800">Урьдчилсан захиалга · ~{{ item.backorder_days }} хоног</span>
                                <span v-else class="badge mt-1 bg-emerald-100 text-emerald-800">Бэлэн</span>
                            </div>
                            <button @click="cart.remove(item.id)" class="text-stone-400 hover:text-red-500"><TrashIcon class="h-5 w-5" /></button>
                        </div>
                        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-3">
                            <QuantityInput :model-value="item.quantity" @update:model-value="setQty(item, $event)" />
                            <div class="text-right"><p class="font-semibold">{{ money(item.line_total) }}</p><p class="text-xs text-stone-400">{{ money(item.price) }} × {{ item.quantity }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="card h-fit p-6">
                <h2 class="text-lg font-semibold">Захиалгын дүн</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-500">Барааны дүн</dt><dd>{{ money(cart.subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-stone-500">Хүргэлт</dt><dd>{{ cart.shipping_fee ? money(cart.shipping_fee) : 'Үнэгүй' }}</dd></div>
                    <div v-if="cart.shipping_fee" class="rounded-lg bg-brand-50 p-2 text-xs text-brand-800">{{ money(cart.free_shipping_threshold - cart.subtotal) }} нэмж захиалбал хүргэлт үнэгүй!</div>
                    <div class="flex justify-between border-t border-stone-200 pt-3 text-base font-bold"><dt>Нийт</dt><dd>{{ money(cart.total) }}</dd></div>
                </dl>
                <p v-if="cart.has_backorder" class="mt-3 text-xs text-amber-700">⏳ Сагсанд урьдчилсан захиалгын бараа байна. Хүргэлт бүх бараа бэлэн болсны дараа хийгдэнэ.</p>
                <router-link :to="{ name: 'checkout' }" class="btn-brand mt-5 w-full py-3">Захиалга хийх</router-link>
                <router-link :to="{ name: 'shop' }" class="btn-ghost mt-2 w-full">Худалдан авалт үргэлжлүүлэх</router-link>
            </aside>
        </div>
    </div>
</template>
