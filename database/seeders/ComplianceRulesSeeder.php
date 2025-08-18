<?php
// database/seeders/ComplianceRulesSeeder.php

namespace Database\Seeders;

use App\Models\ComplianceRule;
use Illuminate\Database\Seeder;

class ComplianceRulesSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            [
                'name' => 'content_length_check',
                'validator_class' => 'App\\Validators\\ContentLengthValidator',
                'parameters' => [
                    'min_words' => 300,
                    'max_words' => 2000,
                    'min_chars' => 1500,
                    'max_chars' => 10000,
                ],
                'description' => 'Validates article content length requirements',
                'priority' => 1,
            ],
            [
                'name' => 'prohibited_topics_check',
                'validator_class' => 'App\\Validators\\ProhibitedTopicsValidator',
                'parameters' => [
                    'prohibited_keywords' => [
                        'explicit content', 'violence', 'hate speech', 
                        'illegal drugs', 'weapons', 'terrorism'
                    ],
                    'prohibited_categories' => ['adult', 'gambling', 'illegal'],
                    'check_title' => true,
                    'check_body' => true,
                    'check_tags' => true,
                ],
                'description' => 'Checks for prohibited topics and keywords',
                'priority' => 2,
            ],
            [
                'name' => 'metadata_validation',
                'validator_class' => 'App\\Validators\\MetadataValidator',
                'parameters' => [
                    'required_fields' => ['title', 'author', 'category'],
                    'title_max_length' => 100,
                    'title_min_length' => 10,
                    'require_thumbnail' => true,
                ],
                'description' => 'Validates required metadata fields',
                'priority' => 3,
            ],
            [
                'name' => 'asset_attribution_check',
                'validator_class' => 'App\\Validators\\AssetAttributionValidator',
                'parameters' => [
                    'require_image_attribution' => true,
                    'require_video_attribution' => true,
                    'allowed_image_sources' => [
                        'cdn.essentiallysports.com',
                        'images.unsplash.com',
                        'cdn.pixabay.com',
                        'example.com'
                    ],
                ],
                'description' => 'Ensures proper attribution for media assets',
                'priority' => 4,
            ],
        ];

        foreach ($rules as $ruleData) {
            ComplianceRule::updateOrCreate(
                ['name' => $ruleData['name']],
                $ruleData
            );
        }
    }
}