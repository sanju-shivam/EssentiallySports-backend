<?php
// app/Services/FeedPublishingController.php

namespace App\Services;

use App\Models\Article;
use App\Models\FeedConfig;
use App\Models\PublishAttempt;
use App\Models\AuditLog;
use App\Exceptions\ComplianceException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FeedPublishingController
{
    protected ComplianceEngine $complianceEngine;
    protected FeedRegistry $feedRegistry;

    public function __construct(ComplianceEngine $complianceEngine, FeedRegistry $feedRegistry)
    {
        $this->complianceEngine = $complianceEngine;
        $this->feedRegistry = $feedRegistry;
    }

    /**
     * Publish an article to a specific feed
     */
    public function publishToFeed(Article $article, string $feedName): PublishAttempt
    {
        return DB::transaction(function () use ($article, $feedName) {
            // Create publish attempt record
            $publishAttempt = PublishAttempt::create([
                'article_id' => $article->id,
                'feed_name' => $feedName,
                'status' => 'pending',
                'attempted_at' => now(),
                'compliance_results' => [],
            ]);

            // Log the attempt
            AuditLog::logEvent('publish_attempt_started', $article->id, $feedName, [
                'attempt_id' => $publishAttempt->id,
                'article_title' => $article->title,
            ]);

            try {
                // Get feed configuration
                $feedConfig = $this->feedRegistry->getFeedConfig($feedName);
                
                if (!$feedConfig || !$feedConfig->is_active) {
                    throw new ComplianceException("Feed '{$feedName}' is not available or inactive");
                }

                // Run compliance checks
                $complianceResults = $this->complianceEngine->validateArticle($article, $feedConfig);
                
                $publishAttempt->update([
                    'compliance_results' => $complianceResults,
                ]);

                // Check if all validations passed
                $failedChecks = collect($complianceResults)->filter(fn($result) => !$result['passed']);
                
                if ($failedChecks->isNotEmpty()) {
                    throw new ComplianceException(
                        "Article failed compliance checks for {$feedName}",
                        $failedChecks->toArray()
                    );
                }

                // All checks passed - proceed with actual publishing
                $externalId = $this->publishToExternalFeed($article, $feedConfig);

                $publishAttempt->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'external_id' => $externalId,
                ]);

                // Log success
                AuditLog::logEvent('publish_success', $article->id, $feedName, [
                    'attempt_id' => $publishAttempt->id,
                    'external_id' => $externalId,
                ]);

                Log::info("Successfully published article {$article->id} to {$feedName}", [
                    'article_id' => $article->id,
                    'feed_name' => $feedName,
                    'external_id' => $externalId,
                ]);

            } catch (ComplianceException $e) {
                $publishAttempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_details' => [
                        'message' => $e->getMessage(),
                        'failed_checks' => $e->getFailedChecks(),
                        'code' => $e->getCode(),
                    ],
                ]);

                // Log failure
                AuditLog::logEvent('publish_failed', $article->id, $feedName, [
                    'attempt_id' => $publishAttempt->id,
                    'error' => $e->getMessage(),
                    'failed_checks' => $e->getFailedChecks(),
                ]);

                Log::warning("Failed to publish article {$article->id} to {$feedName}: {$e->getMessage()}", [
                    'article_id' => $article->id,
                    'feed_name' => $feedName,
                    'failed_checks' => $e->getFailedChecks(),
                ]);

                throw $e;
            } catch (\Exception $e) {
                $publishAttempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_details' => [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'trace' => $e->getTraceAsString(),
                    ],
                ]);

                // Log unexpected error
                AuditLog::logEvent('publish_error', $article->id, $feedName, [
                    'attempt_id' => $publishAttempt->id,
                    'error' => $e->getMessage(),
                ]);

                Log::error("Unexpected error publishing article {$article->id} to {$feedName}: {$e->getMessage()}", [
                    'article_id' => $article->id,
                    'feed_name' => $feedName,
                    'exception' => $e,
                ]);

                throw $e;
            }

            return $publishAttempt;
        });
    }

    /**
     * Publish to multiple feeds
     */
    public function publishToFeeds(Article $article, array $feedNames): array
    {
        $results = [];
        
        foreach ($feedNames as $feedName) {
            try {
                $results[$feedName] = $this->publishToFeed($article, $feedName);
            } catch (\Exception $e) {
                $results[$feedName] = [
                    'error' => $e->getMessage(),
                    'failed_checks' => $e instanceof ComplianceException ? $e->getFailedChecks() : [],
                ];
            }
        }

        return $results;
    }

    /**
     * Simulate publishing to external feed - Manual API validation demonstration
     */
    protected function publishToExternalFeed(Article $article, FeedConfig $feedConfig): string
    {
        // In production, this is where you'd integrate with actual feed APIs
        // For demonstration purposes, we'll show manual validation approach
        
        switch ($feedConfig->name) {
            case 'MSN':
                return $this->simulatePublishToMsn($article, $feedConfig);
            case 'MSN1':
                return $this->simulatePublishToMsn($article, $feedConfig);
            case 'GoogleNews':
                return $this->simulatePublishToGoogleNews($article, $feedConfig);
            case 'AppleNews':
                return $this->simulatePublishToAppleNews($article, $feedConfig);
            default:
                throw new \Exception("Unknown feed type: {$feedConfig->name}");
        }
    }

    protected function simulatePublishToMsn(Article $article, FeedConfig $feedConfig): string
    {
        // Manual API validation demonstration for MSN
        Log::info("=== MSN API Publishing Simulation ===", [
            'article_id' => $article->id,
            'article_title' => $article->title,
        ]);

        // Simulate preparing payload for MSN API
        $payload = [
            'title' => $article->title,
            'content' => $article->body,
            'author' => $article->author,
            'category' => $article->category,
            'thumbnail' => $article->thumbnail_url,
            'metadata' => $article->metadata,
            'published_date' => now()->toISOString(),
        ];

        Log::info("MSN API Payload prepared:", $payload);

        // Simulate API call validation
        $this->validateMsnApiPayload($payload);

        // Simulate successful response
        $externalId = 'msn_article_' . $article->id . '_' . time();
        
        Log::info("MSN API Response (simulated):", [
            'status' => 'success',
            'external_id' => $externalId,
            'message' => 'Article successfully published to MSN feed',
        ]);

        return $externalId;
    }

    protected function simulatePublishToGoogleNews(Article $article, FeedConfig $feedConfig): string
    {
        // Manual API validation demonstration for Google News
        Log::info("=== Google News API Publishing Simulation ===", [
            'article_id' => $article->id,
            'article_title' => $article->title,
        ]);

        $payload = [
            'headline' => $article->title,
            'body' => $article->body,
            'author' => $article->author,
            'section' => $article->category,
            'image_url' => $article->thumbnail_url,
            'publication_date' => now()->toISOString(),
        ];

        Log::info("Google News API Payload prepared:", $payload);

        // Simulate validation
        $this->validateGoogleNewsApiPayload($payload);

        $externalId = 'gn_article_' . $article->id . '_' . time();
        
        Log::info("Google News API Response (simulated):", [
            'status' => 'accepted',
            'article_id' => $externalId,
            'indexing_status' => 'pending',
        ]);

        return $externalId;
    }

    protected function simulatePublishToAppleNews(Article $article, FeedConfig $feedConfig): string
    {
        // Manual API validation demonstration for Apple News
        Log::info("=== Apple News API Publishing Simulation ===", [
            'article_id' => $article->id,
            'article_title' => $article->title,
        ]);

        $payload = [
            'article' => [
                'title' => $article->title,
                'components' => [
                    [
                        'role' => 'body',
                        'text' => strip_tags($article->body)
                    ]
                ],
                'metadata' => [
                    'author' => $article->author,
                    'datePublished' => now()->toISOString(),
                    'excerpt' => substr(strip_tags($article->body), 0, 200) . '...',
                ]
            ]
        ];

        if ($article->thumbnail_url) {
            $payload['article']['components'][] = [
                'role' => 'photo',
                'URL' => $article->thumbnail_url
            ];
        }

        Log::info("Apple News API Payload prepared:", $payload);

        // Simulate validation
        $this->validateAppleNewsApiPayload($payload);

        $externalId = 'an_article_' . $article->id . '_' . time();
        
        Log::info("Apple News API Response (simulated):", [
            'data' => [
                'id' => $externalId,
                'state' => 'LIVE',
                'shareUrl' => "https://apple.news/{$externalId}"
            ]
        ]);

        return $externalId;
    }

    /**
     * Manual validation methods for different feed APIs
     */
    protected function validateMsnApiPayload(array $payload): void
    {
        // MSN API specific validations that would be done manually
        if (empty($payload['title']) || strlen($payload['title']) > 100) {
            throw new \Exception("MSN API Error: Title must be present and under 100 characters");
        }

        if (empty($payload['content']) || strlen($payload['content']) < 300) {
            throw new \Exception("MSN API Error: Content must be at least 300 characters");
        }

        if (empty($payload['author'])) {
            throw new \Exception("MSN API Error: Author is required");
        }

        if (empty($payload['category'])) {
            throw new \Exception("MSN API Error: Category is required");
        }

        // Simulate checking for prohibited content
        $prohibitedTerms = ['violence', 'explicit', 'hate'];
        $content = strtolower($payload['title'] . ' ' . $payload['content']);
        
        foreach ($prohibitedTerms as $term) {
            if (strpos($content, $term) !== false) {
                throw new \Exception("MSN API Error: Content contains prohibited term: {$term}");
            }
        }

        Log::info("MSN API payload validation: PASSED");
    }

    protected function validateGoogleNewsApiPayload(array $payload): void
    {
        // Google News API specific validations
        if (empty($payload['headline']) || strlen($payload['headline']) > 120) {
            throw new \Exception("Google News API Error: Headline must be present and under 120 characters");
        }

        if (empty($payload['body']) || strlen($payload['body']) < 200) {
            throw new \Exception("Google News API Error: Body must be at least 200 characters");
        }

        if (!empty($payload['image_url']) && !filter_var($payload['image_url'], FILTER_VALIDATE_URL)) {
            throw new \Exception("Google News API Error: Invalid image URL format");
        }

        Log::info("Google News API payload validation: PASSED");
    }

    protected function validateAppleNewsApiPayload(array $payload): void
    {
        // Apple News API specific validations
        if (empty($payload['article']['title']) || strlen($payload['article']['title']) > 80) {
            throw new \Exception("Apple News API Error: Title must be present and under 80 characters");
        }

        if (empty($payload['article']['components'])) {
            throw new \Exception("Apple News API Error: Article must have components");
        }

        $hasBodyComponent = false;
        foreach ($payload['article']['components'] as $component) {
            if ($component['role'] === 'body') {
                $hasBodyComponent = true;
                if (empty($component['text']) || strlen($component['text']) < 250) {
                    throw new \Exception("Apple News API Error: Body component must have at least 250 characters");
                }
                break;
            }
        }

        if (!$hasBodyComponent) {
            throw new \Exception("Apple News API Error: Article must have a body component");
        }

        Log::info("Apple News API payload validation: PASSED");
    }
}