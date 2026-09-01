<?php

namespace Shipwake\LaravelAiCrawl\Support;

use Illuminate\Http\Request;

class CrawlerClassifier
{
    private const STATIC_FETCH_DESTINATIONS = [
        'audio', 'embed', 'font', 'image', 'manifest', 'object', 'script', 'serviceworker', 'sharedworker',
        'style', 'track', 'video', 'worker',
    ];

    /**
     * @var array<int, array{pattern: string, category: string, provider: string, agent: string}>
     */
    private const CATALOG = [
        ['pattern' => '/chatgpt-user/i', 'category' => 'ai_answers', 'provider' => 'OpenAI', 'agent' => 'ChatGPT-User'],
        ['pattern' => '/oai-searchbot/i', 'category' => 'ai_answers', 'provider' => 'OpenAI', 'agent' => 'OAI-SearchBot'],
        ['pattern' => '/openai-operator/i', 'category' => 'ai_answers', 'provider' => 'OpenAI', 'agent' => 'OpenAI-Operator'],
        ['pattern' => '/gptbot/i', 'category' => 'training', 'provider' => 'OpenAI', 'agent' => 'GPTBot'],
        ['pattern' => '/claude-user/i', 'category' => 'ai_answers', 'provider' => 'Anthropic', 'agent' => 'Claude-User'],
        ['pattern' => '/claude-searchbot/i', 'category' => 'ai_answers', 'provider' => 'Anthropic', 'agent' => 'Claude-SearchBot'],
        ['pattern' => '/claudebot/i', 'category' => 'training', 'provider' => 'Anthropic', 'agent' => 'ClaudeBot'],
        ['pattern' => '/perplexity-user/i', 'category' => 'ai_answers', 'provider' => 'Perplexity', 'agent' => 'Perplexity-User'],
        ['pattern' => '/perplexitybot/i', 'category' => 'indexing', 'provider' => 'Perplexity', 'agent' => 'PerplexityBot'],
        ['pattern' => '/google-notebooklm/i', 'category' => 'ai_answers', 'provider' => 'Google', 'agent' => 'Google-NotebookLM'],
        ['pattern' => '/google-extended/i', 'category' => 'training', 'provider' => 'Google', 'agent' => 'Google-Extended'],
        ['pattern' => '/googlebot/i', 'category' => 'indexing', 'provider' => 'Google', 'agent' => 'Googlebot'],
        ['pattern' => '/bingbot/i', 'category' => 'indexing', 'provider' => 'Microsoft', 'agent' => 'Bingbot'],
        ['pattern' => '/applebot-extended/i', 'category' => 'training', 'provider' => 'Apple', 'agent' => 'Applebot-Extended'],
        ['pattern' => '/applebot/i', 'category' => 'indexing', 'provider' => 'Apple', 'agent' => 'Applebot'],
        ['pattern' => '/bytespider/i', 'category' => 'training', 'provider' => 'ByteDance', 'agent' => 'Bytespider'],
        ['pattern' => '/ccbot/i', 'category' => 'training', 'provider' => 'Common Crawl', 'agent' => 'CCBot'],
    ];

    /**
     * @return array{category: string, provider: string, agent: string}|null
     */
    public function classify(?string $userAgent): ?array
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return null;
        }

        foreach (self::CATALOG as $profile) {
            if (preg_match($profile['pattern'], $userAgent) === 1) {
                return [
                    'category' => $profile['category'],
                    'provider' => $profile['provider'],
                    'agent' => $profile['agent'],
                ];
            }
        }

        return null;
    }

    public function shouldTrack(Request $request): bool
    {
        if (! in_array(strtoupper($request->method()), ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($this->classify($request->userAgent()) === null) {
            return false;
        }

        $destination = strtolower((string) $request->header('Sec-Fetch-Dest'));

        if ($destination !== '' && in_array($destination, self::STATIC_FETCH_DESTINATIONS, true)) {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');
        $ignoredPrefixes = config('shipwake-ai-crawl.ignored_path_prefixes', []);

        if (collect($ignoredPrefixes)->contains(fn (string $prefix): bool => $path === $prefix || str_starts_with($path, $prefix.'/'))) {
            return false;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $ignoredExtensions = config('shipwake-ai-crawl.ignored_extensions', []);

        if ($extension !== '' && in_array(strtolower($extension), array_map('strtolower', $ignoredExtensions), true) && ! $this->isCrawlerFacingPath($path)) {
            return false;
        }

        return true;
    }

    private function isCrawlerFacingPath(string $path): bool
    {
        return in_array($path, ['/robots.txt', '/llms.txt', '/llms-full.txt'], true)
            || preg_match('/(?:^|\/)sitemap(?:[-_][^/]+)?\.xml$/i', $path) === 1;
    }
}
