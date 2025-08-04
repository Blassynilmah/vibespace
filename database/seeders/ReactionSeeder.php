<?php

// database/seeders/ReactionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reaction;

class ReactionSeeder extends Seeder
{
    public function run(): void
    {
        Reaction::create([
            'user_id' => 1,
            'mood_board_id' => 1,
            'mood' => 'hyped',
        ]);
    }
}
