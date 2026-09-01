<?php

namespace Shipwake\LaravelAiCrawl\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Shipwake\LaravelAiCrawl\Services\AiCrawlerClient;
use Throwable;

class SendAiCrawlerRequest implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 10;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(AiCrawlerClient $client): void
    {
        try {
            $client->send($this->payload);
        } catch (Throwable $exception) {
            Log::warning('Shipwake AI crawler tracking failed.', [
                'exception' => $exception::class,
            ]);
        }
    }
}
