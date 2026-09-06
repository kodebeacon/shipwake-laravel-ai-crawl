<?php

namespace Shipwake\LaravelAiCrawl\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Shipwake\LaravelAiCrawl\Http\Middleware\TrackAiCrawler;
use Shipwake\LaravelAiCrawl\ShipwakeAiCrawlServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ShipwakeAiCrawlServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', 'https://package.test');
        $app['config']->set('queue.default', 'sync');
    }

    protected function defineRoutes($router): void
    {
        $router->get('/tracked', fn () => response('tracked', 201))
            ->middleware(TrackAiCrawler::class);
    }
}
