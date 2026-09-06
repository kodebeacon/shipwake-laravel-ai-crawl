<?php

use Shipwake\LaravelAiCrawl\Services\AiCrawlerClient;
use Shipwake\LaravelAiCrawl\Support\CrawlerClassifier;

it('merges its configuration and registers singleton services', function (): void {
    expect(config('shipwake-ai-crawl.ignored_path_prefixes'))->toContain('/api', '/assets')
        ->and(app(CrawlerClassifier::class))->toBe(app(CrawlerClassifier::class))
        ->and(app(AiCrawlerClient::class))->toBe(app(AiCrawlerClient::class));
});

it('publishes its configuration file', function (): void {
    $this->artisan('vendor:publish', [
        '--tag' => 'shipwake-ai-crawl-config',
        '--force' => true,
    ])->assertExitCode(0);

    expect(config_path('shipwake-ai-crawl.php'))->toBeFile()
        ->and(file_get_contents(config_path('shipwake-ai-crawl.php')))->toContain("'endpoint' => env('SHIPWAKE_AI_CRAWL_ENDPOINT')");
});
