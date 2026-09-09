# BeautyPro Supply — Гоо сайхны салоны тоног төхөөрөмжийн онлайн дэлгүүр

Laravel 12 + Vue 3 + Tailwind CSS 4 + MySQL дээр бүтээсэн бүрэн динамик, responsive e-commerce систем.

## Боломжууд

| Хэсэг | Боломж |
| --- | --- |
| **Дэлгүүр (хэрэглэгч)** | Нүүр, ангилал, хайлт, шүүлт (өнгө / хэмжээ / брэнд / үнэ / бэлэн байгаа), эрэмбэ, хуудаслалт |
| **Бүтээгдэхүүн** | Өнгө · хэмжээ · тоо ширхгийн багц (1 / 6 / 12 / 50 …) сонголт бүр өөрийн SKU, үнэ, үлдэгдэлтэй |
| **Урьдчилсан захиалга** | Үлдэгдэл дууссан сонголтыг ч захиалж болно (backorder); хугацаа бүтээгдэхүүн тус бүрээр тохируулна |
| **Сагс** | Зочин сагс (token) → нэвтрэхэд автоматаар нэгтгэнэ, үнэгүй хүргэлтийн босго |
| **Захиалга** | Хүргэлтийн хаяг, тэмдэглэл, QPay эсвэл бэлэн (хүргэлтээр) төлбөр, цуцлах, түүх/timeline |
| **QPay** | QPay v2 merchant API (нэхэмжлэх үүсгэх, төлбөр шалгах, callback). `QPAY_MOCK=true` үед локал симуляц |
| **Админ** | Хянах самбар (орлого, төлөв, дуусаж буй үлдэгдэл), бүтээгдэхүүн + сонголт CRUD, зураг upload, ангилал, захиалгын төлөв / төлбөр / хүргэгч томилох, хэрэглэгч & хүргэгч удирдлага |
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
DB_DATABASE=beautypro
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

| Эрх | И-мэйл |
| --- | --- |
| Админ | admin@beautypro.mn |
| Хэрэглэгч | customer@beautypro.mn |
| Хүргэлтийн ажилтан | courier@beautypro.mn |

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

## Бүтэц

```
app/Http/Controllers/Api       Дэлгүүрийн API (auth, catalog, cart, orders, payments)
app/Http/Controllers/Admin     Админ API
app/Http/Controllers/Courier   Хүргэлтийн ажилтны API
app/Services/QPayService.php   QPay интеграц
app/Services/CartService.php   Зочин / хэрэглэгчийн сагс
resources/js/pages             Vue хуудсууд (store, admin, courier)
resources/js/layouts           Store / Admin / Courier layout
database/seeders               Демо өгөгдөл
```

## Тест

```bash
php artisan test
```
