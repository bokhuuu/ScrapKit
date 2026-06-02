<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ScrapeFailed;

/**
 * Listens for the ScrapeFailed event and dispatches failure notifications.
 *
 * Resolves the profile from config to get the configured notifiers.
 * Each notifier is instantiated and called with the error payload.
 * Adding a new notifier to the profile is the only change needed.
 */
class SendScrapeFailedNotification
{
    public function handle(ScrapeFailed $event): void
    {
        $profileClass = config('scraper.profiles.' . $event->source);
        $profile      = new $profileClass();

        $payload = [
            'source'        => $event->source,
            'status'        => 'failed',
            'error' => $event->errorMessage,
        ];

        foreach ($profile->getNotifiers() as $notifierClass) {
            $notifier = new $notifierClass();
            $notifier->notify($payload);
        }
    }
}
