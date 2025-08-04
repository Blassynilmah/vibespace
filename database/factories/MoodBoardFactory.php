<?php

namespace Database\Factories;

use App\Models\MoodBoard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MoodBoardFactory extends Factory
{
    protected $model = MoodBoard::class;

    public function definition()
    {
        return [
            'user_id'     => User::factory(), // or an existing user ID if already seeded
            'title'       => $this->faker->words(3, true),
            'description' => $this->faker->sentence(12),
            'latest_mood' => $this->faker->randomElement(['relaxed', 'craving', 'hyped', 'obsessed']),
        ];
    }
}

