<?php

namespace Shipwake\LaravelAiCrawl\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Log;
use Shipwake\LaravelAiCrawl\Services\AiCrawlerClient;
use Throwable;

class SendAiCrawlerRequest implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 10;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    /**
     * Retry transient failures, but never fail the host application's queue.
     */
    public function handle(AiCrawlerClient $client): void
    {
        try {
            $client->send($this->payload);
        } catch (Throwable $exception) {
            if ($this->canRetry()) {
                throw $exception;
            }

            Log::warning('Shipwake AI crawler tracking failed.', [
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * Retry transient transport failures after ten and sixty seconds.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    private function canRetry(): bool
    {
        return $this->job !== null
            && ! $this->job instanceof SyncJob
            && $this->attempts() < $this->tries;
    }
}
