<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('jeopardy_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->integer('score')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_teams');
    }
};
