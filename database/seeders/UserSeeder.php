<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the API consumer user and generates Sanctum tokens.
 *
 * Tokens are printed to the console once and never stored in plain text.
 * Copy each token immediately and paste into the consuming project's .env file.
 *
 * LaraKit  → SCRAPKIT_API_TOKEN=...
 * LaraAI   → SCRAPKIT_API_TOKEN=...
 */
class UserSeeder extends Seeder
{
    /**
     * Create the API user and generate one token per consuming project.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => config('app.api_user_email', 'api@scrapkit.local')],
            [
                'name' => 'ScrapKit API',
                'password' => bcrypt(config('app.api_user_password', 'secret')),
            ]
        );

        $larakit = $user->createToken('larakit')->plainTextToken;
        $laraai = $user->createToken('laraai')->plainTextToken;

        $this->command->info("LaraKit token: {$larakit}");
        $this->command->info("LaraAI token:  {$laraai}");
        $this->command->warn('Copy these tokens now - they will never be shown again.');
    }
}
