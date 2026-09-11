<?php

namespace Shipwake\LaravelAiCrawl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Shipwake\LaravelAiCrawl\Jobs\SendAiCrawlerRequest;
use Shipwake\LaravelAiCrawl\Support\CrawlerClassifier;
use Symfony\Component\HttpFoundation\Response;

class TrackAiCrawler
{
    public function __construct(private readonly CrawlerClassifier $classifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('shipwake-ai-crawl.site_key')
            || ! config('shipwake-ai-crawl.server_token')
            || ! $this->classifier->shouldTrack($request)) {
            return $next($request);
        }

        $response = $next($request);
        $payload = [
            'site_key' => config('shipwake-ai-crawl.site_key'),
            'href' => $this->publicUrl($request),
            'referrer' => $request->headers->get('referer'),
            'ai' => [
                'user_agent' => (string) $request->userAgent(),
                'status_code' => $response->getStatusCode(),
                'source' => 'server_middleware',
            ],
        ];

        $job = (new SendAiCrawlerRequest($payload))
            ->onQueue((string) config('shipwake-ai-crawl.queue', 'analytics'));

        $connection = config('shipwake-ai-crawl.connection');

        if (is_string($connection) && $connection !== '') {
            $job->onConnection($connection);
        }

        app()->terminating(static fn () => dispatch($job));

        return $response;
    }

    private function publicUrl(Request $request): string
    {
        $origin = config('shipwake-ai-crawl.public_origin') ?: config('app.url');

        if (is_string($origin) && $origin !== '') {
            return rtrim($origin, '/').'/'.ltrim($request->getRequestUri(), '/');
        }

        return $request->fullUrl();
    }
}
