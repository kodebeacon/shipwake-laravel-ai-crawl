<?php

namespace Shipwake\LaravelAiCrawl;

use Illuminate\Support\ServiceProvider;
use Shipwake\LaravelAiCrawl\Services\AiCrawlerClient;
use Shipwake\LaravelAiCrawl\Support\CrawlerClassifier;

class ShipwakeAiCrawlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shipwake-ai-crawl.php', 'shipwake-ai-crawl');

        $this->app->singleton(CrawlerClassifier::class);
        $this->app->singleton(AiCrawlerClient::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/shipwake-ai-crawl.php' => config_path('shipwake-ai-crawl.php'),
        ], 'shipwake-ai-crawl-config');
    }
}
