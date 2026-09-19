<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Админ', 'email' => 'admin@beautypro.mn', 'phone' => '99001122', 'role' => 'admin', 'city' => 'Улаанбаатар'],
            ['name' => 'Батбаяр', 'email' => 'courier@beautypro.mn', 'phone' => '88112233', 'role' => 'courier', 'city' => 'Улаанбаатар'],
            ['name' => 'Тэмүүлэн', 'email' => 'courier2@beautypro.mn', 'phone' => '88445566', 'role' => 'courier', 'city' => 'Улаанбаатар'],
            ['name' => 'Сарнай', 'email' => 'customer@beautypro.mn', 'phone' => '99887766', 'role' => 'customer', 'city' => 'Улаанбаатар', 'address' => 'Хан-Уул дүүрэг, 15-р хороо, Мишээл экспо, Beauty Studio', 'is_verified' => true, 'verified_at' => now()->subDays(30), 'verify_provider' => 'verify.mn-mock', 'verified_phone' => '99887766'],
            ['name' => 'Номин', 'email' => 'nomin@salon.mn', 'phone' => '95123456', 'role' => 'customer', 'city' => 'Улаанбаатар', 'address' => 'Баянзүрх дүүрэг, 26-р хороо, Nomin Beauty Salon', 'is_verified' => true, 'verified_at' => now()->subDays(12), 'verify_provider' => 'verify.mn-mock', 'verified_phone' => '95123456'],
            ['name' => 'Ариунаа', 'email' => 'ariunaa@salon.mn', 'phone' => '96543210', 'role' => 'customer', 'city' => 'Дархан', 'address' => 'Дархан сум, 5-р баг, Glam Salon'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u + ['password' => 'password']);
        }
    }
}
