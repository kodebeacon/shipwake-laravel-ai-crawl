<?php

namespace Shipwake\LaravelAiCrawl\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCrawlerClient
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload): bool
    {
        $siteKey = (string) ($payload['site_key'] ?? config('shipwake-ai-crawl.site_key'));

        if ($siteKey === ''
            || ! config('shipwake-ai-crawl.server_token')
            || ! config('shipwake-ai-crawl.endpoint')) {
            return false;
        }

        $request = $this->request();
        $response = $request->post((string) config('shipwake-ai-crawl.endpoint'), $payload);

        if ($response->successful()) {
            return true;
        }

        Log::warning('Shipwake AI crawler tracking returned an error.', [
            'status' => $response->status(),
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
