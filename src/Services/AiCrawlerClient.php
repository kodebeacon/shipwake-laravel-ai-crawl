<?php

namespace Shipwake\LaravelAiCrawl\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCrawlerClient
{
    private const ENDPOINT = 'https://shipwake.dev/api/ai-crawls';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload): bool
    {
        $siteKey = (string) ($payload['site_key'] ?? config('shipwake-ai-crawl.site_key'));

        if ($siteKey === ''
            || ! config('shipwake-ai-crawl.server_token')) {
            return false;
        }

        $request = $this->request();
        $response = $request->post(self::ENDPOINT, $payload);

        if ($response->successful()) {
            return true;
        }

        Log::warning('Shipwake AI crawler tracking returned an error.', [
            'status' => $response->status(),
            'reason' => $response->json('reason'),
        ]);

        return false;
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('shipwake-ai-crawl.timeout', 1))
            ->connectTimeout((int) config('shipwake-ai-crawl.connect_timeout', 1));

        $request = $request->withToken((string) config('shipwake-ai-crawl.server_token'));

        return $request;
    }
}
