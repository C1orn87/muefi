<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JeopardyTeam extends Model
{
    protected $fillable = ['session_id', 'name', 'score'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(JeopardySession::class, 'session_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(JeopardyPlayer::class, 'team_id');
    }
}
