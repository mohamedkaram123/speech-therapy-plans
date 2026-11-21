<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'supervisor@example.com'], [
            'name' => 'المشرف العام',
            'password' => 'password',
            'role' => UserRole::Supervisor,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'teacher@example.com'], [
            'name' => 'معلم التخاطب',
            'password' => 'password',
            'role' => UserRole::Teacher,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
