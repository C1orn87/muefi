<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Extend jeopardy_sessions ────────────────────────────────────────────
        Schema::table('jeopardy_sessions', function (Blueprint $table) {
            $table->boolean('show_answer')->default(false)->after('active_question_id');
            $table->boolean('buzzer_open')->default(false)->after('show_answer');
            $table->timestamp('question_opened_at')->nullable()->after('buzzer_open');
            $table->unsignedTinyInteger('buzzer_delay_seconds')->default(3)->after('question_opened_at');
        });

        // ── Buzzes ──────────────────────────────────────────────────────────────
        Schema::create('jeopardy_buzzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('jeopardy_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('jeopardy_questions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('jeopardy_players')->cascadeOnDelete();
            $table->unsignedSmallInteger('buzz_order');
            $table->timestamp('buzzed_at');

            $table->unique(['session_id', 'question_id', 'player_id']);
        });

        // ── Number guesses ──────────────────────────────────────────────────────
        Schema::create('jeopardy_guesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('jeopardy_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('jeopardy_questions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('jeopardy_players')->cascadeOnDelete();
            $table->decimal('guess', 12, 2);
            $table->timestamp('submitted_at');

            $table->unique(['session_id', 'question_id', 'player_id']); // one guess per player
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_guesses');
        Schema::dropIfExists('jeopardy_buzzes');

        Schema::table('jeopardy_sessions', function (Blueprint $table) {
            $table->dropColumn(['show_answer', 'buzzer_open', 'question_opened_at', 'buzzer_delay_seconds']);
        });
    }
};
