<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublishAttemptsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Example: simulate multiple publish attempts for 3 different articles
        $articles = [1, 2, 3,4,5,6,7,21,20]; // assume articles already seeded in "articles" table
        // [1, 2, 3,4,5,6,7,21,20]
        foreach ($articles as $articleId) {
            // First attempt - failed compliance (MSN)
            DB::table('publish_attempts')->insert([
                'article_id'       => $articleId,
                'feed_name'        => 'MSN',
                'status'           => 'failed',
                'compliance_results' => json_encode([
                    'title_length_check' => 'passed',
                    'body_length_check'  => 'failed',
                    'metadata_check'     => 'passed',
                ]),
                'error_details'    => json_encode([
                    'error' => 'Body too short. MSN requires at least 200 words.'
                ]),
                'attempted_at'     => $now->subMinutes(30),
                'completed_at'     => $now->subMinutes(29),
                'external_id'      => null,
                'created_at'       => $now->subMinutes(30),
                'updated_at'       => $now->subMinutes(29),
            ]);

            // Second attempt - passed (MSN)
            DB::table('publish_attempts')->insert([
                'article_id'       => $articleId,
                'feed_name'        => 'MSN',
                'status'           => 'success',
                'compliance_results' => json_encode([
                    'title_length_check' => 'passed',
                    'body_length_check'  => 'passed',
                    'metadata_check'     => 'passed',
                ]),
                'error_details'    => null,
                'attempted_at'     => $now->subMinutes(20),
                'completed_at'     => $now->subMinutes(19),
                'external_id'      => 'MSN-' . strtoupper(uniqid()),
                'created_at'       => $now->subMinutes(20),
                'updated_at'       => $now->subMinutes(19),
            ]);

            // Third attempt - pending (Google News)
            DB::table('publish_attempts')->insert([
                'article_id'       => $articleId,
                'feed_name'        => 'Google News',
                'status'           => 'pending',
                'compliance_results' => json_encode([
                    'title_length_check' => 'passed',
                    'prohibited_words'   => 'pending',
                    'thumbnail_check'    => 'pending',
                ]),
                'error_details'    => null,
                'attempted_at'     => $now->subMinutes(5),
                'completed_at'     => null,
                'external_id'      => null,
                'created_at'       => $now->subMinutes(5),
                'updated_at'       => $now->subMinutes(5),
            ]);
        }
    }
}
