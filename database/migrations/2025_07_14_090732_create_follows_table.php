<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade'); // the one doing the following
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade'); // the one being followed
            $table->timestamps();

            $table->unique(['follower_id', 'following_id']); // prevent duplicate follows
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};

