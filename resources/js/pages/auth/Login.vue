<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useCartStore } from '../../stores/cart';
import { useUiStore } from '../../stores/ui';
import { errorMessage } from '../../lib/api';

const auth = useAuthStore();
const cart = useCartStore();
const ui = useUiStore();
const route = useRoute();
const router = useRouter();
const form = reactive({ email: '', password: '', remember: true });
const loading = ref(false);
const error = ref('');

const demo = [
    { label: 'Админ', email: 'admin@chanaresui.mn' },
    { label: 'Хэрэглэгч', email: 'customer@chanaresui.mn' },
    { label: 'Хүргэлтийн ажилтан', email: 'courier@chanaresui.mn' },
];

async function submit() {
    loading.value = true; error.value = '';
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
            <form @submit.prevent="submit" class="card mt-8 space-y-4 p-6 sm:p-8">
                <div><label class="label">И-мэйл</label><input v-model="form.email" type="email" class="input" required autocomplete="email" /></div>
                <div><label class="label">Нууц үг</label><input v-model="form.password" type="password" class="input" required autocomplete="current-password" /></div>
                <label class="flex items-center gap-2 text-sm text-stone-600"><input type="checkbox" v-model="form.remember" class="rounded border-stone-300 text-brand-600" /> Намайг сана</label>
                <p v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
                <button :disabled="loading" class="btn-brand w-full py-3">{{ loading ? 'Нэвтэрч байна...' : 'Нэвтрэх' }}</button>
            </form>
            <div class="mt-6 rounded-2xl border border-dashed border-stone-300 p-4 text-xs text-stone-500">
                <p class="mb-2 font-semibold text-stone-700">Демо бүртгэлүүд (нууц үг: <code>password</code>)</p>
                <div class="flex flex-wrap gap-2"><button v-for="d in demo" :key="d.email" type="button" @click="form.email = d.email; form.password = 'password'" class="rounded-lg bg-stone-100 px-2.5 py-1 font-medium hover:bg-stone-200">{{ d.label }}</button></div>
            </div>
        </div>
    </div>
</template>
