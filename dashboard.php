<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/IcalAvailability.php';

$password = DASHBOARD_PASSWORD;
if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Incorrect password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$data = hh_site_data();
$properties = $data['properties'];
$settings = hh_load_site_settings();
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$successMessage = '';
$errorMessages = [];

if ($isLoggedIn && (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') && isset($_POST['action'])) {
    $action = (string) $_POST['action'];
    $propertySlug = isset($_POST['property_slug']) ? strtolower(trim((string) $_POST['property_slug'])) : '';
    $validExt = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

    $storeUploadFile = static function (array $file, $targetDir, $prefix) use ($validExt, &$errorMessages) {
        if (!isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
            $errorMessages[] = 'No file uploaded.';
            return '';
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errorMessages[] = 'Upload failed. Please try again.';
            return '';
        }
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $validExt, true)) {
            $errorMessages[] = 'Only jpg, jpeg, png, webp, and avif files are allowed.';
            return '';
        }
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = $prefix . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $ext;
        $absolutePath = rtrim($targetDir, '/') . '/' . $fileName;
        if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
            $errorMessages[] = 'Unable to save uploaded file.';
            return '';
        }

        return $fileName;
    };

    $storeUpload = static function ($fileKey, $targetDir, $prefix) use (&$storeUploadFile, &$errorMessages) {
        if (!isset($_FILES[$fileKey]) || !is_array($_FILES[$fileKey])) {
            $errorMessages[] = 'No file uploaded.';
            return '';
        }

        return $storeUploadFile($_FILES[$fileKey], $targetDir, $prefix);
    };

    if ($action === 'save_listing_booking_settings') {
        $submittedMinNights = $_POST['min_nights'] ?? [];
        $submittedIcalUrls = $_POST['ical_url'] ?? [];

        if (!is_array($submittedMinNights) || !is_array($submittedIcalUrls)) {
            $errorMessages[] = 'Invalid booking settings payload.';
        } else {
            if (!isset($settings['listing_booking']) || !is_array($settings['listing_booking'])) {
                $settings['listing_booking'] = [];
            }

            foreach ($properties as $slug => $property) {
                $rawMinNights = $submittedMinNights[$slug] ?? hh_default_min_nights($slug);
                $minNights = (int) $rawMinNights;
                if ($minNights < 1) {
                    $minNights = hh_default_min_nights($slug);
                }

                $icalUrl = trim((string) ($submittedIcalUrls[$slug] ?? ''));
                if ($icalUrl !== '' && filter_var($icalUrl, FILTER_VALIDATE_URL) === false) {
                    $errorMessages[] = ucfirst($slug) . ': iCal URL is not a valid URL.';
                }

                $settings['listing_booking'][$slug] = [
                    'min_nights' => $minNights,
                    'ical_url' => $icalUrl,
                ];
            }

            if (empty($errorMessages)) {
                hh_save_site_settings($settings);
                $successMessage = 'Booking settings updated for all listings.';
            }
        }
    } elseif ($action === 'upload_main_hero') {
        $name = $storeUpload('main_hero', __DIR__ . '/assets/heroes', 'main-hero');
        if ($name !== '') {
            $settings['main_hero_image'] = 'assets/heroes/' . $name;
            hh_save_site_settings($settings);
            $successMessage = 'Main homepage hero image updated.';
        }
    } elseif ($action === 'refresh_ical_sync') {
        $refreshTarget = strtolower(trim((string) ($_POST['refresh_property_slug'] ?? 'all')));
        $availabilitySync = new IcalAvailability();
        $targets = [];

        if ($refreshTarget === 'all') {
            $targets = array_keys($properties);
        } elseif (isset($properties[$refreshTarget])) {
            $targets = [$refreshTarget];
        } else {
            $errorMessages[] = 'Please choose a valid property for iCal refresh.';
        }

        $refreshSummaries = [];
        foreach ($targets as $slug) {
            $bookingSettings = hh_listing_booking_settings($settings, $slug);
            $icalUrl = trim((string) ($bookingSettings['ical_url'] ?? ''));
            $propertyName = (string) ($properties[$slug]['name'] ?? ucfirst($slug));

            if ($icalUrl === '') {
                $errorMessages[] = $propertyName . ': iCal URL is not configured.';
                continue;
            }

            $result = $availabilitySync->getBlockedDates($icalUrl, $slug, true);
            $blockedCount = count($result['blocked_dates'] ?? []);
            $source = (string) ($result['source'] ?? 'unknown');
            $refreshSummaries[] = $propertyName . ' (' . $source . ', ' . $blockedCount . ' blocked dates)';
        }

        if (!empty($refreshSummaries)) {
            $successMessage = 'iCal refresh complete: ' . implode(' | ', $refreshSummaries);
        }
    } elseif (($action === 'upload_listing_photo' || $action === 'upload_listing_photos' || $action === 'upload_listing_hero' || $action === 'set_existing_listing_hero') && isset($properties[$propertySlug])) {
        $folder = ucfirst($propertySlug) . '/Photos';
        $absoluteFolder = __DIR__ . '/' . $folder;

        if ($action === 'upload_listing_photo') {
            $name = $storeUpload('listing_photo', $absoluteFolder, 'gallery');
            if ($name !== '') {
                $successMessage = 'Photo added to ' . ucfirst($propertySlug) . ' listing gallery.';
            }
        }

        if ($action === 'upload_listing_photos') {
            $batchUpload = $_FILES['listing_photos'] ?? null;
            if (!is_array($batchUpload) || !isset($batchUpload['name']) || !is_array($batchUpload['name'])) {
                $errorMessages[] = 'Choose at least one listing image to upload.';
            } else {
                $uploadedPaths = [];

                foreach ($batchUpload['name'] as $index => $originalName) {
                    if (trim((string) $originalName) === '') {
                        continue;
                    }

                    $singleFile = [
                        'name' => $batchUpload['name'][$index] ?? '',
                        'type' => $batchUpload['type'][$index] ?? '',
                        'tmp_name' => $batchUpload['tmp_name'][$index] ?? '',
                        'error' => $batchUpload['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $batchUpload['size'][$index] ?? 0,
                    ];

                    $fileName = $storeUploadFile($singleFile, $absoluteFolder, 'gallery');
                    if ($fileName !== '') {
                        $uploadedPaths[] = $folder . '/' . $fileName;
                    }
                }

                if (count($uploadedPaths) === 0 && empty($errorMessages)) {
                    $errorMessages[] = 'No listing images were uploaded.';
                }

                if (count($uploadedPaths) > 0) {
                    if (isset($_POST['set_first_as_hero']) && (string) $_POST['set_first_as_hero'] === '1') {
                        $settings['property_hero_overrides'][$propertySlug] = $uploadedPaths[0];
                        hh_save_site_settings($settings);
                        $successMessage = count($uploadedPaths) . ' image(s) uploaded to ' . ucfirst($propertySlug) . '. First image set as listing hero.';
                    } else {
                        $successMessage = count($uploadedPaths) . ' image(s) uploaded to ' . ucfirst($propertySlug) . '.';
                    }
                }
            }
        }

        if ($action === 'upload_listing_hero') {
            $name = $storeUpload('listing_hero', $absoluteFolder, 'hero');
            if ($name !== '') {
                $settings['property_hero_overrides'][$propertySlug] = $folder . '/' . $name;
                hh_save_site_settings($settings);
                $successMessage = ucfirst($propertySlug) . ' hero image updated.';
            }
        }

        if ($action === 'set_existing_listing_hero') {
            $chosen = trim((string) ($_POST['existing_hero_path'] ?? ''));
            $prefix = $folder . '/';
            if ($chosen === '' || strpos($chosen, $prefix) !== 0 || !file_exists(__DIR__ . '/' . $chosen)) {
                $errorMessages[] = 'Please choose a valid existing listing photo.';
            } else {
                $settings['property_hero_overrides'][$propertySlug] = $chosen;
                hh_save_site_settings($settings);
                $successMessage = ucfirst($propertySlug) . ' hero set from existing listing photo.';
            }
        }
    } elseif ($action !== 'upload_main_hero') {
        $errorMessages[] = 'Invalid property selection.';
    }

    $settings = hh_load_site_settings();
}

$pricelabsStatus = 'Pending';
$airbnbStatus = 'Pending';
$listingsData = [];
$reviewsData = [];
$systemAlerts = [];
$propertyPhotoOptions = [];
$bookingSettingsByProperty = [];
$calendarDiagnostics = [];

foreach ($properties as $slug => $property) {
    $bookingSettingsByProperty[$slug] = hh_listing_booking_settings($settings, $slug);
}

foreach ($properties as $slug => $property) {
    $folder = __DIR__ . '/' . ucfirst($slug) . '/Photos';
    $propertyPhotoOptions[$slug] = [];
    if (!is_dir($folder)) {
        continue;
    }
    $files = scandir($folder);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || strpos($file, '.DS_Store') !== false) {
            continue;
        }
        if (!preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $file)) {
            continue;
        }
        $propertyPhotoOptions[$slug][] = ucfirst($slug) . '/Photos/' . $file;
    }
}

if ($isLoggedIn) {
    require_once __DIR__ . '/includes/PriceLabsAPI.php';
    require_once __DIR__ . '/includes/AirbnbScraper.php';

    $pl = new PriceLabsAPI(PRICELABS_API_TOKEN);
    $plData = $pl->getListings();
    $pricelabsStatus = ($plData['source'] ?? 'fallback') === 'live' ? 'Live data connected' : 'Fallback pricing only';

    $scraper = new AirbnbScraper();
    $availabilitySync = new IcalAvailability();

    foreach ($properties as $key => $property) {
        $reviewsData[$key] = $scraper->getListingInfo($property['airbnb_url']);
        $listingsData[$key] = $pl->getListingDefaults($key);

        $icalUrl = trim((string) ($bookingSettingsByProperty[$key]['ical_url'] ?? ''));
        $diagnostic = $availabilitySync->getBlockedDates($icalUrl, $key);
        $diagnostic['blocked_count'] = count($diagnostic['blocked_dates'] ?? []);
        $calendarDiagnostics[$key] = $diagnostic;
    }

    $allReviewSources = array_map(static function ($item) {
        return $item['source'] ?? 'unverified';
    }, $reviewsData);
    $airbnbStatus = in_array('listing-page', $allReviewSources, true) ? 'Partial summary detected' : 'No verified review import';

    if (($plData['source'] ?? 'fallback') !== 'live') {
        $systemAlerts[] = 'PriceLabs is using fallback pricing. Verify your API token and listing IDs are correctly mapped in PriceLabs.';
    }

    if (!in_array('listing-page', $allReviewSources, true)) {
        $systemAlerts[] = 'Airbnb listing data could not be scraped. Fallback data from site configuration is being displayed instead.';
    }

    foreach ($properties as $slug => $property) {
        $icalUrl = trim((string) ($bookingSettingsByProperty[$slug]['ical_url'] ?? ''));
        if ($icalUrl === '') {
            $systemAlerts[] = 'Booked-date sync is not configured for ' . $property['name'] . '. Add the Airbnb iCal URL in Booking sync settings.';
            continue;
        }

        $source = (string) ($calendarDiagnostics[$slug]['source'] ?? 'unknown');
        if ($source === 'unavailable') {
            $systemAlerts[] = 'Booked-date sync failed for ' . $property['name'] . '. Check iCal URL and hosting network access.';
        }
    }
}

$cacheDir = __DIR__ . '/cache/';
$analyticsFile = $cacheDir . 'analytics.json';
$analytics = ['views' => 0, 'intents' => 0];
if (file_exists($analyticsFile)) {
    $stored = json_decode(file_get_contents($analyticsFile), true);
    if (is_array($stored)) {
        $analytics = $stored;
    }
}
$totalViews = (int) ($analytics['views'] ?? 0);
$bookingIntents = (int) ($analytics['intents'] ?? 0);
$conversionRate = $totalViews > 0 ? round(($bookingIntents / $totalViews) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Holland Homes</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-page">
<?php if (!$isLoggedIn): ?>
    <div class="dashboard-login-shell">
        <div class="login-box">
            <h1>Admin Login</h1>
            <p>Enter your password to access the admin dashboard.</p>
            <?php if (isset($error)): ?>
                <p class="form-error"><?php echo $escape($error); ?></p>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <input type="password" name="password" placeholder="Enter admin password">
                <button type="submit" class="btn">Login</button>
            </form>
            <a href="index.php" class="text-link">Back to website</a>
        </div>
    </div>
<?php else: ?>
    <div class="dashboard-shell">
        <div class="dashboard-topbar">
            <div>
                <span class="eyebrow eyebrow-dark">Admin</span>
                <h1>Property Dashboard</h1>
                <p class="form-note">Built for non-technical updates: follow the quick steps below.</p>
            </div>
            <div class="dashboard-actions">
                <a href="index.php" class="btn btn-secondary">View Site</a>
                <a href="?logout=1" class="btn">Logout</a>
            </div>
        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="notice-success"><?php echo $escape($successMessage); ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessages)): ?>
            <?php foreach ($errorMessages as $errorMessage): ?>
                <div class="notice-error"><?php echo $escape($errorMessage); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <section class="dashboard-card dashboard-help-card">
            <div class="section-heading slim">
                <span class="eyebrow eyebrow-dark">Quick start</span>
                <h2>How to update this website</h2>
            </div>
            <ol class="dashboard-steps">
                <li>Choose a property and upload one or more listing photos.</li>
                <li>Pick which image should be the hero image (large top image on the property page).</li>
                <li>Update minimum nights and optional calendar sync link in booking settings.</li>
            </ol>
        </section>

        <section class="dashboard-card">
            <div class="section-heading slim">
                <span class="eyebrow eyebrow-dark">Photos & visuals</span>
                <h2>Upload listing images and set hero photos</h2>
            </div>
            <div class="dashboard-form-grid">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <input type="hidden" name="action" value="upload_main_hero">
                    <h3>Main homepage hero</h3>
                    <p class="form-note">This image appears on the homepage hero section.</p>
                    <p class="form-note">Current file: <?php echo $escape(hh_main_hero_image($settings)); ?></p>
                    <input type="file" name="main_hero" accept=".jpg,.jpeg,.png,.webp,.avif" required>
                    <button type="submit" class="btn">Upload Main Hero</button>
                </form>

                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <input type="hidden" name="action" value="upload_listing_photos">
                    <h3>Upload listing photos</h3>
                    <label class="field-label" for="listing-media-property">Property</label>
                    <select name="property_slug" id="listing-media-property" class="dashboard-select" required>
                        <?php foreach ($properties as $slug => $property): ?>
                            <option value="<?php echo $escape($slug); ?>"><?php echo $escape($property['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-note" id="listing-media-count"></p>
                    <input type="file" name="listing_photos[]" accept=".jpg,.jpeg,.png,.webp,.avif" multiple required>
                    <label class="admin-checkbox-row">
                        <input type="checkbox" name="set_first_as_hero" value="1">
                        <span>Use the first uploaded image as this listing's hero image.</span>
                    </label>
                    <button type="submit" class="btn">Upload Listing Images</button>
                </form>

                <form method="POST" class="admin-form">
                    <input type="hidden" name="action" value="set_existing_listing_hero">
                    <h3>Set hero from existing listing photo</h3>
                    <label class="field-label" for="property-hero-select">Property</label>
                    <select name="property_slug" id="property-hero-select" class="dashboard-select" required>
                        <?php foreach ($properties as $slug => $property): ?>
                            <option value="<?php echo $escape($slug); ?>"><?php echo $escape($property['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="field-label" for="existing-hero-path">Choose photo</label>
                    <select name="existing_hero_path" id="existing-hero-path" class="dashboard-select" required></select>
                    <div class="existing-hero-preview-wrap" id="existing-hero-preview-wrap">
                        <img src="" alt="Selected hero preview" id="existing-hero-preview">
                    </div>
                    <button type="submit" class="btn">Use Existing Photo</button>
                </form>
            </div>
            <div class="listing-media-grid">
                <?php foreach ($properties as $slug => $property): ?>
                    <?php
                    $photos = $propertyPhotoOptions[$slug] ?? [];
                    $currentHero = hh_property_hero_image($property, $settings);
                    ?>
                    <article class="listing-media-card">
                        <div class="listing-media-header">
                            <h3><?php echo $escape($property['name']); ?></h3>
                            <span><?php echo $escape((string) count($photos)); ?> image(s)</span>
                        </div>
                        <p class="form-note">Current hero: <?php echo $escape(basename($currentHero)); ?></p>
                        <?php if (!empty($photos)): ?>
                            <div class="listing-media-thumbs">
                                <?php foreach (array_slice($photos, 0, 4) as $photoPath): ?>
                                    <img src="<?php echo $escape($photoPath); ?>" alt="<?php echo $escape($property['name']); ?> preview">
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="form-note">No listing photos uploaded yet.</p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-card" style="margin-top:1rem;">
            <div class="section-heading slim">
                <span class="eyebrow eyebrow-dark">Booking rules</span>
                <h2>Minimum nights and calendar sync</h2>
            </div>
            <form method="POST" class="dashboard-inline-form">
                <input type="hidden" name="action" value="refresh_ical_sync">
                <label for="refresh-property-select" class="field-label">Refresh calendar for</label>
                <select id="refresh-property-select" name="refresh_property_slug" class="dashboard-select">
                    <option value="all">All properties</option>
                    <?php foreach ($properties as $slug => $property): ?>
                        <option value="<?php echo $escape($slug); ?>"><?php echo $escape($property['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Refresh iCal Sync Now</button>
            </form>
            <p class="form-note" style="margin: 0.4rem 0 0.9rem;">This forces a fresh pull from each selected Airbnb iCal feed and updates the blocked-date cache immediately.</p>
            <form method="POST" class="admin-form">
                    <input type="hidden" name="action" value="save_listing_booking_settings">
                    <h3>Booking sync settings</h3>
                    <p class="form-note">Set minimum nights and add an Airbnb iCal URL for each property if you want booked dates to auto-sync.</p>
                    <?php foreach ($properties as $slug => $property): ?>
                        <?php $bookingSettings = $bookingSettingsByProperty[$slug] ?? hh_listing_booking_settings($settings, $slug); ?>
                        <div class="booking-settings-row">
                            <label for="min-nights-<?php echo $escape($slug); ?>"><?php echo $escape($property['name']); ?></label>
                            <input
                                id="min-nights-<?php echo $escape($slug); ?>"
                                type="number"
                                min="1"
                                step="1"
                                name="min_nights[<?php echo $escape($slug); ?>]"
                                value="<?php echo $escape((string) ($bookingSettings['min_nights'] ?? hh_default_min_nights($slug))); ?>"
                                class="dashboard-select"
                            >
                            <input
                                type="url"
                                name="ical_url[<?php echo $escape($slug); ?>]"
                                value="<?php echo $escape((string) ($bookingSettings['ical_url'] ?? '')); ?>"
                                placeholder="https://www.airbnb.com/calendar/ical/...ics"
                                class="dashboard-select"
                            >
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn">Save Booking Settings</button>
            </form>

            <?php if (!empty($calendarDiagnostics)): ?>
                <div class="comparison-table-wrap" style="margin-top: 1rem;">
                    <table class="comparison-table dashboard-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>iCal source</th>
                                <th>Blocked dates</th>
                                <th>Sync message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $slug => $property): ?>
                                <?php $diagnostic = $calendarDiagnostics[$slug] ?? ['source' => 'unknown', 'blocked_count' => 0, 'message' => '']; ?>
                                <tr>
                                    <td><?php echo $escape($property['name']); ?></td>
                                    <td><?php echo $escape((string) ($diagnostic['source'] ?? 'unknown')); ?></td>
                                    <td><?php echo $escape((string) ($diagnostic['blocked_count'] ?? 0)); ?></td>
                                    <td><?php echo $escape((string) ($diagnostic['message'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div class="dashboard-stat-grid">
            <article class="dashboard-stat-card">
                <span>Total Page Views (30d)</span>
                <strong><?php echo number_format($totalViews); ?></strong>
            </article>
            <article class="dashboard-stat-card">
                <span>Booking Intents (30d)</span>
                <strong><?php echo number_format($bookingIntents); ?></strong>
            </article>
            <article class="dashboard-stat-card">
                <span>Conversion Rate</span>
                <strong><?php echo $escape((string) $conversionRate); ?>%</strong>
            </article>
        </div>

        <div class="dashboard-content-grid">
            <section class="dashboard-card">
                <div class="section-heading slim">
                    <span class="eyebrow eyebrow-dark">Property status</span>
                    <h2>Portfolio overview</h2>
                </div>
                <div class="comparison-table-wrap">
                    <table class="comparison-table dashboard-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Nightly Rate</th>
                                <th>Min Stay</th>
                                <th>Pricing Source</th>
                                <th>Calendar Sync</th>
                                <th>Review Source</th>
                                <th>Review Summary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $key => $property): ?>
                                <tr>
                                    <td><?php echo $escape($property['name']); ?></td>
                                    <td><?php echo $escape(hh_currency_symbol($listingsData[$key]['currency'] ?? 'USD') . number_format((float) ($listingsData[$key]['price'] ?? 0))); ?></td>
                                    <td><?php echo $escape((string) ($bookingSettingsByProperty[$key]['min_nights'] ?? hh_default_min_nights($key))); ?> nights</td>
                                    <td><?php echo $escape(ucfirst($listingsData[$key]['source'] ?? 'fallback')); ?></td>
                                    <td>
                                        <?php if (trim((string) ($bookingSettingsByProperty[$key]['ical_url'] ?? '')) === ''): ?>
                                            Not set
                                        <?php else: ?>
                                            <?php
                                            $source = (string) ($calendarDiagnostics[$key]['source'] ?? 'unknown');
                                            $count = (int) ($calendarDiagnostics[$key]['blocked_count'] ?? 0);
                                            echo $escape($source . ' / ' . $count . ' dates');
                                            ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $escape(ucfirst(str_replace('-', ' ', $reviewsData[$key]['source'] ?? 'unverified'))); ?></td>
                                    <td>
                                        <?php if (($reviewsData[$key]['source'] ?? 'unverified') === 'listing-page'): ?>
                                            <?php echo $escape((string) $reviewsData[$key]['rating']); ?> / <?php echo $escape((string) $reviewsData[$key]['reviews_count']); ?> reviews
                                        <?php else: ?>
                                            No verified import
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="dashboard-card">
                <div class="section-heading slim">
                    <span class="eyebrow eyebrow-dark">Integrations</span>
                    <h2>System status</h2>
                </div>
                <div class="status-stack">
                    <div class="status-row">
                        <strong>PriceLabs</strong>
                        <span class="status-pill <?php echo strpos($pricelabsStatus, 'Live') !== false ? 'good' : 'warn'; ?>"><?php echo $escape($pricelabsStatus); ?></span>
                    </div>
                    <div class="status-row">
                        <strong>Airbnb Reviews</strong>
                        <span class="status-pill <?php echo strpos($airbnbStatus, 'Partial') !== false ? 'good' : 'warn'; ?>"><?php echo $escape($airbnbStatus); ?></span>
                    </div>
                </div>
                <?php if (!empty($systemAlerts)): ?>
                    <div class="alert-stack">
                        <?php foreach ($systemAlerts as $alert): ?>
                            <div class="alert-card"><?php echo $escape($alert); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const optionsByProperty = <?php echo json_encode($propertyPhotoOptions); ?>;
            const heroPropertySelect = document.getElementById('property-hero-select');
            const existingHeroSelect = document.getElementById('existing-hero-path');
            const heroPreviewWrap = document.getElementById('existing-hero-preview-wrap');
            const heroPreview = document.getElementById('existing-hero-preview');
            const listingMediaProperty = document.getElementById('listing-media-property');
            const listingMediaCount = document.getElementById('listing-media-count');

            const getFileName = (path) => {
                if (typeof path !== 'string') {
                    return '';
                }
                const parts = path.split('/');
                return parts[parts.length - 1] || path;
            };

            const renderHeroOptions = () => {
                if (!heroPropertySelect || !existingHeroSelect) {
                    return;
                }

                const slug = heroPropertySelect.value;
                const options = optionsByProperty[slug] || [];
                existingHeroSelect.innerHTML = '';

                if (options.length === 0) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No images uploaded for this listing yet';
                    option.disabled = true;
                    option.selected = true;
                    existingHeroSelect.appendChild(option);
                } else {
                    options.forEach((path) => {
                        const option = document.createElement('option');
                        option.value = path;
                        option.textContent = getFileName(path);
                        existingHeroSelect.appendChild(option);
                    });
                }

                updateHeroPreview();
            };

            const updateHeroPreview = () => {
                if (!existingHeroSelect || !heroPreviewWrap || !heroPreview) {
                    return;
                }

                const selectedPath = existingHeroSelect.value;
                if (!selectedPath) {
                    heroPreviewWrap.classList.remove('is-visible');
                    heroPreview.removeAttribute('src');
                    return;
                }

                heroPreview.src = selectedPath;
                heroPreviewWrap.classList.add('is-visible');
            };

            const renderListingCount = () => {
                if (!listingMediaProperty || !listingMediaCount) {
                    return;
                }
                const slug = listingMediaProperty.value;
                const count = (optionsByProperty[slug] || []).length;
                listingMediaCount.textContent = count + ' current image(s) in this listing gallery.';
            };

            heroPropertySelect?.addEventListener('change', renderHeroOptions);
            existingHeroSelect?.addEventListener('change', updateHeroPreview);
            listingMediaProperty?.addEventListener('change', renderListingCount);

            renderHeroOptions();
            renderListingCount();
        });
    </script>
<?php endif; ?>
</body>
</html>
