<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Orchestrates all database seeders.
 *
 * Run via: php artisan db:seed
 * Safe to re-run - all seeders use firstOrCreate or equivalent.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {

        $this->call(UserSeeder::class);
    }
}
