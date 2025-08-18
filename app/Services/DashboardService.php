<?php
// app/Services/DashboardService.php

namespace App\Services;

use App\Models\Article;
use App\Models\PublishAttempt;
use App\Models\FeedConfig;
use App\Models\AuditLog;
use App\Models\ComplianceRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getComplianceOverview(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_attempts' => PublishAttempt::where('attempted_at', '>=', $startDate)->count(),
            'successful_publishes' => PublishAttempt::where('attempted_at', '>=', $startDate)
                ->where('status', 'success')->count(),
            'failed_attempts' => PublishAttempt::where('attempted_at', '>=', $startDate)
                ->where('status', 'failed')->count(),
            'compliance_issues_by_rule' => $this->getComplianceIssuesByRule($startDate),
            'feed_performance' => $this->getFeedPerformanceMetrics($startDate),
            'trending_failure_reasons' => $this->getTrendingFailureReasons($startDate),
        ];
    }

    public function getComplianceIssuesByRule(Carbon $startDate): array
    {
        $failedAttempts = PublishAttempt::where('attempted_at', '>=', $startDate)
            ->where('status', 'failed')
            ->get();

        $issuesByRule = [];
        
        foreach ($failedAttempts as $attempt) {
            $complianceResults = $attempt->compliance_results ?? [];
            
            foreach ($complianceResults as $ruleName => $result) {
                if (!$result['passed']) {
                    $issuesByRule[$ruleName] = ($issuesByRule[$ruleName] ?? 0) + 1;
                }
            }
        }

        arsort($issuesByRule);
        return $issuesByRule;
    }

    public function getFeedPerformanceMetrics(Carbon $startDate): array
    {
        $feeds = FeedConfig::where('is_active', true)->get();
        $performance = [];

        foreach ($feeds as $feed) {
            $attempts = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $startDate);
            
            $total = $attempts->count();
            $successful = $attempts->where('status', 'success')->count();
            $failed = $attempts->where('status', 'failed')->count();
            
            $avgResponseTime = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $startDate)
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - attempted_at))) as avg_time')
                ->value('avg_time');

            $performance[$feed->name] = [
                'display_name' => $feed->display_name,
                'total_attempts' => $total,
                'successful' => $successful,
                'failed' => $failed,
                'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                'avg_response_time_seconds' => round($avgResponseTime ?? 0, 2),
            ];
        }

        return $performance;
    }

    public function getTrendingFailureReasons(Carbon $startDate): array
    {
        $failedAttempts = PublishAttempt::where('attempted_at', '>=', $startDate)
            ->where('status', 'failed')
            ->get();

        $reasons = [];
        
        foreach ($failedAttempts as $attempt) {
            $errorDetails = $attempt->error_details ?? [];
            $failedChecks = $errorDetails['failed_checks'] ?? [];
            
            foreach ($failedChecks as $rule => $details) {
                $message = $details['message'] ?? 'Unknown error';
                $reasons[$message] = ($reasons[$message] ?? 0) + 1;
            }
        }

        arsort($reasons);
        return array_slice($reasons, 0, 10, true);
    }

    public function getArticleComplianceHistory(Article $article): array
    {
        $attempts = PublishAttempt::where('article_id', $article->id)
            ->with('feedConfig')
            ->orderBy('attempted_at', 'desc')
            ->get();

        $history = [];
        
        foreach ($attempts as $attempt) {
            $complianceResults = $attempt->compliance_results ?? [];
            $passedChecks = collect($complianceResults)->filter(fn($r) => $r['passed'])->count();
            $totalChecks = count($complianceResults);
            
            $history[] = [
                'attempt_id' => $attempt->id,
                'feed_name' => $attempt->feed_name,
                'feed_display_name' => $attempt->feedConfig->display_name ?? $attempt->feed_name,
                'status' => $attempt->status,
                'attempted_at' => $attempt->attempted_at->toISOString(),
                'completed_at' => $attempt->completed_at?->toISOString(),
                'compliance_score' => $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100) : 0,
                'passed_checks' => $passedChecks,
                'total_checks' => $totalChecks,
                'external_id' => $attempt->external_id,
                'compliance_details' => $complianceResults,
                'error_details' => $attempt->error_details,
            ];
        }

        return $history;
    }

    public function getSystemHealthStatus(): array
    {
        $last24h = now()->subDay();
        $lastWeek = now()->subWeek();

        // Check recent activity
        $recentAttempts = PublishAttempt::where('attempted_at', '>=', $last24h)->count();
        $recentFailures = PublishAttempt::where('attempted_at', '>=', $last24h)
            ->where('status', 'failed')->count();

        // Check rule execution
        $activeRules = ComplianceRule::where('is_active', true)->count();
        $recentRuleExecutions = AuditLog::where('event_type', 'compliance_check_completed')
            ->where('created_at', '>=', $last24h)->count();

        // Check feed health
        $activeFeeds = FeedConfig::where('is_active', true)->count();
        $problematicFeeds = [];
        
        foreach (FeedConfig::where('is_active', true)->get() as $feed) {
            $feedFailures = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $last24h)
                ->where('status', 'failed')->count();
            
            $feedAttempts = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $last24h)->count();
            
            if ($feedAttempts > 0 && ($feedFailures / $feedAttempts) > 0.5) {
                $problematicFeeds[] = $feed->name;
            }
        }

        $overallHealth = 'healthy';
        if ($recentAttempts > 0 && ($recentFailures / $recentAttempts) > 0.3) {
            $overallHealth = 'warning';
        }
        if (!empty($problematicFeeds) || ($recentAttempts > 0 && ($recentFailures / $recentAttempts) > 0.7)) {
            $overallHealth = 'critical';
        }

        return [
            'overall_health' => $overallHealth,
            'system_metrics' => [
                'recent_attempts_24h' => $recentAttempts,
                'recent_failures_24h' => $recentFailures,
                'failure_rate_24h' => $recentAttempts > 0 ? round(($recentFailures / $recentAttempts) * 100, 2) : 0,
                'active_rules' => $activeRules,
                'recent_rule_executions_24h' => $recentRuleExecutions,
                'active_feeds' => $activeFeeds,
                'problematic_feeds' => $problematicFeeds,
            ],
            'recommendations' => $this->getHealthRecommendations($overallHealth, [
                'failure_rate' => $recentAttempts > 0 ? ($recentFailures / $recentAttempts) : 0,
                'problematic_feeds' => $problematicFeeds,
                'rule_executions' => $recentRuleExecutions,
            ]),
        ];
    }

    private function getHealthRecommendations(string $health, array $metrics): array
    {
        $recommendations = [];

        if ($health === 'critical') {
            $recommendations[] = 'System requires immediate attention - high failure rate detected';
        }

        if ($metrics['failure_rate'] > 0.3) {
            $recommendations[] = 'Review recent compliance rule changes - high failure rate indicates possible issues';
        }

        if (!empty($metrics['problematic_feeds'])) {
            $feeds = implode(', ', $metrics['problematic_feeds']);
            $recommendations[] = "Check feed configurations for: {$feeds} - multiple failures detected";
        }

        if ($metrics['rule_executions'] === 0) {
            $recommendations[] = 'No compliance checks executed recently - verify system is processing articles';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'System operating normally - no issues detected';
        }

        return $recommendations;
    }
}