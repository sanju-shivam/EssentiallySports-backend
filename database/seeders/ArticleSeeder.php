<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        foreach (range(1, 20) as $index) {
            DB::table('articles')->insert([
                'title'         => $faker->sentence(6),
                'body'          => $faker->paragraph(8),
                'author'        => $faker->name,
                'category'      => $faker->randomElement(['Sports', 'Tech', 'Health', 'Business', 'Entertainment']),
                'thumbnail_url' => $faker->imageUrl(640, 480, 'articles', true),
                'metadata'      => json_encode([
                    'views' => $faker->numberBetween(10, 5000),
                    'likes' => $faker->numberBetween(0, 1000),
                ]),
                'tags'          => json_encode($faker->words(5)),
                'status'        => $faker->randomElement(['draft', 'ready', 'published', 'failed']),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
