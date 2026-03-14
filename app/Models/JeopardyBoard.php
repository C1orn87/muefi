<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JeopardyBoard extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'is_public', 'columns', 'rows',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(JeopardyCategory::class, 'board_id')->orderBy('order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(JeopardySession::class, 'board_id');
    }
}
