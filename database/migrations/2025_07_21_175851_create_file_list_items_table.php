<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileListItemsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_list_id')->constrained('file_lists')->onDelete('cascade');
            $table->foreignId('file_id')->constrained('user_files')->onDelete('cascade');           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_list_items');
    }
};
