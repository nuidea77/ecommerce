<script setup>
import { reactive, ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api, { errorMessage } from '../lib/api';
import { money } from '../lib/format';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import { RadioGroup, RadioGroupOption } from '@headlessui/vue';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';

const router = useRouter();
const cart = useCartStore();
const auth = useAuthStore();
const ui = useUiStore();

const form = reactive({
    shipping_name: auth.user?.name || '', shipping_phone: auth.user?.phone || '', shipping_city: auth.user?.city || 'Улаанбаатар',
    shipping_district: '', shipping_address: auth.user?.address || '', note: '', payment_method: 'qpay',
});
const errors = ref({});
const submitting = ref(false);
const cities = computed(() => ui.config?.cities || ['Улаанбаатар']);
const districts = computed(() => ui.config?.districts || []);

const methods = [
    { value: 'qpay', title: 'QPay', text: 'Банкны аппаар QR код уншуулж шууд төлнө', icon: '📱' },
    { value: 'cash', title: 'Бэлнээр хүргэлт дээр', text: 'Барааг хүлээн авахдаа хүргэлтийн ажилтанд төлнө', icon: '💵' },
];

onMounted(() => { cart.fetch(); ui.loadConfig(); });

async function submit() {
    errors.value = {};
    submitting.value = true;
    try {
        const { data } = await api.post('/orders', form);
        await cart.fetch();
        ui.toast(`Захиалга ${data.order.order_number} үүслээ`);
        router.push(data.order.payment_method === 'qpay' ? { name: 'pay', params: { number: data.order.order_number } } : { name: 'order', params: { number: data.order.order_number }, query: { created: 1 } });
    } catch (e) {
        if (e.response?.data?.verification_required) return router.push({ name: 'verify', query: { redirect: '/checkout' } });
        errors.value = e.response?.data?.errors || {};
        ui.toast(errorMessage(e), 'error');
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="container-x py-8">
        <h1 class="font-display text-3xl font-bold">Захиалга хийх</h1>
        <form @submit.prevent="submit" class="mt-6 grid gap-8 lg:grid-cols-[1fr_380px]">
            <div class="space-y-6">
                <section class="card p-6">
                    <h2 class="text-lg font-semibold">1. Хүргэлтийн мэдээлэл</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div><label class="label">Хүлээн авагчийн нэр *</label><input v-model="form.shipping_name" class="input" required /><p v-if="errors.shipping_name" class="mt-1 text-xs text-red-600">{{ errors.shipping_name[0] }}</p></div>
                        <div><label class="label">Утас *</label><input v-model="form.shipping_phone" class="input" required placeholder="99001122" /><p v-if="errors.shipping_phone" class="mt-1 text-xs text-red-600">{{ errors.shipping_phone[0] }}</p></div>
                        <div><label class="label">Хот / Аймаг *</label><select v-model="form.shipping_city" class="input"><option v-for="c in cities" :key="c">{{ c }}</option></select></div>
                        <div v-if="form.shipping_city === 'Улаанбаатар'"><label class="label">Дүүрэг</label><select v-model="form.shipping_district" class="input"><option value="">Сонгох</option><option v-for="d in districts" :key="d">{{ d }}</option></select></div>
                        <div class="sm:col-span-2"><label class="label">Дэлгэрэнгүй хаяг *</label><textarea v-model="form.shipping_address" rows="2" class="input" required placeholder="Хороо, гудамж, байр, орц, тоот, салоны нэр"></textarea><p v-if="errors.shipping_address" class="mt-1 text-xs text-red-600">{{ errors.shipping_address[0] }}</p></div>
                        <div class="sm:col-span-2"><label class="label">Нэмэлт тэмдэглэл</label><textarea v-model="form.note" rows="2" class="input" placeholder="Хүргэлтийн цаг, тусгай заавар..."></textarea></div>
                    </div>
                </section>

                <section class="card p-6">
                    <h2 class="text-lg font-semibold">2. Төлбөрийн хэлбэр</h2>
                    <RadioGroup v-model="form.payment_method" class="mt-4 grid gap-3 sm:grid-cols-2">
                        <RadioGroupOption v-for="m in methods" :key="m.value" :value="m.value" v-slot="{ checked }" as="template">
                            <div class="relative cursor-pointer rounded-2xl p-4 ring-1 transition" :class="checked ? 'bg-brand-50 ring-2 ring-brand-600' : 'bg-white ring-stone-200 hover:ring-stone-400'">
                                <div class="flex items-start gap-3"><span class="text-2xl">{{ m.icon }}</span><div><p class="font-semibold">{{ m.title }}</p><p class="text-xs text-stone-500">{{ m.text }}</p></div></div>
                                <CheckCircleIcon v-if="checked" class="absolute right-3 top-3 h-5 w-5 text-brand-600" />
                            </div>
                        </RadioGroupOption>
                    </RadioGroup>
                </section>
            </div>

            <aside class="card h-fit p-6">
                <h2 class="text-lg font-semibold">Таны захиалга</h2>
                <ul class="mt-4 divide-y divide-stone-100">
                    <li v-for="item in cart.items" :key="item.id" class="flex gap-3 py-3">
                        <img :src="item.image" class="h-14 w-14 rounded-lg bg-stone-100 object-cover" alt="" />
                        <div class="flex-1 text-sm"><p class="font-medium leading-tight">{{ item.name }}</p><p class="text-xs text-stone-500">{{ item.variant_label }} × {{ item.quantity }}</p><span v-if="item.is_backorder" class="text-xs font-medium text-amber-600">Урьдчилсан захиалга</span></div>
                        <p class="text-sm font-semibold">{{ money(item.line_total) }}</p>
                    </li>
                </ul>
                <dl class="mt-3 space-y-2 border-t border-stone-200 pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-500">Барааны дүн</dt><dd>{{ money(cart.subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-stone-500">Хүргэлт</dt><dd>{{ cart.shipping_fee ? money(cart.shipping_fee) : 'Үнэгүй' }}</dd></div>
                    <div class="flex justify-between border-t border-stone-200 pt-3 text-base font-bold"><dt>Нийт төлөх</dt><dd>{{ money(cart.total) }}</dd></div>
                </dl>
                <p v-if="errors.cart" class="mt-3 text-sm text-red-600">{{ errors.cart[0] }}</p>
                <button type="submit" :disabled="submitting || !cart.items.length" class="btn-brand mt-5 w-full py-3 text-base">{{ submitting ? 'Үүсгэж байна...' : form.payment_method === 'qpay' ? 'QPay-ээр төлөх' : 'Захиалга баталгаажуулах' }}</button>
                <p class="mt-3 text-center text-xs text-stone-400">Захиалга өгснөөр үйлчилгээний нөхцөлийг зөвшөөрч байна.</p>
            </aside>
        </form>
    </div>
</template>
