<?php
// app/Http/Controllers/FeedController.php

namespace App\Http\Controllers;

use App\Models\FeedConfig;
use App\Services\FeedRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeedController extends Controller
{
    protected FeedRegistry $feedRegistry;

    public function __construct(FeedRegistry $feedRegistry)
    {
        $this->feedRegistry = $feedRegistry;
    }

    public function index(): JsonResponse
    {
        $feeds = FeedConfig::with(['publishAttempts' => function($query) {
            $query->latest()->limit(10);
        }])->get();

        return response()->json($feeds);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:feed_configs',
            'display_name' => 'required|string',
            'configuration' => 'required|array',
            'compliance_rules' => 'required|array',
            'api_endpoint' => 'nullable|url',
            'api_credentials' => 'nullable|array',
        ]);

        $feed = $this->feedRegistry->registerFeed($validated);

        return response()->json($feed, 201);
    }

    public function show(FeedConfig $feedConfig): JsonResponse
    {
        $feedConfig->load(['publishAttempts' => function($query) {
            $query->latest()->limit(50);
        }]);

        return response()->json($feedConfig);
    }

    public function update(FeedConfig $feedConfig, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => 'sometimes|string',
            'configuration' => 'sometimes|array',
            'compliance_rules' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'api_endpoint' => 'sometimes|nullable|url',
            'api_credentials' => 'sometimes|nullable|array',
        ]);

        $this->feedRegistry->updateFeed($feedConfig->name, $validated);
        $feedConfig->refresh();

        return response()->json($feedConfig);
    }

    public function getStats(FeedConfig $feedConfig): JsonResponse
    {
        $stats = [
            'total_attempts' => $feedConfig->publishAttempts()->count(),
            'successful_publishes' => $feedConfig->publishAttempts()->where('status', 'success')->count(),
            'failed_attempts' => $feedConfig->publishAttempts()->where('status', 'failed')->count(),
            'pending_attempts' => $feedConfig->publishAttempts()->where('status', 'pending')->count(),
            'last_24h_attempts' => $feedConfig->publishAttempts()
                ->where('attempted_at', '>=', now()->subDay())
                ->count(),
            'success_rate' => 0,
        ];

        if ($stats['total_attempts'] > 0) {
            $stats['success_rate'] = round(($stats['successful_publishes'] / $stats['total_attempts']) * 100, 2);
        }

        return response()->json($stats);
    }
}