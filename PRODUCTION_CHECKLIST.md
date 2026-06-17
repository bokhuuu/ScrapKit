# Production Checklist

Run through this checklist before going live with ScrapKit.
ScrapKit ships as a Docker stack (`docker-compose.yml` - nginx, app, worker,
horizon, chrome, mysql, redis). Every item must be confirmed before the
application runs against real targets in production.

---

## Environment

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` - never expose errors publicly
- [ ] `APP_KEY` is set and unique - run `docker compose exec app php artisan key:generate` if not
- [ ] `APP_URL` matches your actual domain with `https://`
- [ ] `.env` is not committed to version control
- [ ] `.env.example` has all keys documented with no real values
- [ ] `DB_HOST=mysql`, `REDIS_HOST=redis`, `CHROMEDRIVER_URL=http://chrome:4444/wd/hub` - Docker service names, not `127.0.0.1`

---

## Database

- [ ] Database user has only the permissions it needs (avoid using root in production where possible)
- [ ] `DB_PASSWORD` is a strong random password
- [ ] Migrations have been run - `docker compose exec app php artisan migrate --force`
- [ ] The `mysql_data` Docker volume is backed up regularly (daily minimum)
- [ ] MySQL's container port is not exposed to the public internet - firewall blocks it or remove the host port mapping entirely if no external access is needed

---

## Security

- [ ] `APP_DEBUG=false` (listed again - this one is critical)
- [ ] HTTPS is enabled and HTTP redirects to HTTPS at the host-level reverse proxy
- [ ] SSL certificate is valid and auto-renewing (Certbot on the host, not inside a container)
- [ ] `BCRYPT_ROUNDS=12` or higher
- [ ] Host firewall configured - only ports 80, 443, and 22 open; Docker-internal ports (8080, 3307, 4444) are not exposed beyond what's needed
- [ ] SSH root login disabled
- [ ] Strong SSH password or key-based authentication only
- [ ] Sanctum tokens are scoped per consuming project, not shared broadly
- [ ] `LISTAM_EMAIL` / `LISTAM_PASSWORD` (and any future profile credentials) are real production values, not test/dev accounts

---

## Mail

- [ ] `MAIL_MAILER` is set to a real provider (smtp, mailgun, resend, ses)
- [ ] `MAIL_FROM_ADDRESS` uses your actual domain
- [ ] Test email sending works - `docker compose exec app php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@example.com'))`
- [ ] `SCRAPER_MAIL_TO` is set to the correct production recipient

---

## Telegram Notifications

- [ ] `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID` are real production values
- [ ] Test notification fires correctly - trigger a `ScrapeCompleted` or `ScrapeFailed` event manually and confirm the message arrives

---

## Queue & Horizon

- [ ] `QUEUE_CONNECTION=redis`
- [ ] `REDIS_HOST=redis` (the Docker service name, not localhost or an IP)
- [ ] The `horizon` container is running - `docker compose ps`
- [ ] `restart: unless-stopped` is set on the `app`, `worker`, and `horizon` services so they survive crashes and reboots
- [ ] `docker compose exec app php artisan horizon:status` returns `running`
- [ ] Failed jobs are monitored - check `/horizon` dashboard
- [ ] `HorizonServiceProvider` gate restricts dashboard access appropriately for production (not `app()->environment('local')`)
- [ ] Horizon is restarted after every deployment - `docker compose restart horizon`

---

## Browser Automation

- [ ] `chrome` (selenium/standalone-chrome) container is running - `docker compose ps`
- [ ] `shm_size: 2gb` is set on the `chrome` service - prevents Chrome crashes under load
- [ ] `BROWSER_POOL_SIZE` is sized to the production server's available RAM (~150-200MB per browser instance)
- [ ] A test scrape run completes end-to-end against the production stack - `docker compose exec app php artisan scraper:run listam --pages=1`
- [ ] Stealth config (`SCRAPER_USER_AGENT`, `SCRAPER_BROWSER_LANGUAGES`) is current and not flagged by the target site

---

## Cache & Performance

- [ ] `docker compose exec app php artisan optimize` has been run (caches config, routes, views)
- [ ] `CACHE_STORE=redis` in production for better performance
- [ ] Opcache is enabled in the PHP image - confirm `Dockerfile` includes a production-tuned opcache configuration

---

## Storage & Exports

- [ ] Storage directory permissions are correct inside the container - `entrypoint.sh` re-applies this automatically on every boot, confirm it ran without error in container logs
- [ ] `storage/app/exports` persists across container restarts (verify it's not relying on the container's writable layer alone - mount as a volume if exports must survive a rebuild)
- [ ] A test export runs successfully for each configured format (excel, csv, json, real_estate_report)

---

## Data Drift Detection

- [ ] `SCRAPER_DRIFT_MIN_LISTINGS` reflects expected production scrape volume, not local testing defaults
- [ ] `SCRAPER_DRIFT_MAX_NULL_RATE` has been reviewed against real field population rates from a recent production-scale run

---

## Error Tracking

- [ ] `SENTRY_LARAVEL_DSN` is set to your real Sentry DSN
- [ ] Test Sentry is receiving errors - trigger a test exception
- [ ] `SENTRY_TRACES_SAMPLE_RATE` is appropriate for your traffic (0.1 = 10%)
- [ ] `SENTRY_ENVIRONMENT=production`

---

## Logging

- [ ] `LOG_LEVEL=error` in production to reduce noise
- [ ] Log files are rotated - `LOG_CHANNEL=daily` recommended for production
- [ ] Container logs are accessible for debugging - `docker compose logs -f app`
- [ ] Logs are not publicly accessible

---

## Final Checks

- [ ] `GET /api/health` returns a healthy status for database, Redis, and queue
- [ ] API rate limiting is active - confirm `API_RATE_LIMIT` and `API_PER_TOKEN_LIMIT` behave as expected under test load
- [ ] Run the full Pest test suite one final time - `docker compose exec app php artisan test`
- [ ] Check the `/horizon` dashboard is reachable only by intended users
- [ ] Confirm all seven containers restart cleanly after a host reboot - `sudo reboot`, then `docker compose ps`
