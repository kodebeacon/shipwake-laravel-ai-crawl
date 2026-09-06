<?php

use Illuminate\Support\Facades\Log;
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

it('logs transport exceptions without failing the queue worker', function (): void {
    $client = Mockery::mock(AiCrawlerClient::class);
    $client->shouldReceive('send')->once()->andThrow(new RuntimeException('Network failure.'));
    Log::spy();

    (new SendAiCrawlerRequest(['site_key' => 'site-key']))->handle($client);

    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'Shipwake AI crawler tracking failed.'
            && $context === ['exception' => RuntimeException::class];
    });
});
