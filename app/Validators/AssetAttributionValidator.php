<?php
// app/Validators/AssetAttributionValidator.php

namespace App\Validators;

use App\Models\Article;
use App\Models\FeedConfig;

class AssetAttributionValidator extends BaseValidator
{
    public function validate(Article $article, array $parameters, FeedConfig $feedConfig): array
    {
        $requireImageAttribution = $parameters['require_image_attribution'] ?? true;
        $requireVideoAttribution = $parameters['require_video_attribution'] ?? true;
        $allowedImageSources = $parameters['allowed_image_sources'] ?? [];

        $issues = [];

        // Check thumbnail attribution
        if ($requireImageAttribution && $article->thumbnail_url) {
            $metadata = $article->metadata ?? [];
            // dd($metadata);
            // if (empty($metadata['thumbnail_attribution'])) {
            //     $issues[] = "Thumbnail requires attribution but none provided";
            // }

            // Check if thumbnail source is allowed
            if (!empty($allowedImageSources)) {
                $thumbnailDomain = parse_url($article->thumbnail_url, PHP_URL_HOST);
                if (!in_array($thumbnailDomain, $allowedImageSources)) {
                    $issues[] = "Thumbnail source '{$thumbnailDomain}' is not in allowed sources list";
                }
            }
        }

        // Check for images in content
        if ($requireImageAttribution) {
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $article->body, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $index => $imageUrl) {
                    $imageDomain = parse_url($imageUrl, PHP_URL_HOST);
                    
                    // Check attribution in metadata
                    $attributionKey = "image_{$index}_attribution";
                    if (empty($metadata[$attributionKey])) {
                        $issues[] = "Image at index {$index} requires attribution";
                    }

                    // Check allowed sources
                    if (!empty($allowedImageSources) && !in_array($imageDomain, $allowedImageSources)) {
                        $issues[] = "Image source '{$imageDomain}' at index {$index} is not allowed";
                    }
                }
            }
        }

        // Check for videos in content
        if ($requireVideoAttribution) {
            preg_match_all('/<video[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $article->body, $videoMatches);
            if (!empty($videoMatches[1])) {
                foreach ($videoMatches[1] as $index => $videoUrl) {
                    $attributionKey = "video_{$index}_attribution";
                    if (empty($metadata[$attributionKey])) {
                        $issues[] = "Video at index {$index} requires attribution";
                    }
                }
            }
        }

        if (!empty($issues)) {
            return $this->fail(
                'Asset attribution validation failed',
                [
                    'issues' => $issues,
                    'found_images' => $matches[1] ?? [],
                    'found_videos' => $videoMatches[1] ?? [],
                    'current_attributions' => array_filter($metadata, function($key) {
                        return strpos($key, '_attribution') !== false;
                    }, ARRAY_FILTER_USE_KEY),
                ]
            );
        }

        return $this->pass('All assets properly attributed');
    }
}