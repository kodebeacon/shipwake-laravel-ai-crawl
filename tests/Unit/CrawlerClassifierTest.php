<?php

use Illuminate\Http\Request;
use Shipwake\LaravelAiCrawl\Support\CrawlerClassifier;

function classifierRequest(string $method = 'GET', string $uri = '/docs', ?string $userAgent = 'GPTBot/1.2', array $headers = []): Request
{
    $server = [];

    if ($userAgent !== null) {
        $server['HTTP_USER_AGENT'] = $userAgent;
    }

    foreach ($headers as $name => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }

    return Request::create($uri, $method, server: $server);
}

dataset('crawler catalog', [
    'ChatGPT user' => ['ChatGPT-User/1.0', ['category' => 'ai_answers', 'provider' => 'OpenAI', 'agent' => 'ChatGPT-User']],
    'OpenAI search' => ['OAI-SearchBot/1.0', ['category' => 'ai_answers', 'provider' => 'OpenAI', 'agent' => 'OAI-SearchBot']],
    'OpenAI operator' => ['OpenAI-Operator/1.0', ['category' => 'ai_answers', 'provider' => 'OpenAI', 'agent' => 'OpenAI-Operator']],
    'OpenAI training' => ['gPtBoT/1.2', ['category' => 'training', 'provider' => 'OpenAI', 'agent' => 'GPTBot']],
    'Claude user' => ['Claude-User/1.0', ['category' => 'ai_answers', 'provider' => 'Anthropic', 'agent' => 'Claude-User']],
    'Claude search' => ['Claude-SearchBot/1.0', ['category' => 'ai_answers', 'provider' => 'Anthropic', 'agent' => 'Claude-SearchBot']],
    'Claude training' => ['ClaudeBot/1.0', ['category' => 'training', 'provider' => 'Anthropic', 'agent' => 'ClaudeBot']],
    'Perplexity user' => ['Perplexity-User/1.0', ['category' => 'ai_answers', 'provider' => 'Perplexity', 'agent' => 'Perplexity-User']],
    'Perplexity indexer' => ['PerplexityBot/1.0', ['category' => 'indexing', 'provider' => 'Perplexity', 'agent' => 'PerplexityBot']],
    'NotebookLM' => ['Google-NotebookLM/1.0', ['category' => 'ai_answers', 'provider' => 'Google', 'agent' => 'Google-NotebookLM']],
    'Google training' => ['Google-Extended/1.0', ['category' => 'training', 'provider' => 'Google', 'agent' => 'Google-Extended']],
    'Google indexer' => ['Googlebot/2.1', ['category' => 'indexing', 'provider' => 'Google', 'agent' => 'Googlebot']],
    'Bing indexer' => ['bingbot/2.0', ['category' => 'indexing', 'provider' => 'Microsoft', 'agent' => 'Bingbot']],
    'Apple training' => ['Applebot-Extended/1.0', ['category' => 'training', 'provider' => 'Apple', 'agent' => 'Applebot-Extended']],
    'Apple indexer' => ['Applebot/1.0', ['category' => 'indexing', 'provider' => 'Apple', 'agent' => 'Applebot']],
    'ByteDance training' => ['Bytespider/1.0', ['category' => 'training', 'provider' => 'ByteDance', 'agent' => 'Bytespider']],
    'Common Crawl training' => ['CCBot/2.0', ['category' => 'training', 'provider' => 'Common Crawl', 'agent' => 'CCBot']],
]);

it('classifies every supported crawler', function (string $userAgent, array $expected): void {
    expect(app(CrawlerClassifier::class)->classify($userAgent))->toBe($expected);
})->with('crawler catalog');

it('does not classify missing, blank, or unknown user agents', function (?string $userAgent): void {
    expect(app(CrawlerClassifier::class)->classify($userAgent))->toBeNull();
})->with([null, '', '  ', 'Mozilla/5.0']);

it('only tracks crawler GET and HEAD requests', function (string $method, bool $expected): void {
    expect(app(CrawlerClassifier::class)->shouldTrack(classifierRequest(method: $method)))->toBe($expected);
})->with([
    'GET' => ['GET', true],
    'HEAD' => ['HEAD', true],
    'POST' => ['POST', false],
    'PUT' => ['PUT', false],
    'PATCH' => ['PATCH', false],
    'DELETE' => ['DELETE', false],
    'OPTIONS' => ['OPTIONS', false],
]);

it('does not track crawler asset fetches', function (string $destination): void {
    expect(app(CrawlerClassifier::class)->shouldTrack(classifierRequest(headers: ['Sec-Fetch-Dest' => $destination])))->toBeFalse();
})->with([
    'audio', 'embed', 'font', 'image', 'manifest', 'object', 'script', 'serviceworker', 'sharedworker',
    'style', 'track', 'video', 'worker',
]);

it('tracks document requests with a non-static fetch destination', function (): void {
    expect(app(CrawlerClassifier::class)->shouldTrack(classifierRequest(headers: ['Sec-Fetch-Dest' => 'document'])))->toBeTrue();
});

it('does not track configured path prefixes while preserving exact boundaries', function (): void {
    $classifier = app(CrawlerClassifier::class);

    expect($classifier->shouldTrack(classifierRequest(uri: '/api/health')))->toBeFalse()
        ->and($classifier->shouldTrack(classifierRequest(uri: '/assets/app.js')))->toBeFalse()
        ->and($classifier->shouldTrack(classifierRequest(uri: '/api-docs')))->toBeTrue();
});

it('does not track ignored file extensions except crawler-facing files', function (string $uri, bool $expected): void {
    expect(app(CrawlerClassifier::class)->shouldTrack(classifierRequest(uri: $uri)))->toBe($expected);
})->with([
    'stylesheet' => ['/assets/app.css', false],
    'script' => ['/build/app.js', false],
    'image' => ['/images/logo.webp', false],
    'robots' => ['/robots.txt', true],
    'llms' => ['/llms.txt', true],
    'full llms' => ['/llms-full.txt', true],
    'sitemap' => ['/sitemap.xml', true],
    'named sitemap' => ['/sitemap-index.xml', true],
    'nested sitemap' => ['/nested/sitemap_products.xml', true],
    'non-sitemap XML' => ['/site-sitemap.xml', false],
]);

it('honours custom ignored paths and extensions', function (): void {
    config()->set('shipwake-ai-crawl.ignored_path_prefixes', ['/private']);
    config()->set('shipwake-ai-crawl.ignored_extensions', ['feed']);

    $classifier = app(CrawlerClassifier::class);

    expect($classifier->shouldTrack(classifierRequest(uri: '/private/report')))->toBeFalse()
        ->and($classifier->shouldTrack(classifierRequest(uri: '/public/feed.feed')))->toBeFalse()
        ->and($classifier->shouldTrack(classifierRequest(uri: '/api/health')))->toBeTrue();
});
