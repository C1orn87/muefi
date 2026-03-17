<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JeopardySession extends Model
{
    protected $fillable = [
        'board_id', 'host_id', 'code', 'status', 'point_percentage', 'active_question_id',
        'show_answer', 'buzzer_open', 'question_opened_at', 'buzzer_delay_seconds',
        'current_turn_player_id', 'pending_question_id',
        'revealed_hint_count',
    ];

    protected $casts = [
        'point_percentage'    => 'integer',
        'show_answer'         => 'boolean',
        'buzzer_open'         => 'boolean',
        'question_opened_at'  => 'datetime',
        'buzzer_delay_seconds'=> 'integer',
    ];

    /**
     * Generate a unique join code.
     */
    public static function generateCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(JeopardyBoard::class, 'board_id');
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function activeQuestion(): BelongsTo
    {
        return $this->belongsTo(JeopardyQuestion::class, 'active_question_id');
    }

    public function currentTurnPlayer(): BelongsTo
    {
        return $this->belongsTo(JeopardyPlayer::class, 'current_turn_player_id');
    }

    public function pendingQuestion(): BelongsTo
    {
        return $this->belongsTo(JeopardyQuestion::class, 'pending_question_id');
    }

    public function sessionQuestions(): HasMany
    {
        return $this->hasMany(JeopardySessionQuestion::class, 'session_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(JeopardyPlayer::class, 'session_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(JeopardyTeam::class, 'session_id');
    }

    public function buzzes(): HasMany
    {
        return $this->hasMany(JeopardyBuzz::class, 'session_id');
    }

    public function guesses(): HasMany
    {
        return $this->hasMany(JeopardyGuess::class, 'session_id');
    }

    /**
     * Returns IDs of questions that have been revealed on this session's board.
     */
    public function revealedQuestionIds(): array
    {
        return $this->sessionQuestions()
            ->where('is_revealed', true)
            ->pluck('question_id')
            ->toArray();
    }
}
