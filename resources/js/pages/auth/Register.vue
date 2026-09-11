<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useCartStore } from '../../stores/cart';
import { useUiStore } from '../../stores/ui';
import { errorMessage } from '../../lib/api';

const auth = useAuthStore(); const cart = useCartStore(); const ui = useUiStore();
const route = useRoute(); const router = useRouter();
const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const loading = ref(false); const errors = ref({});

async function submit() {
    loading.value = true; errors.value = {};
    try {
        await auth.register(form);
        await cart.fetch();
        ui.toast('Бүртгэл амжилттай үүслээ!');
        router.push({ name: 'verify', query: route.query.redirect ? { redirect: route.query.redirect } : {} });
    } catch (e) { errors.value = e.response?.data?.errors || {}; ui.toast(errorMessage(e), 'error'); }
    finally { loading.value = false; }
}
</script>
<template>
    <div class="container-x flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-md">
            <div class="text-center"><h1 class="font-display text-3xl font-bold">Бүртгүүлэх</h1><p class="mt-2 text-sm text-stone-500">Бүртгэлтэй юу? <router-link :to="{ name: 'login', query: route.query }" class="font-medium text-brand-700 hover:underline">Нэвтрэх</router-link></p></div>
            <form @submit.prevent="submit" class="card mt-8 space-y-4 p-6 sm:p-8">
                <div><label class="label">Нэр / Салоны нэр</label><input v-model="form.name" class="input" required /><p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p></div>
                <div><label class="label">И-мэйл</label><input v-model="form.email" type="email" class="input" required /><p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email[0] }}</p></div>
                <div><label class="label">Утас</label><input v-model="form.phone" class="input" required /><p v-if="errors.phone" class="mt-1 text-xs text-red-600">{{ errors.phone[0] }}</p></div>
                <div><label class="label">Нууц үг</label><input v-model="form.password" type="password" class="input" required minlength="6" /><p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password[0] }}</p></div>
                <div><label class="label">Нууц үг давтах</label><input v-model="form.password_confirmation" type="password" class="input" required /></div>
                <button :disabled="loading" class="btn-brand w-full py-3">{{ loading ? 'Үүсгэж байна...' : 'Бүртгүүлэх' }}</button>
            </form>
        </div>
    </div>
</template>
