# PriceLabs Sync — Investigation Results

## Finding: PriceLabs IS Syncing Successfully

After investigating, the PriceLabs API integration is **working correctly**:

- **API Status:** HTTP 200 (success)
- **Data Source:** `live` (not fallback)
- **All 3 properties found in cache:**
  - **The Chalet** → ID `608635403291162192` ✓
- **The Oasis** → ID `49479938` ✓
  - **The Villa** → ID `1278298080134872974` ✓
- **Last refresh:** March 6–7, 2026
- **Cache TTL:** 1 hour (3600 seconds) — after 1 hour, fresh data is fetched from PriceLabs

## How It Works

1. `PriceLabsAPI.php` calls `https://api.pricelabs.co/v1/listings` with your API key
2. Results are cached in `cache/pricelabs_listings.json` for 1 hour
3. Each property page calls `getListingDefaults()` which maps your property slugs to PriceLabs listing IDs and returns the `base` price

## If You're Seeing Unexpected Behavior

If something still looks off, check the following:

1. **Are you seeing "fallback" prices?** — Visit a property page and look for the text: "Live starting rates are synced through PriceLabs." If you see "Starting rates are shown here" instead, it means the live fetch failed on that page load.
2. **Is the API key still valid?** — Your key is set in `includes/config.php`. If PriceLabs rotated or expired it, update the `PRICELABS_API_TOKEN` constant.
3. **Clear the cache manually** — Delete `cache/pricelabs_listings.json` to force a fresh fetch on the next page load.
4. **Check PHP error logs** — The code logs curl errors via `error_log()`. Check your server's PHP error log for lines starting with `PriceLabs API curl error:`.

## What You May Need To Do

- **If everything looks fine now:** No action needed.
- **If prices look stale:** Delete `cache/pricelabs_listings.json` and reload a property page.
- **If you changed PriceLabs plans or API keys:** Update `PRICELABS_API_TOKEN` in `includes/config.php`.
- **Let me know specifically what looks wrong** so I can dig deeper into the exact symptom.

---

# Outscraper Reviews Setup

## Step 1: Get Your Outscraper API Key

1. Sign up or log in at [https://app.outscraper.com](https://app.outscraper.com)
2. Go to **Profile** → **API Keys** (or [https://app.outscraper.com/profile](https://app.outscraper.com/profile))
3. Copy your API key

## Step 2: Add the Key to Config

Open `includes/config.php` and paste your key into the `OUTSCRAPER_API_KEY` constant:

```php
define('OUTSCRAPER_API_KEY', 'YOUR_API_KEY_HERE');
```

## Step 3: Run the First Sync

From the terminal, run:

```bash
php sync_reviews.php --force
```

This will fetch the 4 most recent reviews for each property from Outscraper and store them in `cache/reviews.json`.

## Step 4: Set Up Weekly Cron (Optional)

To auto-sync once per week (e.g., Sunday at 3 AM):

```
0 3 * * 0 php /full/path/to/sync_reviews.php
```

Or you can run `sync_reviews.php` manually whenever you want fresh reviews. The system automatically skips syncs if the last one was less than 7 days ago (unless you pass `--force`).

## How It Works

- Reviews are stored in `cache/reviews.json` as a simple JSON database
- The **home page** shows the 6 most recent reviews across all properties
- Each **property page** shows that property's specific reviews (up to 4)
- Reviews only appear on the site once the first sync has been run
