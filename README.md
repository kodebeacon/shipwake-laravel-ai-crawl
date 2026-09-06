# shipwake/laravel-ai-crawl

Laravel middleware for observing AI and search crawler requests on the server. It uses the same four product categories as Shipwake (`ai_answers`, `indexing`, `training`, `other`) and dispatches a small request to Shipwake after the response has been sent.

## Install

```bash
composer require shipwake/laravel-ai-crawl
php artisan vendor:publish --tag=shipwake-ai-crawl-config
```

Set these values in `.env`:

```dotenv
SHIPWAKE_SITE_KEY=your_site_public_key
SHIPWAKE_SERVER_TOKEN=your_private_server_token
SHIPWAKE_AI_CRAWL_ENDPOINT=https://your-shipwake-host/api/ai-crawls
SHIPWAKE_AI_CRAWL_QUEUE=analytics
```

Register `Shipwake\LaravelAiCrawl\Http\Middleware\TrackAiCrawler::class` in the `web` middleware group from `bootstrap/app.php`.

The middleware calls the application normally, then queues the outbound request when Laravel terminates the response. The configured queue and connection are therefore honoured, and a slow analytics endpoint cannot delay the page or the Inertia response.

## Laravel + Inertia + React

No React or SPA changes are needed. Inertia navigation still works through Laravel and the middleware observes the server request around the response. Keep `window.signal.track(...)` for human pageviews and client-side route changes; this package is the complementary server-side crawler signal.

The site key identifies the site and is the same public key used by the browser tracker. Generate or rotate the private server token from the Shipwake dashboard, then keep it only in server-side environment variables. The package sends the token as the Bearer credential and the raw user agent only to the Shipwake ingestion endpoint for classification; server-side crawler requests do not submit visitor IPs or create visitor identities.
