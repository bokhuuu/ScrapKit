# ScrapKit 🕷️

Universal Laravel scraping template - build once, reuse for any site.

---

## What It Does

ScrapKit is a production-grade data extraction engine.
Add two files per new site. Everything else - queues, pipeline, deduplication, exports, notifications - works automatically.

**Currently scraping:** [list.am](https://list.am) - Armenia's largest classifieds platform (110,000+ active listings)
**Active client delivery:** Yerevan real estate market research for client

---

## Part of a Larger Vision

ScrapKit is the data collection layer of a three-template system:

| Project      | Role                                                 |
| ------------ | ---------------------------------------------------- |
| **ScrapKit** | Extracts data from any website automatically         |
| **LaraAI**   | Analyzes and understands scraped data with AI        |
| **LaraKit**  | Admin dashboard - visualize results, control scrapes |

**Real-world example (Client use case):**

```
ScrapKit  → scrapes list.am every night → 10,000 new listings in database
LaraAI    → "Kentron prices rose 8% this month"
LaraKit   → Client team opens dashboard → live price map, trend charts, export reports
```

## Sample Output

> Coming - will be added after first full scrape run

Excel export preview and sample file will be placed in `/examples` once Phase 9 (Export Layer) is complete.

---

## Performance

> Benchmarks coming after Phase 6 (Queue & Jobs) is complete

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

```
Entry (artisan / API)
  └── ScraperManager
        └── Queue Jobs (parallel)
              └── BaseScraper (Dusk)
                    └── Pipeline Stages
                          └── ListingRepository → MySQL
                                └── Events → Notifiers / ExportManager
```

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
- Telegram notification fired on `ScrapeFailed` event (Phase 8)
- Sentry integration planned for Phase 13

---

## Proxy Support

> Planned - Phase 10

Rotating proxy support via `ProxyResolver` service - configurable per profile. Some sites need proxies, others don't.

---

## Tech Stack

| Layer              | Technology                  |
| ------------------ | --------------------------- |
| Framework          | Laravel 12, PHP 8.4         |
| Browser automation | Laravel Dusk + ChromeDriver |
| Queue              | Database → Redis (Phase 10) |
| Cache              | Database → Redis (Phase 10) |
| Queue monitoring   | Laravel Horizon (Phase 10)  |
| Export             | Maatwebsite Excel           |
| Notifications      | Telegram Bot SDK            |
| Testing            | Pest                        |
| Code style         | Laravel Pint (strict_types) |
| Database           | MySQL (utf8mb4)             |
| Deployment         | Docker + VPS Ubuntu 24.04   |
| CI/CD              | GitHub Actions              |

---

## Build Progress

### Foundation

- Laravel 12 + PHP 8.4 installed and configured
- MySQL database, queue driver set to database
- Laravel Dusk installed
- Laravel Pint configured with strict_types enforcement
- Folder structure established, Git repository initialized

### Core Contracts

- ScraperProfileInterface, AuthStrategyInterface
- AbstractScraperProfile, ListAmProfile
- ListingDTO, ScraperState enum

### Database & Repository

- Listings migration (with `new_construction`, `renovation`, `images` columns)
- ScraperRuns migration
- `Listing` model, `ScraperRun` model
- `ListingRepository`, `ScraperRunRepository`

### Processing Pipeline

- `PipelineStageInterface` contract
- `NormalizeStringFieldsStage`
- `ValidateRequiredFieldsStage`
- `CleanPriceStage`, `CleanPhoneStage`, `CleanAreaStage`, `CleanBuildingTypeStage`
- `DeduplicateStage` - URL-based, halts early on duplicate
- `EnrichDistrictStage`
- `ScraperPipeline` orchestrator
- `InvalidListingException`, `DuplicateListingException`

### Browser Automation

- `BaseScraper` abstract class - profile-driven delay + jitter
- `StealthConfig` - ChromeDriver fingerprint hardening
- `ListAmScraper` - verified selectors, label-based spec extraction, image URL collection
- `config/scraper.php` - all settings configurable via `.env`
- ⬜ `BrowserPool` - deferred to Phase 10

### Authentication

- `CookieAuthStrategy` - cookie-first session restore, form login fallback, saves cookies to disk
- `FormLoginStrategy` - fills email/password form, submits, confirms success via DOM selector
- Cookie storage convention - `storage/app/scraper/cookies/{profileName}.json`
- `BaseScraper` - `setAuthStrategy()` + `ensureAuthenticated()` added
- `ListAmScraper` - `ensureAuthenticated()` called before phone reveal click

### Queue & Jobs

- `CrawlIndexPageJob` - one per index page, dispatches detail jobs in parallel
- `CrawlDetailPageJob` - crawl → DTO → pipeline → DB
- `ScrapeCompletedJob` - fires when batch completes, marks run finished
- Queue tables confirmed (jobs, job_batches, failed_jobs)
- `ThrottledRetryMiddleware` - backoff on retries
- `RateLimitedMiddleware` - written, activates in Phase 10 with Redis
- Failed job handling via `failed_jobs` table + artisan queue:\* commands

### ⬜ Orchestration

- `ScraperManager` - loads profile, dispatches jobs
- `scraper:run` artisan command
- `scraper:status` artisan command
- `scraper:cancel` artisan command

### ⬜ Events & Notifications

- `ListingSaved`, `ScrapeFailed`, `ScrapeCompleted` events
- `TelegramNotifier`, `SlackNotifier`
- `NotifierInterface`

### ⬜ Export Layer

- `ExporterInterface`
- `ExcelExporter`, `CsvExporter`, `JsonExporter`
- `ExportManager`
- Client-specific Excel report (4 sheets)
- Sample output added to `/examples`

### ⬜ Caching & Performance

- Redis setup
- Cache scraped pages + price statistics
- Rate limiting per domain
- Laravel Horizon queue monitoring
- `BrowserPool` - parallel browser instances
- `ProxyResolver` - rotating proxy support

### ⬜ API Layer

- Sanctum authentication
- `POST /api/scrape/start`
- `GET  /api/scrape/status`
- `POST /api/scrape/cancel`
- `GET  /api/listings`
- `GET  /api/listings/stats`
- `GET  /api/health`
- API rate limiting

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

| Task                                             | Status |
| ------------------------------------------------ | ------ |
| Scrape apartments - list.am/category/60          | -      |
| Scrape commercial for sale - list.am/category/56 | -      |
| Scrape commercial for rent - list.am/category/58 | -      |
| Data cleaning and normalization                  | -      |
| District price per sqm analysis                  | -      |
| Commercial availability report                   | -      |
| Formatted Excel deliverable (4 sheets)           | -      |
| PDF/PowerPoint summary report                    | -      |

---

## Known Limitations

- Phone numbers on list.am require authenticated login - Dusk handles this but adds scrape time per listing
- JavaScript-rendered pages require ChromeDriver - cannot use lightweight HTTP-only scraping for all sites
- BrowserPool deferred to Phase 10 - currently single browser instance per job
- No proxy rotation yet - suitable for polite scraping volumes, not aggressive bulk extraction

---
