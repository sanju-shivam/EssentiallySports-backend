<?php
// config/compliance.php

return [
    'cache_duration' => 3600, // 1 hour
    
    'validators' => [
        'content_length' => [
            'class' => 'App\\Validators\\ContentLengthValidator',
            'description' => 'Validates article word and character count',
        ],
        'prohibited_topics' => [
            'class' => 'App\\Validators\\ProhibitedTopicsValidator',
            'description' => 'Checks for prohibited keywords and categories',
        ],
        'metadata' => [
            'class' => 'App\\Validators\\MetadataValidator',
            'description' => 'Validates required metadata fields',
        ],
        'asset_attribution' => [
            'class' => 'App\\Validators\\AssetAttributionValidator',
            'description' => 'Ensures proper attribution for images and videos',
        ],
    ],

    'monitoring' => [
        'alert_on_consecutive_failures' => 5,
        'alert_email' => env('COMPLIANCE_ALERT_EMAIL'),
        'slack_webhook' => env('COMPLIANCE_SLACK_WEBHOOK'),
    ],

    'audit' => [
        'retention_days' => 365,
        'log_all_attempts' => true,
        'log_compliance_details' => true,
    ],
];