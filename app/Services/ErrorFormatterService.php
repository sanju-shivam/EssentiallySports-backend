<?php
// app/Services/ErrorFormatterService.php

namespace App\Services;
use App\Models\Article;

class ErrorFormatterService
{
    public function formatComplianceErrors(array $failedChecks): array
    {
        $formattedErrors = [];

        foreach ($failedChecks as $ruleName => $details) {
            $formattedErrors[] = [
                'rule' => $ruleName,
                'title' => $this->getRuleDisplayName($ruleName),
                'message' => $this->makeUserFriendly($details['message'] ?? ''),
                'suggestions' => $this->getSuggestions($ruleName, $details),
                'severity' => $this->getRuleSeverity($ruleName),
            ];
        }

        return $formattedErrors;
    }

    private function getRuleDisplayName(string $ruleName): string
    {
        $displayNames = [
            'content_length_check' => 'Content Length Requirements',
            'prohibited_topics_check' => 'Content Policy Compliance',
            'metadata_validation' => 'Article Information Requirements',
            'asset_attribution_check' => 'Media Attribution Requirements',
        ];

        return $displayNames[$ruleName] ?? ucwords(str_replace('_', ' ', $ruleName));
    }

    private function makeUserFriendly(string $technicalMessage): string
    {
        $friendlyMessages = [
            'Word count' => 'Article length',
            'Character count' => 'Article length',
            'prohibited keywords' => 'content that violates our guidelines',
            'metadata' => 'required article information',
            'thumbnail' => 'featured image',
            'attribution' => 'image credits',
        ];

        $message = $technicalMessage;
        foreach ($friendlyMessages as $technical => $friendly) {
            $message = str_ireplace($technical, $friendly, $message);
        }

        return $message;
    }

    private function getSuggestions(string $ruleName, array $details): array
    {
        switch ($ruleName) {
            case 'content_length_check':
                return $this->getContentLengthSuggestions($details);
            case 'prohibited_topics_check':
                return $this->getProhibitedTopicsSuggestions($details);
            case 'metadata_validation':
                return $this->getMetadataSuggestions($details);
            case 'asset_attribution_check':
                return $this->getAssetAttributionSuggestions($details);
            default:
                return ['Please review the article and make necessary corrections.'];
        }
    }

    private function getContentLengthSuggestions(array $details): array
    {
        $suggestions = [];
        $issues = $details['details']['issues'] ?? [];

        foreach ($issues as $issue) {
            if (strpos($issue, 'below minimum') !== false) {
                $suggestions[] = 'Add more detailed content to reach the minimum length requirement.';
                $suggestions[] = 'Consider expanding on key points or adding relevant examples.';
            } elseif (strpos($issue, 'exceeds maximum') !== false) {
                $suggestions[] = 'Shorten the article by removing less essential information.';
                $suggestions[] = 'Consider splitting into multiple articles if appropriate.';
            }
        }

        return array_unique($suggestions);
    }

    private function getProhibitedTopicsSuggestions(array $details): array
    {
        $suggestions = [];
        $foundKeywords = $details['details']['found_keywords'] ?? [];

        if (!empty($foundKeywords)) {
            $suggestions[] = 'Remove or rephrase content containing: ' . implode(', ', $foundKeywords);
            $suggestions[] = 'Use alternative terminology that conveys the same meaning.';
        }

        $suggestions[] = 'Ensure content complies with feed community guidelines.';
        $suggestions[] = 'Focus on informative, appropriate content for the target audience.';

        return $suggestions;
    }

    private function getMetadataSuggestions(array $details): array
    {
        $suggestions = [];
        $issues = $details['details']['issues'] ?? [];

        foreach ($issues as $issue) {
            if (strpos($issue, 'title') !== false) {
                if (strpos($issue, 'missing') !== false) {
                    $suggestions[] = 'Add a descriptive title to your article.';
                } else {
                    $suggestions[] = 'Adjust the title length to meet requirements.';
                }
            } elseif (strpos($issue, 'author') !== false) {
                $suggestions[] = 'Specify the article author in the author field.';
            } elseif (strpos($issue, 'category') !== false) {
                $suggestions[] = 'Select an appropriate category for your article.';
            } elseif (strpos($issue, 'thumbnail') !== false) {
                $suggestions[] = 'Add a high-quality featured image to your article.';
            }
        }

        return array_unique($suggestions);
    }

    private function getAssetAttributionSuggestions(array $details): array
    {
        return [
            'Add proper attribution for all images and media used in the article.',
            'Ensure you have permission to use all visual content.',
            'Include photo credits in the article metadata.',
            'Use only images from approved sources when possible.',
        ];
    }

    private function getRuleSeverity(string $ruleName): string
    {
        $severityMap = [
            'prohibited_topics_check' => 'high',
            'metadata_validation' => 'medium',
            'content_length_check' => 'medium',
            'asset_attribution_check' => 'low',
        ];

        return $severityMap[$ruleName] ?? 'medium';
    }

    public function generateEditorReport(Article $article, array $failedChecks): array
    {
        $formattedErrors = $this->formatComplianceErrors($failedChecks);
        
        return [
            'article_id' => $article->id,
            'article_title' => $article->title,
            'total_issues' => count($formattedErrors),
            'high_priority_issues' => count(array_filter($formattedErrors, fn($e) => $e['severity'] === 'high')),
            'issues' => $formattedErrors,
            'next_steps' => [
                'Review and address each issue listed above',
                'Make the suggested corrections to your article',
                'Test compliance again before attempting to publish',
                'Contact the editorial team if you need assistance',
            ],
            'estimated_fix_time' => $this->estimateFixTime($formattedErrors),
        ];
    }

    private function estimateFixTime(array $errors): string
    {
        $highPriority = count(array_filter($errors, fn($e) => $e['severity'] === 'high'));
        $mediumPriority = count(array_filter($errors, fn($e) => $e['severity'] === 'medium'));
        $lowPriority = count(array_filter($errors, fn($e) => $e['severity'] === 'low'));

        $estimatedMinutes = ($highPriority * 15) + ($mediumPriority * 10) + ($lowPriority * 5);

        if ($estimatedMinutes < 15) {
            return 'Less than 15 minutes';
        } elseif ($estimatedMinutes < 60) {
            return $estimatedMinutes . ' minutes';
        } else {
            $hours = round($estimatedMinutes / 60, 1);
            return $hours . ' hours';
        }
    }
}