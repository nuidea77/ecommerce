<?php

return [
    'name' => env('SHOP_NAME', 'Чанар Есүй'),
    'currency' => '₮',
    'shipping_fee' => (float) env('SHOP_SHIPPING_FEE', 5000),
    'free_shipping_threshold' => (float) env('SHOP_FREE_SHIPPING', 300000),
    'cities' => ['Улаанбаатар', 'Дархан', 'Эрдэнэт', 'Чойбалсан', 'Ховд', 'Мөрөн', 'Бусад'],
    'districts' => [
        'Баянгол', 'Баянзүрх', 'Сүхбаатар', 'Хан-Уул', 'Чингэлтэй', 'Сонгинохайрхан', 'Налайх', 'Багануур', 'Багахангай',
    ],
];
