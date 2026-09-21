<script setup>
import { reactive, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useCartStore } from '../../stores/cart';
import { useUiStore } from '../../stores/ui';
import { errorMessage } from '../../lib/api';
import { rules, passwordStrength } from '../../lib/validate';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const auth = useAuthStore(); const cart = useCartStore(); const ui = useUiStore();
const route = useRoute(); const router = useRouter();
const form = reactive({ name: '', phone: '', password: '', password_confirmation: '' });
const touched = reactive({});
const loading = ref(false); const serverErrors = ref({}); const showPw = ref(false);

const errors = computed(() => ({
    name: rules.name(form.name), phone: rules.phone(form.phone), password: rules.password(form.password), password_confirmation: rules.confirm(form.password_confirmation, form.password),
}));
const show = (f) => (touched[f] ? serverErrors.value[f]?.[0] || errors.value[f] : '');
const valid = computed(() => Object.values(errors.value).every((e) => !e));
const strength = computed(() => passwordStrength(form.password));
const strengthLabel = ['', 'Сул', 'Дунд', 'Сайн', 'Маш сайн'];
const strengthColor = ['', 'bg-red-500', 'bg-amber-500', 'bg-brand-500', 'bg-emerald-600'];

async function submit() {
    Object.keys(form).forEach((k) => (touched[k] = true));
    if (!valid.value) return;
    loading.value = true; serverErrors.value = {};
    try {
        await auth.register(form);
        await cart.fetch();
        ui.toast('Бүртгэл үүслээ. Одоо дугаараа баталгаажуулна уу.');
        router.push({ name: 'verify', query: route.query.redirect ? { redirect: route.query.redirect } : {} });
    } catch (e) { serverErrors.value = e.response?.data?.errors || {}; ui.toast(errorMessage(e), 'error'); }
    finally { loading.value = false; }
}
</script>
<template>
    <div class="container-x flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-md">
            <div class="text-center"><img :src="'/images/logo.png'" alt="Чанар Есүй" class="mx-auto mb-6 h-14 w-auto" /><h1 class="font-display text-3xl font-bold">Бүртгүүлэх</h1><p class="mt-2 text-sm text-stone-500">Бүртгэлтэй юу? <router-link :to="{ name: 'login', query: route.query }" class="font-medium text-brand-700 hover:underline">Нэвтрэх</router-link></p></div>
            <form @submit.prevent="submit" novalidate class="card mt-8 space-y-4 p-6 sm:p-8">
                <div>
                    <label class="label">Нэр / Салоны нэр</label>
                    <input v-model.trim="form.name" @blur="touched.name = true" class="input" :class="show('name') && 'ring-red-400'" autocomplete="name" placeholder="Сарнай" />
                    <p v-if="show('name')" class="mt-1 text-xs text-red-600">{{ show('name') }}</p>
                </div>
                <div>
                    <label class="label">Утасны дугаар</label>
                    <div class="flex"><span class="flex items-center rounded-l-xl bg-cream-100 px-3 text-sm text-stone-500 ring-1 ring-inset ring-cream-300">+976</span><input v-model.trim="form.phone" @blur="touched.phone = true" class="input rounded-l-none font-mono tracking-wider" :class="show('phone') && 'ring-red-400'" inputmode="tel" autocomplete="tel-national" maxlength="16" placeholder="99001122" /></div>
                    <p v-if="show('phone')" class="mt-1 text-xs text-red-600">{{ show('phone') }}</p>
                    <p v-else class="mt-1 text-xs text-stone-400">Энэ дугаараар нэвтэрч, SMS-ээр баталгаажуулна.</p>
                </div>
                <div>
                    <label class="label">Нууц үг</label>
                    <div class="relative"><input v-model="form.password" @blur="touched.password = true" :type="showPw ? 'text' : 'password'" class="input pr-10" :class="show('password') && 'ring-red-400'" autocomplete="new-password" /><button type="button" @click="showPw = !showPw" class="absolute right-3 top-2.5 text-stone-400 hover:text-stone-600"><component :is="showPw ? EyeSlashIcon : EyeIcon" class="h-5 w-5" /></button></div>
                    <div v-if="form.password" class="mt-2 flex items-center gap-2"><div class="flex flex-1 gap-1"><span v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full" :class="i <= strength ? strengthColor[strength] : 'bg-stone-200'"></span></div><span class="text-[11px] text-stone-500">{{ strengthLabel[strength] }}</span></div>
                    <p v-if="show('password')" class="mt-1 text-xs text-red-600">{{ show('password') }}</p>
                    <p v-else class="mt-1 text-xs text-stone-400">Хамгийн багадаа 8 тэмдэгт, үсэг ба тоо агуулна.</p>
                </div>
                <div>
                    <label class="label">Нууц үг давтах</label>
                    <input v-model="form.password_confirmation" @blur="touched.password_confirmation = true" :type="showPw ? 'text' : 'password'" class="input" :class="show('password_confirmation') && 'ring-red-400'" autocomplete="new-password" />
                    <p v-if="show('password_confirmation')" class="mt-1 text-xs text-red-600">{{ show('password_confirmation') }}</p>
                </div>
                <button :disabled="loading" class="btn-brand w-full py-3">{{ loading ? 'Үүсгэж байна...' : 'Бүртгүүлэх' }}</button>
                <p class="text-center text-xs text-stone-400">Бүртгүүлсний дараа утасны дугаараа SMS-ээр баталгаажуулах алхам гарна.</p>
            </form>
        </div>
    </div>
</template>
