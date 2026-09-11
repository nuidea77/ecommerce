<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';

const route = useRoute(); const router = useRouter(); const auth = useAuthStore(); const ui = useUiStore();
const form = reactive({ state: route.query.state || '', last_name: '', first_name: auth.user?.name || '', register_number: '' });
const errors = ref({}); const busy = ref(false);
async function submit() {
    busy.value = true; errors.value = {};
    try { const { data } = await api.post('/verify/mock/complete', form); auth.setUser(data.user); router.replace({ name: 'verify', query: { status: 'success' } }); }
    catch (e) { errors.value = e.response?.data?.errors || {}; ui.toast(errorMessage(e), 'error'); }
    finally { busy.value = false; }
}
</script>
<template>
    <div class="container-x flex min-h-[70vh] items-center justify-center py-12">
        <div class="w-full max-w-md">
            <div class="mb-6 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-3 text-center text-xs text-amber-900">🧪 Энэ бол verify.mn-ийн симуляци (VERIFY_MOCK=true). Бодит орчинд энэ алхамыг verify.mn өөрөө гүйцэтгэнэ.</div>
            <div class="card overflow-hidden">
                <div class="flex items-center gap-3 bg-[#0b3d91] px-6 py-4 text-white"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 font-bold">V</span><div><p class="font-bold">verify.mn</p><p class="text-xs text-blue-200">Иргэний баталгаажуулалт</p></div></div>
                <form @submit.prevent="submit" class="space-y-4 p-6">
                    <p class="text-sm text-stone-600"><b>BeautyPro Supply</b> таны овог, нэр, регистрийн дугаарыг хүсч байна.</p>
                    <div><label class="label">Овог</label><input v-model="form.last_name" class="input" required /><p v-if="errors.last_name" class="mt-1 text-xs text-red-600">{{ errors.last_name[0] }}</p></div>
                    <div><label class="label">Нэр</label><input v-model="form.first_name" class="input" required /><p v-if="errors.first_name" class="mt-1 text-xs text-red-600">{{ errors.first_name[0] }}</p></div>
                    <div><label class="label">Регистрийн дугаар</label><input v-model="form.register_number" class="input font-mono uppercase" placeholder="УБ95010112" required /><p v-if="errors.register_number" class="mt-1 text-xs text-red-600">{{ errors.register_number[0] }}</p><p v-if="errors.state" class="mt-1 text-xs text-red-600">{{ errors.state[0] }}</p></div>
                    <button :disabled="busy" class="btn w-full bg-[#0b3d91] py-3 text-white hover:bg-[#0a3479]">{{ busy ? 'Баталгаажуулж байна...' : 'Зөвшөөрч баталгаажуулах' }}</button>
                    <router-link :to="{ name: 'verify' }" class="btn-ghost w-full">Цуцлах</router-link>
                </form>
            </div>
        </div>
    </div>
</template>
