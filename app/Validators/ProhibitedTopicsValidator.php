<?php
// app/Validators/ProhibitedTopicsValidator.php

namespace App\Validators;

use App\Models\Article;
use App\Models\FeedConfig;

class ProhibitedTopicsValidator extends BaseValidator
{
    public function validate(Article $article, array $parameters, FeedConfig $feedConfig): array
    {
        $prohibitedKeywords = $parameters['prohibited_keywords'] ?? [];
        $prohibitedCategories = $parameters['prohibited_categories'] ?? [];
        $checkTitle = $parameters['check_title'] ?? true;
        $checkBody = $parameters['check_body'] ?? true;
        $checkTags = $parameters['check_tags'] ?? true;

        $foundIssues = [];

        // Check category
        if (in_array($article->category, $prohibitedCategories)) {
            $foundIssues[] = "Article category '{$article->category}' is prohibited";
        }

        // Check for prohibited keywords
        $textToCheck = '';
        if ($checkTitle) $textToCheck .= ' ' . $article->title;
        if ($checkBody) $textToCheck .= ' ' . $article->body;
        if ($checkTags && $article->tags) $textToCheck .= ' ' . implode(' ', $article->tags);

        $textToCheck = strtolower($textToCheck);

        $foundKeywords = [];
        foreach ($prohibitedKeywords as $keyword) {
            if (strpos($textToCheck, strtolower($keyword)) !== false) {
                $foundKeywords[] = $keyword;
            }
        }

        if (!empty($foundKeywords)) {
            $foundIssues[] = "Contains prohibited keywords: " . implode(', ', $foundKeywords);
        }

        if (!empty($foundIssues)) {
            return $this->fail(
                'Content contains prohibited topics or keywords',
                [
                    'issues' => $foundIssues,
                    'found_keywords' => $foundKeywords,
                    'category' => $article->category,
                    'prohibited_categories' => $prohibitedCategories,
                    'prohibited_keywords' => $prohibitedKeywords,
                ]
            );
        }

        return $this->pass('No prohibited topics or keywords found');
    }
}