<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jeopardy_sessions', function (Blueprint $table) {
            // Whose turn it is to pick a card (null = no turn system / free-for-all)
            $table->unsignedBigInteger('current_turn_player_id')
                  ->nullable()
                  ->after('buzzer_delay_seconds');

            // The card a player has selected but the host hasn't opened yet
            $table->unsignedBigInteger('pending_question_id')
                  ->nullable()
                  ->after('current_turn_player_id');

            $table->foreign('current_turn_player_id')
                  ->references('id')
                  ->on('jeopardy_players')
                  ->nullOnDelete();

            $table->foreign('pending_question_id')
                  ->references('id')
                  ->on('jeopardy_questions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jeopardy_sessions', function (Blueprint $table) {
            $table->dropForeign(['current_turn_player_id']);
            $table->dropForeign(['pending_question_id']);
            $table->dropColumn(['current_turn_player_id', 'pending_question_id']);
        });
    }
};
