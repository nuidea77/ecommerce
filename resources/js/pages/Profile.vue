<script setup>
import { reactive, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import { errorMessage } from '../lib/api';
import { ROLE } from '../lib/format';
import AccountShell from '../components/account/AccountShell.vue';

const auth = useAuthStore();
const ui = useUiStore();
const form = reactive({ name: auth.user.name, email: auth.user.email || '', city: auth.user.city || '', address: auth.user.address || '', password: '', password_confirmation: '' });
const saving = ref(false);
async function save() {
    saving.value = true;
    try { await auth.updateProfile(form); form.password = form.password_confirmation = ''; ui.toast('Профайл хадгалагдлаа'); }
    catch (e) { ui.toast(errorMessage(e), 'error'); }
    finally { saving.value = false; }
}
</script>
<template>
    <AccountShell title="Профайл">
        <div class="card mt-6 p-6">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-xl font-bold text-brand-700">{{ auth.user.name.slice(0, 1) }}</span>
                <div><p class="font-mono font-semibold tracking-wider">+976 {{ auth.user.phone }}</p><div class="mt-1 flex flex-wrap gap-1.5"><span class="badge" :class="ROLE[auth.user.role].cls">{{ ROLE[auth.user.role].label }}</span><span v-if="auth.user.is_verified" class="badge bg-emerald-100 text-emerald-800">✓ Дугаар баталгаажсан</span><router-link v-else-if="auth.isCustomer" :to="{ name: 'verify' }" class="badge bg-amber-100 text-amber-800">Баталгаажаагүй →</router-link></div></div>
            </div>
            <dl v-if="auth.user.is_verified" class="mt-5 grid gap-3 rounded-xl bg-cream-100 p-4 text-sm sm:grid-cols-2"><div><dt class="text-stone-500">Баталгаажсан дугаар</dt><dd class="font-mono font-medium">{{ auth.user.verified_phone }}</dd></div><div><dt class="text-stone-500">Баталгаажуулагч</dt><dd class="font-medium">SMS (144773)</dd></div></dl>
            <form @submit.prevent="save" class="mt-6 grid gap-4 sm:grid-cols-2">
                <div><label class="label">Нэр</label><input v-model="form.name" class="input" required /></div>
                <div><label class="label">И-мэйл (заавал биш)</label><input v-model="form.email" type="email" class="input" placeholder="name@example.mn" /></div>
                <div><label class="label">Хот</label><input v-model="form.city" class="input" /></div>
                <div class="sm:col-span-2"><label class="label">Хаяг</label><textarea v-model="form.address" rows="2" class="input"></textarea></div>
                <div><label class="label">Шинэ нууц үг</label><input v-model="form.password" type="password" class="input" placeholder="Өөрчлөхгүй бол хоосон (8+, үсэг ба тоо)" /></div>
                <div><label class="label">Нууц үг давтах</label><input v-model="form.password_confirmation" type="password" class="input" /></div>
                <div class="sm:col-span-2"><button :disabled="saving" class="btn-brand">Хадгалах</button></div>
            </form>
        </div>
    </AccountShell>
</template>
