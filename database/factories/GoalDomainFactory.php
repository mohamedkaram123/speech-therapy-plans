<?php

namespace Database\Factories;

use App\Models\GoalDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalDomain>
 */
class GoalDomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'مجال '.fake()->unique()->numberBetween(1, 100000),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
