<?php
// database/seeders/FeedConfigSeeder.php

namespace Database\Seeders;

use App\Models\FeedConfig;
use Illuminate\Database\Seeder;

class FeedConfigSeeder extends Seeder
{
    public function run()
    {
        $feeds = [
            [
                'name' => 'MSN',
                'display_name' => 'Microsoft MSN',
                'configuration' => [
                    'manual_validation' => true,
                    'timeout' => 30,
                    'retry_attempts' => 3,
                    'api_specifications' => [
                        'required_fields' => ['title', 'content', 'author', 'category'],
                        'title_max_length' => 100,
                        'content_min_length' => 300,
                        'prohibited_terms' => ['violence', 'explicit', 'hate'],
                    ],
                ],
                'compliance_rules' => [
                    'content_length_check',
                    'prohibited_topics_check',
                    'metadata_validation',
                    'asset_attribution_check',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'GoogleNews',
                'display_name' => 'Google News',
                'configuration' => [
                    'manual_validation' => true,
                    'timeout' => 45,
                    'retry_attempts' => 2,
                    'api_specifications' => [
                        'required_fields' => ['headline', 'body'],
                        'headline_max_length' => 120,
                        'body_min_length' => 200,
                    ],
                ],
                'compliance_rules' => [
                    'content_length_check',
                    'metadata_validation',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'AppleNews',
                'display_name' => 'Apple News',
                'configuration' => [
                    'manual_validation' => true,
                    'timeout' => 60,
                    'retry_attempts' => 3,
                    'api_specifications' => [
                        'required_fields' => ['title', 'components'],
                        'title_max_length' => 80,
                        'body_component_min_length' => 250,
                    ],
                ],
                'compliance_rules' => [
                    'content_length_check',
                    'metadata_validation',
                    'asset_attribution_check',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($feeds as $feedData) {
            FeedConfig::updateOrCreate(
                ['name' => $feedData['name']],
                $feedData
            );
        }
    }
}