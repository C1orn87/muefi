<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('jeopardy_boards')->cascadeOnDelete();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();

            // Unique shareable code, e.g. "awxhawxt"
            $table->string('code', 12)->unique();

            // Status: lobby | active | finished
            $table->string('status', 16)->default('lobby');

            // Scoring toggle: 100 | 50 | 0
            $table->unsignedTinyInteger('point_percentage')->default(100);

            // Currently active question (null = no question selected)
            $table->foreignId('active_question_id')
                  ->nullable()
                  ->constrained('jeopardy_questions')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_sessions');
    }
};
