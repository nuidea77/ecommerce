<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { dateTime } from '../../lib/format';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';
import AccountShell from '../../components/account/AccountShell.vue';
import { ShieldCheckIcon, CheckBadgeIcon, LockClosedIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';

const route = useRoute(); const router = useRouter();
const auth = useAuthStore(); const ui = useUiStore();
const status = ref(null); const starting = ref(false);
const errorReason = computed(() => (route.query.status === 'error' ? route.query.reason || 'Тодорхойгүй алдаа' : null));

async function load() {
    const { data } = await api.get('/verify/status');
    status.value = data;
    if (data.verified && !auth.isVerified) await auth.fetch();
}
async function start() {
    starting.value = true;
    try {
        const { data } = await api.post('/verify/start');
        if (data.verified) return load();
        if (data.mock) router.push(data.url.replace(location.origin, ''));
        else window.location.href = data.url;
    } catch (e) { ui.toast(errorMessage(e), 'error'); starting.value = false; }
}
onMounted(async () => {
    await load();
    if (route.query.status === 'success') { await auth.fetch(); ui.toast('Бүртгэл амжилттай баталгаажлаа! ✓'); if (route.query.redirect) router.replace(route.query.redirect); }
});
</script>
<template>
    <AccountShell title="Бүртгэл баталгаажуулах">
        <div v-if="status" class="space-y-6">
            <!-- Verified -->
            <div v-if="status.verified" class="card overflow-hidden">
                <div class="flex flex-col items-start gap-5 bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white sm:flex-row sm:items-center">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20"><CheckBadgeIcon class="h-10 w-10" /></span>
                    <div><p class="text-sm text-emerald-100">verify.mn · {{ dateTime(status.verified_at) }}</p><h2 class="text-2xl font-bold">Таны бүртгэл баталгаажсан</h2><p class="mt-1 text-emerald-50">Та бүх үйлчилгээг бүрэн ашиглах боломжтой.</p></div>
                </div>
                <dl class="grid gap-4 p-6 sm:grid-cols-3 text-sm">
                    <div><dt class="text-stone-500">Овог</dt><dd class="mt-0.5 font-semibold">{{ status.last_name }}</dd></div>
                    <div><dt class="text-stone-500">Нэр</dt><dd class="mt-0.5 font-semibold">{{ status.first_name }}</dd></div>
                    <div><dt class="text-stone-500">Регистрийн дугаар</dt><dd class="mt-0.5 font-mono font-semibold">{{ status.register_number }}</dd></div>
                </dl>
                <div class="border-t border-stone-100 px-6 py-4"><router-link :to="route.query.redirect || { name: 'account' }" class="btn-brand">Үргэлжлүүлэх</router-link></div>
            </div>

            <!-- Not verified -->
            <template v-else>
                <div v-if="errorReason" class="rounded-2xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">⚠️ Баталгаажуулалт амжилтгүй боллоо: {{ errorReason }}. Дахин оролдоно уу.</div>
                <div class="card overflow-hidden">
                    <div class="flex flex-col items-start gap-5 bg-stone-900 p-6 text-white sm:flex-row sm:items-center">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand-600"><ShieldCheckIcon class="h-10 w-10" /></span>
                        <div><p class="text-sm text-stone-400">Нэг удаагийн үйлдэл · 1 минут</p><h2 class="text-2xl font-bold">verify.mn-ээр бүртгэлээ баталгаажуулна уу</h2><p class="mt-1 text-stone-300">Захиалга өгөх, хүргэлт авахын өмнө таны хувийн мэдээллийг албан ёсны эх сурвалжаар баталгаажуулна.</p></div>
                    </div>
                    <div class="grid gap-6 p-6 lg:grid-cols-[1fr_300px]">
                        <div>
                            <h3 class="font-semibold">Хэрхэн ажилладаг вэ?</h3>
                            <ol class="mt-3 space-y-3 text-sm text-stone-600">
                                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">1</span>"Баталгаажуулах" товч дарснаар verify.mn руу шилжинэ.</li>
                                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">2</span>verify.mn дээр e-Mongolia / ДАН-аар нэвтэрч, өөрийгөө таниулна.</li>
                                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">3</span>Овог, нэр, регистрийн дугаар автоматаар манай системд бүртгэгдэж, таны бүртгэл баталгаажна.</li>
                            </ol>
                            <div class="mt-6 flex flex-wrap items-center gap-3">
                                <button @click="start" :disabled="starting" class="btn-brand px-6 py-3 text-base"><ShieldCheckIcon class="h-5 w-5" /> {{ starting ? 'Шилжүүлж байна...' : 'verify.mn-ээр баталгаажуулах' }} <ArrowTopRightOnSquareIcon class="h-4 w-4" /></button>
                                <router-link :to="{ name: 'shop' }" class="btn-ghost">Дараа хийх</router-link>
                            </div>
                            <p v-if="status.mock" class="mt-4 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-3 text-xs text-amber-900">🧪 Туршилтын горим (VERIFY_MOCK=true): verify.mn-ийн оронд локал симуляцийн хуудас нээгдэнэ. Бодит мерчант эрхээ .env-д оруулснаар шууд verify.mn руу шилжинэ.</p>
                        </div>
                        <div class="rounded-2xl bg-stone-50 p-5 text-sm">
                            <p class="flex items-center gap-2 font-semibold"><LockClosedIcon class="h-4 w-4 text-stone-500" /> Таны мэдээлэл аюулгүй</p>
                            <ul class="mt-3 space-y-2 text-stone-600">
                                <li>• Регистрийн дугаар зөвхөн баталгаажуулалтад ашиглагдана</li>
                                <li>• Мэдээлэл шифрлэгдэн хадгалагдана, гуравдагч этгээдэд дамжуулахгүй</li>
                                <li>• Нэг регистрийн дугаараар нэг л бүртгэл баталгаажна</li>
                            </ul>
                            <p class="mt-4 text-xs text-stone-400">Баталгаажуулагч: verify.mn</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AccountShell>
</template>
