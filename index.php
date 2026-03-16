<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/AirbnbScraper.php';
require_once __DIR__ . '/includes/OutscraperReviews.php';

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
foreach (array_keys($properties) as $slug) {
    if (!in_array($slug, $homepageSlugs, true)) {
        $homepageSlugs[] = $slug;
    }
}

$regionLabels = [
    'home' => 'Rockaway Beach, Oregon',
    'chalet' => 'Rockaway Beach, Oregon',
    'villa' => 'Palm Springs, California',
];

$positioningLabels = [
    'home' => 'Spa-forward group retreat',
    'chalet' => 'Quiet dog-friendly chalet',
    'villa' => 'Design-led desert villa',
];

$listingSnapshots = [];
$propertyImageSets = [];
$reviewTotal = 0;
$featuredSlug = '';
$featuredReviews = -1;

foreach ($homepageSlugs as $slug) {
    $property = $properties[$slug] ?? null;
    if (!$property) {
        continue;
    }

    $liveData = $scraper->getListingInfo($property['airbnb_url']);
    $listingSnapshots[$slug] = hh_merge_airbnb_snapshot($property['fallback_airbnb'], $liveData);
    $reviewCount = (int) ($listingSnapshots[$slug]['reviews_count'] ?? 0);
    $reviewTotal += $reviewCount;

    if ($reviewCount > $featuredReviews) {
        $featuredReviews = $reviewCount;
        $featuredSlug = $slug;
    }

    $images = [];
    foreach ([
        hh_property_hero_image($property, $settings),
        $property['card_image'] ?? '',
        $property['hero_image'] ?? '',
    ] as $candidate) {
        $candidate = trim((string) $candidate);
        if ($candidate !== '' && !in_array($candidate, $images, true)) {
            $images[] = $candidate;
        }
    }

    $folder = __DIR__ . '/' . ucfirst($slug) . '/Photos';
    if (is_dir($folder)) {
        $files = scandir($folder);
        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || strpos($file, '.DS_Store') !== false) {
                    continue;
                }
                if (!preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $file)) {
                    continue;
                }

                $relative = ucfirst($slug) . '/Photos/' . $file;
                if (!in_array($relative, $images, true)) {
                    $images[] = $relative;
                }
            }
        }
    }

    if (empty($images)) {
        $images[] = hh_main_hero_image($settings);
    }

    while (count($images) < 3) {
        $images[] = $images[count($images) - 1];
    }

    $propertyImageSets[$slug] = array_slice($images, 0, 3);
}

$defaultSlug = $featuredSlug !== '' ? $featuredSlug : ($homepageSlugs[0] ?? array_key_first($properties));
$featuredListing = $listingSnapshots[$defaultSlug] ?? [];
$featuredProperty = $properties[$defaultSlug] ?? reset($properties);

$reviewsApi = new OutscraperReviews(OUTSCRAPER_API_KEY);
$recentReviews = $reviewsApi->getAllRecentReviews(6);
$hasReviews = !empty($recentReviews);
$homeHeroBackdrop = file_exists(__DIR__ . '/assets/hh_hero.jpg') ? 'assets/hh_hero.jpg' : hh_main_hero_image($settings);

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
    <title>Holland Homes | Elevated Vacation Rentals in Rockaway Beach & Palm Springs</title>
    <meta name="description" content="Discover elevated vacation rentals by Holland Homes in Rockaway Beach, Oregon and Palm Springs, California. Book direct with a cleaner, more considered experience.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="home-page">
    <header id="main-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">Holland<i>Homes</i></a>
            <button type="button" class="mobile-nav-toggle" id="mobile-nav-toggle" aria-expanded="false" aria-controls="mobile-nav-panel" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav class="nav-links">
                <a href="#properties">Properties</a>
                <?php if ($hasReviews): ?><a href="#reviews">Reviews</a><?php endif; ?>
                <a href="about.php">About Us</a>
            </nav>
        </div>
        <div class="mobile-nav-panel" id="mobile-nav-panel" hidden>
            <div class="container mobile-nav-panel-inner">
                <a href="#properties">Properties</a>
                <?php if ($hasReviews): ?><a href="#reviews">Reviews</a><?php endif; ?>
                <a href="about.php">About Us</a>
            </div>
        </div>
    </header>

    <main class="home-main">
        <section class="home-hero-premium full-bleed">
            <div class="home-hero-bg">
                <img src="<?php echo $escape($homeHeroBackdrop); ?>" alt="Holland Homes — Elevated Vacation Rentals">
            </div>
            <div class="home-hero-overlay">
                <div class="container hero-content">
                    <div class="hero-text-wrap">
                        <span class="eyebrow">Curated Collection</span>
                        <h1>Holland <i>Homes</i></h1>
                        <p>Elevated vacation rentals with a stronger sense of place.</p>
                        <div class="hero-actions">
                            <a href="#properties" class="btn">Explore Properties</a>
                            <a href="about.php" class="btn btn-outline">Our Story</a>
                        </div>
                    </div>
                    <div class="hero-stats-grid">
                        <div class="hero-stat-item">
                            <span class="hero-stat-num">03</span>
                            <span class="hero-stat-label">Boutique Stays</span>
                        </div>
                        <div class="hero-stat-item">
                            <span class="hero-stat-num">5.0</span>
                            <span class="hero-stat-label">Avg Rating</span>
                        </div>
                    </div>
                </div>
                <div class="hero-scroll-indicator">
                    <span class="scroll-line"></span>
                </div>
            </div>
        </section>

        <section id="properties" class="home-properties section-padding">
            <div class="container">
                <div class="home-property-cards fade-in-section">
                    <?php foreach ($homepageSlugs as $index => $slug): ?>
                        <?php
                            $property = $properties[$slug] ?? [];
                            $listing = $listingSnapshots[$slug] ?? [];
                            $locationLabel = $regionLabels[$slug] ?? ($listing['location'] ?? 'Featured destination');
                            $listingType = ($listing['listing_label'] ?? '') ?: ($listing['listing_type'] ?? 'Entire place');
                            $reviewsCount = (int) ($listing['reviews_count'] ?? 0);
                            $cardImage = $propertyImageSets[$slug][0] ?? hh_main_hero_image($settings);
                        ?>
                        <a href="property.php?id=<?php echo $escape($slug); ?>" class="home-property-card-link">
                            <article class="home-property-card-full">
                                <div class="home-property-card-image">
                                    <img src="<?php echo $escape($cardImage); ?>" alt="<?php echo $escape($property['name'] ?? 'Property'); ?>" loading="lazy">
                                </div>
                                <div class="home-property-card-body">
                                    <span class="journey-step"><?php echo $escape($locationLabel); ?></span>
                                    <h3><?php echo $escape($property['name'] ?? 'Property'); ?></h3>
                                    <p><?php echo $escape($property['summary'] ?? 'A thoughtfully designed stay with a distinct sense of place.'); ?></p>
                                    <div class="home-property-card-meta">
                                        <span><?php echo $escape($listingType); ?></span>
                                        <span><?php echo $escape(hh_airbnb_fact($listing['bedrooms'] ?? 0, 'bedroom') . ' / ' . hh_airbnb_fact($listing['baths'] ?? 0, 'bath')); ?></span>
                                        <span><span class="airbnb-star">&#9733;</span> <?php echo $escape($listing['rating'] ?? '5.0'); ?> · <?php echo $escape((string) $reviewsCount); ?> reviews</span>
                                    </div>
                                </div>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>


        <?php if ($hasReviews): ?>
            <section id="reviews" class="home-review-gallery full-bleed">
                <div class="container home-review-gallery-grid section-padding">
                    <div class="home-review-intro fade-in-section">
                        <span class="eyebrow eyebrow-dark">Guest proof</span>
                        <h2>Strong homes only matter if the hosting matches.</h2>
                        <p>Reviews are still synced from verified Airbnb stays while direct booking expands. They remain the clearest proof that the experience holds up after arrival.</p>
                        <div class="home-review-metrics">
                            <div>
                                <span>Total reviews</span>
                                <strong><?php echo $escape(number_format($reviewTotal)); ?></strong>
                            </div>
                            <div>
                                <span>Featured property</span>
                                <strong><?php echo $escape($featuredProperty['name'] ?? 'Signature stay'); ?></strong>
                            </div>
                        </div>
                    </div>
                    <article class="home-review-spotlight fade-in-section">
                        <p class="home-review-spotlight-quote">"<?php echo $escape($featuredProperty['guest_review'] ?? 'A memorable stay from start to finish.'); ?>"</p>
                        <div class="home-review-spotlight-meta">
                            <span><?php echo $escape($featuredProperty['guest_review_name'] ?? 'Recent guest'); ?></span>
                            <span><?php echo $escape($featuredProperty['name'] ?? 'Property'); ?></span>
                            <span><span class="airbnb-star">&#9733;</span> <?php echo $escape($featuredListing['rating'] ?? '5.0'); ?></span>
                        </div>
                    </article>
                    <div class="home-review-column fade-in-section">
                        <?php foreach (array_slice($recentReviews, 0, 3) as $review): ?>
                            <?php
                                $reviewPropertySlug = $review['property_slug'] ?? '';
                                $reviewPropertyName = isset($properties[$reviewPropertySlug]) ? $properties[$reviewPropertySlug]['name'] : 'Holland Homes';
                            ?>
                            <article class="home-review-snippet">
                                <a href="property.php?id=<?php echo $escape($reviewPropertySlug ?: $defaultSlug); ?>"><?php echo $escape($reviewPropertyName); ?></a>
                                <p>"<?php echo $escape($review['text'] ?? 'Great stay.'); ?>"</p>
                                <strong><?php echo $escape($review['author'] ?? 'Guest'); ?><?php if (!empty($review['date'])): ?> · <?php echo $escape(date('M Y', strtotime($review['date']))); ?><?php endif; ?></strong>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

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
                        <li><a href="#properties">Our Properties</a></li>
                        <?php if ($hasReviews): ?><li><a href="#reviews">Guest Reviews</a></li><?php endif; ?>
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

            const fadeObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, { threshold: 0.12 });

            document.querySelectorAll('.fade-in-section').forEach((element) => {
                fadeObserver.observe(element);
            });

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
                    if (window.innerWidth > 760) {
                        closeMobileNav();
                    }
                });
            }
        });
    </script>
</body>
</html>
