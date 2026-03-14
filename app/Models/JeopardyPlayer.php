<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JeopardyPlayer extends Model
{
    protected $fillable = ['session_id', 'user_id', 'name', 'score', 'team_id'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(JeopardySession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(JeopardyTeam::class, 'team_id');
    }
}
