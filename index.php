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
                <a href="#collection">Collection</a>
                <a href="#properties">Residences</a>
                <?php if ($hasReviews): ?><a href="#reviews">Reviews</a><?php endif; ?>
                <a href="#contact">Book Direct</a>
            </nav>
        </div>
        <div class="mobile-nav-panel" id="mobile-nav-panel" hidden>
            <div class="container mobile-nav-panel-inner">
                <a href="#collection">Collection</a>
                <a href="#properties">Residences</a>
                <?php if ($hasReviews): ?><a href="#reviews">Reviews</a><?php endif; ?>
                <a href="#contact">Book Direct</a>
            </div>
        </div>
    </header>

    <main class="home-main">
        <section class="home-hero full-bleed">
            <div class="container home-hero-grid">
                <div class="home-hero-copy fade-up">
                    <span class="eyebrow eyebrow-dark">Curated direct-book collection</span>
                    <p class="home-hero-kicker">Rockaway Beach, Oregon and Palm Springs, California</p>
                    <h1>A smaller collection of homes with a sharper point of view.</h1>
                    <p class="home-hero-summary">Holland Homes pairs a spa-forward beach house, a quiet dog-friendly chalet, and a design-led Palm Springs villa under one hosting standard, with direct booking that feels cleaner than a marketplace detour.</p>
                    <div class="home-hero-proof">
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
                            <strong><span class="airbnb-star">&#9733;</span> <?php echo $escape($featuredListing['rating'] ?? '5.0'); ?></strong>
                        </div>
                    </div>
                    <div class="home-hero-actions">
                        <a href="#properties" class="btn">View the collection</a>
                        <a href="#contact" class="home-link-arrow">How direct booking works</a>
                    </div>
                </div>
                <div class="home-hero-media fade-up">
                    <figure class="home-hero-stage">
                        <img src="<?php echo $escape($homeHeroBackdrop); ?>" alt="Holland Homes signature collection hero">
                        <figcaption class="home-hero-stage-caption">
                            <span>Holland Homes collection</span>
                            <strong>Direct-book stays across Rockaway Beach and Palm Springs.</strong>
                        </figcaption>
                    </figure>
                    <div class="home-hero-rail">
                        <?php foreach (array_slice($homepageSlugs, 0, 3) as $slug): ?>
                            <?php
                                $railProperty = $properties[$slug] ?? [];
                                $railImage = $propertyImageSets[$slug][0] ?? hh_main_hero_image($settings);
                            ?>
                            <article class="home-hero-property-card">
                                <div class="home-hero-property-image">
                                    <img src="<?php echo $escape($railImage); ?>" alt="<?php echo $escape($railProperty['name'] ?? 'Property'); ?>">
                                </div>
                                <div class="home-hero-property-body">
                                    <span><?php echo $escape($railProperty['name'] ?? ucfirst($slug)); ?></span>
                                    <strong><?php echo $escape($positioningLabels[$slug] ?? ($regionLabels[$slug] ?? 'Signature stay')); ?></strong>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="container home-hero-subrail fade-in-section">
                <div class="home-hero-subrail-copy">
                    <p>Book direct with live calendar validation, secure Stripe checkout, and fewer platform fees standing between the guest and the stay.</p>
                    <p>Address details and the direct host line are released after booking confirmation so the booking path stays private and orderly.</p>
                </div>
                <div class="home-hero-subrail-mark">Superhost managed</div>
            </div>
        </section>

        <section id="collection" class="home-collection-intro section-padding">
            <div class="container home-collection-intro-grid">
                <div class="section-heading fade-in-section home-section-lockup">
                    <span class="eyebrow eyebrow-dark">The collection</span>
                    <h2>Built for guests choosing a mood, not just a floor plan.</h2>
                </div>
                <div class="home-collection-text fade-in-section">
                    <p>Some trips call for a sauna, hot tub, cold plunge, and enough room for the whole group. Some call for a quieter coast reset with a dog at your feet. Others should feel unmistakably Palm Springs from the first image to the final night. The portfolio stays intentionally tight so each residence keeps its own personality.</p>
                </div>
                <div id="snapshot" class="home-ledger-list fade-in-section">
                    <?php foreach ($homepageSlugs as $index => $slug): ?>
                        <?php
                            $property = $properties[$slug] ?? [];
                            $listing = $listingSnapshots[$slug] ?? [];
                            $locationLabel = $regionLabels[$slug] ?? ($listing['location'] ?? 'Featured destination');
                            $listingType = ($listing['listing_label'] ?? '') ?: ($listing['listing_type'] ?? 'Entire place');
                            $reviewsCount = (int) ($listing['reviews_count'] ?? 0);
                        ?>
                        <article class="home-ledger-card">
                            <div class="home-ledger-index"><?php echo $escape(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></div>
                            <div class="home-ledger-main">
                                <span class="journey-step"><?php echo $escape($locationLabel); ?></span>
                                <h3><?php echo $escape($property['name'] ?? 'Property'); ?></h3>
                                <p><?php echo $escape($property['summary'] ?? 'A thoughtfully designed stay with a distinct sense of place.'); ?></p>
                            </div>
                            <div class="home-ledger-meta">
                                <span><?php echo $escape($positioningLabels[$slug] ?? 'Signature stay'); ?></span>
                                <span><?php echo $escape($listingType); ?></span>
                                <span><?php echo $escape(hh_airbnb_fact($listing['bedrooms'] ?? 0, 'bedroom') . ' / ' . hh_airbnb_fact($listing['baths'] ?? 0, 'bath')); ?></span>
                                <span><span class="airbnb-star">&#9733;</span> <?php echo $escape($listing['rating'] ?? '5.0'); ?> · <?php echo $escape((string) $reviewsCount); ?> reviews</span>
                            </div>
                            <a href="property.php?id=<?php echo $escape($slug); ?>" class="text-link home-ledger-link">Explore residence</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="properties" class="home-residences section-padding">
            <div class="container">
                <?php foreach ($homepageSlugs as $index => $slug): ?>
                    <?php
                        $property = $properties[$slug] ?? [];
                        $listing = $listingSnapshots[$slug] ?? [];
                        $imageSet = $propertyImageSets[$slug] ?? [hh_main_hero_image($settings), hh_main_hero_image($settings), hh_main_hero_image($settings)];
                        $locationLabel = $regionLabels[$slug] ?? ($listing['location'] ?? 'Featured destination');
                        $details = array_slice($property['detail_blocks'] ?? [], 0, 2);
                    ?>
                    <article class="residence-feature fade-in-section<?php echo $index % 2 === 1 ? ' alt' : ''; ?>">
                        <div class="residence-feature-media">
                            <figure class="residence-media-main">
                                <img src="<?php echo $escape($imageSet[0] ?? hh_main_hero_image($settings)); ?>" alt="<?php echo $escape($property['name'] ?? 'Property'); ?> main photo">
                            </figure>
                            <div class="residence-media-stack">
                                <figure class="residence-media-secondary">
                                    <img src="<?php echo $escape($imageSet[1] ?? $imageSet[0] ?? hh_main_hero_image($settings)); ?>" alt="<?php echo $escape($property['name'] ?? 'Property'); ?> detail photo">
                                </figure>
                                <figure class="residence-media-tertiary">
                                    <img src="<?php echo $escape($imageSet[2] ?? $imageSet[0] ?? hh_main_hero_image($settings)); ?>" alt="<?php echo $escape($property['name'] ?? 'Property'); ?> gallery photo">
                                </figure>
                            </div>
                        </div>
                        <div class="residence-feature-copy">
                            <div class="residence-feature-heading">
                                <span class="eyebrow eyebrow-dark"><?php echo $escape($locationLabel); ?></span>
                                <h2><?php echo $escape($property['name'] ?? 'Property'); ?></h2>
                                <p><?php echo $escape($property['summary'] ?? 'A thoughtfully designed stay with a distinct sense of place.'); ?></p>
                            </div>
                            <div class="residence-ledger">
                                <div class="residence-ledger-item">
                                    <span>Positioning</span>
                                    <strong><?php echo $escape($positioningLabels[$slug] ?? 'Signature stay'); ?></strong>
                                </div>
                                <div class="residence-ledger-item">
                                    <span>Listing type</span>
                                    <strong><?php echo $escape(($listing['listing_label'] ?? '') ?: ($listing['listing_type'] ?? 'Entire place')); ?></strong>
                                </div>
                                <div class="residence-ledger-item">
                                    <span>Rooms</span>
                                    <strong><?php echo $escape(hh_airbnb_fact($listing['bedrooms'] ?? 0, 'bedroom') . ' · ' . hh_airbnb_fact($listing['beds'] ?? 0, 'bed') . ' · ' . hh_airbnb_fact($listing['baths'] ?? 0, 'bath')); ?></strong>
                                </div>
                                <div class="residence-ledger-item">
                                    <span>Guest score</span>
                                    <strong><span class="airbnb-star">&#9733;</span> <?php echo $escape($listing['rating'] ?? '5.0'); ?> from <?php echo $escape((string) ($listing['reviews_count'] ?? 0)); ?> reviews</strong>
                                </div>
                            </div>
                            <div class="residence-detail-list">
                                <?php foreach ($details as $detail): ?>
                                    <div class="residence-detail-item">
                                        <strong><?php echo $escape($detail['title'] ?? 'Standout detail'); ?></strong>
                                        <p><?php echo $escape($detail['text'] ?? 'Thoughtful details shape the experience throughout the stay.'); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <blockquote class="residence-quote">
                                <p>"<?php echo $escape($property['guest_review'] ?? 'A memorable stay from start to finish.'); ?>"</p>
                                <footer>
                                    <span><?php echo $escape($property['guest_review_name'] ?? 'Recent guest'); ?></span>
                                    <span><?php echo $escape($property['name'] ?? 'Property'); ?></span>
                                </footer>
                            </blockquote>
                            <div class="residence-actions">
                                <a href="property.php?id=<?php echo $escape($slug); ?>" class="btn">View <?php echo $escape($property['name'] ?? 'Property'); ?></a>
                                <div class="residence-review-mark"><?php echo $escape($property['badges'][0] ?? 'Guest-loved stay'); ?></div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="home-booking-values bg-dark full-bleed">
            <div class="container home-booking-values-grid section-padding">
                <div class="section-heading light fade-in-section">
                    <span class="eyebrow">Book direct</span>
                    <h2>The direct path should feel more considered, not more makeshift.</h2>
                    <p>Holland Homes is moving the booking experience away from Airbnb without dropping the standards guests expect from a high-trust stay.</p>
                </div>
                <div class="home-booking-values-list fade-in-section">
                    <?php foreach ($bookingValues as $value): ?>
                        <article class="booking-value-row">
                            <span><?php echo $escape($value['number']); ?></span>
                            <div>
                                <h3><?php echo $escape($value['title']); ?></h3>
                                <p><?php echo $escape($value['text']); ?></p>
                            </div>
                        </article>
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

        <section id="contact" class="home-final-cta section-padding">
            <div class="container home-final-cta-shell fade-in-section">
                <div>
                    <span class="eyebrow eyebrow-dark">Start with the property</span>
                    <h2>Choose the house that fits the trip, then book without the marketplace detour.</h2>
                </div>
                <div class="home-final-cta-copy">
                    <p>Each residence has its own live calendar, pricing behavior, and booking rules. Once the dates make sense, checkout stays simple, secure, and direct.</p>
                </div>
                <div class="home-final-cta-actions">
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
                        <li><a href="#collection">The Collection</a></li>
                        <?php if ($hasReviews): ?><li><a href="#reviews">Guest Reviews</a></li><?php endif; ?>
                        <li><a href="#contact">Book Direct</a></li>
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
