<?php
// app/Models/AuditLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'event_type',
        'article_id',
        'feed_name',
        'context',
        'user_id',
        'ip_address'
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public static function logEvent(string $eventType, ?int $articleId = null, ?string $feedName = null, array $context = [])
    {
        return self::create([
            'event_type' => $eventType,
            'article_id' => $articleId,
            'feed_name' => $feedName,
            'context' => $context,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);
    }
}