<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Shipwake\LaravelAiCrawl\Services\AiCrawlerClient;

beforeEach(function (): void {
    config()->set('shipwake-ai-crawl.site_key', 'configured-site-key');
    config()->set('shipwake-ai-crawl.server_token', 'private-server-token');
    config()->set('shipwake-ai-crawl.timeout', 4);
    config()->set('shipwake-ai-crawl.connect_timeout', 2);
});

it('sends the payload as authenticated JSON with configured timeouts', function (): void {
    $options = [];

    Http::fake(function (HttpRequest $request, array $requestOptions) use (&$options) {
        $options = $requestOptions;

        return Http::response(['accepted' => true], 202);
    });

    $payload = crawlerPayload();

    expect(app(AiCrawlerClient::class)->send($payload))->toBeTrue();

    Http::assertSent(function (HttpRequest $request): bool {
        return $request->url() === 'https://shipwake.dev/api/ai-crawls'
            && $request->hasHeader('Authorization', 'Bearer private-server-token')
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === crawlerPayload();
    });

    expect($options['timeout'])->toBe(4)
        ->and($options['connect_timeout'])->toBe(2);
});

it('does not send a request without every required credential', function (string $setting): void {
    Http::fake();
    $payload = crawlerPayload();

    if ($setting === 'site_key') {
        unset($payload['site_key']);
    }

    config()->set("shipwake-ai-crawl.{$setting}", null);

    expect(app(AiCrawlerClient::class)->send($payload))->toBeFalse();
    Http::assertNothingSent();
})->with(['site_key', 'server_token']);

it('does not send a request when the payload site key is blank', function (): void {
    Http::fake();

    expect(app(AiCrawlerClient::class)->send([...crawlerPayload(), 'site_key' => '']))->toBeFalse();
    Http::assertNothingSent();
});

it('returns false and logs the Signal rejection reason', function (): void {
    Http::fake([
        'https://shipwake.dev/api/ai-crawls' => Http::response([
            'accepted' => false,
            'reason' => 'domain_mismatch',
        ], 403),
    ]);
    Log::spy();

    expect(app(AiCrawlerClient::class)->send(crawlerPayload()))->toBeFalse();

    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'Shipwake AI crawler tracking returned an error.'
            && $context === ['status' => 403, 'reason' => 'domain_mismatch'];
    });
});

it('allows connection failures to be handled by the queued job', function (): void {
    Http::fake(fn () => throw new ConnectionException('Connection refused.'));

    expect(fn () => app(AiCrawlerClient::class)->send(crawlerPayload()))->toThrow(ConnectionException::class);
});

function crawlerPayload(): array
{
    return [
        'site_key' => 'payload-site-key',
        'href' => 'https://playbrackit.com/docs',
        'referrer' => null,
        'ai' => [
            'user_agent' => 'GPTBot/1.2',
            'status_code' => 200,
            'source' => 'server_middleware',
        ],
    ];
}
