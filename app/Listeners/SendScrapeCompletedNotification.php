<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ScrapeCompleted;

/**
 * Listens for the ScrapeCompleted event and dispatches notifications.
 *
 * Resolves the profile from config to get the configured notifiers.
 * Each notifier is instantiated and called with the event payload.
 * Adding a new notifier to the profile is the only change needed.
 */
class SendScrapeCompletedNotification
{
    public function handle(ScrapeCompleted $event): void
    {
        $profileClass = config('scraper.profiles.' . $event->source);
        $profile      = app($profileClass);

        $payload = [
            'source'        => $event->source,
            'status'        => 'completed',
            'listing_count' => $event->listingCount,
        ];

        foreach ($profile->getNotifiers() as $notifierClass) {
            $notifier = app($notifierClass);
            $notifier->notify($payload);
        }
    }
}
