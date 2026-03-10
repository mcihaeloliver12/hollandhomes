<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/booking.php';
require_once __DIR__ . '/includes/PriceLabsAPI.php';
require_once __DIR__ . '/includes/IcalAvailability.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? strtolower(trim((string) $_POST['id'])) : '';
$data = hh_site_data();
$properties = $data['properties'];

if (!isset($properties[$id])) {
    header('Location: index.php');
    exit;
}

$property = $properties[$id];
$settings = hh_load_site_settings();
$bookingSettings = hh_listing_booking_settings($settings, $id);
$fallbackMinStay = (int) ($bookingSettings['min_nights'] ?? hh_default_min_nights($id));
$priceLabs = new PriceLabsAPI(PRICELABS_API_TOKEN);
$dynamicCalendarData = hh_load_pricelabs_calendar_data($id, $priceLabs, $_POST['checkin'] ?? null, $_POST['checkout'] ?? null);
$availabilitySync = new IcalAvailability();
$availabilityData = $availabilitySync->getBlockedDates((string) ($bookingSettings['ical_url'] ?? ''), $id);
$blockedDates = is_array($availabilityData['blocked_dates'] ?? null) ? $availabilityData['blocked_dates'] : [];
$validation = hh_validate_booking_selection($_POST['checkin'] ?? '', $_POST['checkout'] ?? '', $blockedDates, $fallbackMinStay, $dynamicCalendarData);

if (!$validation['valid']) {
    header('Location: ' . hh_booking_error_redirect_url($id, $validation['errors'][0] ?? 'Select valid dates to continue.'));
    exit;
}

$protection = strtolower(trim((string) ($_POST['protection'] ?? 'waivo')));
$includePet = hh_boolean_like($_POST['include_pet'] ?? '0', false);
$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$contactAck = isset($_POST['contact_ack']) && (string) $_POST['contact_ack'] === '1';
$termsAck = isset($_POST['terms_ack']) && (string) $_POST['terms_ack'] === '1';

$checkoutParams = [
    'id' => $id,
    'checkin' => (string) ($_POST['checkin'] ?? ''),
    'checkout' => (string) ($_POST['checkout'] ?? ''),
    'protection' => $protection,
    'include_pet' => $includePet ? '1' : '0',
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'phone' => $phone,
];
$checkoutUrl = 'checkout.php?' . http_build_query($checkoutParams);

if ($firstName === '' || $lastName === '') {
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Enter the guest first and last name.'));
    exit;
}

if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Enter a valid email address.'));
    exit;
}

if ($phone === '') {
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Enter a phone number for the booking confirmation.'));
    exit;
}

if (!$contactAck || !$termsAck) {
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Confirm the checkout acknowledgements before continuing.'));
    exit;
}

$checkoutDefaults = hh_checkout_defaults();

try {
    $quote = hh_build_booking_quote($id, $validation['checkin'], $validation['checkout'], $checkoutDefaults, $priceLabs, $includePet);
} catch (RuntimeException $exception) {
    header('Location: ' . hh_booking_error_redirect_url($id, $exception->getMessage()));
    exit;
}

if (!isset($quote['protection_options'][$protection])) {
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Choose a valid protection option.'));
    exit;
}

if (!hh_stripe_is_configured()) {
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Stripe is not configured yet. Add the live keys in includes/config.php first.'));
    exit;
}

$protectionOption = $quote['protection_options'][$protection];
$currency = strtolower((string) ($checkoutDefaults['currency'] ?? 'USD'));
$baseUrl = hh_base_url();
$successUrl = $baseUrl . '/checkout.php?' . http_build_query([
    'id' => $id,
    'checkin' => hh_date_iso($validation['checkin']),
    'checkout' => hh_date_iso($validation['checkout']),
    'protection' => $protection,
    'include_pet' => $includePet ? '1' : '0',
    'status' => 'success',
    'session_id' => '{CHECKOUT_SESSION_ID}',
]);
$cancelUrl = $baseUrl . '/checkout.php?' . http_build_query([
    'id' => $id,
    'checkin' => hh_date_iso($validation['checkin']),
    'checkout' => hh_date_iso($validation['checkout']),
    'protection' => $protection,
    'include_pet' => $includePet ? '1' : '0',
    'status' => 'cancelled',
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'phone' => $phone,
]);

$fields = [
    'mode' => 'payment',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'submit_type' => 'pay',
    'payment_method_types' => ['card'],
    'billing_address_collection' => 'auto',
    'customer_email' => $email,
    'allow_promotion_codes' => 'true',
    'metadata' => [
        'property_slug' => $id,
        'property_name' => (string) ($property['name'] ?? $id),
        'checkin' => hh_date_iso($validation['checkin']),
        'checkout' => hh_date_iso($validation['checkout']),
        'nights' => (string) $quote['nights'],
        'guest_name' => trim($firstName . ' ' . $lastName),
        'guest_phone' => $phone,
        'protection' => $protectionOption['label'],
        'include_pet' => !empty($quote['include_pet']) ? 'yes' : 'no',
        'cleaning_fee' => number_format((float) $quote['cleaning_fee'], 2, '.', ''),
        'pet_fee' => number_format((float) $quote['pet_fee_applied'], 2, '.', ''),
        'state_tax_rate' => number_format((float) $quote['state_tax_rate'], 2, '.', ''),
        'state_tax_amount' => number_format((float) $quote['state_tax_amount'], 2, '.', ''),
        'city_tax_rate' => number_format((float) $quote['city_tax_rate'], 2, '.', ''),
        'city_tax_amount' => number_format((float) $quote['city_tax_amount'], 2, '.', ''),
        'estimated_total' => number_format((float) $quote['estimated_total'], 2, '.', ''),
        'deposit_due' => number_format((float) $quote['deposit_due'], 2, '.', ''),
        'remaining_balance' => number_format((float) $quote['remaining_balance'], 2, '.', ''),
    ],
    'line_items' => [
        [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => (string) ($property['name'] ?? 'Holland Homes') . ' reservation deposit',
                    'description' => 'Deposit for ' . $quote['nights'] . '-night stay from ' . hh_date_iso($validation['checkin']) . ' to ' . hh_date_iso($validation['checkout']) . ', including fees and taxes in the estimate.',
                ],
                'unit_amount' => hh_money_cents($quote['deposit_due']),
            ],
            'quantity' => 1,
        ],
        [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => $protectionOption['label'],
                    'description' => $protectionOption['description'],
                ],
                'unit_amount' => hh_money_cents($protectionOption['amount']),
            ],
            'quantity' => 1,
        ],
    ],
];

$curl = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 20);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . STRIPE_SECRET_KEY,
    'Content-Type: application/x-www-form-urlencoded',
]);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));

$response = curl_exec($curl);
$curlError = curl_error($curl);
$statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if (!is_string($response) || $response === '') {
    $message = $curlError !== '' ? $curlError : 'Stripe did not return a checkout session.';
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Stripe error: ' . $message));
    exit;
}

$decoded = json_decode($response, true);
if ($statusCode >= 400 || !is_array($decoded)) {
    $message = is_array($decoded) ? (string) ($decoded['error']['message'] ?? 'Unable to create Stripe checkout session.') : 'Unable to create Stripe checkout session.';
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Stripe error: ' . $message));
    exit;
}

$redirectUrl = trim((string) ($decoded['url'] ?? ''));
if ($redirectUrl === '') {
    header('Location: ' . $checkoutUrl . '&checkout_error=' . rawurlencode('Stripe returned a session without a redirect URL.'));
    exit;
}

header('Location: ' . $redirectUrl);
exit;
