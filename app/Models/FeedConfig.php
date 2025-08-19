<?php
// app/Models/FeedConfig.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedConfig extends Model
{
    use HasFactory;

    protected $appends = ['guidelines'];


    protected $fillable = [
        'name',
        'display_name',
        'configuration',
        'compliance_rules',
        'is_active',
        'api_endpoint',
        'api_credentials'
    ];

    protected $casts = [
        'configuration' => 'array',
        'compliance_rules' => 'array',
        'api_credentials' => 'array',
        'is_active' => 'boolean',
    ];

    public function publishAttempts(): HasMany
    {
        return $this->hasMany(PublishAttempt::class, 'feed_name', 'name');
    }

    public function getActiveRules()
    {
        return ComplianceRule::whereIn('name', $this->compliance_rules)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    public function getGuidelinesAttribute()
{
    return $this->configuration;
}
}