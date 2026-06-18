<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tracks where a scraper run is in its lifecycle - from just
 * created, through running, to one of three possible endings.
 */
enum ScraperState: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * Whether this state is a final one - once a run reaches
     * Completed, Failed, or Cancelled, it can't change again.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed,
            self::Failed,
            self::Cancelled => true,
            default => false,
        };
    }
}
