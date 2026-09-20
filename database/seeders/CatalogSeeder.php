<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    protected array $colors = [
        'Хар' => '#1c1917', 'Цагаан' => '#f5f5f4', 'Ягаан' => '#f472b6', 'Алтан' => '#d4a017',
        'Мөнгөлөг' => '#c0c0c0', 'Улаан' => '#dc2626', 'Хөх' => '#2563eb', 'Ногоон' => '#16a34a',
        'Бор' => '#78350f', 'Саарал' => '#6b7280', 'Тунгалаг' => '#e0f2fe', 'Бежевый' => '#e7d5c0',
    ];

    public function run(): void
    {
        $categories = [
            ['name' => 'Үсний тоног төхөөрөмж', 'slug' => 'hair', 'icon' => '💇‍♀️', 'description' => 'Үс хатаагч, шулуутгагч, буржгар хийгч, үсний уураар'],
            ['name' => 'Салоны тавилга', 'slug' => 'furniture', 'icon' => '🪑', 'description' => 'Сандал, толь, угаалгын нэгж, тэргэнцэр'],
            ['name' => 'Хумсны хэрэгсэл', 'slug' => 'nails', 'icon' => '💅', 'description' => 'UV LED лампа, хумсны машин, гель лак, маникюрын ширээ'],
            ['name' => 'Косметологийн аппарат', 'slug' => 'skin', 'icon' => '✨', 'description' => 'Нүүрний уураар, LED маск, гидрафейшл, косметологийн ор'],
            ['name' => 'Хайч, сам, машин', 'slug' => 'tools', 'icon' => '✂️', 'description' => 'Мэргэжлийн хайч, нимгэлэгч, сам, үс засах машин'],
            ['name' => 'Хэрэглээний материал', 'slug' => 'supplies', 'icon' => '🧴', 'description' => 'Алчуур, бээлий, нөмрөг, тугалган цаас, будгийн хэрэгсэл'],
        ];

        $catModels = [];
        foreach ($categories as $i => $c) {
            $catModels[$c['slug']] = Category::updateOrCreate(['slug' => $c['slug']], $c + ['sort_order' => $i, 'image' => "/images/photos/cat-{$c['slug']}.jpg"]);
        }

        $products = [
            // ---------------- HAIR ----------------
            [
                'cat' => 'hair', 'slug' => 'professional-hair-dryer-2200w', 'name' => 'Мэргэжлийн үс хатаагч 2200W', 'brand' => 'Parlux',
                'short' => 'Салоны зориулалттай хүчирхэг AC мотортой, ион технологитой үс хатаагч.',
                'desc' => "Өдөр бүр олон цагаар ажиллах салонд зориулсан 2200W чадалтай мэргэжлийн үс хатаагч. Ион технологи нь үсийг гялалзуулж, статик цахилгааныг арилгана.\n\n• 2 хурд, 3 халуун тохиргоо\n• Хүйтэн салхи товч\n• 2 хушуу, 1 диффузер дагалдана\n• 3 метр урт кабель",
                'price' => 289000, 'compare' => 340000, 'featured' => true, 'sold' => 142,
                'specs' => ['Чадал' => '2200W', 'Мотор' => 'AC 2000 цаг', 'Жин' => '520 г', 'Баталгаа' => '24 сар'],
                'variants' => [
                    ['color' => 'Хар', 'price' => 289000, 'stock' => 18],
                    ['color' => 'Ягаан', 'price' => 289000, 'stock' => 7],
                    ['color' => 'Алтан', 'price' => 309000, 'stock' => 0],
                    ['color' => 'Мөнгөлөг', 'price' => 299000, 'stock' => 4],
                ],
            ],
            [
                'cat' => 'hair', 'slug' => 'ionic-hair-dryer-compact', 'name' => 'Компакт ион үс хатаагч 1800W', 'brand' => 'BaByliss PRO',
                'short' => 'Хөнгөн, чимээ багатай, аялалд тохиромжтой салоны үс хатаагч.',
                'desc' => 'Ердөө 380 грамм жинтэй, гар ядраахгүй хөнгөн загвар. Нано титан технологи нь үсийг зөөлөн хатаана.',
                'price' => 189000, 'compare' => null, 'featured' => false, 'sold' => 65,
                'specs' => ['Чадал' => '1800W', 'Жин' => '380 г', 'Баталгаа' => '12 сар'],
                'variants' => [
                    ['color' => 'Хар', 'price' => 189000, 'stock' => 12],
                    ['color' => 'Цагаан', 'price' => 189000, 'stock' => 9],
                ],
            ],
            [
                'cat' => 'hair', 'slug' => 'titanium-flat-iron', 'name' => 'Титан үс шулуутгагч', 'brand' => 'GHD',
                'short' => '230°C хүртэл халдаг, дижитал дэлгэцтэй титан хавтантай шулуутгагч.',
                'desc' => "Кератин болон ботокс эмчилгээнд тохиромжтой 230°C хүртэл халдаг мэргэжлийн шулуутгагч.\n\n• Дижитал температур тохиргоо 120–230°C\n• 30 секундэд халдаг\n• Хөвөгч хавтан\n• Автомат унтраалт",
                'price' => 245000, 'compare' => 280000, 'featured' => true, 'sold' => 98,
                'specs' => ['Хавтан' => 'Титан', 'Температур' => '120–230°C', 'Хавтангийн өргөн' => '25мм / 38мм'],
                'variants' => [
                    ['color' => 'Хар', 'size' => '25мм', 'price' => 245000, 'stock' => 15],
                    ['color' => 'Хар', 'size' => '38мм', 'price' => 265000, 'stock' => 6],
                    ['color' => 'Ягаан', 'size' => '25мм', 'price' => 245000, 'stock' => 3],
                    ['color' => 'Ягаан', 'size' => '38мм', 'price' => 265000, 'stock' => 0],
                    ['color' => 'Алтан', 'size' => '25мм', 'price' => 255000, 'stock' => 8],
                ],
            ],
            [
                'cat' => 'hair', 'slug' => 'curling-wand-set', 'name' => 'Буржгар хийгч 5-in-1 багц', 'brand' => 'Lescolton',
                'short' => 'Солигддог 5 хушуутай буржгар хийгч, керамик турмалин бүрээстэй.',
                'desc' => 'Нэг төхөөрөмжөөр 5 өөр төрлийн буржгар хийх боломжтой. 9мм-ээс 32мм хүртэлх хушуу дагалдана.',
                'price' => 165000, 'compare' => 199000, 'featured' => false, 'sold' => 54,
                'specs' => ['Хушуу' => '9/13/19/25/32мм', 'Температур' => '80–230°C'],
                'variants' => [
                    ['color' => 'Ягаан', 'price' => 165000, 'stock' => 10],
                    ['color' => 'Хар', 'price' => 165000, 'stock' => 5],
                ],
            ],
            [
                'cat' => 'hair', 'slug' => 'hair-steamer-stand', 'name' => 'Үсний уураар (суурьтай)', 'brand' => 'Salon Pro',
                'short' => 'Кератин, маск, будаг эмчилгээнд зориулсан озонтой мэргэжлийн үсний уураар.',
                'desc' => 'Дугуйтай суурьтай, өндөр тохируулгатай. Эмчилгээний үр дүнг 3 дахин нэмэгдүүлнэ.',
                'price' => 420000, 'compare' => null, 'featured' => true, 'sold' => 21,
                'specs' => ['Чадал' => '650W', 'Таймер' => '0–60 мин', 'Усны сав' => '450мл'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 420000, 'stock' => 4],
                    ['color' => 'Хар', 'price' => 420000, 'stock' => 0],
                ],
            ],
            // ---------------- FURNITURE ----------------
            [
                'cat' => 'furniture', 'slug' => 'hydraulic-salon-chair', 'name' => 'Гидравлик салоны сандал', 'brand' => 'Beauty Line',
                'short' => 'Хромон суурьтай, 360° эргэдэг, өндөр тохируулгатай үсчний сандал.',
                'desc' => "Өндөр чанарын PU арьсан бүрээстэй, 150 кг хүртэл даацтай гидравлик насос.\n\n• Өндөр: 45–58 см\n• Суудлын өргөн: 52 см\n• Баталгаа 12 сар",
                'price' => 690000, 'compare' => 790000, 'featured' => true, 'sold' => 38,
                'specs' => ['Даац' => '150 кг', 'Суурь' => 'Хром, 60см', 'Материал' => 'PU арьс'],
                'variants' => [
                    ['color' => 'Хар', 'price' => 690000, 'stock' => 6],
                    ['color' => 'Цагаан', 'price' => 690000, 'stock' => 3],
                    ['color' => 'Бор', 'price' => 720000, 'stock' => 2],
                    ['color' => 'Ягаан', 'price' => 720000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'furniture', 'slug' => 'shampoo-backwash-unit', 'name' => 'Толгой угаалгын нэгж', 'brand' => 'Beauty Line',
                'short' => 'Керамик раковинтай, налуу тохируулгатай тавтай толгой угаалгын сандал.',
                'desc' => 'Хөлийн тавиур, хүзүүний дэртэй. Халуун хүйтэн усны холигч, шүршүүр дагалдана.',
                'price' => 1250000, 'compare' => null, 'featured' => false, 'sold' => 12,
                'specs' => ['Раковин' => 'Керамик', 'Хэмжээ' => '150×65×95 см'],
                'variants' => [
                    ['color' => 'Хар', 'price' => 1250000, 'stock' => 2],
                    ['color' => 'Цагаан', 'price' => 1290000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'furniture', 'slug' => 'barber-chair-classic', 'name' => 'Барберын сандал Classic', 'brand' => 'Barber King',
                'short' => 'Хүнд даацтай, хойш налдаг, толгойн тулгууртай барберын сандал.',
                'desc' => 'Сахал засах, нүүр арчилгаанд тохиромжтой 180° хойш налдаг. Ган хийцтэй суурь.',
                'price' => 1450000, 'compare' => 1650000, 'featured' => true, 'sold' => 9,
                'specs' => ['Даац' => '200 кг', 'Налалт' => '180°'],
                'variants' => [
                    ['color' => 'Хар', 'price' => 1450000, 'stock' => 3],
                    ['color' => 'Бор', 'price' => 1450000, 'stock' => 1],
                ],
            ],
            [
                'cat' => 'furniture', 'slug' => 'styling-mirror-station', 'name' => 'Толин ажлын станц LED', 'brand' => 'Beauty Line',
                'short' => 'LED гэрэлтэй, тавиур, шүүгээтэй үсчний ажлын станц.',
                'desc' => 'Хоёр талын гэрэлтүүлэгтэй том толь, үс хатаагчийн тавиур, 2 шүүгээтэй.',
                'price' => 890000, 'compare' => null, 'featured' => false, 'sold' => 15,
                'specs' => ['Хэмжээ' => '80×45×200 см', 'Гэрэл' => 'LED 6500K'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 890000, 'stock' => 4],
                    ['color' => 'Хар', 'price' => 890000, 'stock' => 2],
                ],
            ],
            [
                'cat' => 'furniture', 'slug' => 'salon-trolley-cart', 'name' => 'Салоны тэргэнцэр 4 тавиуртай', 'brand' => 'Salon Pro',
                'short' => 'Дугуйтай, 4 тавиуртай, үс хатаагч тогтоогчтой хэрэгслийн тэргэнцэр.',
                'desc' => 'ABS хуванцар, хөнгөн, амархан цэвэрлэгддэг. Будаг, хайч, хэрэгслээ эмхлэхэд төгс.',
                'price' => 145000, 'compare' => 165000, 'featured' => false, 'sold' => 71,
                'specs' => ['Тавиур' => '4', 'Хэмжээ' => '40×35×85 см'],
                'variants' => [
                    ['color' => 'Хар', 'price' => 145000, 'stock' => 20],
                    ['color' => 'Цагаан', 'price' => 145000, 'stock' => 14],
                    ['color' => 'Ягаан', 'price' => 149000, 'stock' => 8],
                ],
            ],
            // ---------------- NAILS ----------------
            [
                'cat' => 'nails', 'slug' => 'uv-led-nail-lamp-120w', 'name' => 'UV LED хумсны лампа 120W', 'brand' => 'SUN X',
                'short' => 'Бүх төрлийн гель хатаадаг, мэдрэгчтэй, 4 таймертай лампа.',
                'desc' => "Гель лак, билдер гель, полигель бүгдийг хатаана.\n\n• 10/30/60/99 сек таймер\n• Хөдөлгөөн мэдрэгч\n• LCD дэлгэц\n• Салдаг ёроол (хөлийн хумсанд)",
                'price' => 89000, 'compare' => 110000, 'featured' => true, 'sold' => 210,
                'specs' => ['Чадал' => '120W', 'LED' => '36 ширхэг', 'Долгион' => '365+405nm'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 89000, 'stock' => 25],
                    ['color' => 'Ягаан', 'price' => 89000, 'stock' => 18],
                    ['color' => 'Хар', 'price' => 89000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'nails', 'slug' => 'electric-nail-drill-35000', 'name' => 'Хумсны цахилгаан машин 35000 RPM', 'brand' => 'Strong',
                'short' => 'Чимээ багатай, бага чичиргээтэй мэргэжлийн маникюр педикюрын машин.',
                'desc' => 'Хөлийн педаль, 6 фрез дагалдана. Эргэлтийн чиглэл солигдоно. Салонд өдөр бүр ашиглахад тохиромжтой.',
                'price' => 320000, 'compare' => null, 'featured' => true, 'sold' => 88,
                'specs' => ['Эргэлт' => '35000 RPM', 'Чадал' => '65W'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 320000, 'stock' => 9],
                    ['color' => 'Ягаан', 'price' => 320000, 'stock' => 5],
                ],
            ],
            [
                'cat' => 'nails', 'slug' => 'gel-polish-set', 'name' => 'Гель лакны багц', 'brand' => 'Kodi Professional',
                'short' => '8мл, 21 хоног барих, өнгө баялаг мэргэжлийн гель лак.',
                'desc' => 'Тэгш бүрхэлт, хурц өнгө. 1, 6, 12 ширхгээр багцалж авах боломжтой.',
                'price' => 12000, 'compare' => null, 'featured' => false, 'sold' => 640,
                'specs' => ['Хэмжээ' => '8 мл', 'Хатаах' => 'UV/LED 60 сек'],
                'variants' => [
                    ['color' => 'Улаан', 'pack_size' => 1, 'pack_label' => '1 ширхэг', 'price' => 12000, 'stock' => 60],
                    ['color' => 'Улаан', 'pack_size' => 6, 'pack_label' => '6-н багц', 'price' => 66000, 'stock' => 10],
                    ['color' => 'Улаан', 'pack_size' => 12, 'pack_label' => '12-н багц', 'price' => 120000, 'stock' => 4],
                    ['color' => 'Ягаан', 'pack_size' => 1, 'pack_label' => '1 ширхэг', 'price' => 12000, 'stock' => 45],
                    ['color' => 'Ягаан', 'pack_size' => 6, 'pack_label' => '6-н багц', 'price' => 66000, 'stock' => 8],
                    ['color' => 'Ягаан', 'pack_size' => 12, 'pack_label' => '12-н багц', 'price' => 120000, 'stock' => 0],
                    ['color' => 'Тунгалаг', 'pack_size' => 1, 'pack_label' => '1 ширхэг', 'price' => 12000, 'stock' => 80],
                    ['color' => 'Тунгалаг', 'pack_size' => 6, 'pack_label' => '6-н багц', 'price' => 66000, 'stock' => 12],
                    ['color' => 'Бежевый', 'pack_size' => 1, 'pack_label' => '1 ширхэг', 'price' => 12000, 'stock' => 30],
                    ['color' => 'Бежевый', 'pack_size' => 12, 'pack_label' => '12-н багц', 'price' => 120000, 'stock' => 3],
                ],
            ],
            [
                'cat' => 'nails', 'slug' => 'manicure-table-with-dust-collector', 'name' => 'Маникюрын ширээ тоос сорогчтой', 'brand' => 'Beauty Line',
                'short' => 'Суурилуулсан тоос сорогч, LED гэрэл, шүүгээтэй маникюрын ширээ.',
                'desc' => 'Ажлын орчинг цэвэр байлгах 60W тоос сорогч суурилагдсан. Гарын дэр дагалдана.',
                'price' => 560000, 'compare' => 640000, 'featured' => false, 'sold' => 17,
                'specs' => ['Хэмжээ' => '100×45×75 см', 'Тоос сорогч' => '60W'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 560000, 'stock' => 3],
                    ['color' => 'Хар', 'price' => 560000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'nails', 'slug' => 'nail-brush-set', 'name' => 'Хумсны зураг багсны багц', 'brand' => 'Kodi Professional',
                'short' => 'Нарийн зураг, градиент, тодруулгад зориулсан 15 ширхэг багс.',
                'desc' => 'Нейлон үстэй, металл бариултай. Салоны мастеруудад тохиромжтой бүрэн багц.',
                'price' => 28000, 'compare' => null, 'featured' => false, 'sold' => 155,
                'specs' => ['Тоо' => '15 ширхэг'],
                'variants' => [
                    ['color' => 'Ягаан', 'pack_size' => 1, 'pack_label' => '1 багц', 'price' => 28000, 'stock' => 25],
                    ['color' => 'Ягаан', 'pack_size' => 5, 'pack_label' => '5 багц', 'price' => 125000, 'stock' => 5],
                    ['color' => 'Хар', 'pack_size' => 1, 'pack_label' => '1 багц', 'price' => 28000, 'stock' => 18],
                ],
            ],
            // ---------------- SKIN ----------------
            [
                'cat' => 'skin', 'slug' => 'facial-steamer-ozone', 'name' => 'Нүүрний уураар озонтой', 'brand' => 'Salon Pro',
                'short' => 'Нүүрний нүх сүв нээх, гүн цэвэрлэгээнд зориулсан суурьтай уураар.',
                'desc' => 'Озон функц нь бактери устгана. Дугуйтай суурь, эргэдэг хушуу. Таймер 0–60 мин.',
                'price' => 380000, 'compare' => null, 'featured' => false, 'sold' => 33,
                'specs' => ['Чадал' => '750W', 'Усны сав' => '500мл'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 380000, 'stock' => 5],
                ],
            ],
            [
                'cat' => 'skin', 'slug' => 'led-light-therapy-mask', 'name' => 'LED гэрлийн эмчилгээний маск 7 өнгө', 'brand' => 'DermaLux',
                'short' => 'Батга, үрчлээ, пигментацийн эсрэг 7 өнгийн фотон LED маск.',
                'desc' => 'Улаан, хөх, ногоон, шар, ягаан, цайвар хөх, цагаан гэрлийн горим. Хүзүүний хэсэгтэй.',
                'price' => 210000, 'compare' => 260000, 'featured' => true, 'sold' => 76,
                'specs' => ['LED' => '150 ширхэг', 'Горим' => '7 өнгө'],
                'variants' => [
                    ['color' => 'Цагаан', 'size' => 'Нүүр', 'price' => 210000, 'stock' => 11],
                    ['color' => 'Цагаан', 'size' => 'Нүүр + хүзүү', 'price' => 260000, 'stock' => 4],
                ],
            ],
            [
                'cat' => 'skin', 'slug' => 'hydrafacial-machine-7in1', 'name' => 'Гидрафейшл аппарат 7-in-1', 'brand' => 'HydroPro',
                'short' => 'Гидродермабразия, ультрасоник, RF, хүчилтөрөгч шүршигч бүхий косметологийн аппарат.',
                'desc' => "Салоны хамгийн эрэлттэй нүүрний эмчилгээг нэг аппаратаар.\n\n• Гидродермабразия\n• Ультрасоник хусуур\n• Хүйтэн/халуун бариул\n• RF үрчлээ арилгагч\n• Хүчилтөрөгч шүршигч\n• Био микротоглогч\n• LED",
                'price' => 2900000, 'compare' => 3400000, 'featured' => true, 'sold' => 6,
                'specs' => ['Функц' => '7', 'Дэлгэц' => '10" мэдрэгчтэй', 'Баталгаа' => '12 сар'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 2900000, 'stock' => 2],
                    ['color' => 'Хар', 'price' => 2900000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'skin', 'slug' => 'magnifying-lamp-5x', 'name' => 'Томруулдаг LED лампа 5X', 'brand' => 'Salon Pro',
                'short' => 'Сормуус, нүүр арчилгаанд зориулсан суурьтай томруулдаг гэрэл.',
                'desc' => 'Хүйтэн LED гэрэл, 5 дахин томруулдаг линз. Дугуйтай суурьтай эсвэл ширээнд хавчдаг хувилбар.',
                'price' => 135000, 'compare' => null, 'featured' => false, 'sold' => 49,
                'specs' => ['Томруулалт' => '5X', 'Гэрэл' => 'LED 12W'],
                'variants' => [
                    ['color' => 'Цагаан', 'size' => 'Суурьтай', 'price' => 135000, 'stock' => 7],
                    ['color' => 'Цагаан', 'size' => 'Хавчаартай', 'price' => 95000, 'stock' => 12],
                ],
            ],
            [
                'cat' => 'skin', 'slug' => 'facial-bed-electric', 'name' => 'Косметологийн цахилгаан ор', 'brand' => 'Beauty Line',
                'short' => '3 мотортой, удирдлагатай, сормуус, нүүр, массажийн ор.',
                'desc' => 'Өндөр, толгой, хөлийн хэсэг тус тусдаа цахилгаан тохируулгатай. 250 кг даац.',
                'price' => 1850000, 'compare' => null, 'featured' => false, 'sold' => 7,
                'specs' => ['Мотор' => '3', 'Даац' => '250 кг', 'Хэмжээ' => '190×70 см'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 1850000, 'stock' => 2],
                    ['color' => 'Хар', 'price' => 1850000, 'stock' => 1],
                    ['color' => 'Ягаан', 'price' => 1890000, 'stock' => 0],
                ],
            ],
            // ---------------- TOOLS ----------------
            [
                'cat' => 'tools', 'slug' => 'professional-hair-scissors', 'name' => 'Мэргэжлийн үсний хайч', 'brand' => 'Jaguar',
                'short' => 'Японы 440C гангаар хийсэн, гар ядраахгүй эргономик мэргэжлийн хайч.',
                'desc' => 'Хурц ирмэг, зөөлөн хөдөлгөөн. 5.5", 6.0", 6.5" хэмжээтэй.',
                'price' => 185000, 'compare' => 220000, 'featured' => true, 'sold' => 124,
                'specs' => ['Ган' => 'Japanese 440C', 'Хатуулаг' => '58–60 HRC'],
                'variants' => [
                    ['color' => 'Мөнгөлөг', 'size' => '5.5"', 'price' => 185000, 'stock' => 10],
                    ['color' => 'Мөнгөлөг', 'size' => '6.0"', 'price' => 195000, 'stock' => 8],
                    ['color' => 'Мөнгөлөг', 'size' => '6.5"', 'price' => 205000, 'stock' => 0],
                    ['color' => 'Алтан', 'size' => '5.5"', 'price' => 215000, 'stock' => 3],
                    ['color' => 'Алтан', 'size' => '6.0"', 'price' => 225000, 'stock' => 2],
                    ['color' => 'Хар', 'size' => '6.0"', 'price' => 215000, 'stock' => 5],
                ],
            ],
            [
                'cat' => 'tools', 'slug' => 'thinning-scissors', 'name' => 'Нимгэлэгч хайч 30 шүдтэй', 'brand' => 'Jaguar',
                'short' => 'Үсийг зөөлөн нимгэлж, байгалийн хэлбэр гаргах хайч.',
                'desc' => '30 шүдтэй, 15–20% нимгэлэлт. Үндсэн хайчтай хослуулан хэрэглэнэ.',
                'price' => 165000, 'compare' => null, 'featured' => false, 'sold' => 67,
                'specs' => ['Шүд' => '30', 'Нимгэлэлт' => '15–20%'],
                'variants' => [
                    ['color' => 'Мөнгөлөг', 'size' => '5.5"', 'price' => 165000, 'stock' => 9],
                    ['color' => 'Мөнгөлөг', 'size' => '6.0"', 'price' => 175000, 'stock' => 4],
                    ['color' => 'Алтан', 'size' => '6.0"', 'price' => 195000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'tools', 'slug' => 'carbon-comb-set', 'name' => 'Карбон самны багц 10 ширхэг', 'brand' => 'Y.S. Park',
                'short' => 'Халуунд тэсвэртэй, статикгүй, мэргэжлийн карбон самны бүрэн багц.',
                'desc' => 'Засалт, будаг, хэв гаргах бүх төрлийн сам нэг багцад.',
                'price' => 45000, 'compare' => 55000, 'featured' => false, 'sold' => 189,
                'specs' => ['Тоо' => '10 ширхэг', 'Материал' => 'Карбон'],
                'variants' => [
                    ['color' => 'Хар', 'pack_size' => 1, 'pack_label' => '1 багц', 'price' => 45000, 'stock' => 30],
                    ['color' => 'Хар', 'pack_size' => 5, 'pack_label' => '5 багц', 'price' => 200000, 'stock' => 6],
                    ['color' => 'Ягаан', 'pack_size' => 1, 'pack_label' => '1 багц', 'price' => 45000, 'stock' => 12],
                ],
            ],
            [
                'cat' => 'tools', 'slug' => 'hair-clipper-cordless', 'name' => 'Утасгүй үс засах машин', 'brand' => 'Wahl',
                'short' => 'Литий батерейтай, 3 цаг ажилладаг, 8 хамгаалалттай мэргэжлийн машин.',
                'desc' => 'Магнит мотор, титан ир. Барбер, үсчний өдөр тутмын хэрэглээнд.',
                'price' => 275000, 'compare' => null, 'featured' => true, 'sold' => 92,
                'specs' => ['Батерей' => '180 мин', 'Ир' => 'Титан', 'Хамгаалалт' => '8 ширхэг'],
                'variants' => [
                    ['color' => 'Хар', 'price' => 275000, 'stock' => 14],
                    ['color' => 'Алтан', 'price' => 295000, 'stock' => 6],
                    ['color' => 'Мөнгөлөг', 'price' => 285000, 'stock' => 0],
                ],
            ],
            // ---------------- SUPPLIES ----------------
            [
                'cat' => 'supplies', 'slug' => 'wax-heater-double', 'name' => 'Лав халаагч давхар', 'brand' => 'Pro Wax',
                'short' => 'Хоёр савтай, тус тусдаа температур тохируулгатай депиляцийн лав халаагч.',
                'desc' => '2×500мл сав, 35–110°C тохируулга. Халуун, хүйтэн лав хоёуланд тохиромжтой.',
                'price' => 95000, 'compare' => 115000, 'featured' => false, 'sold' => 58,
                'specs' => ['Сав' => '2×500мл', 'Температур' => '35–110°C'],
                'variants' => [
                    ['color' => 'Цагаан', 'price' => 95000, 'stock' => 9],
                    ['color' => 'Ягаан', 'price' => 95000, 'stock' => 6],
                ],
            ],
            [
                'cat' => 'supplies', 'slug' => 'disposable-towels-pack', 'name' => 'Нэг удаагийн алчуур', 'brand' => 'Salon Pro',
                'short' => '40×80 см, өндөр шингээлттэй, зөөлөн нэг удаагийн салоны алчуур.',
                'desc' => 'Эрүүл ахуйн шаардлага хангасан, угаах шаардлагагүй. 50, 100, 500 ширхгээр.',
                'price' => 25000, 'compare' => null, 'featured' => false, 'sold' => 420,
                'specs' => ['Хэмжээ' => '40×80 см', 'Жин' => '60 гсм'],
                'variants' => [
                    ['color' => 'Цагаан', 'pack_size' => 50, 'pack_label' => '50 ширхэг', 'price' => 25000, 'stock' => 40],
                    ['color' => 'Цагаан', 'pack_size' => 100, 'pack_label' => '100 ширхэг', 'price' => 45000, 'stock' => 25],
                    ['color' => 'Цагаан', 'pack_size' => 500, 'pack_label' => '500 ширхэг', 'price' => 200000, 'stock' => 5],
                    ['color' => 'Ягаан', 'pack_size' => 50, 'pack_label' => '50 ширхэг', 'price' => 25000, 'stock' => 15],
                    ['color' => 'Ягаан', 'pack_size' => 100, 'pack_label' => '100 ширхэг', 'price' => 45000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'supplies', 'slug' => 'nitrile-gloves-box', 'name' => 'Нитрил бээлий (100ш хайрцаг)', 'brand' => 'SafeTouch',
                'short' => 'Нунтаггүй, харшил үүсгэдэггүй, будаг, химид тэсвэртэй нитрил бээлий.',
                'desc' => 'Үс будах, маникюр, косметологийн үйлчилгээнд. S, M, L, XL хэмжээ.',
                'price' => 32000, 'compare' => null, 'featured' => false, 'sold' => 380,
                'specs' => ['Тоо' => '100 ш/хайрцаг', 'Материал' => 'Нитрил'],
                'variants' => [
                    ['color' => 'Хар', 'size' => 'S', 'pack_size' => 1, 'pack_label' => '1 хайрцаг', 'price' => 32000, 'stock' => 20],
                    ['color' => 'Хар', 'size' => 'M', 'pack_size' => 1, 'pack_label' => '1 хайрцаг', 'price' => 32000, 'stock' => 35],
                    ['color' => 'Хар', 'size' => 'L', 'pack_size' => 1, 'pack_label' => '1 хайрцаг', 'price' => 32000, 'stock' => 22],
                    ['color' => 'Хар', 'size' => 'M', 'pack_size' => 10, 'pack_label' => '10 хайрцаг', 'price' => 290000, 'stock' => 4],
                    ['color' => 'Ягаан', 'size' => 'S', 'pack_size' => 1, 'pack_label' => '1 хайрцаг', 'price' => 34000, 'stock' => 12],
                    ['color' => 'Ягаан', 'size' => 'M', 'pack_size' => 1, 'pack_label' => '1 хайрцаг', 'price' => 34000, 'stock' => 0],
                    ['color' => 'Хөх', 'size' => 'M', 'pack_size' => 1, 'pack_label' => '1 хайрцаг', 'price' => 30000, 'stock' => 28],
                    ['color' => 'Хөх', 'size' => 'L', 'pack_size' => 1, 'pack_label' => '1 хайрцаг', 'price' => 30000, 'stock' => 16],
                ],
            ],
            [
                'cat' => 'supplies', 'slug' => 'salon-cape-waterproof', 'name' => 'Салоны нөмрөг ус нэвтрүүлдэггүй', 'brand' => 'Salon Pro',
                'short' => 'Үс засалт, будагт зориулсан хөнгөн, ус үл нэвтрэх нөмрөг.',
                'desc' => 'Хүзүүний тохируулгатай товчтой. 140×160 см. Угааж дахин хэрэглэнэ.',
                'price' => 18000, 'compare' => null, 'featured' => false, 'sold' => 260,
                'specs' => ['Хэмжээ' => '140×160 см', 'Материал' => 'Полиэстер PU'],
                'variants' => [
                    ['color' => 'Хар', 'pack_size' => 1, 'pack_label' => '1 ширхэг', 'price' => 18000, 'stock' => 50],
                    ['color' => 'Хар', 'pack_size' => 6, 'pack_label' => '6 ширхэг', 'price' => 96000, 'stock' => 10],
                    ['color' => 'Ягаан', 'pack_size' => 1, 'pack_label' => '1 ширхэг', 'price' => 18000, 'stock' => 22],
                    ['color' => 'Саарал', 'pack_size' => 1, 'pack_label' => '1 ширхэг', 'price' => 18000, 'stock' => 0],
                ],
            ],
            [
                'cat' => 'supplies', 'slug' => 'foil-roll-for-highlights', 'name' => 'Тугалган цаас (мелирование)', 'brand' => 'Framar',
                'short' => 'Урьдчилан хэвлэсэн, тасардаггүй мэргэжлийн үс будгийн тугалган цаас.',
                'desc' => '12 см өргөн, 100 м урт. Хайрцагтай, зүсэгчтэй.',
                'price' => 22000, 'compare' => null, 'featured' => false, 'sold' => 310,
                'specs' => ['Өргөн' => '12 см', 'Урт' => '100 м'],
                'variants' => [
                    ['color' => 'Мөнгөлөг', 'pack_size' => 1, 'pack_label' => '1 роль', 'price' => 22000, 'stock' => 40],
                    ['color' => 'Мөнгөлөг', 'pack_size' => 12, 'pack_label' => '12 роль', 'price' => 240000, 'stock' => 3],
                    ['color' => 'Ягаан', 'pack_size' => 1, 'pack_label' => '1 роль', 'price' => 24000, 'stock' => 15],
                ],
            ],
            [
                'cat' => 'supplies', 'slug' => 'hair-color-bowl-brush-set', 'name' => 'Будгийн аяга, багсны багц', 'brand' => 'Framar',
                'short' => 'Хэмжээстэй аяга, 3 багс, хавчаар бүхий үс будгийн багц.',
                'desc' => 'Гулсдаггүй резинэн ёроолтой аяга. Химид тэсвэртэй багс.',
                'price' => 15000, 'compare' => 19000, 'featured' => false, 'sold' => 275,
                'specs' => ['Багц' => 'Аяга + 3 багс + 2 хавчаар'],
                'variants' => [
                    ['color' => 'Хар', 'pack_size' => 1, 'pack_label' => '1 багц', 'price' => 15000, 'stock' => 36],
                    ['color' => 'Хар', 'pack_size' => 10, 'pack_label' => '10 багц', 'price' => 135000, 'stock' => 5],
                    ['color' => 'Ягаан', 'pack_size' => 1, 'pack_label' => '1 багц', 'price' => 15000, 'stock' => 28],
                    ['color' => 'Ногоон', 'pack_size' => 1, 'pack_label' => '1 багц', 'price' => 15000, 'stock' => 0],
                ],
            ],
        ];

        foreach ($products as $p) {
            $product = Product::updateOrCreate(['slug' => $p['slug']], [
                'category_id' => $catModels[$p['cat']]->id,
                'name' => $p['name'],
                'brand' => $p['brand'],
                'short_description' => $p['short'],
                'description' => $p['desc'],
                'base_price' => min(array_column($p['variants'], 'price')),
                'compare_price' => $p['compare'],
                'images' => ["/images/photos/{$p['slug']}.jpg"],
                'specs' => $p['specs'],
                'is_active' => true,
                'is_featured' => $p['featured'],
                'allow_backorder' => true,
                'backorder_days' => rand(7, 21),
                'sold_count' => $p['sold'],
                'rating' => round(rand(40, 50) / 10, 1),
                'reviews_count' => (int) max(3, round($p['sold'] * 0.35)),
                'views' => rand(50, 900),
            ]);

            $product->variants()->delete();
            foreach ($p['variants'] as $i => $v) {
                $product->variants()->create([
                    'sku' => strtoupper(Str::substr(Str::slug($p['slug'], ''), 0, 6)).'-'.$product->id.'-'.($i + 1),
                    'color' => $v['color'] ?? null,
                    'color_hex' => isset($v['color']) ? ($this->colors[$v['color']] ?? null) : null,
                    'size' => $v['size'] ?? null,
                    'pack_size' => $v['pack_size'] ?? 1,
                    'pack_label' => $v['pack_label'] ?? null,
                    'price' => $v['price'],
                    'stock' => $v['stock'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
