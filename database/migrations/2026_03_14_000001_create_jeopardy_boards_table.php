<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedTinyInteger('columns')->default(6);  // number of categories
            $table->unsignedTinyInteger('rows')->default(5);     // number of point rows
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_boards');
    }
};
