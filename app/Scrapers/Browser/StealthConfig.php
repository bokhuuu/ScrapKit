<?php

declare(strict_types=1);

namespace App\Scrapers\Browser;

/**
 * Stealth configuration for browser automation.
 *
 * ChromeDriver leaves three detectable fingerprints by default:
 *   1. navigator.webdriver = true  (JavaScript property)
 *   2. HeadlessChrome in user agent (HTTP header)
 *   3. Automation-specific Chrome features enabled
 *
 * This class eliminates all three, making our browser
 * indistinguishable from a real human using Chrome.
 *
 * Usage - override chromeArguments() in your scraper:
 *   protected function chromeArguments(): array
 *   {
 *       return [
 *           ...parent::chromeArguments(),
 *           ...StealthConfig::chromeFlags(),
 *       ];
 *   }
 *
 * Then call StealthConfig::javascriptPatches() after navigate()
 * to remove navigator.webdriver at the JS level.
 */
class StealthConfig
{
    /**
     * Chrome startup flags that hide automation fingerprints.
     *
     * These are passed to Chrome before any page loads.
     */
    public static function chromeFlags(): array
    {
        return [
            // Removes the AutomationControlled flag Chrome sets internally.
            // Without this, JavaScript can detect: window.chrome.runtime exists
            // in a way that only happens under automation.
            '--disable-blink-features=AutomationControlled',

            // Replace HeadlessChrome user agent with a real browser string.
            // Sites read this header on every request - HeadlessChrome is an
            // immediate bot signal.
            '--user-agent='.self::userAgent(),

            // Disable the automation info bar Chrome shows at the top:
            // "Chrome is being controlled by automated software"
            '--disable-infobars',

            // Disable extensions popup that sometimes appears in automation
            '--disable-extensions',

        ];
    }

    /**
     * JavaScript to inject after every page load.
     *
     * Chrome flags remove fingerprints at the browser level,
     * but navigator.webdriver must also be patched at the JS level
     * because some sites check it after the page renders.
     *
     * Call this via Dusk's script() method after navigate():
     *   $this->browser->script(StealthConfig::javascriptPatches());
     *
     * @return string[]
     */
    public static function javascriptPatches(): array
    {
        return [
            // Remove navigator.webdriver entirely.
            // Setting to undefined makes it invisible to site JS checks.
            "Object.defineProperty(navigator, 'webdriver', {get: () => undefined})",

            // Fake the plugins array - real browsers have plugins, headless has none.
            // Some sites check plugins.length === 0 as a bot signal.
            "Object.defineProperty(navigator, 'plugins', {get: () => [1, 2, 3]})",

            // Fake supported languages - headless Chrome often has empty languages.
            "Object.defineProperty(navigator, 'languages', {get: () => ".json_encode(
                explode(',', (string) config('scraper.browser_languages', 'en-US,en'))
            ).'})',
        ];
    }

    /**
     * A realistic Chrome user agent string.
     *
     * Pulled from config so it can be updated in .env when Chrome
     * releases a new version - no code change needed.
     *
     * Defaults to a current Windows Chrome string which is the most
     * common real-world browser fingerprint.
     */
    public static function userAgent(): string
    {
        return (string) config(
            'scraper.user_agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
        );
    }
}
