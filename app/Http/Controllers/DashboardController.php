<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\PublishAttempt;
use App\Models\FeedConfig;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        $last24h = now()->subDay();
        $last7d = now()->subWeek();

        $stats = [
            'total_articles' => Article::count(),
            'articles_last_24h' => Article::where('created_at', '>=', $last24h)->count(),
            'total_publish_attempts' => PublishAttempt::count(),
            'attempts_last_24h' => PublishAttempt::where('attempted_at', '>=', $last24h)->count(),
            'success_rate_24h' => 0,
            'success_rate_7d' => 0,
            'active_feeds' => FeedConfig::where('is_active', true)->count(),
        ];

        // Calculate success rates
        $attempts24h = PublishAttempt::where('attempted_at', '>=', $last24h)->count();
        $successes24h = PublishAttempt::where('attempted_at', '>=', $last24h)
            ->where('status', 'success')->count();
        
        $attempts7d = PublishAttempt::where('attempted_at', '>=', $last7d)->count();
        $successes7d = PublishAttempt::where('attempted_at', '>=', $last7d)
            ->where('status', 'success')->count();

        $stats['success_rate_24h'] = $attempts24h > 0 ? round(($successes24h / $attempts24h) * 100, 2) : 0;
        $stats['success_rate_7d'] = $attempts7d > 0 ? round(($successes7d / $attempts7d) * 100, 2) : 0;

        return response()->json($stats);
    }

    public function feedPerformance(): JsonResponse
    {
        $feeds = FeedConfig::where('is_active', true)->get();
        $performance = [];

        foreach ($feeds as $feed) {
            $last24h = now()->subDay();
            $last7d = now()->subWeek();

            $attempts24h = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $last24h)->count();
            $successes24h = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $last24h)
                ->where('status', 'success')->count();

            $attempts7d = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $last7d)->count();
            $successes7d = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $last7d)
                ->where('status', 'success')->count();

            $performance[$feed->name] = [
                'display_name' => $feed->display_name,
                'attempts_24h' => $attempts24h,
                'successes_24h' => $successes24h,
                'success_rate_24h' => $attempts24h > 0 ? round(($successes24h / $attempts24h) * 100, 2) : 0,
                'attempts_7d' => $attempts7d,
                'successes_7d' => $successes7d,
                'success_rate_7d' => $attempts7d > 0 ? round(($successes7d / $attempts7d) * 100, 2) : 0,
            ];
        }

        return response()->json($performance);
    }

    public function complianceFailures(Request $request): JsonResponse
    {
        $feedName = $request->get('feed');
        $days = $request->get('days', 7);
        $limit = $request->get('limit', 50);

        $query = PublishAttempt::with(['article:id,title,author', 'feedConfig:name,display_name'])
            ->where('status', 'failed')
            ->where('attempted_at', '>=', now()->subDays($days));

        if ($feedName) {
            $query->where('feed_name', $feedName);
        }

        $failures = $query->orderBy('attempted_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($failures);
    }

    public function auditTrail(Request $request): JsonResponse
    {
        $articleId = $request->get('article_id');
        $feedName = $request->get('feed_name');
        $eventType = $request->get('event_type');
        $days = $request->get('days', 30);

        $query = AuditLog::with('article:id,title')
            ->where('created_at', '>=', now()->subDays($days));

        if ($articleId) {
            $query->where('article_id', $articleId);
        }

        if ($feedName) {
            $query->where('feed_name', $feedName);
        }

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')
            ->paginate(100);

        return response()->json($auditLogs);
    }
}