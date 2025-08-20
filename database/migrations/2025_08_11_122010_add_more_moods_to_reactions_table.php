<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update the enum to include the original moods + the new ones
        DB::statement("
            ALTER TABLE reactions 
            MODIFY mood ENUM(
                'relaxed','craving','hyped','obsessed',
                'chill','cozy','daydreaming','playful','euphoric',
                'spicy','moody','mysterious','petty','unbothered',
                'dead','chaotic','inspired','vibey','silly','curious'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        // Rollback to the original enum definition
        DB::statement("
            ALTER TABLE reactions 
            MODIFY mood ENUM('relaxed','craving','hyped','obsessed') NOT NULL
        ");
    }
};
