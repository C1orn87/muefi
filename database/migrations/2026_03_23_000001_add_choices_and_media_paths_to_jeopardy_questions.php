<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jeopardy_questions', function (Blueprint $table) {
            // JSON array of option strings for multiple_choice type
            // e.g. ["Paris", "London", "Berlin", "Madrid"]
            $table->json('choices')->nullable()->after('hints');

            // JSON array of storage paths for duel / four_pics types
            // e.g. ["jeopardy/images/a.jpg", "jeopardy/images/b.jpg"]
            $table->json('media_paths')->nullable()->after('choices');
        });
    }

    public function down(): void
    {
        Schema::table('jeopardy_questions', function (Blueprint $table) {
            $table->dropColumn(['choices', 'media_paths']);
        });
    }
};
