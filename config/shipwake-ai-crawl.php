<?php

return [
    'site_key' => env('SHIPWAKE_SITE_KEY'),
    'server_token' => env('SHIPWAKE_SERVER_TOKEN'),
    'timeout' => (int) env('SHIPWAKE_AI_CRAWL_TIMEOUT', 1),
    'connect_timeout' => (int) env('SHIPWAKE_AI_CRAWL_CONNECT_TIMEOUT', 1),
    'queue' => env('SHIPWAKE_AI_CRAWL_QUEUE', 'analytics'),
    'connection' => env('SHIPWAKE_AI_CRAWL_CONNECTION'),
    'public_origin' => env('SHIPWAKE_PUBLIC_ORIGIN'),
    'ignored_path_prefixes' => ['/api', '/build', '/assets', '/storage', '/vendor'],
    'ignored_extensions' => ['css', 'js', 'map', 'json', 'xml', 'txt', 'ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'woff', 'woff2', 'ttf', 'pdf', 'zip'],
    'debug' => (bool) env('SHIPWAKE_AI_CRAWL_DEBUG', false),
];
