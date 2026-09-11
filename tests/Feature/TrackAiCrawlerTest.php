<?php

use Illuminate\Support\Facades\Queue;
use Shipwake\LaravelAiCrawl\Http\Middleware\TrackAiCrawler;
use Shipwake\LaravelAiCrawl\Jobs\SendAiCrawlerRequest;

beforeEach(function (): void {
    config()->set('shipwake-ai-crawl.site_key', 'site-public-key');
    config()->set('shipwake-ai-crawl.server_token', 'private-server-token');
    config()->set('shipwake-ai-crawl.queue', 'crawler-events');
    config()->set('shipwake-ai-crawl.connection', null);
    config()->set('shipwake-ai-crawl.public_origin', 'https://canonical.test');
});

it('queues crawler responses with their canonical public URL', function (): void {
    Queue::fake();

    $this->withHeaders([
        'User-Agent' => 'GPTBot/1.2',
        'Referer' => 'https://search.example/result',
    ])->get('/tracked?source=ai')->assertCreated();

    Queue::assertPushedOn('crawler-events', SendAiCrawlerRequest::class, function (SendAiCrawlerRequest $job): bool {
        return $job->payload === [
            'site_key' => 'site-public-key',
            'href' => 'https://canonical.test/tracked?source=ai',
            'referrer' => 'https://search.example/result',
            'ai' => [
                'user_agent' => 'GPTBot/1.2',
                'status_code' => 201,
                'source' => 'server_middleware',
            ],
        ];
    });
});

it('uses the configured queue connection when present', function (): void {
    Queue::fake();
    config()->set('shipwake-ai-crawl.connection', 'redis');

    $this->withHeader('User-Agent', 'GPTBot/1.2')->get('/tracked')->assertCreated();

    $job = null;

    Queue::assertPushed(SendAiCrawlerRequest::class, function (SendAiCrawlerRequest $queuedJob) use (&$job): bool {
        $job = $queuedJob;

        return true;
    });

    expect($job->connection)->toBe('redis')
        ->and($job->queue)->toBe('crawler-events');
});

it('passes through requests when a required tracking setting is absent', function (string $setting): void {
    Queue::fake();
    config()->set("shipwake-ai-crawl.{$setting}", null);

    $this->withHeader('User-Agent', 'GPTBot/1.2')->get('/tracked')->assertCreated();

    Queue::assertNothingPushed();
})->with(['site_key', 'server_token']);

it('passes through regular visitor requests without queueing', function (): void {
    Queue::fake();

    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/tracked')->assertCreated();

    Queue::assertNothingPushed();
});

it('uses the app URL when no explicit public origin is configured', function (): void {
    $middleware = app(TrackAiCrawler::class);
    config()->set('shipwake-ai-crawl.public_origin', null);
    config()->set('app.url', 'https://playbrackit.com');

    expect(publicUrlFrom($middleware, 'https://request.test/tracked?source=app'))->toBe('https://playbrackit.com/tracked?source=app');
});

it('uses the request URL when neither canonical origin is configured', function (): void {
    $middleware = app(TrackAiCrawler::class);
    config()->set('shipwake-ai-crawl.public_origin', null);
    config()->set('app.url', null);

    expect(publicUrlFrom($middleware, 'https://request.test/tracked?source=request'))->toBe('https://request.test/tracked?source=request');
});

function publicUrlFrom(TrackAiCrawler $middleware, string $url): string
{
    $method = new ReflectionMethod($middleware, 'publicUrl');

    return $method->invoke($middleware, Illuminate\Http\Request::create($url));
}
