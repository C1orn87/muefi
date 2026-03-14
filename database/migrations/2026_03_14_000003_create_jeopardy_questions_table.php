<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('jeopardy_categories')->cascadeOnDelete();
            $table->unsignedInteger('points')->default(200);
            $table->unsignedTinyInteger('order')->default(0);

            // Question content
            $table->text('question_text')->nullable();
            $table->text('answer_text')->nullable();

            // Media type: text | image | zoom_image | audio | video | youtube
            $table->string('question_type')->default('text');

            // For file-based media (image, zoom_image, audio, video)
            $table->string('media_path')->nullable();

            // For YouTube URLs
            $table->string('media_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_questions');
    }
};
