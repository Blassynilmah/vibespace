<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MoodBoard;

class MoodBoardSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            'A chill board for rainy days and tea ☕🌧️',
            'Just vibing in the moment 😌✨',
            'For the obsessed fans only 🫠🔥',
            'Midnight cravings hit different 🌙🤤',
        ];

        $moods = ['relaxed', 'craving', 'hyped', 'obsessed'];

        $presetBoards = [
            ['title' => 'Stay Vibes', 'user_id' => 10],
            ['title' => 'Stay Cozy Vibes', 'user_id' => 7],
            ['title' => 'Late Night Loops', 'user_id' => 9],
            ['title' => 'VibeVerse', 'user_id' => 5],
            ['title' => 'Sunset Feels', 'user_id' => 2],
            ['title' => 'Rainy Day Dreaming', 'user_id' => 6],
            ['title' => 'Lo-Fi Feels', 'user_id' => 3],
            ['title' => 'MoodWaves', 'user_id' => 4],
            ['title' => 'Moodies Anonymous', 'user_id' => 1],
        ];

        foreach ($presetBoards as $board) {
            MoodBoard::create([
                'title'        => $board['title'],
                'description'  => $descriptions[array_rand($descriptions)],
                'user_id'      => $board['user_id'],
                'post_count'   => rand(2, 12),
                'latest_mood'  => $moods[array_rand($moods)],
                'created_at'   => now()->subHours(rand(1, 48)),
                'updated_at'   => now(),
            ]);
        }

        // Optional factory usage if you've defined one
        // MoodBoard::factory()->count(10)->create();
    }
}
