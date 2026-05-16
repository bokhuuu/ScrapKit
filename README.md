# ScrapKit 🕷️

Universal Laravel scraping template - build once, reuse for any site.

---

## What It Does

ScrapKit is a production-grade data extraction engine built on Laravel 12.
Add two files per new site. Everything else - queues, pipeline, deduplication, exports, notifications - works automatically.

**Currently scraping:** [list.am](https://list.am) - Armenia's largest classifieds platform
**Active client delivery:** Yerevan real estate market research for client

---

## Quick Start

```bash
git clone https://github.com/bokhuuu/ScrapKit
cd ScrapKit
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

---

## Adding a New Site

Two files. That's it.

**1. Create a profile** - describes the site:

```php
class SsGeProfile extends AbstractScraperProfile
{
    public function getName(): string { return 'ssge'; }
    public function getBaseUrl(): string { return 'https://ss.ge'; }
    public function getIndexUrlPattern(): string { return 'https://ss.ge/en/.../{page}'; }
    public function getMaxPages(): int { return 30; }
}
```

**2. Create a scraper** - extracts the data:

```php
class SsGeScraper extends BaseScraper
{
    public function crawlIndexPage(int $page): array { ... }
    public function crawlDetailPage(string $url): array { ... }
}
```

Queue, pipeline, dedup, export, notifications - zero changes needed.

---

## Architecture

```
Entry (artisan / API)
  └── ScraperManager
        └── Queue Jobs (parallel)
              └── BaseScraper (Dusk)
                    └── Pipeline Stages
                          └── ListingRepository → MySQL
                                └── Events → Notifiers / ExportManager
```

---

## Tech Stack

| Layer              | Technology                  |
| ------------------ | --------------------------- |
| Framework          | Laravel 12, PHP 8.4         |
| Browser automation | Laravel Dusk + ChromeDriver |
| Queue              | Database → Redis (Phase 10) |
| Export             | Maatwebsite Excel           |
| Notifications      | Telegram Bot SDK            |
| Testing            | Pest                        |
| Code style         | Laravel Pint (strict_types) |
| Database           | MySQL (utf8mb4)             |
| Deployment         | Docker + VPS Ubuntu 24.04   |
| CI/CD              | GitHub Actions              |

---

## Build Progress

### ✅ Foundation

### ✅ Core Contracts

- ScraperProfileInterface, AuthStrategyInterface
- AbstractScraperProfile, ListAmProfile
- ListingDTO, ScraperState enum

### ✅ Database & Repository

- Listings migration
- ScraperRuns migration
- Listing model
- ScraperRun model
- ListingRepository
- ScraperRunRepository

### Processing Pipeline

### Browser Automation

### Authentication

### Queue & Jobs

### Orchestration

### Events & Notifications

### Export Layer

### Redis & Horizon

### API Layer

### Testing

### Docker & Deployment

### Documentation

---
