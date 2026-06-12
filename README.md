# ScrapKit 🕷️

![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![Laravel](https://img.shields.io/badge/Laravel-12-red)
![License](https://img.shields.io/badge/license-MIT-green)
![CI](https://github.com/bokhuuu/ScrapKit/actions/workflows/ci.yml/badge.svg)

Universal Laravel scraping template - build once, reuse for any site.

---

## What It Does

ScrapKit is a production-grade data extraction engine.
Add two files per new site. Everything else - queues, pipeline, deduplication, exports, notifications - works automatically.

**Currently scraping:** [list.am](https://list.am) - Armenia's largest classifieds platform (110,000+ active listings)
**Active client delivery:** Yerevan real estate market research for a real estate market entry client

---

## Part of a Larger Vision

ScrapKit is the data collection layer of a three-template system:

| Project      | Role                                                 |
| ------------ | ---------------------------------------------------- |
| **ScrapKit** | Extracts data from any website automatically         |
| **LaraAI**   | Analyzes and understands scraped data with AI        |
| **LaraKit**  | Admin dashboard - visualize results, control scrapes |

**Real-world example (Real estate client use case):**
ScrapKit → scrapes list.am every night → 10,000 new listings in database
LaraAI → "Kentron prices rose 8% this month"
LaraKit → Client team opens dashboard → live price map, trend charts, export reports

---

## Quick Start

```bash
git clone https://github.com/bokhuuu/ScrapKit
cd ScrapKit
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan scraper:run listam --pages=50
```

---

## Sample Output

A sample Yerevan real estate market research report is available in `/examples`:
examples/real_estate_market_report_sample.xlsx

Generated from 501 live Yerevan apartment listings. Contains 8 sheets:

| Sheet                 | Content                                         |
| --------------------- | ----------------------------------------------- |
| All Listings          | Full raw dataset — all scraped fields           |
| Market Overview       | Headline statistics — avg price, median, supply |
| District Analysis     | Pricing and supply breakdown per district       |
| Room Type Analysis    | Product mix and pricing by bedroom count        |
| Building Stock        | Construction type and condition distribution    |
| Price Distribution    | Market segmentation by price bracket            |
| Agency Intelligence   | Owner vs agency split, top participants         |
| Floor & Size Analysis | Price premium by floor, size, ceiling height    |

---

## Performance

> Redis active. BrowserPool wired and active. Benchmarks coming after first full production run.

| Mode                            | Expected throughput  |
| ------------------------------- | -------------------- |
| Single browser, 3s delay        | ~800 listings/hour   |
| BrowserPool parallel (Phase 10) | ~3,000 listings/hour |

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
    public function getIndexSelectors(): array { return [...]; }
    public function getDetailSelectors(): array { return [...]; }
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

Entry (artisan / API)
└── ScraperManager
└── Queue Jobs (parallel)
└── BaseScraper (Dusk)
└── Pipeline Stages
└── ListingRepository → MySQL
└── Events
├── ScrapeCompleted → ExportManager + TelegramNotifier + MailNotifier
├── ScrapeFailed → TelegramNotifier + MailNotifier
└── ListingSaved → (future: webhooks, downstream sync)
Pipeline (default):
NormalizeStringFieldsStage → ValidateRequiredFieldsStage → CleanPriceStage
→ CleanPhoneStage → CleanAreaStage → CalculatePricePerSqmStage
→ CleanBuildingTypeStage → DeduplicateStage

FilterCurrencyStage (profile-injected)
EnrichDistrictStage (profile-injected)

Export (profile-driven):
ExcelExporter → generic .xlsx
CsvExporter → generic .csv
JsonExporter → generic .json
RealEstateMarketReport → 8-sheet client deliverable

---

## How It Avoids Detection

ScrapKit is built with responsible, polite scraping in mind:

- Per-domain rate limiting - configurable delay between requests via `config/scraper.php`
- Random jitter - delay randomized ±variance to avoid predictable patterns
- Stealth browser config - ChromeDriver fingerprint hardening via `StealthConfig`
- Retry with backoff - failed jobs retry automatically with exponential delay
- Respectful crawling - never hammers a server; mimics natural browsing pace

All values are environment-driven - no hardcoded delays anywhere.

---

## Deduplication Strategy

Every listing is identified by its **source URL** (unique per listing on every known target site).
Before saving, `DeduplicateStage` checks if the URL already exists in the database.
Duplicate → pipeline halts early, job completes cleanly. No double inserts, no wasted writes.

---

## Scheduling

ScrapKit integrates with Laravel Scheduler for fully automated runs:

```php
// Runs every night at 02:00
$schedule->command('scraper:run listam --pages=100')->dailyAt('02:00');
```

Configure in `app/Console/Kernel.php`. No cron setup needed beyond Laravel's standard scheduler entry.

---

## Error Handling & Logging

- All scraper errors logged to `storage/logs/scraper.log` with full context
- Failed queue jobs stored in `failed_jobs` table - inspectable and replayable
- `ScraperRun` model tracks full lifecycle: `pending → running → completed / failed`
- Telegram + email notification fired on `ScrapeFailed` and `ScrapeCompleted` events
- Sentry integration planned for Phase 13

---

## Proxy Support

Rotating proxy support via `ProxyResolver` service - configurable per profile.
Activate by setting `SCRAPER_PROXY_ENABLED=true` with proxy list in `.env`.

---

## Tech Stack

| Layer              | Technology                   |
| ------------------ | ---------------------------- |
| Framework          | Laravel 12, PHP 8.4          |
| Browser automation | Laravel Dusk + ChromeDriver  |
| Queue              | Redis                        |
| Cache              | Redis                        |
| Queue monitoring   | Laravel Horizon              |
| Export             | Maatwebsite Excel            |
| Notifications      | Telegram Bot SDK + SMTP Mail |
| Testing            | Pest                         |
| Code style         | Laravel Pint (strict_types)  |
| Database           | MySQL (utf8mb4)              |
| Deployment         | Docker + VPS Ubuntu 24.04    |
| CI/CD              | GitHub Actions               |

---

## Build Progress

### ✅ Foundation

- Laravel 12 + PHP 8.4 installed and configured
- MySQL database, queue driver set to redis
- Laravel Dusk installed
- Laravel Pint configured with strict_types enforcement
- Folder structure established, Git repository initialized

### ✅ Core Contracts

- ScraperProfileInterface, AuthStrategyInterface
- AbstractScraperProfile, ListAmProfile
- ListingDTO, ScraperState enum

### ✅ Database & Repository

- Listings migration (columns match ListingDTO exactly)
- ScraperRuns migration
- `Listing` model, `ScraperRun` model
- `ListingRepository`, `ScraperRunRepository`

### ✅ Processing Pipeline

- `PipelineStageInterface` contract
- `NormalizeStringFieldsStage`
- `ValidateRequiredFieldsStage` - required fields injected from profile
- `CleanPriceStage`, `CleanPhoneStage`, `CleanAreaStage`, `CleanBuildingTypeStage`
- `DeduplicateStage` - URL-based, halts early on duplicate
- `EnrichDistrictStage` - real estate specific, injected via ListAmProfile
- `ScraperPipeline` orchestrator
- `InvalidListingException`, `DuplicateListingException`
- `CalculatePricePerSqmStage` - derives price/sqm after price and area are clean
- `FilterCurrencyStage` - profile-injected, drops non-accepted currencies

### ✅ Browser Automation

- `BaseScraper` abstract class - profile-driven delay + jitter
- `StealthConfig` - ChromeDriver fingerprint hardening
- `ListAmScraper` - verified selectors, label-based spec extraction, image URL collection
- `config/scraper.php` - all settings configurable via `.env`
- `BrowserPool` - in-process pool, reuses open browsers across jobs, no Redis session IDs

### ✅ Authentication

- `CookieAuthStrategy` - cookie-first session restore, form login fallback
- `FormLoginStrategy` - fills email/password form, submits, confirms via DOM
- Cookie storage convention - `storage/app/scraper/cookies/{profileName}.json`

### ✅ Queue & Jobs

- `CrawlIndexPageJob` - one per index page, dispatches detail jobs in parallel
- `CrawlDetailPageJob` - crawl → DTO → pipeline → DB
- `ScrapeCompletedJob` - fires when batch completes, marks run finished
- `ThrottledRetryMiddleware` - backoff on retries
- `RateLimitedMiddleware` - concurrent job throttling per domain via Redis
- Failed job handling via `failed_jobs` table

### ✅ Orchestration

- `ScraperManager` - loads profile, creates ScraperRun, dispatches Bus::batch()
- `scraper:run` - start a scrape run from terminal
- `scraper:status` - check latest run state for a source
- `scraper:cancel` - cancel an active run by ID

### ✅ Events & Notifications

- `ListingSaved`, `ScrapeFailed`, `ScrapeCompleted` events
- `NotifierInterface` contract
- `TelegramNotifier` - instant alerts via Telegram bot
- `MailNotifier` - professional email notifications via SMTP
- `SendScrapeCompletedNotification`, `SendScrapeFailedNotification` listeners
- Profile-driven - each profile declares which notifiers fire

### ✅ Export Layer

- `ExporterInterface` - contract all exporters implement
- `ExcelExporter` - generic single-sheet Excel export
- `CsvExporter` - generic CSV export, machine-readable
- `JsonExporter` - generic JSON export, API/pipeline ready
- `ExportManager` - orchestrates all configured exporters post-scrape
- `TriggerScrapeExport` listener - bridges ScrapeCompleted event to ExportManager
- `RealEstateMarketReport` - 8-sheet client deliverable (District Analysis, Room Types, Building Stock, Price Distribution, Agency Intelligence, Floor & Size Analysis)
- Config-driven - formats declared in profile `getExports()`, classes resolved via `config/scraper.php`
- Sample report added to `/examples`

### ✅ Caching & Performance

- ✅ Redis setup
- ✅ Rate limiting per domain (RateLimitedMiddleware active)
- ✅ Cache scraped pages + price statistics
- ✅ Laravel Horizon queue monitoring
- ✅ `BrowserPool` - in-process pool, wired into CrawlDetailPageJob
- ✅ `ProxyResolver` - rotating proxy support

### ⬜ API Layer

- Sanctum authentication
- `POST /api/scrape/start`
- `GET  /api/scrape/status`
- `POST /api/scrape/cancel`
- `GET  /api/listings`
- `GET  /api/listings/stats`
- `GET  /api/health`
- API rate limiting
- Security headers middleware
- FormRequest validation for all POST endpoints
- Per-token API rate limiting

### ⬜ Testing

- Unit tests for all pipeline stages (Pest)
- Mock browser responses
- Repository tests with SQLite in-memory
- Feature test: full scrape run end-to-end
- Test coverage report + badge

### ⬜ Docker & Deployment

- `Dockerfile`, `docker-compose.yml`
- GitHub Actions CI/CD pipeline
- `.env.example` fully documented
- Sentry error tracking integration
- VPS deployment guide

### ⬜ Documentation

- `CONTRIBUTING.md`
- Architecture diagram (image)
- Postman collection for all API endpoints

---

## Client Deliverable

| Task                                             | Status  |
| ------------------------------------------------ | ------- |
| Scrape apartments - list.am/category/60          | ✅ Done |
| 8-sheet Excel report template built              | ✅ Done |
| Scrape commercial for sale - list.am/category/56 | ⬜      |
| Scrape commercial for rent - list.am/category/58 | ⬜      |
| Data cleaning and normalization                  | ✅ Done |
| District price per sqm analysis                  | ✅ Done |
| Commercial availability report                   | ⬜      |
| Final formatted Excel deliverable                | ⬜      |
| PDF/PowerPoint summary report                    | ⬜      |

---

## Known Limitations

- Phone numbers on list.am require authenticated login - Dusk handles this but adds scrape time per listing
- JavaScript-rendered pages require ChromeDriver - cannot use lightweight HTTP-only scraping for all sites
- BrowserPool uses in-process browser reuse - each worker maintains its own pool, no cross-process sharing
- ProxyResolver implemented - activate by setting SCRAPER_PROXY_ENABLED=true with proxy list in .env
- Image galleries on list.am load lazily - only the first image captured per listing
- list.am does not expose individual agency names on listing pages - agency vs owner split is tracked, specific agency identity is not

---
