<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ScraperState;
use App\Models\ScraperRun;
use Illuminate\Support\Collection;

class ScraperRunRepository
{
    public function save(array $data): ScraperRun
    {
        return ScraperRun::create($data);
    }

    public function updateState(int $id, ScraperState $state): void
    {
        ScraperRun::where('id', $id)->update(['state' => $state]);
    }

    public function findBySource(string $source): Collection
    {
        return ScraperRun::forSource($source)->get();
    }

    public function findLatest(string $source): ?ScraperRun
    {
        return ScraperRun::where('source', $source)->latest()->first();
    }
}
