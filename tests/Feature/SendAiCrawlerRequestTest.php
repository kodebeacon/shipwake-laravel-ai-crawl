<?php

use Illuminate\Support\Facades\Log;
use Shipwake\LaravelAiCrawl\Exceptions\RetryableCrawlerRequest;
use Shipwake\LaravelAiCrawl\Jobs\SendAiCrawlerRequest;
use Shipwake\LaravelAiCrawl\Services\AiCrawlerClient;

afterEach(function (): void {
    Mockery::close();
});

it('delegates queued payloads to the crawler client', function (): void {
    $payload = ['site_key' => 'site-key'];
    $client = Mockery::mock(AiCrawlerClient::class);
    $client->shouldReceive('send')->once()->with($payload)->andReturnTrue();

    (new SendAiCrawlerRequest($payload))->handle($client);
});

it('rethrows failures while the queue can still retry', function (Throwable $exception): void {
    $client = Mockery::mock(AiCrawlerClient::class);
    $client->shouldReceive('send')->once()->andThrow($exception);
    Log::spy();

    $job = (new SendAiCrawlerRequest(['site_key' => 'site-key']))->withFakeQueueInteractions();

    expect(fn () => $job->handle($client))->toThrow($exception::class)
        ->and($job->backoff())->toBe([10, 60]);

    Log::shouldNotHaveReceived('warning');
})->with([
    'transport failure' => [new RuntimeException('Network failure.')],
    'retryable status' => [new RetryableCrawlerRequest(503)],
]);

it('logs the failure instead of failing the host queue on the last attempt', function (): void {
    $client = Mockery::mock(AiCrawlerClient::class);
    $client->shouldReceive('send')->once()->andThrow(new RetryableCrawlerRequest(503));
    Log::spy();

    $job = (new SendAiCrawlerRequest(['site_key' => 'site-key']))->withFakeQueueInteractions();
    $job->job->attempts = 3;

    $job->handle($client);

    $job->assertNotFailed();
    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $message === 'Shipwake AI crawler tracking failed.'
        && $context === ['exception' => RetryableCrawlerRequest::class]);
});

it('does not retry inline when the host runs a sync queue', function (): void {
    $client = Mockery::mock(AiCrawlerClient::class);
    $client->shouldReceive('send')->once()->andThrow(new RuntimeException('Network failure.'));
    Log::spy();

    $job = new SendAiCrawlerRequest(['site_key' => 'site-key']);
    $job->setJob(new Illuminate\Queue\Jobs\SyncJob(app(), '{}', 'sync', 'default'));

    $job->handle($client);

    Log::shouldHaveReceived('warning')->once();
});
