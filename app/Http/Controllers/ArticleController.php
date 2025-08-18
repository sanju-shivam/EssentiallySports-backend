<?php
// app/Http/Controllers/ArticleController.php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\FeedPublishingController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    protected FeedPublishingController $feedPublisher;

    public function __construct(FeedPublishingController $feedPublisher)
    {
        $this->feedPublisher = $feedPublisher;
    }

    public function index(): JsonResponse
    {
        $articles = Article::with('publishAttempts')->paginate(20);
        return response()->json($articles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'author' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:50',
            'thumbnail_url' => 'nullable|url',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $article = Article::create($validated);

        return response()->json($article, 201);
    }

    public function show(Article $article): JsonResponse
    {
        $article->load(['publishAttempts.feedConfig', 'auditLogs']);
        return response()->json($article);
    }

    public function publishToFeed(Article $article, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feed_name' => 'required|string',
        ]);

        try {
            $publishAttempt = $this->feedPublisher->publishToFeed($article, $validated['feed_name']);
            
            return response()->json([
                'success' => true,
                'message' => 'Article published successfully',
                'publish_attempt' => $publishAttempt,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_details' => $e instanceof \App\Exceptions\ComplianceException ? $e->getFailedChecks() : null,
            ], 422);
        }
    }

    public function publishToMultipleFeeds(Article $article, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feed_names' => 'required|array',
            'feed_names.*' => 'string',
        ]);

        $results = $this->feedPublisher->publishToFeeds($article, $validated['feed_names']);

        $successCount = count(array_filter($results, function($result) {
            return !isset($result['error']);
        }));

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Published to {$successCount} out of " . count($validated['feed_names']) . " feeds",
            'results' => $results,
        ]);
    }
}