<?php

namespace Database\Factories;

use App\Models\BankGoal;
use App\Models\GoalDomain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BankGoal>
 */
class BankGoalFactory extends Factory
{
    public function definition(): array
    {
        $text = 'أن ينطق الطالب '.fake()->unique()->words(3, true).' بشكل صحيح '.Str::random(6);

        return [
            'uuid' => (string) Str::uuid(),
            'goal_domain_id' => GoalDomain::factory(),
            'level' => fake()->randomElement(['المستوى الأول', 'المستوى الثاني']),
            'subcategory' => null,
            'printed_code' => (string) fake()->unique()->numberBetween(1, 9999),
            'goal_text' => $text,
            'training_elements' => null,
            'generalization' => null,
            'fingerprint' => hash('sha256', $text),
            'needs_review' => false,
            'review_reason' => null,
        ];
    }
}
