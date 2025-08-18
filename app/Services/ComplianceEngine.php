<?php
// app/Services/ComplianceEngine.php

namespace App\Services;

use App\Models\Article;
use App\Models\FeedConfig;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class ComplianceEngine
{
    /**
     * Validate an article against all rules for a specific feed
     */
    public function validateArticle(Article $article, FeedConfig $feedConfig): array
    {
        $results = [];
        $rules = $feedConfig->getActiveRules();

        AuditLog::logEvent('compliance_check_started', $article->id, $feedConfig->name, [
            'rules_count' => $rules->count(),
            'rule_names' => $rules->pluck('name')->toArray(),
        ]);

        foreach ($rules as $rule) {
            try {
                $validator = $this->createValidator($rule->validator_class);
                $result = $validator->validate($article, $rule->parameters ?? [], $feedConfig);
                
                $results[$rule->name] = [
                    'rule' => $rule->name,
                    'passed' => $result['passed'],
                    'message' => $result['message'],
                    'details' => $result['details'] ?? [],
                    'executed_at' => now()->toISOString(),
                ];

                Log::debug("Compliance check executed", [
                    'article_id' => $article->id,
                    'feed_name' => $feedConfig->name,
                    'rule' => $rule->name,
                    'passed' => $result['passed'],
                ]);

            } catch (\Exception $e) {
                $results[$rule->name] = [
                    'rule' => $rule->name,
                    'passed' => false,
                    'message' => "Validation error: {$e->getMessage()}",
                    'details' => ['exception' => $e->getMessage()],
                    'executed_at' => now()->toISOString(),
                ];

                Log::error("Compliance check failed with exception", [
                    'article_id' => $article->id,
                    'feed_name' => $feedConfig->name,
                    'rule' => $rule->name,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $passedCount = collect($results)->filter(fn($r) => $r['passed'])->count();
        $totalCount = count($results);

        AuditLog::logEvent('compliance_check_completed', $article->id, $feedConfig->name, [
            'total_rules' => $totalCount,
            'passed_rules' => $passedCount,
            'failed_rules' => $totalCount - $passedCount,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Create validator instance
     */
    protected function createValidator(string $validatorClass)
    {
        if (!class_exists($validatorClass)) {
            throw new \Exception("Validator class does not exist: {$validatorClass}");
        }

        return new $validatorClass();
    }

    /**
     * Get available validators
     */
    public function getAvailableValidators(): array
    {
        return [
            'App\\Validators\\ContentLengthValidator',
            'App\\Validators\\ProhibitedTopicsValidator',
            'App\\Validators\\MetadataValidator',
            'App\\Validators\\AssetAttributionValidator',
        ];
    }
}