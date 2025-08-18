<?php
// config/feeds.php

return [
    'msn' => [
        'display_name' => 'Microsoft MSN',
        'description' => 'Microsoft MSN syndication feed - manual API integration',
        'manual_validation' => true,
        'content_requirements' => [
            'min_words' => 300,
            'max_words' => 2000,
            'min_chars' => 1500,
            'max_chars' => 10000,
            'title_max_length' => 100,
            'title_min_length' => 10,
            'require_thumbnail' => true,
            'require_image_attribution' => true,
        ],
        'api_specifications' => [
            'required_fields' => ['title', 'content', 'author', 'category'],
            'title_max_length' => 100,
            'content_min_length' => 300,
            'prohibited_terms' => ['violence', 'explicit', 'hate'],
            'image_required' => true,
        ],
        'prohibited_keywords' => [
            'explicit content',
            'violence',
            'hate speech',
        ],
        'prohibited_categories' => [
            'adult',
            'gambling',
            'illegal',
        ],
        'compliance_rules' => [
            'content_length_check',
            'prohibited_topics_check',
            'metadata_validation',
            'asset_attribution_check',
        ],
    ],

    'google_news' => [
        'display_name' => 'Google News',
        'description' => 'Google News Publisher - manual API integration',
        'manual_validation' => true,
        'content_requirements' => [
            'min_words' => 200,
            'max_words' => 5000,
            'title_max_length' => 120,
            'require_thumbnail' => false,
        ],
        'api_specifications' => [
            'required_fields' => ['headline', 'body'],
            'headline_max_length' => 120,
            'body_min_length' => 200,
            'image_optional' => true,
        ],
        'compliance_rules' => [
            'content_length_check',
            'metadata_validation',
        ],
    ],

    'apple_news' => [
        'display_name' => 'Apple News',
        'description' => 'Apple News Publisher - manual API integration',
        'manual_validation' => true,
        'content_requirements' => [
            'min_words' => 250,
            'max_words' => 3000,
            'title_max_length' => 80,
            'require_thumbnail' => true,
            'require_image_attribution' => true,
        ],
        'api_specifications' => [
            'required_fields' => ['title', 'components'],
            'title_max_length' => 80,
            'body_component_min_length' => 250,
            'components_required' => true,
        ],
        'compliance_rules' => [
            'content_length_check',
            'metadata_validation',
            'asset_attribution_check',
        ],
    ],
];