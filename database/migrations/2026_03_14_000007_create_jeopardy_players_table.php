<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeopardy_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('jeopardy_sessions')->cascadeOnDelete();

            // Nullable: guests don't need to be registered users
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->integer('score')->default(0);

            // Nullable: solo player if null, otherwise member of a team
            $table->foreignId('team_id')
                  ->nullable()
                  ->constrained('jeopardy_teams')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_players');
    }
};
