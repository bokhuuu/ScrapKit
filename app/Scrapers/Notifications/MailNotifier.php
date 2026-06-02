<?php

declare(strict_types=1);

namespace App\Scrapers\Notifications;

use App\Scrapers\Notifications\Contracts\NotifierInterface;
use Illuminate\Support\Facades\Mail;

/**
 * Sends scraper notifications via email.
 *
 * The message format is the same regardless of which event triggered it.
 */
class MailNotifier implements NotifierInterface
{
    public function notify(array $payload): void
    {
        $to = config('services.mail.scraper_to');

        Mail::raw(
            $this->buildMessage($payload),
            function ($message) use ($to): void {
                $message
                    ->to($to)
                    ->subject('ScrapKit Alert — ' . ($payload['source'] ?? 'unknown'));
            }
        );
    }

    private function buildMessage(array $payload): string
    {
        $source = $payload['source'] ?? 'unknown';
        $status = $payload['status'] ?? 'unknown';
        $count  = $payload['listing_count'] ?? null;
        $error  = $payload['error'] ?? null;
        $date   = now()->format('Y-m-d H:i');

        $message  = "ScrapKit Notification\n";
        $message .= "=====================\n\n";
        $message .= "Source:  {$source}\n";
        $message .= "Status:  {$status}\n";
        $message .= "Date:    {$date}\n";

        if ($count !== null) {
            $message .= "Listings saved: {$count}\n";
        }

        if ($error !== null) {
            $message .= "\nError:\n{$error}\n";
        }

        return $message;
    }
}
