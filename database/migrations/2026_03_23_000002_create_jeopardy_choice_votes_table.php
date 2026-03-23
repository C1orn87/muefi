<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_choice_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('jeopardy_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('jeopardy_questions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('jeopardy_players')->cascadeOnDelete();

            // 0-based index of the selected choice / image slot
            $table->unsignedTinyInteger('choice_index');

            $table->timestamps();

            // One vote per player per question per session (upsertable)
            $table->unique(['session_id', 'question_id', 'player_id'], 'unique_vote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_choice_votes');
    }
};
