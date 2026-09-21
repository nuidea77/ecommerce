// Shared client-side rules (mirrors the server rules in AuthController / App\Support\Phone).
export const PHONE_RE = /^(?:\+?976)?[6-9]\d{7}$/;
export const NAME_RE = /^[\p{L}][\p{L}\s'\-.]{1,49}$/u;
export const PASSWORD_RE = /^(?=.*\p{L})(?=.*\d).{8,}$/u;

export const normalizePhone = (v) => {
    const d = String(v || '').replace(/[\s\-()]+/g, '');
    return PHONE_RE.test(d) ? d.slice(-8) : null;
};

export const rules = {
    phone: (v) => (normalizePhone(v) ? '' : 'Утасны дугаар 8 оронтой, 6-9-өөр эхэлнэ (ж: 99001122).'),
    name: (v) => (NAME_RE.test(String(v || '').trim()) ? '' : 'Нэр зөвхөн үсэг, зай, зураас агуулна (2-50 тэмдэгт).'),
    password: (v) => (PASSWORD_RE.test(String(v || '')) ? '' : 'Нууц үг 8-аас дээш тэмдэгттэй, үсэг болон тоо агуулна.'),
    confirm: (v, p) => (v === p ? '' : 'Нууц үг давталт таарахгүй байна.'),
};

export const passwordStrength = (v = '') => {
    let s = 0;
    if (v.length >= 8) s++;
    if (/\p{L}/u.test(v) && /\d/.test(v)) s++;
    if (/[A-ZА-ЯӨҮ]/.test(v) && /[a-zа-яөү]/.test(v)) s++;
    if (/[^\p{L}\d]/u.test(v) || v.length >= 12) s++;
    return s; // 0-4
};
