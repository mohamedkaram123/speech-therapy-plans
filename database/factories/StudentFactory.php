<?php

namespace Database\Factories;

use App\Enums\StudentPeriod;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Support\HijriDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        $birth = fake()->dateTimeBetween('-14 years', '-4 years');

        return [
            'full_name' => fake()->randomElement(['أحمد', 'محمد', 'سارة', 'نورة', 'خالد', 'ليان']).' '
                .fake()->randomElement(['عبدالله', 'سعيد', 'الشريف', 'القحطاني', 'الحربي']),
            'birth_date' => $birth->format('Y-m-d'),
            'birth_date_hijri' => HijriDate::toHijri(\Illuminate\Support\Carbon::instance($birth)),
            'teacher_id' => User::factory()->state(['role' => UserRole::Teacher]),
            'period' => fake()->randomElement(StudentPeriod::cases()),
            'diagnosis' => fake()->optional()->randomElement(['تأخر لغوي', 'اضطراب نطق', 'صعوبات تواصل']),
        ];
    }
}
