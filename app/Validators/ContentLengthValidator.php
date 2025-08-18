<?php
// app/Validators/ContentLengthValidator.php

namespace App\Validators;

use App\Models\Article;
use App\Models\FeedConfig;

class ContentLengthValidator extends BaseValidator
{
    public function validate(Article $article, array $parameters, FeedConfig $feedConfig): array
    {
        $minWords = $parameters['min_words'] ?? 300;
        $maxWords = $parameters['max_words'] ?? 2000;
        $minChars = $parameters['min_chars'] ?? 1500;
        $maxChars = $parameters['max_chars'] ?? 10000;

        $wordCount = $article->word_count;
        $charCount = $article->character_count;

        $issues = [];

        if ($wordCount < $minWords) {
            $issues[] = "Word count ({$wordCount}) is below minimum ({$minWords})";
        }

        if ($wordCount > $maxWords) {
            $issues[] = "Word count ({$wordCount}) exceeds maximum ({$maxWords})";
        }

        if ($charCount < $minChars) {
            $issues[] = "Character count ({$charCount}) is below minimum ({$minChars})";
        }

        if ($charCount > $maxChars) {
            $issues[] = "Character count ({$charCount}) exceeds maximum ({$maxChars})";
        }

        if (!empty($issues)) {
            return $this->fail(
                'Content length validation failed: ' . implode(', ', $issues),
                [
                    'word_count' => $wordCount,
                    'character_count' => $charCount,
                    'requirements' => [
                        'min_words' => $minWords,
                        'max_words' => $maxWords,
                        'min_chars' => $minChars,
                        'max_chars' => $maxChars,
                    ],
                    'issues' => $issues,
                ]
            );
        }

        return $this->pass('Content length is within acceptable range', [
            'word_count' => $wordCount,
            'character_count' => $charCount,
        ]);
    }
}