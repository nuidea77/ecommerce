export const money = (v) => `${Math.round(Number(v) || 0).toLocaleString('en-US')}₮`;

export const dateTime = (v) => {
    if (!v) return '';
    const d = new Date(v);
    return d.toLocaleString('mn-MN', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
};

export const dateOnly = (v) => {
    if (!v) return '';
    return new Date(v).toLocaleDateString('mn-MN', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

export const ORDER_STATUS = {
    pending: { label: 'Хүлээгдэж буй', cls: 'bg-amber-100 text-amber-800' },
    awaiting_payment: { label: 'Төлбөр хүлээгдэж буй', cls: 'bg-orange-100 text-orange-800' },
    confirmed: { label: 'Баталгаажсан', cls: 'bg-sky-100 text-sky-800' },
    processing: { label: 'Бэлтгэж буй', cls: 'bg-indigo-100 text-indigo-800' },
    shipped: { label: 'Хүргэлтэнд гарсан', cls: 'bg-violet-100 text-violet-800' },
    delivered: { label: 'Хүргэгдсэн', cls: 'bg-emerald-100 text-emerald-800' },
    cancelled: { label: 'Цуцлагдсан', cls: 'bg-stone-200 text-stone-700' },
};

export const PAYMENT_STATUS = {
    unpaid: { label: 'Төлөгдөөгүй', cls: 'bg-red-100 text-red-700' },
    paid: { label: 'Төлөгдсөн', cls: 'bg-emerald-100 text-emerald-800' },
    refunded: { label: 'Буцаагдсан', cls: 'bg-stone-200 text-stone-700' },
    failed: { label: 'Амжилтгүй', cls: 'bg-red-100 text-red-700' },
};

export const PAYMENT_METHOD = {
    qpay: 'QPay',
};

export const DELIVERY_STATUS = {
    unassigned: { label: 'Томилогдоогүй', cls: 'bg-stone-100 text-stone-600' },
    assigned: { label: 'Томилогдсон', cls: 'bg-sky-100 text-sky-800' },
    picked_up: { label: 'Хүлээн авсан', cls: 'bg-indigo-100 text-indigo-800' },
    in_transit: { label: 'Замд яваа', cls: 'bg-violet-100 text-violet-800' },
    delivered: { label: 'Хүргэгдсэн', cls: 'bg-emerald-100 text-emerald-800' },
    failed: { label: 'Амжилтгүй', cls: 'bg-red-100 text-red-700' },
};

export const HISTORY_LABEL = {
    pending: 'Захиалга үүссэн',
    awaiting_payment: 'Төлбөр хүлээгдэж байна',
    confirmed: 'Баталгаажсан',
    processing: 'Бэлтгэж эхэлсэн',
    shipped: 'Хүргэлтэнд гарсан',
    delivered: 'Хүргэгдсэн',
    cancelled: 'Цуцлагдсан',
    paid: 'Төлбөр төлөгдсөн',
    unpaid: 'Төлбөр төлөгдөөгүй',
    refunded: 'Төлбөр буцаагдсан',
    failed: 'Амжилтгүй',
    assigned: 'Хүргэлтийн ажилтан томилогдсон',
    unassigned: 'Хүргэлтийн ажилтан цуцлагдсан',
    picked_up: 'Хүргэгч бараа хүлээн авсан',
    in_transit: 'Хүргэлт замд яваа',
};

export const ROLE = {
    admin: { label: 'Админ', cls: 'bg-brand-100 text-brand-800' },
    customer: { label: 'Хэрэглэгч', cls: 'bg-stone-100 text-stone-700' },
    courier: { label: 'Хүргэлтийн ажилтан', cls: 'bg-sky-100 text-sky-800' },
};
