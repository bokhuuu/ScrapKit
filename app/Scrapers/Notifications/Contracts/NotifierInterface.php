<?php

declare(strict_types=1);

namespace App\Scrapers\Notifications\Contracts;

/**
 * Contract for all notifiers in the scraper template.
 *
 * Each notifier implements a specific delivery channel (Telegram, Mail, etc.).
 * The profile decides which notifiers fire - the events layer calls them uniformly.
 */
interface NotifierInterface
{
    /**
     * Send a notification through this channel.
     *
     * The payload carries event data - source, run ID, listing count, error message.
     * Each notifier picks what it needs from the payload and ignores the rest.
     */
    public function notify(array $payload): void;
}
