<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JeopardyBuzz extends Model
{
    protected $table = 'jeopardy_buzzes';

    public $timestamps = false;

    protected $fillable = ['session_id', 'question_id', 'player_id', 'buzz_order', 'buzzed_at'];

    protected $casts = [
        'buzzed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(JeopardySession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(JeopardyQuestion::class, 'question_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(JeopardyPlayer::class, 'player_id');
    }
}
