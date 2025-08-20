<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoToMoodBoardsTable extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('mood_boards', function (Blueprint $table) {
        $table->string('video')->nullable()->after('image');
    });
}

public function down(): void
{
    Schema::table('mood_boards', function (Blueprint $table) {
        $table->dropColumn('video');
    });
}

};
