# Чанар Есүй — Гоо сайхны салоны тоног төхөөрөмжийн онлайн дэлгүүр

<p><img src="public/images/logo.png" alt="Чанар Есүй" width="260"></p>

Laravel 12 + Vue 3 + Tailwind CSS 4 + MySQL дээр бүтээсэн бүрэн динамик, responsive e-commerce систем.

## Боломжууд

| Хэсэг | Боломж |
| --- | --- |
| **Дэлгүүр (хэрэглэгч)** | Нүүр, ангилал, хайлт, шүүлт (өнгө / хэмжээ / брэнд / үнэ / бэлэн байгаа), эрэмбэ, хуудаслалт |
| **Бүтээгдэхүүн** | Өнгө · хэмжээ · тоо ширхгийн багц (1 / 6 / 12 / 50 …) сонголт бүр өөрийн SKU, үнэ, үлдэгдэлтэй |
| **Урьдчилсан захиалга** | Үлдэгдэл дууссан сонголтыг ч захиалж болно (backorder); хугацаа бүтээгдэхүүн тус бүрээр тохируулна |
| **Бүртгэл** | Утасны дугаар + нууц үгээр (Монголын 8 оронтой дугаар, 6-9-өөр эхэлсэн; нууц үг 8+ тэмдэгт, үсэг ба тоо; нэр зөвхөн үсэг). Бүртгүүлмэгц баталгаажуулах хуудас нээгдэнэ |
| **verify.mn баталгаажуулалт** | Бүртгэлтэй дугаараас нь 144773 руу нэг удаагийн 6 оронтой код SMS-ээр илгээж дугаараа баталгаажуулна (Mobile-Originated SMS). Баталгаажаагүй бол захиалга өгөх боломжгүй. API түлхүүргүй үед `VERIFY_MOCK=true` локал симуляц |
| **Хэрэглэгчийн самбар** | Захиалгын статистик, баталгаажуулалтын төлөв, хүргэлт хянах, урьдчилсан захиалгын бараа, төлбөр хүлээгдэж буй захиалга, дахин захиалах |
| **Сагс** | Зочин сагс (token) → нэвтрэхэд автоматаар нэгтгэнэ, үнэгүй хүргэлтийн босго |
| **Захиалга** | Хүргэлтийн хаяг, тэмдэглэл, QPay эсвэл бэлэн (хүргэлтээр) төлбөр, цуцлах, түүх/timeline |
| **QPay** | QPay v2 merchant API (нэхэмжлэх үүсгэх, төлбөр шалгах, callback). `QPAY_MOCK=true` үед локал симуляц |
| **Админ** | Хянах самбар (7/14/30/90 хоногийн үе, KPI өөрчлөлт, өдрийн орлогын график + хүснэгт, төлбөрийн хэлбэр, ангиллын борлуулалт, анхаарал шаардсан захиалга, нийлүүлэх шаардлагатай урьдчилсан захиалга, хүргэгчийн гүйцэтгэл, баталгаажсан хэрэглэгч), бүтээгдэхүүн + сонголт CRUD, зураг upload, ангилал, захиалгын төлөв / төлбөр / хүргэгч томилох, хэрэглэгч & хүргэгч удирдлага |
| **Хүргэлтийн ажилтан** | Өөрт томилогдсон хүргэлтүүд, статистик, статус шинэчлэх (хүлээн авсан → замд → хүргэсэн / амжилтгүй), бэлэн мөнгө хүлээн авах |

## Суулгах

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

`.env` дотор MySQL тохиргоогоо оруулна:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chanar_esui
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed      # демо бараа, хэрэглэгч, захиалга үүснэ
php artisan storage:link        # зураг upload-д
npm run build                   # эсвэл npm run dev
php artisan serve
```

→ http://localhost:8000

### Демо бүртгэл (нууц үг: `password`)

Нэвтрэлт утасны дугаараар (и-мэйл заавал биш).

| Эрх | Утас |
| --- | --- |
| Админ | 99001122 |
| Хэрэглэгч | 99887766 |
| Хүргэлтийн ажилтан | 88112233 |

## QPay тохиргоо

[developer.qpay.mn](https://developer.qpay.mn) дээрээс мерчант эрх авсны дараа:

```env
QPAY_MOCK=false
QPAY_BASE_URL=https://merchant.qpay.mn/v2
QPAY_USERNAME=...
QPAY_PASSWORD=...
QPAY_INVOICE_CODE=...
QPAY_CALLBACK_URL=https://yourdomain.mn/api/payments/qpay/callback
```

`QPAY_MOCK=true` (анхдагч) үед бодит API дуудахгүй, төлбөрийн хуудсан дээр "симуляц" товч гарч ирнэ.

## verify.mn тохиргоо

[verify.mn](https://verify.mn) — Монголын Mobile-Originated SMS баталгаажуулалт. Хэрэглэгч утаснаасаа **144773** дугаарт нэг удаагийн кодыг илгээж, бид `GET /sessions/{id}` дээр `sessionStatus === "VERIFIED"` болсныг шалгаж баталгаажуулна.

```env
VERIFY_MN_API_KEY=...                     # verify.mn dashboard-аас (заавал, хэзээ ч commit хийхгүй)
VERIFY_MN_BASE_URL=https://api.verify.mn
VERIFY_MN_CALLBACK_URL=https://yourdomain.mn/api/verify/callback   # заавал биш; хүрэх боломжгүй URL бүртгэхгүй
VERIFY_MN_RESPONSE_SMS=                   # заавал биш хариу SMS (<=160 ASCII); операторын шинж чанараас хамаарна
VERIFY_MOCK=false
VERIFY_REQUIRE_FOR_CHECKOUT=true
```

Урсгал: `POST /api/verify/start {phone}` → verify.mn `POST /sessions` (шинэ санамсаргүй 6 оронтой код) → хэрэглэгчид `displayInstruction`-ийг үгчлэн харуулж, `smsUri` (sms:144773?body=код) товч санал болгоно → SPA 3 секунд тутам `GET /api/verify/sessions/{id}/check` дуудна (verify.mn `GET /sessions/{id}`) → `VERIFIED` болмогц polling зогсоно; хугацаа (`expiresAt`, 300с) дууссан бол шинэ код үүсгэнэ. Callback тохируулсан бол verify.mn `GET /api/verify/callback/{token}` руу дуудна — бид шууд 200 буцааж, хариу илгээсний дараа `GET /sessions/{id}`-ээр дахин шалгана (callback-д өөрт нь итгэхгүй).

Сервер талд `App\Services\VerifyService::verifyPhone(string $phone, ?User $user): bool` — session үүсгээд `VERIFIED` хүртэл 3с тутам шалгаж `true`, хугацаа дуусвал `false` буцаана. SMS бүр хэрэглэгчид 150₮.

## Бүтэц

```
app/Http/Controllers/Api       Дэлгүүрийн API (auth, catalog, cart, orders, payments)
app/Http/Controllers/Admin     Админ API
app/Http/Controllers/Courier   Хүргэлтийн ажилтны API
app/Services/QPayService.php   QPay интеграц
app/Services/VerifyService.php verify.mn SMS баталгаажуулалт (verifyPhone)
app/Services/CartService.php   Зочин / хэрэглэгчийн сагс
resources/js/pages             Vue хуудсууд (store, admin, courier)
resources/js/layouts           Store / Admin / Courier layout
database/seeders               Демо өгөгдөл
```

## Тест

```bash
php artisan test
```
