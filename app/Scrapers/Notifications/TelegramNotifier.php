<?php

declare(strict_types=1);

namespace App\Scrapers\Notifications;

use App\Scrapers\Notifications\Contracts\NotifierInterface;
use Telegram\Bot\Api;

/**
 * Sends scraper notifications to a Telegram chat.
 *
 * The message format is the same regardless of which event triggered it.
 */
class TelegramNotifier implements NotifierInterface
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('services.telegram.token'));
    }

    public function notify(array $payload): void
    {
        $this->telegram->sendMessage([
            'chat_id' => config('services.telegram.chat_id'),
            'text'    => $this->buildMessage($payload),
        ]);
    }

    private function buildMessage(array $payload): string
    {
        $source  = $payload['source'] ?? 'unknown';
        $status  = $payload['status'] ?? 'unknown';
        $count   = $payload['listing_count'] ?? null;
        $error   = $payload['error'] ?? null;

        $message = "ScrapKit [{$source}] — {$status}";

        if ($count !== null) {
            $message .= "\nListings saved: {$count}";
        }

        if ($error !== null) {
            $message .= "\nError: {$error}";
        }

        return $message;
    }
}
