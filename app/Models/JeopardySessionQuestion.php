<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JeopardySessionQuestion extends Model
{
    protected $fillable = ['session_id', 'question_id', 'is_revealed', 'zoom_level', 'pixelate_level'];

    protected $casts = [
        'is_revealed'    => 'boolean',
        'zoom_level'     => 'integer',
        'pixelate_level' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(JeopardySession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(JeopardyQuestion::class, 'question_id');
    }
}
