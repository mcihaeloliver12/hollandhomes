<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/booking.php';
require_once __DIR__ . '/includes/PriceLabsAPI.php';
require_once __DIR__ . '/includes/IcalAvailability.php';

$id = isset($_GET['id']) ? strtolower(trim((string) $_GET['id'])) : '';
$data = hh_site_data();
$properties = $data['properties'];
$settings = hh_load_site_settings();

if (!isset($properties[$id])) {
    header('Location: index.php');
    exit;
}

$property = $properties[$id];
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$bookingSettings = hh_listing_booking_settings($settings, $id);
$fallbackMinStay = (int) ($bookingSettings['min_nights'] ?? hh_default_min_nights($id));
$priceLabs = new PriceLabsAPI(PRICELABS_API_TOKEN);
$dynamicCalendarData = hh_load_pricelabs_calendar_data($id, $priceLabs, $_GET['checkin'] ?? null, $_GET['checkout'] ?? null);
$availabilitySync = new IcalAvailability();
$availabilityData = $availabilitySync->getBlockedDates((string) ($bookingSettings['ical_url'] ?? ''), $id);
$blockedDates = is_array($availabilityData['blocked_dates'] ?? null) ? $availabilityData['blocked_dates'] : [];
$validation = hh_validate_booking_selection($_GET['checkin'] ?? '', $_GET['checkout'] ?? '', $blockedDates, $fallbackMinStay, $dynamicCalendarData);

if (!$validation['valid']) {
    header('Location: ' . hh_booking_error_redirect_url($id, $validation['errors'][0] ?? 'Select valid dates to continue.'));
    exit;
}

$checkoutDefaults = hh_checkout_defaults();

try {
    $quote = hh_build_booking_quote($id, $validation['checkin'], $validation['checkout'], $checkoutDefaults, $priceLabs);
} catch (RuntimeException $exception) {
    header('Location: ' . hh_booking_error_redirect_url($id, $exception->getMessage()));
    exit;
}

$selectedProtection = strtolower(trim((string) ($_GET['protection'] ?? 'waivo')));
if (!isset($quote['protection_options'][$selectedProtection])) {
    $selectedProtection = 'waivo';
}
$selectedOption = $quote['protection_options'][$selectedProtection];
$dueToday = $quote['deposit_due'] + (float) $selectedOption['amount'];
$currency = (string) ($checkoutDefaults['currency'] ?? 'USD');
$depositPercent = (int) ($checkoutDefaults['deposit_percent'] ?? DIRECT_BOOKING_DEPOSIT_PERCENT);
$balanceDueDays = (int) ($checkoutDefaults['balance_due_days'] ?? DIRECT_BOOKING_BALANCE_DUE_DAYS);
$stripeReady = hh_stripe_is_configured();
$checkoutError = trim((string) ($_GET['checkout_error'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$firstName = trim((string) ($_GET['first_name'] ?? ''));
$lastName = trim((string) ($_GET['last_name'] ?? ''));
$email = trim((string) ($_GET['email'] ?? ''));
$phone = trim((string) ($_GET['phone'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | <?php echo $escape($property['name']); ?> | Holland Homes</title>
    <meta name="description" content="Direct booking checkout for <?php echo $escape($property['name']); ?>.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="checkout-page">
    <header id="main-header" class="scrolled">
        <div class="container header-inner">
            <a href="index.php" class="logo">Holland <i>Homes</i></a>
            <nav class="nav-links">
                <a href="property.php?id=<?php echo $escape($id); ?>">Back to Property</a>
                <a href="index.php#properties">All Properties</a>
            </nav>
        </div>
    </header>

    <main class="section-padding checkout-shell">
        <div class="container checkout-grid">
            <section class="checkout-main">
                <span class="eyebrow eyebrow-dark">Direct Booking Checkout</span>
                <h1>Review your stay at <?php echo $escape($property['name']); ?></h1>
                <p class="checkout-intro">Your dates already cleared the minimum-stay and availability rules. Continue to Stripe when the guest details and protection option look right.</p>

                <?php if ($status === 'success'): ?>
                    <div class="booking-banner booking-banner-success">Your Stripe checkout returned successfully. Holland Homes will send booking confirmation details, including the direct phone number, after review.</div>
                <?php elseif ($status === 'cancelled'): ?>
                    <div class="booking-banner booking-banner-warning">Stripe checkout was cancelled. Your dates are still here if you want to try again.</div>
                <?php endif; ?>

                <?php if ($checkoutError !== ''): ?>
                    <div class="booking-banner booking-banner-error"><?php echo $escape($checkoutError); ?></div>
                <?php endif; ?>

                <form action="create_checkout_session.php" method="post" class="checkout-form-panel">
                    <input type="hidden" name="id" value="<?php echo $escape($id); ?>">
                    <input type="hidden" name="checkin" value="<?php echo $escape(hh_date_iso($validation['checkin'])); ?>">
                    <input type="hidden" name="checkout" value="<?php echo $escape(hh_date_iso($validation['checkout'])); ?>">

                    <div class="checkout-card">
                        <div class="section-heading slim">
                            <span class="eyebrow eyebrow-dark">Stay details</span>
                            <h2>What you are reserving</h2>
                        </div>
                        <div class="checkout-stat-row">
                            <div>
                                <strong><?php echo $escape($quote['checkin_label']); ?></strong>
                                <span>Check-in</span>
                            </div>
                            <div>
                                <strong><?php echo $escape($quote['checkout_label']); ?></strong>
                                <span>Check-out</span>
                            </div>
                            <div>
                                <strong><?php echo $escape((string) $quote['nights']); ?></strong>
                                <span>Nights</span>
                            </div>
                        </div>
                        <div class="checkout-note-stack">
                            <p>Direct phone support, address details, and arrival instructions are shared after booking is confirmed.</p>
                            <p>Minimum stay rules vary by check-in date and were validated from current pricing data for this stay.</p>
                        </div>
                    </div>

                    <div class="checkout-card">
                        <div class="section-heading slim">
                            <span class="eyebrow eyebrow-dark">Guest details</span>
                            <h2>Who is booking</h2>
                        </div>
                        <div class="field-grid">
                            <label class="field-group">
                                <span>First name</span>
                                <input type="text" name="first_name" value="<?php echo $escape($firstName); ?>" required>
                            </label>
                            <label class="field-group">
                                <span>Last name</span>
                                <input type="text" name="last_name" value="<?php echo $escape($lastName); ?>" required>
                            </label>
                            <label class="field-group">
                                <span>Email</span>
                                <input type="email" name="email" value="<?php echo $escape($email); ?>" required>
                            </label>
                            <label class="field-group">
                                <span>Phone</span>
                                <input type="tel" name="phone" value="<?php echo $escape($phone); ?>" required>
                            </label>
                        </div>
                    </div>

                    <div class="checkout-card">
                        <div class="section-heading slim">
                            <span class="eyebrow eyebrow-dark">Today's payment</span>
                            <h2>Deposit now, balance later</h2>
                        </div>
                        <div class="quote-row">
                            <span>Reservation deposit due today</span>
                            <strong><?php echo $escape(hh_money_format($quote['deposit_due'], $currency)); ?></strong>
                        </div>
                        <p class="form-note"><?php echo $escape((string) $depositPercent); ?>% of the current lodging estimate is collected in Stripe today. The remaining balance can be invoiced separately <?php echo $escape((string) $balanceDueDays); ?> days before arrival.</p>
                    </div>

                    <div class="checkout-card">
                        <div class="section-heading slim">
                            <span class="eyebrow eyebrow-dark">Protection option</span>
                            <h2>Choose deposit or Waivo</h2>
                        </div>
                        <div class="protection-options">
                            <?php foreach ($quote['protection_options'] as $option): ?>
                                <label class="protection-option<?php echo $selectedProtection === $option['id'] ? ' selected' : ''; ?>" data-protection-option data-option-id="<?php echo $escape($option['id']); ?>" data-option-amount="<?php echo $escape((string) $option['amount']); ?>">
                                    <input type="radio" name="protection" value="<?php echo $escape($option['id']); ?>" <?php echo $selectedProtection === $option['id'] ? 'checked' : ''; ?>>
                                    <div>
                                        <div class="protection-option-topline">
                                            <strong><?php echo $escape($option['label']); ?></strong>
                                            <span class="option-pill"><?php echo $escape($option['pill']); ?></span>
                                        </div>
                                        <p><?php echo $escape($option['description']); ?></p>
                                    </div>
                                    <span class="protection-price"><?php echo $escape($option['amount_label']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="checkout-card">
                        <div class="section-heading slim">
                            <span class="eyebrow eyebrow-dark">Before Stripe</span>
                            <h2>Confirm the booking basics</h2>
                        </div>
                        <label class="form-check">
                            <input type="checkbox" name="contact_ack" value="1" required>
                            <span>I understand the direct phone number and arrival details are released after booking confirmation.</span>
                        </label>
                        <label class="form-check">
                            <input type="checkbox" name="terms_ack" value="1" required>
                            <span>I understand today's charge is a reservation deposit plus the selected protection option.</span>
                        </label>
                    </div>

                    <div class="checkout-actions">
                        <button type="submit" class="btn checkout-submit" <?php echo $stripeReady ? '' : 'disabled'; ?>>Continue to Stripe Checkout</button>
                        <?php if (!$stripeReady): ?>
                            <p class="form-note">Stripe keys are not configured yet in <code>includes/config.php</code>, so this button stays locked until the live credentials are added.</p>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <aside class="checkout-sidebar">
                <article class="checkout-summary-card">
                    <img src="<?php echo $escape(hh_property_hero_image($property, $settings)); ?>" alt="<?php echo $escape($property['name']); ?>">
                    <div class="checkout-summary-body">
                        <span class="property-kicker"><?php echo $escape($property['name']); ?></span>
                        <h2>Booking summary</h2>
                        <div class="quote-row">
                            <span>Estimated lodging subtotal</span>
                            <strong><?php echo $escape(hh_money_format($quote['nightly_subtotal'], $currency)); ?></strong>
                        </div>
                        <div class="quote-row">
                            <span>Protection option</span>
                            <strong id="selected-protection-label"><?php echo $escape($selectedOption['label']); ?></strong>
                        </div>
                        <div class="quote-row total-row">
                            <span>Due today in Stripe</span>
                            <strong id="due-today-amount"><?php echo $escape(hh_money_format($dueToday, $currency)); ?></strong>
                        </div>
                        <div class="quote-row subdued-row">
                            <span>Estimated remaining balance</span>
                            <strong><?php echo $escape(hh_money_format($quote['remaining_balance'], $currency)); ?></strong>
                        </div>
                        <p class="form-note">Nightly pricing source: <?php echo $escape($quote['pricing_source'] === 'live' ? 'PriceLabs live pricing' : 'fallback nightly estimate'); ?>.</p>
                    </div>
                </article>

                <article class="checkout-card checkout-breakdown-card">
                    <span class="eyebrow eyebrow-dark">Nightly estimate</span>
                    <ul class="nightly-breakdown">
                        <?php foreach ($quote['nightly_rates'] as $night): ?>
                            <li>
                                <span><?php echo $escape($night['label']); ?></span>
                                <strong><?php echo $escape(hh_money_format($night['amount'], $currency)); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>

                <article class="checkout-card trust-card">
                    <span class="eyebrow eyebrow-dark">What happens next</span>
                    <ol class="checkout-steps">
                        <li>Stripe collects today's reservation deposit and your selected protection option.</li>
                        <li>Holland Homes confirms the booking and shares the direct phone number plus arrival details.</li>
                        <li>The remaining balance can be invoiced separately before arrival.</li>
                    </ol>
                </article>
            </aside>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const protectionOptions = document.querySelectorAll('[data-protection-option]');
            const dueTodayAmount = document.getElementById('due-today-amount');
            const protectionLabel = document.getElementById('selected-protection-label');
            const depositAmount = <?php echo json_encode((float) $quote['deposit_due']); ?>;
            const currencySymbol = <?php echo json_encode(hh_currency_symbol($currency)); ?>;

            const formatMoney = (amount) => `${currencySymbol}${Number(amount).toFixed(2)}`;

            const updateSelection = () => {
                const checked = document.querySelector('input[name="protection"]:checked');
                if (!checked) {
                    return;
                }

                const selectedCard = checked.closest('[data-protection-option]');
                const selectedAmount = Number(selectedCard?.dataset.optionAmount || 0);
                const selectedLabel = selectedCard?.querySelector('strong')?.textContent || '';
                protectionOptions.forEach((option) => option.classList.toggle('selected', option === selectedCard));
                if (dueTodayAmount) {
                    dueTodayAmount.textContent = formatMoney(depositAmount + selectedAmount);
                }
                if (protectionLabel) {
                    protectionLabel.textContent = selectedLabel;
                }
            };

            protectionOptions.forEach((option) => {
                option.addEventListener('click', () => {
                    const radio = option.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                    }
                    updateSelection();
                });
            });

            updateSelection();
        });
    </script>
</body>
</html>
