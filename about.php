<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/AirbnbScraper.php';

$data = hh_site_data();
$site = $data['site'];
$properties = $data['properties'];
$settings = hh_load_site_settings();
$scraper = new AirbnbScraper();
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$homepageSlugs = [];
foreach (['home', 'chalet', 'villa'] as $preferredSlug) {
    if (isset($properties[$preferredSlug])) {
        $homepageSlugs[] = $preferredSlug;
    }
}

$reviewTotal = 0;
$featuredRating = '5.0';
foreach ($homepageSlugs as $slug) {
    $property = $properties[$slug] ?? null;
    if (!$property) continue;
    $liveData = $scraper->getListingInfo($property['airbnb_url']);
    $snapshot = hh_merge_airbnb_snapshot($property['fallback_airbnb'], $liveData);
    $reviewTotal += (int) ($snapshot['reviews_count'] ?? 0);
    if ($slug === 'home') {
        $featuredRating = $snapshot['rating'] ?? '4.99';
    }
}

$bookingValues = [
    [
        'number' => '01',
        'title' => 'Availability is checked before payment',
        'text' => 'Guests only move into checkout when the dates clear blocked nights and the current stay rules for that property.',
    ],
    [
        'number' => '02',
        'title' => 'Secure direct checkout',
        'text' => 'Payment is handled through Stripe so the booking path feels intentional, not like a side door off a marketplace.',
    ],
    [
        'number' => '03',
        'title' => 'Protection stays transparent',
        'text' => 'Checkout gives guests a clear choice between a refundable security deposit and Waivo damage protection.',
    ],
    [
        'number' => '04',
        'title' => 'Arrival details are released after booking',
        'text' => 'The property address and direct host phone line are shared once the reservation is confirmed.',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Holland Homes</title>
    <meta name="description" content="Holland Homes pairs a spa-forward beach house, a quiet dog-friendly chalet, and a design-led Palm Springs villa under one hosting standard.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="about-page">
    <header id="main-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">Holland<i>Homes</i></a>
            <button type="button" class="mobile-nav-toggle" id="mobile-nav-toggle" aria-expanded="false" aria-controls="mobile-nav-panel" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav class="nav-links">
                <a href="index.php#properties">Properties</a>
                <a href="about.php">About Us</a>
            </nav>
        </div>
        <div class="mobile-nav-panel" id="mobile-nav-panel" hidden>
            <div class="container mobile-nav-panel-inner">
                <a href="index.php#properties">Properties</a>
                <a href="about.php">About Us</a>
            </div>
        </div>
    </header>

    <main>
        <section class="about-hero">
            <div class="container about-content">
                <span class="eyebrow eyebrow-dark">About Holland Homes</span>
                <h1>A smaller collection of homes with a sharper point of view.</h1>
                <p class="about-tagline">Holland Homes pairs a spa-forward beach house, a quiet dog-friendly chalet, and a design-led Palm Springs villa under one hosting standard, with direct booking that feels cleaner than a marketplace detour.</p>

                <div class="about-stats">
                    <div>
                        <span>Collection</span>
                        <strong><?php echo $escape((string) count($homepageSlugs)); ?> residences</strong>
                    </div>
                    <div>
                        <span>Guest proof</span>
                        <strong><?php echo $escape(number_format($reviewTotal)); ?> reviews</strong>
                    </div>
                    <div>
                        <span>Featured rating</span>
                        <strong><span class="airbnb-star">&#9733;</span> <?php echo $escape($featuredRating); ?></strong>
                    </div>
                </div>

                <div class="about-body">
                    <p>Certain getaways demand the luxury of a sauna, hot tub, cold plunge, and ample space for everyone to gather. Others invite a serene coastal retreat with a furry companion by your side. Then there are those that embody the quintessential Palm Springs vibe from the very first glimpse to the last evening.</p>
                    <p>The selection remains deliberately curated to ensure that each property retains its unique charm. Every home is Superhost-managed with a commitment to the kind of hosting that earns repeat guests.</p>

                    <h2>How direct booking works</h2>
                    <p>Book direct with live calendar validation, secure Stripe checkout, and fewer platform fees standing between the guest and the stay. Address details and the direct host line are released after booking confirmation so the booking path stays private and orderly.</p>

                    <div class="about-values">
                        <?php foreach ($bookingValues as $value): ?>
                            <article class="about-value-row">
                                <span><?php echo $escape($value['number']); ?></span>
                                <div>
                                    <h3><?php echo $escape($value['title']); ?></h3>
                                    <p><?php echo $escape($value['text']); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="about-cta">
                    <?php foreach ($homepageSlugs as $slug): ?>
                        <?php $property = $properties[$slug] ?? []; ?>
                        <a href="property.php?id=<?php echo $escape($slug); ?>" class="btn"><?php echo $escape($property['name'] ?? ucfirst($slug)); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-dark full-bleed">
        <div class="container footer-shell">
            <div class="footer-branding">
                <h2>Holland <i>Homes</i></h2>
                <p>Elevated vacation rentals with a stronger sense of place.</p>
            </div>
            <div class="footer-columns">
                <div class="footer-links">
                    <h4>Properties</h4>
                    <ul>
                        <?php foreach ($homepageSlugs as $slug): ?>
                            <?php $property = $properties[$slug] ?? []; ?>
                            <li><a href="property.php?id=<?php echo $escape($slug); ?>"><?php echo $escape($property['name'] ?? ucfirst($slug)); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Quick links</h4>
                    <ul>
                        <li><a href="index.php#properties">Our Properties</a></li>
                        <li><a href="about.php">About Us</a></li>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.getElementById('main-header');
            const mobileNavToggle = document.getElementById('mobile-nav-toggle');
            const mobileNavPanel = document.getElementById('mobile-nav-panel');

            if (header) {
                const toggleHeaderState = () => {
                    if (window.scrollY > 18) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                };
                toggleHeaderState();
                window.addEventListener('scroll', toggleHeaderState, { passive: true });
            }

            if (mobileNavToggle && mobileNavPanel) {
                const closeMobileNav = () => {
                    mobileNavToggle.setAttribute('aria-expanded', 'false');
                    mobileNavPanel.hidden = true;
                    document.body.classList.remove('mobile-nav-open');
                };
                const openMobileNav = () => {
                    mobileNavToggle.setAttribute('aria-expanded', 'true');
                    mobileNavPanel.hidden = false;
                    document.body.classList.add('mobile-nav-open');
                };
                mobileNavToggle.addEventListener('click', () => {
                    if (mobileNavToggle.getAttribute('aria-expanded') === 'true') {
                        closeMobileNav();
                    } else {
                        openMobileNav();
                    }
                });
                mobileNavPanel.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', closeMobileNav);
                });
                window.addEventListener('resize', () => {
                    if (window.innerWidth > 760) closeMobileNav();
                });
            }
        });
    </script>
</body>
</html>
