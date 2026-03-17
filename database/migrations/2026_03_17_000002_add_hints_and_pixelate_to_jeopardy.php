<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── hints on each question (ordered array of hint strings) ──────────────
        Schema::table('jeopardy_questions', function (Blueprint $table) {
            $table->json('hints')->nullable()->after('answer_text');
        });

        // ── how many hints have been revealed for the current active question ──
        Schema::table('jeopardy_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('revealed_hint_count')->default(0)->after('pending_question_id');
        });

        // ── pixelation reveal level for pixelate_image questions ───────────────
        // 1 = most pixelated (≈5%), 8 = full clarity (100%)
        Schema::table('jeopardy_session_questions', function (Blueprint $table) {
            $table->unsignedTinyInteger('pixelate_level')->default(1)->after('zoom_level');
        });
    }

    public function down(): void
    {
        Schema::table('jeopardy_questions', function (Blueprint $table) {
            $table->dropColumn('hints');
        });

        Schema::table('jeopardy_sessions', function (Blueprint $table) {
            $table->dropColumn('revealed_hint_count');
        });

        Schema::table('jeopardy_session_questions', function (Blueprint $table) {
            $table->dropColumn('pixelate_level');
        });
    }
};
