<script setup>
import { reactive, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useCartStore } from '../../stores/cart';
import { useUiStore } from '../../stores/ui';
import { errorMessage } from '../../lib/api';
import { rules } from '../../lib/validate';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const auth = useAuthStore(); const cart = useCartStore(); const ui = useUiStore();
const route = useRoute(); const router = useRouter();
const form = reactive({ phone: '', password: '', remember: true });
const touched = reactive({});
const loading = ref(false); const error = ref(''); const showPw = ref(false);
const phoneError = computed(() => (touched.phone ? rules.phone(form.phone) : ''));

const demo = [{ label: 'Админ', phone: '99001122' }, { label: 'Хэрэглэгч', phone: '99887766' }, { label: 'Хүргэлтийн ажилтан', phone: '88112233' }];

async function submit() {
    touched.phone = true; error.value = '';
    if (rules.phone(form.phone) || !form.password) { if (!form.password) error.value = 'Нууц үгээ оруулна уу.'; return; }
    loading.value = true;
    try {
        const user = await auth.login(form);
        await cart.fetch();
        ui.toast(`Тавтай морил, ${user.name}!`);
        const redirect = route.query.redirect;
        if (user.role === 'customer' && !user.is_verified) router.push({ name: 'verify', query: redirect ? { redirect } : {} });
        else if (redirect) router.push(redirect);
        else router.push(user.role === 'admin' ? { name: 'admin.dashboard' } : user.role === 'courier' ? { name: 'courier.deliveries' } : { name: 'account' });
    } catch (e) { error.value = errorMessage(e); }
    finally { loading.value = false; }
}
</script>
<template>
    <div class="container-x flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-md">
            <div class="text-center"><img :src="'/images/logo.png'" alt="Чанар Есүй" class="mx-auto mb-6 h-14 w-auto" /><h1 class="font-display text-3xl font-bold">Нэвтрэх</h1><p class="mt-2 text-sm text-stone-500">Бүртгэлгүй юу? <router-link :to="{ name: 'register', query: route.query }" class="font-medium text-brand-700 hover:underline">Бүртгүүлэх</router-link></p></div>
            <form @submit.prevent="submit" novalidate class="card mt-8 space-y-4 p-6 sm:p-8">
                <div>
                    <label class="label">Утасны дугаар</label>
                    <div class="flex"><span class="flex items-center rounded-l-xl bg-cream-100 px-3 text-sm text-stone-500 ring-1 ring-inset ring-cream-300">+976</span><input v-model.trim="form.phone" @blur="touched.phone = true" class="input rounded-l-none font-mono tracking-wider" :class="phoneError && 'ring-red-400'" inputmode="tel" autocomplete="tel-national" maxlength="16" placeholder="99001122" /></div>
                    <p v-if="phoneError" class="mt-1 text-xs text-red-600">{{ phoneError }}</p>
                </div>
                <div>
                    <label class="label">Нууц үг</label>
                    <div class="relative"><input v-model="form.password" :type="showPw ? 'text' : 'password'" class="input pr-10" autocomplete="current-password" /><button type="button" @click="showPw = !showPw" class="absolute right-3 top-2.5 text-stone-400 hover:text-stone-600"><component :is="showPw ? EyeSlashIcon : EyeIcon" class="h-5 w-5" /></button></div>
                </div>
                <label class="flex items-center gap-2 text-sm text-stone-600"><input type="checkbox" v-model="form.remember" class="rounded border-stone-300 text-brand-600" /> Намайг сана</label>
                <p v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
                <button :disabled="loading" class="btn-brand w-full py-3">{{ loading ? 'Нэвтэрч байна...' : 'Нэвтрэх' }}</button>
            </form>
            <div class="mt-6 rounded-2xl border border-dashed border-stone-300 p-4 text-xs text-stone-500">
                <p class="mb-2 font-semibold text-stone-700">Демо бүртгэлүүд (нууц үг: <code>password</code>)</p>
                <div class="flex flex-wrap gap-2"><button v-for="d in demo" :key="d.phone" type="button" @click="form.phone = d.phone; form.password = 'password'" class="rounded-lg bg-cream-100 px-2.5 py-1 font-medium hover:bg-cream-200">{{ d.label }} · {{ d.phone }}</button></div>
            </div>
        </div>
    </div>
</template>
