<?php
// app/Services/ComplianceMonitor.php

namespace App\Services;

use App\Models\PublishAttempt;
use App\Models\FeedConfig;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComplianceMonitor
{
    public function checkSystemHealth(): array
    {
        $health = [
            'overall_status' => 'healthy',
            'issues' => [],
            'metrics' => [],
        ];

        // Check for consecutive failures
        $consecutiveFailures = $this->checkConsecutiveFailures();
        if ($consecutiveFailures) {
            $health['issues'][] = $consecutiveFailures;
            $health['overall_status'] = 'warning';
        }

        // Check rule execution frequency
        $staleRules = $this->checkStaleRules();
        if (!empty($staleRules)) {
            $health['issues'][] = [
                'type' => 'stale_rules',
                'message' => 'Some compliance rules haven\'t been executed recently',
                'details' => $staleRules,
            ];
            $health['overall_status'] = 'warning';
        }

        // Check feed success rates
        $feedMetrics = $this->getFeedMetrics();
        $health['metrics'] = $feedMetrics;

        foreach ($feedMetrics as $feedName => $metrics) {
            if ($metrics['success_rate'] < 80) {
                $health['issues'][] = [
                    'type' => 'low_success_rate',
                    'feed' => $feedName,
                    'success_rate' => $metrics['success_rate'],
                    'message' => "Feed {$feedName} has success rate below 80%",
                ];
                $health['overall_status'] = 'critical';
            }
        }

        return $health;
    }

    protected function checkConsecutiveFailures(): ?array
    {
        $alertThreshold = config('compliance.monitoring.alert_on_consecutive_failures', 5);
        
        foreach (FeedConfig::where('is_active', true)->get() as $feed) {
            $recentFailures = PublishAttempt::where('feed_name', $feed->name)
                ->where('status', 'failed')
                ->where('attempted_at', '>=', now()->subHour())
                ->count();

            if ($recentFailures >= $alertThreshold) {
                return [
                    'type' => 'consecutive_failures',
                    'feed' => $feed->name,
                    'failure_count' => $recentFailures,
                    'message' => "Feed {$feed->name} has {$recentFailures} consecutive failures",
                ];
            }
        }

        return null;
    }

    protected function checkStaleRules(): array
    {
        $staleRules = [];
        $cutoff = now()->subHours(24);

        $recentAudits = AuditLog::where('event_type', 'compliance_check_completed')
            ->where('created_at', '>=', $cutoff)
            ->get();

        $executedRules = collect($recentAudits)
            ->flatMap(fn($audit) => array_keys($audit->context['results'] ?? []))
            ->unique()
            ->values()
            ->toArray();

        $allActiveRules = \App\Models\ComplianceRule::where('is_active', true)
            ->pluck('name')
            ->toArray();

        $staleRules = array_diff($allActiveRules, $executedRules);

        return array_values($staleRules);
    }

    protected function getFeedMetrics(): array
    {
        $metrics = [];
        $last24h = now()->subDay();

        foreach (FeedConfig::where('is_active', true)->get() as $feed) {
            $totalAttempts = PublishAttempt::where('feed_name', $feed->name)
                ->where('attempted_at', '>=', $last24h)
                ->count();

            $successfulAttempts = PublishAttempt::where('feed_name', $feed->name)
                ->where('status', 'success')
                ->where('attempted_at', '>=', $last24h)
                ->count();

            $successRate = $totalAttempts > 0 ? round(($successfulAttempts / $totalAttempts) * 100, 2) : 0;

            $metrics[$feed->name] = [
                'total_attempts' => $totalAttempts,
                'successful_attempts' => $successfulAttempts,
                'success_rate' => $successRate,
            ];
        }

        return $metrics;
    }

    public function sendAlert(array $healthCheck): void
    {
        if ($healthCheck['overall_status'] === 'healthy') {
            return;
        }

        $alertEmail = config('compliance.monitoring.alert_email');
        $slackWebhook = config('compliance.monitoring.slack_webhook');

        // Send email alert
        if ($alertEmail) {
            try {
                Mail::send('emails.compliance-alert', ['health' => $healthCheck], function ($message) use ($alertEmail, $healthCheck) {
                    $message->to($alertEmail)
                        ->subject("Feed Compliance Alert - Status: {$healthCheck['overall_status']}");
                });
            } catch (\Exception $e) {
                Log::error('Failed to send compliance alert email', ['exception' => $e->getMessage()]);
            }
        }

        // Send Slack alert
        if ($slackWebhook) {
            try {
                $slackMessage = $this->formatSlackMessage($healthCheck);
                Http::post($slackWebhook, $slackMessage);
            } catch (\Exception $e) {
                Log::error('Failed to send compliance alert to Slack', ['exception' => $e->getMessage()]);
            }
        }
    }

    protected function formatSlackMessage(array $healthCheck): array
    {
        $color = [
            'healthy' => 'good',
            'warning' => 'warning',
            'critical' => 'danger',
        ][$healthCheck['overall_status']] ?? 'danger';

        $text = "Feed Compliance System Status: *{$healthCheck['overall_status']}*\n\n";
        
        if (!empty($healthCheck['issues'])) {
            $text .= "*Issues Found:*\n";
            foreach ($healthCheck['issues'] as $issue) {
                $text .= "• {$issue['message']}\n";
            }
        }

        return [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => 'Feed Compliance Alert',
                    'text' => $text,
                    'ts' => time(),
                ],
            ],
        ];
    }
}