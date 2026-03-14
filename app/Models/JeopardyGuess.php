<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JeopardyGuess extends Model
{
    protected $table = 'jeopardy_guesses';

    public $timestamps = false;

    protected $fillable = ['session_id', 'question_id', 'player_id', 'guess', 'submitted_at'];

    protected $casts = [
        'guess'        => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(JeopardyPlayer::class, 'player_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(JeopardyQuestion::class, 'question_id');
    }
}
