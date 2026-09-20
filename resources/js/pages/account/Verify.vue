<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../../lib/api';
import { dateTime } from '../../lib/format';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';
import AccountShell from '../../components/account/AccountShell.vue';
import { ShieldCheckIcon, CheckBadgeIcon, DevicePhoneMobileIcon, ChatBubbleBottomCenterTextIcon, ClockIcon } from '@heroicons/vue/24/outline';

const POLL_MS = 3000; // verify.mn: never poll faster than 3s

const route = useRoute(); const router = useRouter();
const auth = useAuthStore(); const ui = useUiStore();

const status = ref(null);
const session = ref(null);
const form = reactive({ phone: auth.user?.phone || '' });
const errors = ref({});
const starting = ref(false);
const expiredNotice = ref(false);
const now = ref(Date.now());
const mock = reactive({ text: '', busy: false });
let pollTimer = null, clockTimer = null;

const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
const remaining = computed(() => session.value?.expires_at ? Math.max(0, Math.floor((new Date(session.value.expires_at) - now.value) / 1000)) : 0);
const countdown = computed(() => `${Math.floor(remaining.value / 60)}:${String(remaining.value % 60).padStart(2, '0')}`);
const isPending = computed(() => session.value?.status === 'PENDING' && remaining.value > 0);
const isExpired = computed(() => session.value && (session.value.status === 'EXPIRED' || (session.value.status === 'PENDING' && remaining.value <= 0)));

function stopPolling() { clearInterval(pollTimer); pollTimer = null; }

async function load() {
    const { data } = await api.get('/verify/status');
    status.value = data;
    if (data.verified && !auth.isVerified) await auth.fetch();
    if (data.session) { session.value = data.session; startPolling(); }
}

async function start() {
    starting.value = true; errors.value = {}; expiredNotice.value = false;
    try {
        const { data } = await api.post('/verify/start', { phone: form.phone });
        if (data.verified) { await load(); return; }
        session.value = data.session;
        mock.text = '';
        startPolling();
    } catch (e) { errors.value = e.response?.data?.errors || {}; ui.toast(errorMessage(e), 'error'); }
    finally { starting.value = false; }
}

async function check() {
    if (!session.value || !isPending.value) return;
    try {
        const { data } = await api.get(`/verify/sessions/${session.value.session_id}/check`);
        session.value = data.session;
        if (data.verified || data.session.status === 'VERIFIED') return onVerified();
        if (data.session.status === 'EXPIRED') onExpired();
    } catch { /* transient network error: keep polling until expiresAt */ }
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(() => { if (isExpired.value) return onExpired(); check(); }, POLL_MS);
}

async function onVerified() {
    stopPolling(); // stop immediately: every extra SMS costs the user 150₮
    await auth.fetch();
    await load();
    ui.toast('Утасны дугаар амжилттай баталгаажлаа! ✓');
    if (route.query.redirect) router.replace(route.query.redirect);
}

function onExpired() {
    stopPolling();
    if (session.value) session.value.status = 'EXPIRED';
    expiredNotice.value = true;
}

async function mockConfirm() {
    mock.busy = true;
    try { const { data } = await api.post(`/verify/sessions/${session.value.session_id}/mock-confirm`, { text: mock.text }); session.value = data.session; auth.setUser(data.user); await onVerified(); }
    catch (e) { ui.toast(errorMessage(e), 'error'); }
    finally { mock.busy = false; }
}

onMounted(async () => { await load(); clockTimer = setInterval(() => (now.value = Date.now()), 1000); });
onUnmounted(() => { stopPolling(); clearInterval(clockTimer); });
</script>

<template>
    <AccountShell title="Утасны дугаар баталгаажуулах">
        <div v-if="status" class="space-y-6">
            <!-- Verified -->
            <div v-if="status.verified" class="card overflow-hidden">
                <div class="flex flex-col items-start gap-5 bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white sm:flex-row sm:items-center">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20"><CheckBadgeIcon class="h-10 w-10" /></span>
                    <div><p class="text-sm text-emerald-100">verify.mn · {{ dateTime(status.verified_at) }}</p><h2 class="text-2xl font-bold">Таны дугаар баталгаажсан</h2><p class="mt-1 text-emerald-50">Та бүх үйлчилгээг бүрэн ашиглах боломжтой.</p></div>
                </div>
                <dl class="grid gap-4 p-6 text-sm sm:grid-cols-2">
                    <div><dt class="text-stone-500">Баталгаажсан дугаар</dt><dd class="mt-0.5 font-mono text-lg font-semibold">{{ status.verified_phone }}</dd></div>
                    <div><dt class="text-stone-500">Арга</dt><dd class="mt-0.5 font-semibold">SMS (144773) · verify.mn</dd></div>
                </dl>
                <div class="border-t border-stone-100 px-6 py-4"><router-link :to="route.query.redirect || { name: 'account' }" class="btn-brand">Үргэлжлүүлэх</router-link></div>
            </div>

            <template v-else>
                <div v-if="expiredNotice" class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">⏱ Кодын хугацаа дууслаа — хуучин код <b>хүчингүй</b> боллоо, түүнийг илгээх шаардлагагүй. Доороос шинэ код авна уу.</div>

                <div class="card overflow-hidden">
                    <div class="flex flex-col items-start gap-5 bg-brand-900 p-6 text-cream-100 sm:flex-row sm:items-center">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand-600"><ShieldCheckIcon class="h-10 w-10" /></span>
                        <div><p class="text-sm text-cream-300/70">verify.mn · нэг удаагийн SMS</p><h2 class="text-2xl font-bold">Утасны дугаараа баталгаажуулна уу</h2><p class="mt-1 text-cream-200/80">Захиалга өгөхийн өмнө таны утаснаас <b>144773</b> дугаарт нэг удаагийн код илгээж дугаарыг тань баталгаажуулна.</p></div>
                    </div>

                    <!-- Step 1: phone -->
                    <div v-if="!session || isExpired" class="p-6">
                        <form @submit.prevent="start" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="flex-1"><label class="label">Утасны дугаар (SMS илгээх дугаар)</label><input v-model="form.phone" class="input font-mono text-lg" placeholder="99001122" required inputmode="tel" /><p v-if="errors.phone" class="mt-1 text-xs text-red-600">{{ errors.phone[0] }}</p></div>
                            <button :disabled="starting" class="btn-brand px-6 py-3"><DevicePhoneMobileIcon class="h-5 w-5" /> {{ starting ? 'Үүсгэж байна...' : isExpired ? 'Шинэ код авах' : 'Код авах' }}</button>
                        </form>
                        <p class="mt-3 text-xs text-stone-500">Та зөвхөн энэ дугаарын SIM-ээс SMS илгээх ёстой. Илгээх SMS бүр операторын тарифаар <b>150₮</b>.</p>
                    </div>

                    <!-- Step 2: send the SMS -->
                    <div v-else class="grid gap-6 p-6 lg:grid-cols-[1fr_300px]">
                        <div>
                            <div class="rounded-2xl border-2 border-brand-200 bg-brand-50 p-5">
                                <p class="flex items-center gap-2 text-sm font-semibold text-brand-800"><ChatBubbleBottomCenterTextIcon class="h-5 w-5" /> Заавар</p>
                                <p class="mt-2 text-base leading-relaxed text-stone-900">{{ session.display_instruction }}</p>
                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <div class="rounded-xl bg-white px-4 py-2 ring-1 ring-stone-200"><p class="text-[11px] uppercase tracking-wide text-stone-400">Хүлээн авагч</p><p class="font-mono text-xl font-bold">144773</p></div>
                                    <div class="rounded-xl bg-white px-4 py-2 ring-1 ring-stone-200"><p class="text-[11px] uppercase tracking-wide text-stone-400">Мессежийн текст</p><p class="font-mono text-xl font-bold tracking-widest">{{ session.code }}</p></div>
                                </div>
                                <a :href="session.sms_uri" class="btn-brand mt-4 w-full py-3 text-base sm:w-auto"><DevicePhoneMobileIcon class="h-5 w-5" /> SMS илгээх (мессеж нээх)</a>
                                <p v-if="!isMobile" class="mt-2 text-xs text-stone-500">Компьютер дээр байгаа бол дээрх дугаар руу гар утаснаасаа <b>{{ session.code }}</b> гэж бичиж илгээнэ үү.</p>
                            </div>
                            <div class="mt-4 flex items-center gap-3 text-sm text-stone-600">
                                <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-brand-600"></span></span>
                                SMS ирэхийг хүлээж байна… <span class="ml-auto flex items-center gap-1 font-mono" :class="remaining < 60 ? 'text-red-600' : 'text-stone-500'"><ClockIcon class="h-4 w-4" /> {{ countdown }}</span>
                            </div>
                            <p class="mt-2 text-xs text-stone-500">Баталгаажсан эсэх нь энэ хуудсан дээр автоматаар шинэчлэгдэнэ. Операторын хариу SMS (ирэх эсэх нь оператороос хамаарна) баталгаа биш.</p>

                            <div v-if="status.mock" class="mt-5 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                                <p class="font-semibold">🧪 Туршилтын горим (VERIFY_MOCK=true)</p>
                                <p class="mt-1 text-xs">API түлхүүр тохируулаагүй тул verify.mn руу хүсэлт явахгүй. Утаснаас илгээсэн SMS-ийг доор симуляц хийнэ:</p>
                                <form @submit.prevent="mockConfirm" class="mt-2 flex gap-2"><input v-model="mock.text" class="input font-mono" placeholder="SMS текст (код)" required /><button :disabled="mock.busy" class="btn bg-amber-500 text-white hover:bg-amber-600">Илгээх</button></form>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-stone-50 p-5 text-sm">
                            <p class="font-semibold">Анхаарах зүйлс</p>
                            <ul class="mt-3 space-y-2 text-stone-600">
                                <li>• SMS-ийг зөвхөн <b class="font-mono">{{ session.phone }}</b> дугаараас илгээнэ. Өөр SIM-ээс илгээвэл баталгаажихгүй.</li>
                                <li>• Мессежийн текстийг яг <b class="font-mono">{{ session.code }}</b> гэж илгээнэ (нэмэлт үг, тэмдэгтгүй).</li>
                                <li>• SMS бүр <b>150₮</b>. Баталгаажмагц дахин илгээх шаардлагагүй.</li>
                                <li>• Код <b>5 минут</b> хүчинтэй. Хугацаа дууссан бол шинэ код авна.</li>
                            </ul>
                            <button @click="onExpired(); session = null" class="btn-ghost btn-sm mt-4 w-full">Өөр дугаар ашиглах</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AccountShell>
</template>
