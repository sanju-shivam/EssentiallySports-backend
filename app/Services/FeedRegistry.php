<?php
// app/Services/FeedRegistry.php

namespace App\Services;

use App\Models\FeedConfig;
use Illuminate\Support\Facades\Cache;

class FeedRegistry
{
    /**
     * Get feed configuration by name
     */
    public function getFeedConfig(string $feedName): ?FeedConfig
    {
        return Cache::remember("feed_config_1{$feedName}", 0, function () use ($feedName) {
            return FeedConfig::where('name', $feedName)->first();
        });
    }

    /**
     * Get all active feeds
     */
    public function getActiveFeeds(): \Illuminate\Support\Collection
    {
        return Cache::remember('active_feeds', 3600, function () {
            return FeedConfig::where('is_active', true)->get();
        });
    }

    /**
     * Register a new feed
     */
    public function registerFeed(array $feedData): FeedConfig
    {
        $feed = FeedConfig::create($feedData);
        
        // Clear cache
        Cache::forget("feed_config_{$feed->name}");
        Cache::forget('active_feeds');
        
        return $feed;
    }

    /**
     * Update feed configuration
     */
    public function updateFeed(string $feedName, array $updateData): bool
    {
        $updated = FeedConfig::where('name', $feedName)->update($updateData);
        
        if ($updated) {
            Cache::forget("feed_config_{$feedName}");
            Cache::forget('active_feeds');
        }
        
        return $updated > 0;
    }

    /**
     * Check if feed exists and is active
     */
    public function isFeedActive(string $feedName): bool
    {
        $feed = $this->getFeedConfig($feedName);
        return $feed && $feed->is_active;
    }
}