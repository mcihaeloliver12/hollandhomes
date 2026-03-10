<?php

function hh_site_data() {
    return [
        'site' => [
            'brand' => 'Holland Homes',
            'tagline' => 'Curated vacation rentals on the Oregon Coast and in Palm Springs.',
            'hero_title' => 'Your next great getaway starts here.',
            'hero_copy' => 'Holland Homes offers handpicked vacation rentals in Rockaway Beach, Oregon and Palm Springs, California. Every property is Superhost-managed with top-rated reviews and thoughtful amenities.',
            'cta_headline' => 'Find the perfect stay for your next trip',
            'cta_copy' => 'Explore each property to see photos, amenities, availability, and guest reviews, then start a direct booking checkout with Holland Homes.',
        ],
        'properties' => [
            'chalet' => [
                'slug' => 'chalet',
                'name' => 'The Chalet',
                'is_superhost' => true,
                'guest_review' => 'Absolutely loved our stay at The Chalet. The peaceful setting, the sound of the ocean nearby, and the cozy interior made it the perfect retreat. Our dog loved it too!',
                'guest_review_name' => 'Sarah',
                'hero_image' => 'Chalet/Photos/32bf4096-1e57-4dba-85a2-ec831d48344b.jpeg',
                'card_image' => 'Chalet/Photos/6601368b-a037-4868-a1f2-e8fdb3300b1e.jpeg.avif',
                'airbnb_url' => 'https://www.airbnb.com/rooms/608635403291162192',
                'fallback_airbnb' => [
                    'title' => 'Chalet in Rockaway Beach · ★5.0 · 2 bedrooms · 2 beds · 1 bath',
                    'description' => 'This dog-friendly chalet in Rockaway Beach is the perfect place for your next vacation on the Oregon Coast. You will enjoy peace and quiet with a strong connection to the natural setting.',
                    'listing_type' => 'Chalet',
                    'location' => 'Rockaway Beach',
                    'listing_label' => 'Entire chalet',
                    'rating' => '5.0',
                    'reviews_count' => 178,
                    'bedrooms' => 2,
                    'beds' => 2,
                    'baths' => 1,
                    'lat' => 45.60946,
                    'lng' => -123.93659,
                ],
                'summary' => 'A cozy, dog-friendly Oregon Coast chalet surrounded by peace and natural beauty, perfect for couples and small groups.',
                'booking_fees' => [
                    'cleaning_fee' => 200.0,
                    'pet_fee' => 100.0,
                    'pets_allowed' => true,
                    'state_tax_rate' => 1.5,
                    'city_tax_rate' => 10.0,
                ],
                'badges' => ['Dog-friendly', 'Rockaway Beach', 'Oregon Coast', 'Quiet retreat'],
                'detail_blocks' => [
                    [
                        'title' => 'Coastal cabin charm',
                        'text' => 'Tucked away on the Oregon Coast, this chalet offers a peaceful escape with the sound of waves and towering trees just outside your door.',
                    ],
                    [
                        'title' => 'Intimate and cozy',
                        'text' => 'Two bedrooms, two beds, and one bath make this chalet ideal for couples, solo travelers, or a small group looking for quality over quantity.',
                    ],
                    [
                        'title' => 'Bring your dog along',
                        'text' => 'The Chalet is pet-friendly, so your four-legged companion can enjoy the coastal adventure with you.',
                    ],
                ],
                'amenity_groups' => [
                    ['title' => 'Property highlights', 'items' => ['Entire chalet', 'Rockaway Beach location', 'Oregon Coast setting', 'Quiet, natural environment']],
                    ['title' => 'Ideal for', 'items' => ['Couples and small groups', 'Pet owners traveling with dogs', 'Weekend getaways', 'Nature lovers']],
                    ['title' => 'The experience', 'items' => ['Peaceful atmosphere', 'Slower-paced retreat', 'Unplugged coastal weekends', 'Scenic beach walks']],
                    ['title' => 'At a glance', 'items' => ['2 bedrooms', '2 beds', '1 bath', '5.0 star Airbnb rating']],
                ],
                'stay_notes' => [
                    'Best suited for couples, pet owners, and those seeking a quieter coast trip.',
                    'The true highlight is the peaceful setting and connection to nature.',
                    'Dogs are welcome, this is one of our most pet-friendly properties.',
                    'Direct booking dates are validated during checkout before payment is collected.',
                ],
            ],
            'home' => [
                'slug' => 'home',
                'name' => 'The Oasis',
                'is_superhost' => true,
                'guest_review' => 'This place is incredible, the sauna, hot tub, and cold plunge made our group trip unforgettable. Plenty of space for everyone and the host thought of everything.',
                'guest_review_name' => 'James',
                'hero_image' => 'Home/Photos/89432698-1ac9-4b8f-9c5e-1a0813646f64.jpeg',
                'card_image' => 'Home/Photos/99758d42-2e67-440c-bf0f-b71d0c89b21e.jpeg',
                'airbnb_url' => 'https://www.airbnb.com/rooms/49479938',
                'fallback_airbnb' => [
                    'title' => 'Oasis in Rockaway Beach · ★4.99 · 6 bedrooms · 8 beds · 3 baths',
                    'description' => 'Welcome to your private oasis in Rockaway Beach, Oregon. This exclusive retreat features a cedar barrel sauna, hot tub, and cold plunge for a more elevated coastal stay.',
                    'listing_type' => 'Oasis',
                    'location' => 'Rockaway Beach',
                    'listing_label' => 'Entire home',
                    'rating' => '4.99',
                    'reviews_count' => 176,
                    'bedrooms' => 6,
                    'beds' => 8,
                    'baths' => 3,
                    'lat' => 45.58419,
                    'lng' => -123.95132,
                ],
                'summary' => 'A spacious Rockaway Beach retreat featuring a cedar barrel sauna, hot tub, cold plunge, and room for the whole group.',
                'booking_fees' => [
                    'cleaning_fee' => 425.0,
                    'pet_fee' => 200.0,
                    'pets_allowed' => true,
                    'state_tax_rate' => 1.5,
                    'city_tax_rate' => 10.0,
                ],
                'badges' => ['Cedar barrel sauna', 'Hot tub', 'Cold plunge', 'Rockaway Beach'],
                'detail_blocks' => [
                    [
                        'title' => 'Wellness amenities',
                        'text' => 'Unwind in the cedar barrel sauna, soak in the hot tub, or take a refreshing cold plunge, all steps from your door.',
                    ],
                    [
                        'title' => 'Room for everyone',
                        'text' => 'With six bedrooms, eight beds, and three bathrooms, The Oasis comfortably hosts large groups, family reunions, and friend getaways.',
                    ],
                    [
                        'title' => 'Your private oasis',
                        'text' => 'An exclusive coastal retreat in Rockaway Beach designed for relaxation, privacy, and making memories with the people who matter most.',
                    ],
                ],
                'amenity_groups' => [
                    ['title' => 'Wellness amenities', 'items' => ['Cedar barrel sauna', 'Hot tub', 'Cold plunge', 'Private retreat feel']],
                    ['title' => 'Property highlights', 'items' => ['Entire home', 'Rockaway Beach, Oregon', 'Exclusive retreat setting', 'Coastal group getaway']],
                    ['title' => 'At a glance', 'items' => ['6 bedrooms', '8 beds', '3 baths', '4.99 star Airbnb rating']],
                    ['title' => 'Ideal for', 'items' => ['Friend groups', 'Family gatherings', 'Long weekends', 'Large coastal getaways']],
                ],
                'stay_notes' => [
                    'Perfect for groups seeking both space and premium wellness amenities.',
                    'The sauna, hot tub, and cold plunge are available for all guests.',
                    'One of our highest-reviewed properties with nearly 200 five-star reviews.',
                    'Direct booking dates are validated during checkout before payment is collected.',
                ],
            ],
            'villa' => [
                'slug' => 'villa',
                'name' => 'The Villa',
                'is_superhost' => true,
                'guest_review' => 'Stunning modern villa with impeccable design. The Palm Springs vibe was exactly what we were looking for, stylish, relaxing, and perfectly located.',
                'guest_review_name' => 'Michelle',
                'hero_image' => 'Villa/Photos/35177386-0b22-4829-9929-405cf7874481.jpeg',
                'card_image' => 'Villa/Photos/77d45e8d-be50-4ce2-b1db-b749d300fe67.jpeg',
                'airbnb_url' => 'https://www.airbnb.com/rooms/1278298080134872974',
                'fallback_airbnb' => [
                    'title' => 'Villa in Palm Springs · ★5.0 · 3 bedrooms · 3 beds · 2.5 baths',
                    'description' => 'Welcome to The Palm Springs Modern Villa, an exclusive and iconic retreat in the heart of Palm Springs, crafted with a stronger architect-driven identity than a standard vacation rental.',
                    'listing_type' => 'Villa',
                    'location' => 'Palm Springs',
                    'listing_label' => 'Entire villa',
                    'rating' => '5.0',
                    'reviews_count' => 5,
                    'bedrooms' => 3,
                    'beds' => 3,
                    'baths' => 2.5,
                    'lat' => 33.85279734678549,
                    'lng' => -116.53819253037676,
                ],
                'summary' => 'A stunning, architect-designed Palm Springs villa, an iconic modern retreat in the heart of the desert.',
                'booking_fees' => [
                    'cleaning_fee' => 325.0,
                    'pet_fee' => 0.0,
                    'pets_allowed' => false,
                    'state_tax_rate' => 0.0,
                    'city_tax_rate' => 12.5,
                ],
                'badges' => ['Palm Springs', 'Modern villa', 'Architect-led identity', 'Iconic retreat'],
                'detail_blocks' => [
                    [
                        'title' => 'Palm Springs living',
                        'text' => 'Located in the heart of Palm Springs, this villa puts you steps from world-class dining, shopping, and desert adventures.',
                    ],
                    [
                        'title' => 'Architect-designed',
                        'text' => 'Every detail has been thoughtfully crafted, from the modern interiors to the iconic desert-inspired design.',
                    ],
                    [
                        'title' => 'Premium desert retreat',
                        'text' => 'A perfect 5.0 rating speaks for itself. This villa delivers a boutique hotel experience in a private, residential setting.',
                    ],
                ],
                'amenity_groups' => [
                    ['title' => 'Property highlights', 'items' => ['Entire villa', 'Palm Springs location', 'Modern architectural design', 'Iconic retreat setting']],
                    ['title' => 'At a glance', 'items' => ['3 bedrooms', '3 beds', '2.5 baths', '5.0 star Airbnb rating']],
                    ['title' => 'Design & style', 'items' => ['Architect-designed interiors', 'High-impact visual design', 'Palm Springs leisure appeal', 'Premium finishes throughout']],
                    ['title' => 'Ideal for', 'items' => ['Design-conscious travelers', 'Palm Springs weekends', 'Couples and small groups', 'Those who value aesthetics and style']],
                ],
                'stay_notes' => [
                    'A design-forward property with a clean, modern aesthetic.',
                    'Located in the heart of Palm Springs with easy access to everything.',
                    'Perfect 5.0 star rating, every guest has loved their stay.',
                    'Direct booking dates are validated during checkout before payment is collected.',
                ],
            ],
        ],
    ];
}

function hh_currency_symbol($currency) {
    $currency = strtoupper((string) $currency);
    if ($currency === 'USD' || $currency === '$') {
        return '$';
    }

    return $currency !== '' ? $currency . ' ' : '$';
}

function hh_merge_airbnb_snapshot(array $fallback, array $live) {
    $merged = $fallback;

    foreach ($live as $key => $value) {
        if ($value === null) {
            continue;
        }
        if (is_string($value) && trim($value) === '') {
            continue;
        }
        if (is_array($value) && $value === []) {
            continue;
        }

        $merged[$key] = $value;
    }

    return $merged;
}

function hh_airbnb_fact($count, $singular, $plural = null) {
    if ($count === null || $count === '') {
        return '';
    }

    $plural = $plural ?: $singular . 's';
    $label = ((float) $count === 1.0) ? $singular : $plural;

    return rtrim(rtrim((string) $count, '0'), '.') . ' ' . $label;
}

function hh_load_site_settings() {
    $default = [
        'main_hero_image' => 'assets/hh_hero.jpg',
        'property_hero_overrides' => [],
        'listing_booking' => [],
    ];

    $path = __DIR__ . '/../data/site_settings.json';
    if (!file_exists($path)) {
        return $default;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        return $default;
    }

    return array_merge($default, $decoded);
}

function hh_save_site_settings(array $settings) {
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $path = $dir . '/site_settings.json';
    file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function hh_main_hero_image(array $settings) {
    $image = (string) ($settings['main_hero_image'] ?? 'assets/hh_hero.jpg');
    return $image !== '' ? $image : 'assets/hh_hero.jpg';
}

function hh_property_hero_image(array $property, array $settings) {
    $slug = (string) ($property['slug'] ?? '');
    $overrides = $settings['property_hero_overrides'] ?? [];
    if ($slug !== '' && isset($overrides[$slug]) && is_string($overrides[$slug]) && trim($overrides[$slug]) !== '') {
        return $overrides[$slug];
    }

    return (string) ($property['hero_image'] ?? '');
}

function hh_default_min_nights($slug) {
    $slug = strtolower(trim((string) $slug));
    $defaults = [
        'chalet' => 2,
        'home' => 3,
        'villa' => 4,
    ];

    return isset($defaults[$slug]) ? (int) $defaults[$slug] : 2;
}

function hh_listing_booking_settings(array $settings, $slug) {
    $slug = strtolower(trim((string) $slug));
    $listingBooking = $settings['listing_booking'] ?? [];
    $configured = $listingBooking[$slug] ?? [];
    $minNights = (int) ($configured['min_nights'] ?? hh_default_min_nights($slug));
    if ($minNights < 1) {
        $minNights = hh_default_min_nights($slug);
    }

    return [
        'min_nights' => $minNights,
        'ical_url' => trim((string) ($configured['ical_url'] ?? '')),
    ];
}
