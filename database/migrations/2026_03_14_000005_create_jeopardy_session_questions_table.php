<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_session_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('jeopardy_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('jeopardy_questions')->cascadeOnDelete();

            // Has this tile been revealed/used on the board?
            $table->boolean('is_revealed')->default(false);

            // Current zoom level for zoom_image type (start 4, step down to 1)
            $table->unsignedTinyInteger('zoom_level')->default(4);

            $table->unique(['session_id', 'question_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_session_questions');
    }
};
