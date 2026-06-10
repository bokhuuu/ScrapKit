<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Credentials for third party services used by ScrapKit.
    | All values are read from .env - never hardcoded here.
    |
    */

    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'mail' => [
        'scraper_to' => env('SCRAPER_MAIL_TO'),
    ],

];
