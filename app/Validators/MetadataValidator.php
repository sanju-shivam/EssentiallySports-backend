<?php
// app/Validators/MetadataValidator.php

namespace App\Validators;

use App\Models\Article;
use App\Models\FeedConfig;

class MetadataValidator extends BaseValidator
{
    public function validate(Article $article, array $parameters, FeedConfig $feedConfig): array
    {
        $requiredFields = $parameters['required_fields'] ?? ['title', 'author', 'category'];
        $titleMaxLength = $parameters['title_max_length'] ?? 100;
        $titleMinLength = $parameters['title_min_length'] ?? 10;
        $requireThumbnail = $parameters['require_thumbnail'] ?? true;

        $issues = [];

        // Check required fields
        foreach ($requiredFields as $field) {
            $value = $article->{$field};
            if (empty($value)) {
                $issues[] = "Required field '{$field}' is missing or empty";
            }
        }

        // Check title length
        if ($article->title) {
            $titleLength = strlen($article->title);
            if ($titleLength < $titleMinLength) {
                $issues[] = "Title length ({$titleLength}) is below minimum ({$titleMinLength})";
            }
            if ($titleLength > $titleMaxLength) {
                $issues[] = "Title length ({$titleLength}) exceeds maximum ({$titleMaxLength})";
            }
        }

        // Check thumbnail
        if ($requireThumbnail && empty($article->thumbnail_url)) {
            $issues[] = "Thumbnail URL is required but not provided";
        }

        // Validate thumbnail URL if provided
        if ($article->thumbnail_url && !filter_var($article->thumbnail_url, FILTER_VALIDATE_URL)) {
            $issues[] = "Invalid thumbnail URL format";
        }

        if (!empty($issues)) {
            return $this->fail(
                'Metadata validation failed',
                [
                    'issues' => $issues,
                    'current_metadata' => [
                        'title' => $article->title,
                        'title_length' => strlen($article->title ?? ''),
                        'author' => $article->author,
                        'category' => $article->category,
                        'thumbnail_url' => $article->thumbnail_url,
                    ],
                    'requirements' => $parameters,
                ]
            );
        }

        return $this->pass('All metadata requirements satisfied');
    }
}