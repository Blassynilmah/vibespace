<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('message_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('file_path');
            $table->string('file_name')->nullable(); // ✅ no change()
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->json('meta')->nullable(); // optional: dimensions, duration, etc.

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
