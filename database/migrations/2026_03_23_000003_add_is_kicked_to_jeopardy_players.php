<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jeopardy_players', function (Blueprint $table) {
            $table->boolean('is_kicked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('jeopardy_players', function (Blueprint $table) {
            $table->dropColumn('is_kicked');
        });
    }
};
