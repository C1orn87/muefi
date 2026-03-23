<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_click_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id') ->constrained('jeopardy_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('jeopardy_questions')->cascadeOnDelete();
            $table->foreignId('player_id')  ->constrained('jeopardy_players')->cascadeOnDelete();
            $table->decimal('x_pct', 6, 3); // 0.000 – 100.000
            $table->decimal('y_pct', 6, 3);
            $table->timestamps();

            // One vote per player per question per session; replace on re-click
            $table->unique(['session_id', 'question_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_click_votes');
    }
};
