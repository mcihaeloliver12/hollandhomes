<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/site_data.php';
require_once __DIR__ . '/PriceLabsAPI.php';

function hh_checkout_defaults() {
    return [
        'deposit_percent' => defined('DIRECT_BOOKING_DEPOSIT_PERCENT') ? (int) DIRECT_BOOKING_DEPOSIT_PERCENT : 30,
        'security_deposit' => defined('DIRECT_BOOKING_SECURITY_DEPOSIT') ? (float) DIRECT_BOOKING_SECURITY_DEPOSIT : 500.0,
        'waivo_fee' => defined('DIRECT_BOOKING_WAIVO_FEE') ? (float) DIRECT_BOOKING_WAIVO_FEE : 79.0,
        'balance_due_days' => defined('DIRECT_BOOKING_BALANCE_DUE_DAYS') ? (int) DIRECT_BOOKING_BALANCE_DUE_DAYS : 14,
        'currency' => 'USD',
    ];
}

function hh_pricelabs_listing_id($slug) {
    $slug = strtolower(trim((string) $slug));
    $map = [
        'chalet' => '608635403291162192',
        'home' => '49479938',
        'villa' => '1278298080134872974',
    ];

    return $map[$slug] ?? '';
}

function hh_parse_iso_date($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
    return $date ?: null;
}

function hh_date_iso(DateTimeImmutable $date) {
    return $date->format('Y-m-d');
}

function hh_booking_nights(DateTimeImmutable $checkin, DateTimeImmutable $checkout) {
    return (int) $checkin->diff($checkout)->days;
}

function hh_booking_range_has_blocked_dates(DateTimeImmutable $checkin, DateTimeImmutable $checkout, array $blockedDates) {
    $blockedLookup = array_fill_keys($blockedDates, true);

    for ($cursor = $checkin; $cursor < $checkout; $cursor = $cursor->modify('+1 day')) {
        if (isset($blockedLookup[hh_date_iso($cursor)])) {
            return true;
        }
    }

    return false;
}

function hh_boolean_like($value, $default = true) {
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (float) $value !== 0.0;
    }
    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return (bool) $default;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }
        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }
    }

    return (bool) $default;
}

function hh_day_min_stay(array $dayData) {
    $candidates = [
        $dayData['min_stay'] ?? null,
        $dayData['minimum_stay'] ?? null,
        $dayData['min_nights'] ?? null,
        $dayData['minimum_nights'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_numeric($candidate)) {
            $value = (int) $candidate;
            if ($value > 0) {
                return $value;
            }
        }
    }

    return null;
}

function hh_required_min_stay_for_checkin(DateTimeImmutable $checkin, array $dynamicCalendarData, $fallbackMinStay) {
    $fallbackMinStay = max(1, (int) $fallbackMinStay);
    $checkinIso = hh_date_iso($checkin);
    $dayData = $dynamicCalendarData[$checkinIso] ?? null;

    if (!is_array($dayData)) {
        return $fallbackMinStay;
    }

    $dynamicMin = hh_day_min_stay($dayData);
    if ($dynamicMin === null) {
        return $fallbackMinStay;
    }

    return max($fallbackMinStay, $dynamicMin);
}

function hh_booking_range_has_unavailable_pricelabs_dates(DateTimeImmutable $checkin, DateTimeImmutable $checkout, array $dynamicCalendarData) {
    for ($cursor = $checkin; $cursor < $checkout; $cursor = $cursor->modify('+1 day')) {
        $iso = hh_date_iso($cursor);
        $dayData = $dynamicCalendarData[$iso] ?? null;
        if (!is_array($dayData) || !array_key_exists('available', $dayData)) {
            continue;
        }
        if (hh_boolean_like($dayData['available'], true) === false) {
            return true;
        }
    }

    return false;
}

function hh_validate_booking_selection($checkinValue, $checkoutValue, array $blockedDates, $fallbackMinStay, array $dynamicCalendarData = []) {
    $errors = [];
    $checkin = hh_parse_iso_date($checkinValue);
    $checkout = hh_parse_iso_date($checkoutValue);
    $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
    $fallbackMinStay = max(1, (int) $fallbackMinStay);
    $requiredMinStay = $fallbackMinStay;

    if ($checkin === null || $checkout === null) {
        $errors[] = 'Choose both check-in and check-out dates before continuing.';
    } else {
        $requiredMinStay = hh_required_min_stay_for_checkin($checkin, $dynamicCalendarData, $fallbackMinStay);
        if ($checkin < $today) {
            $errors[] = 'Check-in must be today or later.';
        }
        if ($checkout <= $checkin) {
            $errors[] = 'Check-out must be after check-in.';
        }
        if ($checkout > $checkin) {
            $nights = hh_booking_nights($checkin, $checkout);
            if ($nights < $requiredMinStay) {
                $errors[] = 'Your selected check-in date requires at least ' . $requiredMinStay . ' nights.';
            }
            if (hh_booking_range_has_blocked_dates($checkin, $checkout, $blockedDates)) {
                $errors[] = 'One or more nights in that stay are already booked.';
            }
            if (hh_booking_range_has_unavailable_pricelabs_dates($checkin, $checkout, $dynamicCalendarData)) {
                $errors[] = 'One or more nights in that stay are unavailable.';
            }
        }
    }

    return [
        'valid' => $errors === [],
        'errors' => $errors,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'required_min_stay' => $requiredMinStay,
        'nights' => ($checkin && $checkout && $checkout > $checkin) ? hh_booking_nights($checkin, $checkout) : 0,
    ];
}

function hh_fallback_nightly_price($slug, DateTimeImmutable $date, PriceLabsAPI $priceLabs) {
    $defaults = $priceLabs->getListingDefaults($slug);
    $base = (float) ($defaults['price'] ?? 0);
    if ($base <= 0) {
        $base = 250.0;
    }

    $dayOfWeek = (int) $date->format('w');
    if ($dayOfWeek === 5 || $dayOfWeek === 6) {
        return round($base * 1.15, 2);
    }

    return round($base, 2);
}

function hh_extract_pricelabs_nightly_map(array $pricingResponse) {
    $data = $pricingResponse['data'] ?? [];
    $rows = [];

    if (isset($data['prices']) && is_array($data['prices'])) {
        $rows = $data['prices'];
    } elseif (isset($data['data']) && is_array($data['data'])) {
        $rows = $data['data'];
    } elseif (isset($data['pricing']) && is_array($data['pricing'])) {
        $rows = $data['pricing'];
    } elseif (array_is_list($data)) {
        $rows = $data;
    }

    $nightlyMap = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $dateValue = (string) ($row['date'] ?? $row['stay_date'] ?? $row['day'] ?? $row['start_date'] ?? '');
        $date = hh_parse_iso_date($dateValue);
        if ($date === null) {
            continue;
        }

        $priceValue = $row['price'] ?? $row['final_price'] ?? $row['recommended_price'] ?? $row['base_price'] ?? null;
        $hasNumericPrice = is_numeric($priceValue);

        $nightlyMap[hh_date_iso($date)] = [
            'price' => $hasNumericPrice ? round((float) $priceValue, 2) : null,
            'available' => array_key_exists('available', $row) ? hh_boolean_like($row['available'], true) : true,
            'min_stay' => hh_day_min_stay($row),
        ];
    }

    return $nightlyMap;
}

function hh_load_pricelabs_calendar_data($slug, PriceLabsAPI $priceLabs, $startDateValue = null, $endDateValue = null) {
    $startDate = hh_parse_iso_date((string) ($startDateValue ?? ''));
    $endDate = hh_parse_iso_date((string) ($endDateValue ?? ''));

    if ($startDate === null) {
        $startDate = new DateTimeImmutable('today', new DateTimeZone('UTC'));
    }
    if ($endDate === null || $endDate <= $startDate) {
        $endDate = $startDate->modify('+365 days');
    }

    $listingId = hh_pricelabs_listing_id($slug);
    if ($listingId === '') {
        return [];
    }

    $pricingResponse = $priceLabs->getPricingData($listingId, hh_date_iso($startDate), hh_date_iso($endDate));
    return hh_extract_pricelabs_nightly_map($pricingResponse);
}

function hh_property_fee_profile($slug) {
    $siteData = hh_site_data();
    $properties = is_array($siteData['properties'] ?? null) ? $siteData['properties'] : [];
    $property = $properties[$slug] ?? [];
    $fees = is_array($property['booking_fees'] ?? null) ? $property['booking_fees'] : [];

    return [
        'cleaning_fee' => max(0, (float) ($fees['cleaning_fee'] ?? 0)),
        'pet_fee' => max(0, (float) ($fees['pet_fee'] ?? 0)),
        'pets_allowed' => hh_boolean_like($fees['pets_allowed'] ?? false, false),
        'state_tax_rate' => max(0, (float) ($fees['state_tax_rate'] ?? 0)),
        'city_tax_rate' => max(0, (float) ($fees['city_tax_rate'] ?? 0)),
    ];
}

function hh_build_booking_quote($slug, DateTimeImmutable $checkin, DateTimeImmutable $checkout, array $checkoutDefaults, PriceLabsAPI $priceLabs, $includePet = false) {
    $nightlyMap = hh_load_pricelabs_calendar_data($slug, $priceLabs, hh_date_iso($checkin), hh_date_iso($checkout));
    $nightlyRates = [];
    $nightlySubtotal = 0.0;
    $pricingSource = count($nightlyMap) > 0 ? 'live' : 'fallback';

    for ($cursor = $checkin; $cursor < $checkout; $cursor = $cursor->modify('+1 day')) {
        $iso = hh_date_iso($cursor);
        $mapped = $nightlyMap[$iso] ?? null;
        if (is_array($mapped) && hh_boolean_like($mapped['available'] ?? true, true) === false) {
            throw new RuntimeException('Selected dates are no longer available.');
        }

        $price = is_array($mapped) ? (float) ($mapped['price'] ?? 0) : hh_fallback_nightly_price($slug, $cursor, $priceLabs);
        if ($price <= 0) {
            $price = hh_fallback_nightly_price($slug, $cursor, $priceLabs);
            $pricingSource = 'fallback';
        }

        $nightlyRates[] = [
            'date' => $iso,
            'label' => $cursor->format('D, M j'),
            'amount' => round($price, 2),
        ];
        $nightlySubtotal += $price;
    }

    $feeProfile = hh_property_fee_profile($slug);
    $cleaningFee = (float) $feeProfile['cleaning_fee'];
    $petFee = (float) $feeProfile['pet_fee'];
    $petsAllowed = (bool) $feeProfile['pets_allowed'];
    $includePet = $petsAllowed ? hh_boolean_like($includePet, false) : false;
    $petFeeApplied = $includePet ? $petFee : 0.0;
    $stateTaxRate = (float) $feeProfile['state_tax_rate'];
    $cityTaxRate = (float) $feeProfile['city_tax_rate'];
    $taxableSubtotal = round($nightlySubtotal + $cleaningFee + $petFeeApplied, 2);
    $stateTaxAmount = round($taxableSubtotal * ($stateTaxRate / 100), 2);
    $cityTaxAmount = round($taxableSubtotal * ($cityTaxRate / 100), 2);
    $estimatedTotal = round($taxableSubtotal + $stateTaxAmount + $cityTaxAmount, 2);

    $depositPercent = max(1, (int) ($checkoutDefaults['deposit_percent'] ?? 30));
    $securityDeposit = max(0, (float) ($checkoutDefaults['security_deposit'] ?? 0));
    $waivoFee = max(0, (float) ($checkoutDefaults['waivo_fee'] ?? 0));
    $depositDue = round($estimatedTotal * ($depositPercent / 100), 2);

    $protectionOptions = [
        'security_deposit' => [
            'id' => 'security_deposit',
            'label' => 'Refundable security deposit',
            'description' => 'Collected today and returned after checkout if there is no reported damage.',
            'amount' => $securityDeposit,
            'amount_label' => hh_currency_symbol($checkoutDefaults['currency'] ?? 'USD') . number_format($securityDeposit, 0),
            'pill' => 'Refundable',
        ],
        'waivo' => [
            'id' => 'waivo',
            'label' => 'Waivo damage protection',
            'description' => 'Non-refundable damage waiver that replaces a large security deposit.',
            'amount' => $waivoFee,
            'amount_label' => hh_currency_symbol($checkoutDefaults['currency'] ?? 'USD') . number_format($waivoFee, 0),
            'pill' => 'Non-refundable',
        ],
    ];

    return [
        'checkin_label' => $checkin->format('D, M j, Y'),
        'checkout_label' => $checkout->format('D, M j, Y'),
        'nights' => hh_booking_nights($checkin, $checkout),
        'nightly_rates' => $nightlyRates,
        'nightly_subtotal' => round($nightlySubtotal, 2),
        'cleaning_fee' => $cleaningFee,
        'pet_fee' => $petFee,
        'pet_fee_applied' => $petFeeApplied,
        'pets_allowed' => $petsAllowed,
        'include_pet' => $includePet,
        'state_tax_rate' => $stateTaxRate,
        'city_tax_rate' => $cityTaxRate,
        'state_tax_amount' => $stateTaxAmount,
        'city_tax_amount' => $cityTaxAmount,
        'taxable_subtotal' => $taxableSubtotal,
        'estimated_total' => $estimatedTotal,
        'deposit_due' => $depositDue,
        'remaining_balance' => round(max(0, $estimatedTotal - $depositDue), 2),
        'pricing_source' => $pricingSource,
        'protection_options' => $protectionOptions,
    ];
}

function hh_money_cents($amount) {
    return (int) round(((float) $amount) * 100);
}

function hh_money_format($amount, $currency = 'USD') {
    return hh_currency_symbol($currency) . number_format((float) $amount, 2);
}

function hh_stripe_is_configured() {
    return defined('STRIPE_SECRET_KEY') && trim((string) STRIPE_SECRET_KEY) !== '';
}

function hh_booking_error_redirect_url($slug, $message) {
    return 'property.php?id=' . rawurlencode((string) $slug) . '&booking_error=' . rawurlencode((string) $message) . '#book';
}

function hh_base_url() {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($dir === '' || $dir === '.') {
        return $scheme . '://' . $host;
    }

    return $scheme . '://' . $host . $dir;
}
