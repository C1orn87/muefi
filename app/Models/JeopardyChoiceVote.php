<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JeopardyChoiceVote extends Model
{
    protected $fillable = [
        'session_id', 'question_id', 'player_id', 'choice_index',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(JeopardyPlayer::class, 'player_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(JeopardySession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(JeopardyQuestion::class, 'question_id');
    }
}
