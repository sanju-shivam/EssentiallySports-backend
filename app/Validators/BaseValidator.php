<?php
// app/Validators/BaseValidator.php

namespace App\Validators;

use App\Models\Article;
use App\Models\FeedConfig;

abstract class BaseValidator
{
    /**
     * Validate an article
     */
    abstract public function validate(Article $article, array $parameters, FeedConfig $feedConfig): array;

    /**
     * Helper method to return success result
     */
    protected function pass(string $message = 'Validation passed', array $details = []): array
    {
        return [
            'passed' => true,
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * Helper method to return failure result
     */
    protected function fail(string $message, array $details = []): array
    {
        return [
            'passed' => false,
            'message' => $message,
            'details' => $details,
        ];
    }
}