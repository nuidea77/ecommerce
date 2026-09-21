<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Админ', 'email' => 'admin@chanaresui.mn', 'phone' => '99001122', 'role' => 'admin', 'city' => 'Улаанбаатар'],
            ['name' => 'Батбаяр', 'email' => 'courier@chanaresui.mn', 'phone' => '88112233', 'role' => 'courier', 'city' => 'Улаанбаатар'],
            ['name' => 'Тэмүүлэн', 'email' => 'courier2@chanaresui.mn', 'phone' => '88445566', 'role' => 'courier', 'city' => 'Улаанбаатар'],
            ['name' => 'Сарнай', 'email' => 'customer@chanaresui.mn', 'phone' => '99887766', 'role' => 'customer', 'city' => 'Улаанбаатар', 'address' => 'Хан-Уул дүүрэг, 15-р хороо, Мишээл экспо, Beauty Studio', 'is_verified' => true, 'verified_at' => now()->subDays(30), 'verify_provider' => 'sms-mock', 'verified_phone' => '99887766'],
            ['name' => 'Номин', 'email' => 'nomin@salon.mn', 'phone' => '95123456', 'role' => 'customer', 'city' => 'Улаанбаатар', 'address' => 'Баянзүрх дүүрэг, 26-р хороо, Nomin Beauty Salon', 'is_verified' => true, 'verified_at' => now()->subDays(12), 'verify_provider' => 'sms-mock', 'verified_phone' => '95123456'],
            ['name' => 'Ариунаа', 'email' => 'ariunaa@salon.mn', 'phone' => '96543210', 'role' => 'customer', 'city' => 'Дархан', 'address' => 'Дархан сум, 5-р баг, Glam Salon'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['phone' => $u['phone']], $u + ['password' => 'password']);
        }

        $addresses = [
            '99887766' => [
                ['label' => 'Салон', 'recipient_name' => 'Сарнай', 'phone' => '99887766', 'province' => 'Улаанбаатар', 'district' => 'Хан-Уул', 'khoroo' => '15', 'address' => 'Мишээл экспо, Beauty Studio, 2 давхар', 'is_default' => true],
                ['label' => 'Гэр', 'recipient_name' => 'Сарнай', 'phone' => '99887766', 'province' => 'Улаанбаатар', 'district' => 'Баянзүрх', 'khoroo' => '26', 'address' => 'Жуковын 45-р байр, 3 орц, 56 тоот', 'is_default' => false],
            ],
            '95123456' => [
                ['label' => 'Салон', 'recipient_name' => 'Номин', 'phone' => '95123456', 'province' => 'Улаанбаатар', 'district' => 'Баянгол', 'khoroo' => '6', 'address' => 'Nomin Beauty Salon, Ард Аюушийн өргөн чөлөө 12', 'is_default' => true],
            ],
            '96543210' => [
                ['label' => 'Салон', 'recipient_name' => 'Ариунаа', 'phone' => '96543210', 'province' => 'Дархан-Уул', 'district' => 'Дархан', 'khoroo' => '5', 'address' => 'Glam Salon, Их дэлгүүрийн хойно', 'is_default' => true],
            ],
        ];
        foreach ($addresses as $phone => $rows) {
            $user = User::where('phone', $phone)->first();
            if ($user && ! $user->addresses()->exists()) {
                $user->addresses()->createMany($rows);
            }
        }
    }
}
