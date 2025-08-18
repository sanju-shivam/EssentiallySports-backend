<?php
// app/Jobs/PublishArticleJob.php

namespace App\Jobs;

use App\Models\Article;
use App\Services\FeedPublishingController;
use App\Exceptions\ComplianceException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Article $article;
    protected string $feedName;
    protected int $attempts = 3;

    public function __construct(Article $article, string $feedName)
    {
        $this->article = $article;
        $this->feedName = $feedName;
    }

    public function handle(FeedPublishingController $feedPublisher)
    {
        try {
            $publishAttempt = $feedPublisher->publishToFeed($this->article, $this->feedName);
            
            Log::info("Background publish job completed successfully", [
                'article_id' => $this->article->id,
                'feed_name' => $this->feedName,
                'attempt_id' => $publishAttempt->id,
            ]);
            
        } catch (ComplianceException $e) {
            Log::warning("Background publish job failed compliance checks", [
                'article_id' => $this->article->id,
                'feed_name' => $this->feedName,
                'failed_checks' => $e->getFailedChecks(),
            ]);
            
            // Don't retry compliance failures
            $this->fail($e);
            
        } catch (\Exception $e) {
            Log::error("Background publish job failed with exception", [
                'article_id' => $this->article->id,
                'feed_name' => $this->feedName,
                'exception' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            throw $e; // Will trigger retry
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Background publish job failed permanently", [
            'article_id' => $this->article->id,
            'feed_name' => $this->feedName,
            'exception' => $exception->getMessage(),
        ]);
    }
}