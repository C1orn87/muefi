<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JeopardyQuestion extends Model
{
    protected $fillable = [
        'category_id', 'points', 'order',
        'question_text', 'answer_text',
        'question_type', 'media_path', 'media_url',
        'hints',
    ];

    protected $casts = [
        'hints' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(JeopardyCategory::class, 'category_id');
    }

    /**
     * Returns the public URL for file-based media, or the raw URL for YouTube.
     */
    public function mediaUrl(): ?string
    {
        if ($this->media_path) {
            return Storage::url($this->media_path);
        }
        return $this->media_url;
    }

    /**
     * Converts a YouTube watch URL to an embed URL.
     */
    public function youtubeEmbedUrl(): ?string
    {
        if ($this->question_type !== 'youtube' || ! $this->media_url) {
            return null;
        }

        // Handle various YouTube URL formats
        preg_match(
            '/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            $this->media_url,
            $matches
        );

        return isset($matches[1])
            ? "https://www.youtube.com/embed/{$matches[1]}"
            : null;
    }
}
