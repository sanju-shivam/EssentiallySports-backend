<?php
// app/Exceptions/ComplianceException.php

namespace App\Exceptions;

use Exception;

class ComplianceException extends Exception
{
    protected array $failedChecks = [];

    public function __construct(string $message, array $failedChecks = [], int $code = 0, \Throwable $previous = null)
    {
        $this->failedChecks = $failedChecks;
        parent::__construct($message, $code, $previous);
    }

    public function getFailedChecks(): array
    {
        return $this->failedChecks;
    }

    public function getFailedCheckNames(): array
    {
        return array_keys($this->failedChecks);
    }

    public function getFailedCheckMessages(): array
    {
        return array_map(function($check) {
            return $check['message'] ?? 'No message provided';
        }, $this->failedChecks);
    }
}