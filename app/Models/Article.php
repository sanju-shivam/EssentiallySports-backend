<?php
// app/Models/Article.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'author',
        'category',
        'thumbnail_url',
        'metadata',
        'tags',
        'status'
    ];

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
    ];

    public function publishAttempts(): HasMany
    {
        return $this->hasMany(PublishAttempt::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function getWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->body));
    }

    public function getCharacterCountAttribute(): int
    {
        return strlen(strip_tags($this->body));
    }
}