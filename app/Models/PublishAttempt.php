<?php
// app/Models/PublishAttempt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'feed_name',
        'status',
        'compliance_results',
        'error_details',
        'attempted_at',
        'completed_at',
        'external_id'
    ];

    protected $casts = [
        'compliance_results' => 'array',
        'error_details' => 'array',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function feedConfig(): BelongsTo
    {
        return $this->belongsTo(FeedConfig::class, 'feed_name', 'name');
    }
}