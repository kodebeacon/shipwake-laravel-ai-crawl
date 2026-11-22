<?php

namespace Shipwake\LaravelAiCrawl\Exceptions;

use RuntimeException;

class RetryableCrawlerRequest extends RuntimeException
{
    public function __construct(public readonly int $status)
    {
        parent::__construct("Shipwake crawler endpoint returned retryable status {$status}.");
    }
}
