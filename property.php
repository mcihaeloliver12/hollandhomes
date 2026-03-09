<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/booking.php';
require_once __DIR__ . '/includes/AirbnbScraper.php';
require_once __DIR__ . '/includes/PriceLabsAPI.php';
require_once __DIR__ . '/includes/IcalAvailability.php';
require_once __DIR__ . '/includes/OutscraperReviews.php';

$id = isset($_GET['id']) ? strtolower(trim($_GET['id'])) : '';
$data = hh_site_data();
$properties = $data['properties'];
$settings = hh_load_site_settings();

if (!isset($properties[$id])) {
    header('Location: index.php');
    exit;
}

$property = $properties[$id];
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$scraper = new AirbnbScraper();
$liveAirbnbData = $scraper->getListingInfo($property['airbnb_url']);
$airbnbData = hh_merge_airbnb_snapshot($property['fallback_airbnb'], $liveAirbnbData);

$priceLabs = new PriceLabsAPI(PRICELABS_API_TOKEN);
$pricingData = $priceLabs->getListingDefaults($id);
$currencySymbol = hh_currency_symbol($pricingData['currency'] ?? 'USD');
$startingPrice = isset($pricingData['price']) ? $currencySymbol . number_format((float) $pricingData['price']) : 'Call for pricing';
$bookingSettings = hh_listing_booking_settings($settings, $id);
$minStay = (int) ($bookingSettings['min_nights'] ?? hh_default_min_nights($id));
$priceLabsCalendarData = hh_load_pricelabs_calendar_data($id, $priceLabs);
$pricingSource = $pricingData['source'] ?? 'fallback';
$bookingError = trim((string) ($_GET['booking_error'] ?? ''));

$availabilitySync = new IcalAvailability();
$availabilityData = $availabilitySync->getBlockedDates((string) ($bookingSettings['ical_url'] ?? ''), $id);
$blockedDates = is_array($availabilityData['blocked_dates'] ?? null) ? $availabilityData['blocked_dates'] : [];
$availabilitySource = (string) ($availabilityData['source'] ?? 'not-configured');

$propertyDir = __DIR__ . '/' . ucfirst($id) . '/Photos/';
$galleryPhotos = [];
if (is_dir($propertyDir)) {
    $scanned = scandir($propertyDir);
    foreach ($scanned as $file) {
        if ($file === '.' || $file === '..' || strpos($file, '.DS_Store') !== false) {
            continue;
        }

        $path = ucfirst($id) . '/Photos/' . $file;
        if ($path === $property['hero_image']) {
            continue;
        }
        $galleryPhotos[] = $path;
    }
}

$factList = array_filter([
    hh_airbnb_fact($airbnbData['bedrooms'], 'bedroom'),
    hh_airbnb_fact($airbnbData['beds'], 'bed'),
    hh_airbnb_fact($airbnbData['baths'], 'bath'),
    $airbnbData['listing_label'] ?: $airbnbData['listing_type'],
]);

$heroImage = hh_property_hero_image($property, $settings);
$locationText = $airbnbData['location'] ?: 'Featured destination';
$ratingText = $airbnbData['rating'] ?: 'New';
$reviewsCount = (string) ($airbnbData['reviews_count'] ?? '0');
$listingType = $airbnbData['listing_type'] ?: 'Vacation rental';
$description = trim((string) ($airbnbData['description'] ?? $property['summary']));
$summary = trim((string) ($property['summary'] ?? $property['name']));
$bedroomsText = hh_airbnb_fact($airbnbData['bedrooms'], 'bedroom');
$bedsText = hh_airbnb_fact($airbnbData['beds'], 'bed');
$bathsText = hh_airbnb_fact($airbnbData['baths'], 'bath');

$primaryGalleryPhoto = $galleryPhotos[0] ?? '';
$secondaryGalleryPhotos = array_slice($galleryPhotos, 1);
$visibleSecondaryGalleryLimit = 5;
$hiddenSecondaryGalleryCount = max(0, count($secondaryGalleryPhotos) - $visibleSecondaryGalleryLimit);

$reviewsApi = new OutscraperReviews(OUTSCRAPER_API_KEY);
$propertyReviews = $reviewsApi->getPropertyReviews($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $escape($property['name']); ?> | Holland Homes</title>
    <meta name="description" content="<?php echo $escape($description); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        :root{
            --hh-display: 'Instrument Sans', sans-serif;
            --hh-body: 'Manrope', sans-serif;
            --hh-bg: #f6f2eb;
            --hh-card: rgba(255,251,245,0.92);
            --hh-card-solid: #fcf8f2;
            --hh-text: #1b1613;
            --hh-muted: #62564a;
            --hh-line: rgba(27,22,19,0.08);
            --hh-line-strong: rgba(27,22,19,0.14);
            --hh-gold: #8b6646;
            --hh-gold-soft: rgba(139,102,70,0.10);
            --hh-green: #253d34;
            --hh-shadow: 0 24px 60px rgba(18, 16, 13, 0.10);
            --hh-shadow-soft: 0 14px 34px rgba(18, 16, 13, 0.06);
            --hh-radius-xl: 6px;
            --hh-radius-lg: 4px;
            --hh-radius-md: 3px;
            --hh-radius-sm: 2px;
        }

        html {
            scroll-behavior: smooth;
        }

        body.property-page {
            font-family: var(--hh-body);
            background:
                radial-gradient(circle at top left, rgba(139,102,70,0.08), transparent 28%),
                radial-gradient(circle at top right, rgba(37,61,52,0.05), transparent 24%),
                linear-gradient(180deg, #faf8f4 0%, #f4efe7 100%);
            color: var(--hh-text);
        }

        body.property-page h1,
        body.property-page h2,
        body.property-page h3,
        body.property-page h4,
        body.property-page h5,
        body.property-page h6,
        body.property-page .logo {
            font-family: var(--hh-display);
            letter-spacing: -0.055em;
            font-weight: 700;
        }

        body.property-page .nav-links,
        body.property-page .btn,
        body.property-page .text-link,
        body.property-page .hero-kicker,
        body.property-page .eyebrow-luxury {
            font-family: var(--hh-body);
        }

        .property-page-shell {
            position: relative;
            overflow: clip;
        }

        .property-page-shell::before {
            content: "";
            position: absolute;
            top: 160px;
            right: -140px;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(178,135,77,0.07), transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .property-page-shell::after {
            content: "";
            position: absolute;
            top: 900px;
            left: -120px;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(37,61,52,0.05), transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        #main-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(246,239,231,0.84);
            transition: all 0.3s ease;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(27,22,19,0.08);
        }

        #main-header.scrolled {
            background: rgba(249,243,235,0.96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(27,22,19,0.1);
        }

        .header-inner {
            min-height: 80px;
        }

        .nav-links {
            display: flex;
            gap: 22px;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.79rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .nav-links a {
            position: relative;
            text-decoration: none;
        }

        .nav-links a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 0;
            height: 2px;
            background: var(--hh-gold);
            transition: width 0.25s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .property-hero-luxury {
            position: relative;
            min-height: 82vh;
            display: flex;
            align-items: end;
            padding: 140px 0 60px;
            overflow: hidden;
            isolation: isolate;
        }

        .property-hero-luxury .hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center center;
            transform: scale(1.03);
        }

        .property-hero-luxury::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(16,14,12,0.08) 0%, rgba(16,14,12,0.16) 24%, rgba(16,14,12,0.48) 62%, rgba(16,14,12,0.72) 100%);
            z-index: 1;
        }

        .property-hero-luxury::after {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 180px;
            background: linear-gradient(180deg, rgba(246,242,235,0) 0%, rgba(246,242,235,1) 100%);
            z-index: 2;
            pointer-events: none;
        }

        .property-hero-grid {
            position: relative;
            z-index: 3;
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(320px, 410px);
            gap: 30px;
            align-items: end;
        }

        .hero-copy-luxury {
            color: #fff;
            max-width: 760px;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 9px 14px;
            border-radius: var(--hh-radius-sm);
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(10px);
            font-size: 0.72rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .hero-copy-luxury h1 {
            font-size: clamp(3rem, 5vw, 5.9rem);
            line-height: 0.92;
            letter-spacing: -0.06em;
            margin: 0 0 18px;
            color: #fff;
            max-width: 820px;
        }

        .hero-subheadline {
            font-size: clamp(1rem, 2vw, 1.16rem);
            line-height: 1.78;
            color: rgba(255,255,255,0.88);
            max-width: 700px;
            margin-bottom: 24px;
        }

        .hero-stat-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 13px;
            border-radius: var(--hh-radius-sm);
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.16);
            backdrop-filter: blur(10px);
            color: #fff;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-actions .btn {
            min-width: 186px;
        }

        .hero-actions .btn:not(.btn-secondary) {
            background: rgba(249,243,235,0.96);
            color: #171513;
            border: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 16px 36px rgba(0,0,0,0.14);
        }

        .hero-actions .btn.btn-secondary {
            background: rgba(255,255,255,0.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.22);
            backdrop-filter: blur(8px);
        }

        .hero-luxury-panel {
            background: rgba(252,248,242,0.92);
            border: 1px solid rgba(27,22,19,0.08);
            border-radius: var(--hh-radius-lg);
            padding: 24px;
            color: var(--hh-text);
            box-shadow: var(--hh-shadow);
        }

        .hero-luxury-panel h2 {
            color: var(--hh-text);
            margin-bottom: 18px;
            font-size: 1rem;
        }

        .hero-panel-price {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 18px;
        }

        .hero-panel-price strong {
            font-size: 2rem;
            line-height: 1;
            color: var(--hh-text);
        }

        .hero-panel-price span {
            color: var(--hh-muted);
        }

        .hero-panel-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
        }

        .hero-panel-stat {
            padding: 14px;
            border-radius: var(--hh-radius-md);
            background: rgba(27,22,19,0.03);
            border: 1px solid rgba(27,22,19,0.06);
        }

        .hero-panel-stat strong {
            display: block;
            font-size: 1.02rem;
            margin-bottom: 4px;
            color: var(--hh-text);
        }

        .hero-panel-stat span {
            color: var(--hh-muted);
            font-size: 0.86rem;
        }

        .hero-panel-note {
            color: var(--hh-muted);
            line-height: 1.7;
            font-size: 0.93rem;
        }

        .section-tabs-wrap {
            position: relative;
            z-index: 5;
            margin-top: -18px;
            margin-bottom: 32px;
        }

        .section-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px;
            border-radius: var(--hh-radius-lg);
            background: rgba(255,251,245,0.92);
            border: 1px solid rgba(27,22,19,0.06);
            box-shadow: var(--hh-shadow-soft);
        }

        .section-tabs a {
            text-decoration: none;
            color: var(--hh-text);
            padding: 10px 14px;
            border-radius: var(--hh-radius-md);
            background: rgba(27,22,19,0.02);
            border: 1px solid rgba(27,22,19,0.06);
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            transition: all 0.25s ease;
        }

        .section-tabs a:hover {
            background: rgba(27,22,19,0.06);
            color: var(--hh-text);
            transform: translateY(-1px);
        }

        .property-main-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 28px;
            align-items: start;
        }

        .content-card-luxury,
        .booking-card,
        .info-panel,
        .detail-card,
        .amenity-card,
        .review-luxury-card,
        .trust-strip,
        .snapshot-card,
        .media-frame,
        .media-thumb {
            background: rgba(255,255,255,0.72);
            border: 1px solid var(--hh-line);
            box-shadow: var(--hh-shadow-soft);
        }

        .content-card-luxury {
            border-radius: var(--hh-radius-xl);
            padding: 30px;
            margin-bottom: 22px;
        }

        .eyebrow-luxury {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: var(--hh-gold);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .eyebrow-luxury::before {
            content: "";
            width: 24px;
            height: 1px;
            background: rgba(178,135,77,0.45);
        }

        .section-title-luxury {
            font-size: clamp(1.8rem, 2.3vw, 2.45rem);
            line-height: 1.02;
            letter-spacing: -0.055em;
            margin-bottom: 14px;
            color: var(--hh-text);
        }

        .section-intro {
            font-size: 1rem;
            line-height: 1.78;
            color: var(--hh-muted);
            max-width: 760px;
        }

        .trust-strip {
            border-radius: var(--hh-radius-lg);
            padding: 18px 20px;
            margin-top: 24px;
            background: rgba(27,22,19,0.018);
        }

        .trust-strip-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .trust-strip-item strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .trust-strip-item span {
            font-size: 0.91rem;
            color: var(--hh-muted);
        }

        .snapshot-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 22px;
        }

        .snapshot-card {
            border-radius: var(--hh-radius-md);
            padding: 18px 16px;
            text-align: left;
            background: rgba(27,22,19,0.024);
        }

        .snapshot-card strong {
            display: block;
            font-size: 1.02rem;
            margin-bottom: 6px;
        }

        .snapshot-card span {
            color: var(--hh-muted);
            font-size: 0.9rem;
        }

        .review-luxury-card {
            border-radius: var(--hh-radius-lg);
            padding: 26px;
            position: relative;
            overflow: hidden;
        }

        .review-luxury-card::before {
            content: "";
            position: absolute;
            top: -16px;
            left: 14px;
            font-size: 5.8rem;
            line-height: 1;
            color: rgba(178,135,77,0.09);
            font-family: Georgia, serif;
        }

        .guest-review-rating {
            position: relative;
            z-index: 1;
            margin-bottom: 16px;
            font-weight: 700;
            color: var(--hh-text);
        }

        .guest-review-text {
            position: relative;
            z-index: 1;
            font-size: 1.03rem;
            line-height: 1.8;
            color: var(--hh-text);
            margin-bottom: 14px;
            font-style: normal;
        }

        .guest-review-author {
            position: relative;
            z-index: 1;
            color: var(--hh-muted);
            margin: 0;
        }

        .detail-card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 14px;
        }

        .detail-card {
            border-radius: var(--hh-radius-lg);
            padding: 22px;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            background: rgba(27,22,19,0.018);
        }

        .detail-card:hover,
        .amenity-card:hover,
        .info-panel:hover {
            transform: translateY(-2px);
            box-shadow: var(--hh-shadow);
            border-color: rgba(23,21,18,0.12);
        }

        .detail-card h3,
        .amenity-card h3,
        .info-panel h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            letter-spacing: -0.04em;
        }

        .detail-card p,
        .info-panel li,
        .amenity-card li {
            color: var(--hh-muted);
            line-height: 1.8;
        }

        .gallery-shell {
            margin-top: 8px;
        }

        .gallery-featured {
            margin-bottom: 14px;
        }

        .media-frame,
        .media-thumb {
            position: relative;
            overflow: hidden;
            border-radius: var(--hh-radius-md);
            cursor: pointer;
            padding: 0;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }

        .media-frame:hover,
        .media-thumb:hover {
            transform: translateY(-2px);
            box-shadow: var(--hh-shadow);
            border-color: var(--hh-line-strong);
        }

        .media-frame img,
        .media-thumb img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.55s ease;
        }

        .media-frame:hover img,
        .media-thumb:hover img {
            transform: scale(1.025);
        }

        .media-frame::after,
        .media-thumb::after {
            content: "Expand";
            position: absolute;
            right: 12px;
            bottom: 12px;
            padding: 7px 11px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #fff;
            background: rgba(12,12,12,0.56);
            border: 1px solid rgba(255,255,255,0.16);
            backdrop-filter: blur(8px);
            border-radius: var(--hh-radius-sm);
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 0.22s ease, transform 0.22s ease;
            pointer-events: none;
        }

        .media-frame:hover::after,
        .media-thumb:hover::after {
            opacity: 1;
            transform: translateY(0);
        }

        .media-frame {
            min-height: 520px;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .media-thumb {
            min-height: 240px;
        }

        .media-thumb-collapsed {
            display: none;
        }

        .gallery-empty {
            padding: 24px;
            border-radius: var(--hh-radius-lg);
            background: rgba(23,21,18,0.03);
            color: var(--hh-muted);
        }

        .amenity-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 14px;
        }

        .amenity-card {
            border-radius: var(--hh-radius-lg);
            padding: 22px;
            background: rgba(27,22,19,0.02);
        }

        .amenity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .amenity-list li {
            position: relative;
            padding-left: 18px;
            margin-bottom: 10px;
        }

        .amenity-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.78em;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--hh-gold);
            transform: translateY(-50%);
        }

        .two-column-cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 14px;
        }

        .info-panel {
            border-radius: var(--hh-radius-lg);
            padding: 24px;
            background: rgba(27,22,19,0.018);
        }

        .property-sidebar {
            position: relative;
        }

        .sticky-card {
            position: sticky;
            top: 110px;
        }

        .booking-card {
            border-radius: var(--hh-radius-xl);
            padding: 24px;
            background: rgba(255,251,245,0.94);
        }

        .booking-card h2 {
            margin-bottom: 16px;
        }

        .booking-price-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 18px;
            padding: 18px;
            border-radius: var(--hh-radius-lg);
            background: rgba(139,102,70,0.08);
            border: 1px solid rgba(139,102,70,0.14);
        }

        .booking-price-row strong {
            font-size: 2rem;
            line-height: 1;
        }

        .booking-price-row span,
        .booking-note,
        .calendar-disclaimer,
        .booking-followup-note {
            color: var(--hh-muted);
        }

        .booking-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 18px;
        }

        .booking-meta > div {
            padding: 15px 14px;
            border-radius: var(--hh-radius-md);
            background: rgba(27,22,19,0.03);
            border: 1px solid rgba(27,22,19,0.05);
        }

        .booking-meta strong {
            display: block;
            margin-bottom: 4px;
            font-size: 1.04rem;
        }

        .booking-meta span {
            color: var(--hh-muted);
            font-size: 0.88rem;
        }

        .calendar-container {
            margin-top: 20px;
            padding: 18px;
            border-radius: var(--hh-radius-lg);
            background: rgba(27,22,19,0.022);
            border: 1px solid rgba(27,22,19,0.05);
        }

        .calendar-header button {
            width: 40px;
            height: 40px;
            border-radius: var(--hh-radius-sm);
            border: 1px solid rgba(23,21,18,0.08);
            background: #fff;
            cursor: pointer;
        }

        .calendar-days div {
            border-radius: 8px;
        }

        .booking-alert {
            border-radius: var(--hh-radius-md);
        }

        .booking-btn {
            width: 100%;
            margin-top: 14px;
        }

        .booking-fallback-link {
            display: inline-block;
            margin-top: 16px;
        }

        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 22px 0 0;
            padding: 0;
            list-style: none;
        }

        .badge-list li {
            padding: 9px 13px;
            border-radius: var(--hh-radius-md);
            background: rgba(139,102,70,0.08);
            color: #7f5d3f;
            border: 1px solid rgba(139,102,70,0.12);
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .airbnb-star {
            color: #FF385C;
        }

        .superhost-badge {
            color: #FF385C;
            font-weight: 700;
            padding: 4px 8px;
            border: 1px solid rgba(255,56,92,0.16);
            background: rgba(255,56,92,0.08);
            border-radius: var(--hh-radius-sm);
        }

        .footer-shell {
            position: relative;
            z-index: 1;
        }

        .fade-in-section {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .fade-in-section.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .fade-up {
            animation: heroFadeUp 0.9s ease both;
        }

        @keyframes heroFadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .lightbox {
            position: fixed;
            inset: 0;
            background: rgba(10, 10, 10, 0.84);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.24s ease, visibility 0.24s ease;
        }

        .lightbox.is-open {
            opacity: 1;
            visibility: visible;
        }

        .lightbox-inner {
            position: relative;
            width: min(1240px, 100%);
            max-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-image-wrap {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .lightbox-image {
            max-width: 100%;
            max-height: 86vh;
            object-fit: contain;
            border-radius: var(--hh-radius-sm);
            box-shadow: 0 30px 80px rgba(0,0,0,0.35);
            background: #111;
        }

        .lightbox-close,
        .lightbox-prev,
        .lightbox-next {
            position: absolute;
            border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.10);
            color: #fff;
            backdrop-filter: blur(8px);
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
        }

        .lightbox-close:hover,
        .lightbox-prev:hover,
        .lightbox-next:hover {
            background: rgba(255,255,255,0.16);
            border-color: rgba(255,255,255,0.24);
            transform: translateY(-1px);
        }

        .lightbox-close {
            top: -12px;
            right: -12px;
            width: 46px;
            height: 46px;
            border-radius: var(--hh-radius-sm);
            font-size: 1.3rem;
            line-height: 1;
        }

        .lightbox-prev,
        .lightbox-next {
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            border-radius: var(--hh-radius-sm);
            font-size: 1.35rem;
            line-height: 1;
        }

        .lightbox-prev {
            left: -66px;
        }

        .lightbox-next {
            right: -66px;
        }

        .lightbox-counter {
            position: absolute;
            left: 50%;
            bottom: -32px;
            transform: translateX(-50%);
            color: rgba(255,255,255,0.82);
            font-size: 0.82rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        @media (max-width: 1140px) {
            .property-main-grid,
            .property-hero-grid {
                grid-template-columns: 1fr;
            }

            .sticky-card {
                position: relative;
                top: 0;
            }

            .snapshot-grid,
            .trust-strip-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .media-frame {
                min-height: 420px;
            }

            .media-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .lightbox-prev {
                left: 10px;
            }

            .lightbox-next {
                right: 10px;
            }

            .lightbox-close {
                top: 10px;
                right: 10px;
            }
        }

        @media (max-width: 768px) {
            .property-hero-luxury {
                min-height: auto;
                padding: 120px 0 40px;
            }

            .hero-copy-luxury h1 {
                font-size: clamp(2.4rem, 12vw, 4rem);
            }

            .content-card-luxury,
            .booking-card,
            .info-panel,
            .detail-card,
            .amenity-card {
                padding: 22px;
            }

            .detail-card-grid,
            .amenity-grid,
            .two-column-cards,
            .snapshot-grid,
            .trust-strip-grid,
            .booking-meta,
            .media-grid {
                grid-template-columns: 1fr;
            }

            .section-tabs {
                gap: 10px;
            }

            .section-tabs a {
                width: 100%;
                text-align: center;
            }

            .media-frame {
                min-height: 300px;
            }

            .media-thumb {
                min-height: 220px;
            }

            .lightbox {
                padding: 18px;
            }

            .lightbox-prev,
            .lightbox-next {
                width: 46px;
                height: 46px;
            }

            .lightbox-counter {
                bottom: -28px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body class="property-page">
<div class="property-page-shell">

    <header id="main-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">Holland <i>Homes</i></a>
            <nav class="nav-links">
                <a href="index.php#properties">Properties</a>
                <a href="#overview">Overview</a>
                <a href="#details">Highlights</a>
                <a href="#gallery">Gallery</a>
                <a href="#book">Book</a>
            </nav>
        </div>
    </header>

    <section class="hero full-bleed property-hero-luxury">
        <div class="hero-bg" style="background-image: url('<?php echo $escape($heroImage); ?>');"></div>

        <div class="container property-hero-grid">
            <div class="hero-copy-luxury fade-up">
                <div class="hero-kicker">
                    <span><?php echo $escape($locationText); ?></span>
                    <?php if (!empty($property['is_superhost'])): ?>
                        <span>&middot;</span>
                        <span class="superhost-badge">&#9733; Superhost</span>
                    <?php endif; ?>
                </div>

                <h1><?php echo $escape($property['name']); ?></h1>

                <p class="hero-subheadline">
                    <?php echo $escape($description); ?>
                </p>

                <div class="hero-stat-chips">
                    <?php if ($bedroomsText): ?><div class="hero-chip"><?php echo $escape($bedroomsText); ?></div><?php endif; ?>
                    <?php if ($bedsText): ?><div class="hero-chip"><?php echo $escape($bedsText); ?></div><?php endif; ?>
                    <?php if ($bathsText): ?><div class="hero-chip"><?php echo $escape($bathsText); ?></div><?php endif; ?>
                    <div class="hero-chip"><span class="airbnb-star">&#9733;</span> <?php echo $escape($ratingText); ?> rating</div>
                    <div class="hero-chip"><?php echo $escape($reviewsCount); ?> reviews</div>
                </div>

                <div class="hero-actions">
                    <a href="#book" class="btn">Reserve Your Stay</a>
                    <a href="#gallery" class="btn btn-secondary">Explore the Home</a>
                </div>
            </div>

            <aside class="hero-luxury-panel fade-up">
                <h2>Your stay at a glance</h2>

                <div class="hero-panel-price">
                    <strong><?php echo $escape($startingPrice); ?></strong>
                    <span>starting nightly rate</span>
                </div>

                <div class="hero-panel-grid">
                    <div class="hero-panel-stat">
                        <strong>Varies by date</strong>
                        <span>minimum stay</span>
                    </div>
                    <div class="hero-panel-stat">
                        <strong><?php echo $escape($listingType); ?></strong>
                        <span>property type</span>
                    </div>
                    <div class="hero-panel-stat">
                        <strong><?php echo $escape($ratingText); ?></strong>
                        <span>guest rating</span>
                    </div>
                    <div class="hero-panel-stat">
                        <strong><?php echo $escape($reviewsCount); ?></strong>
                        <span>Airbnb reviews</span>
                    </div>
                </div>

                <p class="hero-panel-note">
                    Designed for a memorable stay, with direct booking available once your dates clear availability and minimum-stay rules.
                </p>
            </aside>
        </div>
    </section>

    <div class="container section-tabs-wrap">
        <nav class="section-tabs fade-in-section">
            <a href="#overview">Overview</a>
            <a href="#snapshot">Snapshot</a>
            <a href="#details">Highlights</a>
            <a href="#gallery">Gallery</a>
            <a href="#amenities">Amenities</a>
            <a href="#stay-info">Stay Info</a>
            <a href="#guest-reviews">Reviews</a>
            <a href="#book">Book Direct</a>
        </nav>
    </div>

    <section class="section-padding" style="padding-top: 10px;">
        <div class="container property-main-grid">
            <main>
                <section id="overview" class="content-card-luxury fade-in-section">
                    <span class="eyebrow-luxury">Overview</span>
                    <h2 class="section-title-luxury"><?php echo $escape($summary); ?></h2>
                    <p class="section-intro"><?php echo $escape($description); ?></p>

                    <?php if (!empty($property['badges'])): ?>
                        <ul class="badge-list">
                            <?php foreach ($property['badges'] as $badge): ?>
                                <li><?php echo $escape($badge); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="trust-strip">
                        <div class="trust-strip-grid">
                            <div class="trust-strip-item">
                                <strong>Direct booking</strong>
                                <span>Reserve with live date selection and streamlined checkout.</span>
                            </div>
                            <div class="trust-strip-item">
                                <strong>Guest-approved</strong>
                                <span><?php echo $escape($reviewsCount); ?> Airbnb reviews with a <?php echo $escape($ratingText); ?> rating.</span>
                            </div>
                            <div class="trust-strip-item">
                                <strong>Curated stay</strong>
                                <span>Thoughtful details, strong presentation, and a stay built around comfort.</span>
                            </div>
                            <div class="trust-strip-item">
                                <strong>Prime setting</strong>
                                <span><?php echo $escape($locationText); ?> with the character guests are searching for.</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="snapshot" class="content-card-luxury fade-in-section">
                    <span class="eyebrow-luxury">Property snapshot</span>
                    <h2 class="section-title-luxury">Everything you want to know before you book</h2>

                    <div class="snapshot-grid">
                        <div class="snapshot-card">
                            <strong><?php echo $escape($listingType); ?></strong>
                            <span>property type</span>
                        </div>
                        <div class="snapshot-card">
                            <strong><?php echo $escape($locationText); ?></strong>
                            <span>location</span>
                        </div>
                        <div class="snapshot-card">
                            <strong><?php echo $escape($bedroomsText ?: '—'); ?></strong>
                            <span>bedrooms</span>
                        </div>
                        <div class="snapshot-card">
                            <strong><?php echo $escape($bedsText ?: '—'); ?></strong>
                            <span>beds</span>
                        </div>
                    </div>

                    <div class="review-luxury-card">
                        <div class="guest-review-rating">
                            <span class="airbnb-star">&#9733;</span>
                            <?php echo $escape($ratingText); ?>
                            &middot;
                            <?php echo $escape($reviewsCount); ?> reviews
                            <?php if (!empty($property['is_superhost'])): ?>
                                &middot; <span class="superhost-badge">&#9733; Superhost</span>
                            <?php endif; ?>
                        </div>
                        <p class="guest-review-text"><?php echo $escape($property['guest_review']); ?></p>
                        <p class="guest-review-author"><?php echo $escape($property['guest_review_name']); ?>, Airbnb guest</p>
                    </div>
                </section>

                <section id="details" class="content-card-luxury fade-in-section">
                    <span class="eyebrow-luxury">Why guests love it</span>
                    <h2 class="section-title-luxury">What makes <?php echo $escape($property['name']); ?> feel special</h2>

                    <div class="detail-card-grid">
                        <?php foreach ($property['detail_blocks'] as $block): ?>
                            <article class="detail-card">
                                <h3><?php echo $escape($block['title']); ?></h3>
                                <p><?php echo $escape($block['text']); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section id="gallery" class="content-card-luxury fade-in-section">
                    <span class="eyebrow-luxury">Photo gallery</span>
                    <h2 class="section-title-luxury">A closer look at the experience</h2>

                    <?php if (!empty($galleryPhotos)): ?>
                        <div class="gallery-shell">
                            <?php if ($primaryGalleryPhoto): ?>
                                <div class="gallery-featured">
                                    <button
                                        type="button"
                                        class="media-frame"
                                        data-gallery-index="0"
                                        data-gallery-src="<?php echo $escape($primaryGalleryPhoto); ?>"
                                        aria-label="Open featured property photo"
                                    >
                                        <img
                                            src="<?php echo $escape($primaryGalleryPhoto); ?>"
                                            alt="<?php echo $escape($property['name']); ?> featured photo"
                                            loading="eager"
                                        >
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($secondaryGalleryPhotos)): ?>
                                <div class="media-grid">
                                    <?php foreach ($secondaryGalleryPhotos as $index => $photo): ?>
                                        <button
                                            type="button"
                                            class="media-thumb<?php echo $index >= $visibleSecondaryGalleryLimit ? ' media-thumb-collapsed' : ''; ?>"
                                            data-gallery-index="<?php echo $escape((string) ($index + 1)); ?>"
                                            data-gallery-src="<?php echo $escape($photo); ?>"
                                            aria-label="Open photo <?php echo $escape((string) ($index + 2)); ?>"
                                        >
                                            <img
                                                src="<?php echo $escape($photo); ?>"
                                                alt="<?php echo $escape($property['name']); ?> photo <?php echo $escape((string) ($index + 2)); ?>"
                                                loading="lazy"
                                            >
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($hiddenSecondaryGalleryCount > 0): ?>
                                    <div style="margin-top:14px;">
                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            id="gallery-see-more"
                                            data-hidden-count="<?php echo $escape((string) $hiddenSecondaryGalleryCount); ?>"
                                        >
                                            See more photos (<?php echo $escape((string) $hiddenSecondaryGalleryCount); ?>)
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="gallery-empty">
                            Additional gallery photos will appear here once added to the property photo folder.
                        </div>
                    <?php endif; ?>
                </section>

                <section id="amenities" class="content-card-luxury fade-in-section">
                    <span class="eyebrow-luxury">Amenities & details</span>
                    <h2 class="section-title-luxury">Everything included with your stay</h2>

                    <div class="amenity-grid">
                        <?php foreach ($property['amenity_groups'] as $group): ?>
                            <article class="amenity-card">
                                <h3><?php echo $escape($group['title']); ?></h3>
                                <ul class="amenity-list">
                                    <?php foreach ($group['items'] as $item): ?>
                                        <li><?php echo $escape($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section id="stay-info" class="content-card-luxury fade-in-section">
                    <span class="eyebrow-luxury">Stay information</span>
                    <h2 class="section-title-luxury">Good to know before arrival</h2>

                    <div class="two-column-cards">
                        <article class="info-panel">
                            <span class="eyebrow-luxury" style="margin-bottom:10px;">Stay notes</span>
                            <h3>Helpful details</h3>
                            <ul class="amenity-list">
                                <?php foreach ($property['stay_notes'] as $note): ?>
                                    <li><?php echo $escape($note); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>

                        <article class="info-panel">
                            <span class="eyebrow-luxury" style="margin-bottom:10px;">Booking details</span>
                            <h3>Rates & reservation info</h3>
                            <ul class="amenity-list">
                                <li>From <?php echo $escape($startingPrice); ?> per night</li>
                                <li>Minimum stay varies by check-in date and live availability.</li>
                                <li><?php echo $escape(implode(' / ', $factList)); ?></li>
                                <li>Direct checkout becomes available once your selected dates clear availability and stay rules.</li>
                            </ul>
                        </article>
                    </div>
                </section>

                <?php if (!empty($propertyReviews)): ?>
                <section id="guest-reviews" class="content-card-luxury fade-in-section">
                    <span class="eyebrow-luxury">Guest reviews</span>
                    <h2 class="section-title-luxury">What guests say about <?php echo $escape($property['name']); ?></h2>

                    <div class="property-reviews-grid">
                        <?php foreach ($propertyReviews as $review): ?>
                            <article class="property-review-card">
                                <div class="property-review-stars">
                                    <?php for ($i = 0; $i < (int) ($review['rating'] ?? 5); $i++): ?>
                                        <span class="airbnb-star">&#9733;</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="property-review-text"><?php echo $escape($review['text'] ?? ''); ?></p>
                                <div class="property-review-footer">
                                    <strong class="property-review-author"><?php echo $escape($review['author'] ?? 'Guest'); ?></strong>
                                    <?php if (!empty($review['date'])): ?>
                                        <span class="property-review-date"><?php echo $escape(date('M Y', strtotime($review['date']))); ?></span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </main>

            <aside id="book" class="property-sidebar fade-in-section">
                <div class="booking-card sticky-card">
                    <span class="eyebrow-luxury">Book this property</span>
                    <h2>Start your direct booking</h2>

                    <div class="booking-price-row">
                        <strong><?php echo $escape($startingPrice); ?></strong>
                        <span>starting nightly rate</span>
                    </div>

                    <div class="booking-meta">
                        <div>
                            <strong>Varies</strong>
                            <span>minimum stay</span>
                        </div>
                        <div>
                            <strong><?php echo $escape($reviewsCount); ?></strong>
                            <span>Airbnb reviews</span>
                        </div>
                    </div>

                    <p class="booking-note">
                        <?php if ($pricingSource === 'live'): ?>
                            Live starting rates are synced through PriceLabs. Your final quote is based on the stay you select.
                        <?php else: ?>
                            Starting rates are shown here. Your booking estimate is built after you choose your dates.
                        <?php endif; ?>
                    </p>

                    <?php if ($bookingError !== ''): ?>
                        <div class="booking-alert booking-alert-error"><?php echo $escape($bookingError); ?></div>
                    <?php endif; ?>

                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button id="prev-month" type="button">&lt;</button>
                            <h3 id="calendar-month-year">Month Year</h3>
                            <button id="next-month" type="button">&gt;</button>
                        </div>

                        <div class="calendar-weekdays">
                            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                        </div>

                        <div id="calendar-days" class="calendar-days"></div>

                        <div class="selected-dates" style="display:none; margin-top:20px; text-align:center; font-size:var(--text-xs); text-transform:uppercase;">
                            Check In: <strong id="display-checkin"></strong><br>
                            Check Out: <strong id="display-checkout"></strong>
                        </div>
                    </div>

                    <p class="calendar-disclaimer">
                        <?php if ($availabilitySource === 'live'): ?>
                            Booked dates are synced from your Airbnb calendar feed.
                        <?php elseif ($availabilitySource === 'stale-cache'): ?>
                            Showing the last successful calendar sync because the latest iCal refresh failed.
                        <?php elseif ($availabilitySource === 'unavailable'): ?>
                            Calendar feed could not be refreshed right now. Please verify dates before checkout.
                        <?php else: ?>
                            Booked-date sync is not configured yet, so final confirmation should be checked before collecting payment.
                        <?php endif; ?>
                    </p>

                    <form action="checkout.php" method="get" class="direct-booking-form" id="direct-booking-form">
                        <input type="hidden" name="id" value="<?php echo $escape($id); ?>">
                        <input type="hidden" name="checkin" id="booking-checkin-input" value="">
                        <input type="hidden" name="checkout" id="booking-checkout-input" value="">
                        <div id="booking-validation" class="booking-alert booking-alert-info">Select your dates to unlock direct checkout.</div>
                        <button type="submit" class="btn booking-btn" id="booking-submit" disabled>Continue to Checkout</button>
                    </form>

                    <p class="booking-followup-note">
                        The full address and direct contact details are shared after your booking is confirmed.
                    </p>

                    <a href="<?php echo $escape($property['airbnb_url']); ?>" class="text-link booking-fallback-link" target="_blank" rel="noopener noreferrer">
                        Prefer Airbnb instead? Book on Airbnb
                    </a>
                </div>
            </aside>
        </div>
    </section>

    <footer class="bg-dark full-bleed">
        <div class="container footer-shell">
            <div class="footer-branding">
                <h2>Holland <i>Homes</i></h2>
                <p>Curated vacation rentals on the Oregon Coast and in Palm Springs.</p>
            </div>

            <div class="footer-columns">
                <div class="footer-links">
                    <h4>Portfolio</h4>
                    <ul>
                        <?php foreach ($properties as $slug => $item): ?>
                            <li><a href="property.php?id=<?php echo $escape($slug); ?>"><?php echo $escape($item['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4>Source</h4>
                    <ul>
                        <li><a href="<?php echo $escape($property['airbnb_url']); ?>" target="_blank" rel="noopener noreferrer">Airbnb Listing</a></li>
                        <li><a href="index.php#snapshot">Homepage Snapshot</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Holland Homes. All rights reserved.</p>
                <p>Property details sourced from verified Airbnb listings.</p>
                <p><a href="dashboard.php" class="admin-footer-link">Admin Login</a></p>
            </div>
        </div>
    </footer>

    <?php if (!empty($galleryPhotos)): ?>
        <div class="lightbox" id="gallery-lightbox" aria-hidden="true">
            <div class="lightbox-inner">
                <button type="button" class="lightbox-close" id="lightbox-close" aria-label="Close image viewer">&times;</button>
                <button type="button" class="lightbox-prev" id="lightbox-prev" aria-label="Previous image">&#8249;</button>

                <div class="lightbox-image-wrap">
                    <img src="" alt="" class="lightbox-image" id="lightbox-image">
                </div>

                <button type="button" class="lightbox-next" id="lightbox-next" aria-label="Next image">&#8250;</button>
                <div class="lightbox-counter" id="lightbox-counter"></div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    window.priceLabsData = <?php echo json_encode($priceLabsCalendarData); ?>;
    window.blockedDates = <?php echo json_encode($blockedDates); ?>;
    window.defaultBookingMinStay = <?php echo json_encode($minStay); ?>;
    window.calendarLoaded = true;
</script>
<script src="js/calendar.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const header = document.getElementById('main-header');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 24) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('.fade-in-section').forEach((element) => observer.observe(element));

        setTimeout(() => {
            if (typeof BookingCalendar !== 'undefined') {
                window.bookingCalendar = new BookingCalendar();
            }
        }, 250);

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (!targetId || targetId === '#') return;
                const target = document.querySelector(targetId);
                if (!target) return;
                e.preventDefault();
                const y = target.getBoundingClientRect().top + window.scrollY - 90;
                window.scrollTo({ top: y, behavior: 'smooth' });
            });
        });

        const galleryButtons = Array.from(document.querySelectorAll('[data-gallery-src]'));
        const galleryShell = document.querySelector('.gallery-shell');
        const seeMoreButton = document.getElementById('gallery-see-more');
        const lightbox = document.getElementById('gallery-lightbox');
        const lightboxImage = document.getElementById('lightbox-image');
        const lightboxClose = document.getElementById('lightbox-close');
        const lightboxPrev = document.getElementById('lightbox-prev');
        const lightboxNext = document.getElementById('lightbox-next');
        const lightboxCounter = document.getElementById('lightbox-counter');

        if (galleryButtons.length && lightbox && lightboxImage && lightboxClose && lightboxPrev && lightboxNext && lightboxCounter) {
            const galleryItems = galleryButtons.map((button, index) => {
                const img = button.querySelector('img');
                return {
                    src: button.getAttribute('data-gallery-src'),
                    alt: img ? img.getAttribute('alt') : `Gallery image ${index + 1}`
                };
            });

            let currentIndex = 0;

            const updateLightbox = () => {
                const item = galleryItems[currentIndex];
                if (!item) return;
                lightboxImage.src = item.src;
                lightboxImage.alt = item.alt;
                lightboxCounter.textContent = `${currentIndex + 1} / ${galleryItems.length}`;
            };

            const openLightbox = (index) => {
                currentIndex = index;
                updateLightbox();
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            const closeLightbox = () => {
                lightbox.classList.remove('is-open');
                lightbox.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            const showPrev = () => {
                currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
                updateLightbox();
            };

            const showNext = () => {
                currentIndex = (currentIndex + 1) % galleryItems.length;
                updateLightbox();
            };

            galleryButtons.forEach((button, index) => {
                button.addEventListener('click', () => openLightbox(index));
            });

            lightboxClose.addEventListener('click', closeLightbox);
            lightboxPrev.addEventListener('click', showPrev);
            lightboxNext.addEventListener('click', showNext);

            lightbox.addEventListener('click', (event) => {
                if (event.target === lightbox) {
                    closeLightbox();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (!lightbox.classList.contains('is-open')) return;

                if (event.key === 'Escape') {
                    closeLightbox();
                } else if (event.key === 'ArrowLeft') {
                    showPrev();
                } else if (event.key === 'ArrowRight') {
                    showNext();
                }
            });
        }

        if (galleryShell && seeMoreButton) {
            const collapsedThumbs = galleryShell.querySelectorAll('.media-thumb-collapsed');
            const hiddenCount = Number(seeMoreButton.getAttribute('data-hidden-count') || '0');
            let expanded = false;

            const syncSeeMoreState = () => {
                collapsedThumbs.forEach((thumb) => {
                    thumb.style.display = expanded ? '' : 'none';
                });
                seeMoreButton.textContent = expanded
                    ? 'Show fewer photos'
                    : `See more photos (${hiddenCount})`;
            };

            seeMoreButton.addEventListener('click', () => {
                expanded = !expanded;
                syncSeeMoreState();
            });

            syncSeeMoreState();
        }
    });
</script>
</body>
</html>
