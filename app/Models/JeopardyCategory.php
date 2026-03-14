<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JeopardyCategory extends Model
{
    protected $fillable = ['board_id', 'name', 'order'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(JeopardyBoard::class, 'board_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(JeopardyQuestion::class, 'category_id')->orderBy('order');
    }
}
