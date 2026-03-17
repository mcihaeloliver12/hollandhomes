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
                    'description' => 'This dog-friendly chalet in Rockaway Beach is the perfect place for your next Oregon Coast getaway.',
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
                'about_the_space' => "This dog-friendly chalet in Rockaway Beach is the perfect place for your next vacation on the Oregon Coast. You’ll enjoy peace and quiet with chirping birds and wild deer, while still having everything close at hand—this is an easy base for appreciating the natural beauty of the area with the comforts of home.\n\n<strong>The space</strong>\nSimple yet high-end, woodsy yet beachy, snug yet airy—this dual personality, newly remodeled “A-frame” retreat has an inviting, open concept living space with plenty of windows to let in the natural light. The full kitchen is thoughtfully stocked with dishes, cookware, herbs and spices, a blender, and coffee supplies: a Keurig, French press, and grinder.\n\nEnjoy the sand between your toes as you relax on the beach, or stretch out on the patio with a cup of coffee or a glass of wine. A fire pit sits right off the patio for time spent s’mores roasting and telling ghost stories. If it happens to rain, cozy up with a book next to the wall of windows and electric fireplace, or upstairs in the open loft space with a TV lounge. No matter the weather, a spot to rest and recreate awaits.\n\n<strong>Other things to note</strong>\nThere are only 2 beds in the home, however, there is a Pack 'n Play in the master closet for a little one. There is also a comfortable couch upstairs that might be able to fit a 5th person. Please message me if you are considering 5 people as we might be able to make this work.\n\nTemperatures at the coast rarely rise above the 70’s, but on rare warm days, you’ll need to manage the operable windows and doors, bedroom fans and window shades to your taste; let the cool night air in while you sleep, 60’s style. Our guests love the connection to nature on the Coast—we hope you and your furry friends will, too!\n\n<strong>Parking:</strong> There is free parking for 3 vehicles. Additional vehicles can park on the street near the home.\n\n<strong>Security:</strong> For your safety and the security of the home when it is unoccupied, there is a security camera which monitors the driveway area. Camera is not monitored when guests are present. There are no interior cameras or recording devices.\n\n<strong>HOUSE RULES</strong>\nNo shoes in the house, please.\nWe allow up to 2 well behaved and completely housebroken dogs. There is a $100 pet fee, per dog, for the additional cleaning required after your departure. Sorry, no cats.\nPlease do not leave your dog in the home unattended unless you bring a kennel. Thank you for your understanding.\nNo parties or events. No gatherings of 6 people or more. We strictly enforce this.\n\n<strong>Short term rental license #3124</strong>",
                'booking_fees' => [
                    'cleaning_fee' => 200.0,
                    'pet_fee' => 100.0,
                    'pets_allowed' => true,
                    'state_tax_rate' => 1.5,
                    'state_tax_label' => 'Transient Lodging Tax (Oregon)',
                    'city_tax_rate' => 10.0,
                    'city_tax_label' => 'Transient Room Tax (Rockaway Beach)',
                    'county_tax_rate' => 1.5,
                    'county_tax_label' => 'Transient Lodging Tax (Tillamook)',
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
                    'The sands of Rockaway Beach are only a half-mile walk away, with the rest of the Oregon Coast an easy drive away.',
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
                'card_image' => 'Home/Photos/79de0936-dc9c-4823-87ab-dd5344217580.jpg',
                'airbnb_url' => 'https://www.airbnb.com/rooms/49479938',
                'fallback_airbnb' => [
                    'title' => 'Oasis in Rockaway Beach · ★4.99 · 6 bedrooms · 8 beds · 3 baths',
                    'description' => 'Welcome to your private oasis in Rockaway Beach, Oregon. This exclusive retreat features a cedar barrel sauna, hot tub, and revitalizing cold plunge.',
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
                'about_the_space' => "Welcome to your private oasis in Rockaway Beach, Oregon! This exclusive retreat boasts a cedar barrel sauna, a hot tub, a revitalizing cold plunge, and an outdoor shower equipped with both hot and cold water. Unwind around the wood-burning fire pit or enjoy the convenience of the instant start gas fire pit. With accommodations for up to 12 adults and 2 children, your furry friends are also welcome. Just a quick 2-minute stroll along a sandy path from the backyard leads you directly to the beach.\n\n<strong>The space</strong>\nAmong our favorite features that make this property welcoming in every season are its hot tub, cedar barrel sauna, cold plunge an outdoor shower to rinse off sandy feet and paws, and a s’mores-ready outdoor wood-burning fire pit.\n\nIn addition to 2 bedrooms, the main ground floor offers an open living space with a gas fireplace, a smart TV, and a well-equipped kitchen. On the second floor, you’ll find a game room with plenty of seating, board games, books, toys and puzzles, and a further 4 bedrooms, including a master suite with a gas fireplace and ensuite with a large jetted tub.\n\n<strong>EXTRA PERKS & MORE DETAILS</strong>\nWe want you to feel at home. For families with young children, there is a diaper pail, baby bath, baby monitor as well as a Pack-n-Play stored in the master closet. There’s also a highchair, baby gates, a wagon to haul your stuff or kiddos to the beach, a cooler on wheels, beach toys, beach umbrellas, and beach chairs for the entire family (all stored in the garage).\n\nThis beach retreat is on a quiet, private, dead-end street with close beach access via a private sandy path in the backyard. Soothing ocean sights and sounds, along with fires in the backyard fire pit or on the beach, as well as nearby crabbing and fishing, make this the perfect location and house for your Oregon Coast vacation.\n\nTemperatures at the coast rarely rise above the 70’s, but on rare warm days, you’ll need to manage the operable windows and doors, bedroom fans and window shades to your taste; let the cool night air in while you sleep, 60’s style. Our guests love the connection to nature on the Coast—we hope you and your furry friends will, too!\n\n<strong>OUTDOOR AREA & AMENITIES</strong>\nUse the gas grill and outdoor furniture to dine al fresco.\nIn the evenings, gather around the cozy wood-burning fire pit, or take a dip in the hot tub.\n\nBring your boat for crabbing and fishing. Our garage is large enough to accommodate a medium sized boat.\n\n<strong>Note:</strong> Up to 2 well behaved and completely housebroken dogs are welcome. There is a $200 pet fee for the additional cleaning required after your departure. Sorry, no cats. Please do not leave your dog in the home unattended unless you bring a kennel. Thank you for your understanding:)\n\n<strong>Parking:</strong> There is free parking for a maximum of 6 vehicles. This is strictly enforced. Please note, this is a private street so street parking is not allowed. You can park in the driveway and fit 1-2 vehicles in the garage.\n\n<strong>Security:</strong> For your safety and the security of the house when it is unoccupied, there are security cameras which monitor the driveway and the front yard. There are no cameras in the backyard, hot tub area or inside the house.\n\n<strong>Short Term Rental Permit number:</strong>\n851/21/000036/STVR",
                'booking_fees' => [
                    'cleaning_fee' => 425.0,
                    'pet_fee' => 100.0,
                    'pets_allowed' => true,
                    'state_tax_rate' => 1.5,
                    'state_tax_label' => 'Transient Lodging Tax (Oregon)',
                    'city_tax_rate' => 0.0,
                    'city_tax_label' => '',
                    'county_tax_rate' => 0.0,
                    'county_tax_label' => 'Transient Lodging Tax (Tillamook)',
                ],
                'badges' => ['Cedar barrel sauna', 'Hot tub', 'Cold plunge', 'Rockaway Beach'],
                'detail_blocks' => [
                    [
                        'title' => 'Wellness amenities',
                        'text' => 'Unwind in the cedar barrel sauna, soak in the hot tub, or take a refreshing cold plunge, all steps from your door.',
                    ],
                    [
                        'title' => 'Room for everyone',
                        'text' => 'With six bedrooms, eight beds, and three bathrooms, The Oasis comfortably hosts large groups, family reunions, and friend getaways. Two laundry rooms, upstairs and downstairs living areas add even more flexibility.',
                    ],
                    [
                        'title' => 'Your private oasis',
                        'text' => 'An exclusive coastal retreat in Rockaway Beach designed for relaxation, privacy, and making memories with the people who matter most.',
                    ],
                ],
                'amenity_groups' => [
                    ['title' => 'Wellness amenities', 'items' => ['Cedar barrel sauna', 'Hot tub', 'Cold plunge', 'Private retreat feel']],
                    ['title' => 'Property highlights', 'items' => ['Entire home', 'Cedar barrel sauna, hot tub, and cold plunge', 'Exclusive retreat setting', 'Coastal group getaway']],
                    ['title' => 'At a glance', 'items' => ['6 bedrooms', '8 beds', '3 baths', '4.99 star Airbnb rating']],
                    ['title' => 'Ideal for', 'items' => ['Friend groups', 'Family gatherings', 'Long weekends', 'Large coastal getaways']],
                ],
                'stay_notes' => [
                    'Perfect for groups seeking both space and premium wellness amenities.',
                    'The sauna, hot tub, and cold plunge are available for all guests.',
                    'Only a 2-minute sand-under-toes stroll to the beach.',
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
                    'description' => 'Welcome to The Palm Springs Modern Villa, an exclusive and iconic retreat nestled in the heart of Palm Springs.',
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
                'about_the_space' => "Welcome to The Palm Springs Modern Villa, an exclusive and iconic retreat nestled in the heart of Palm Springs. Crafted by the renowned architectural duo Palmer & Krisel, this sophisticated mid-century modern estate is situated in The Racquet Club Estates neighborhood and embodies the quintessential Palm Springs lifestyle. The impeccably chic interiors, feature three luxurious bedrooms, a chef’s kitchen, and inviting fireplaces both indoors and outdoors.\n\n<strong>The space</strong>\nDiscover your peaceful and entirely private outdoor retreat, complete with stunning views of the San Jacinto Mountains. This exquisite sanctuary features a large saltwater pool, a rejuvenating hot tub, two outdoor showers, a gas firepit, a fully equipped outdoor kitchen, and a delightful dining area al fresco. One of the standout features of this property is that Victoria Park is just steps away—literally in the backyard! Simply open the back gate, and you'll find yourself in Victoria Park, which boasts a brand new playground, sand volleyball courts, and plenty of space for various activities like football and soccer. It's the perfect spot for outdoor fun and recreation!\n\nAdditionally, the garage has been transformed into a game room, complete with classic Pac-Man and foosball for endless entertainment.\n\nThe Palm Springs Modern Villa embodies the ultimate vision of a Palm Springs getaway. If you desire premium, resort-style luxury coupled with the seclusion and flexibility of a vacation rental, you’ve found your paradise. Once you step inside, you may never want to leave!\n\n<strong>Important Information to note:</strong>\nHeating the pool is available for an additional fee: $100 per day (recommended October through April). These charges reflect the actual costs of heating the pool due to the increasing expenses of natural gas; there is no markup included. Spa heating is complementary with your rental. We kindly ask that you inform us at least 48 hours before your arrival if you would like the pool heated, ensuring it is warmed up and ready for you.\n\nPlease be aware that this is not a party venue. The local community is vigilant, and any indication of a gathering/party could result in police involvement and the immediate removal of guests from the premises. Please also note that outdoor music is prohibited in short-term rentals in the Palm Springs area.\n\nSmoking is prohibited inside the home. However, you are welcome to smoke in the backyard, embracing the laid-back Palm Springs lifestyle.\n\nWhile we adore pets, we kindly request that you leave them at home, as this property enforces a strict no-pet policy. Thank you for your understanding.\n\n<strong>Neighborhood Attractions</strong>\nHistorically, the highlight of Palm Springs was The Racquet Club, established in 1934 by actors Charlie Farrell and Ralph Bellamy. This exclusive resort attracted A-list Hollywood stars such as Frank Sinatra, Dean Martin, and Marilyn Monroe, who enjoyed tennis and poolside socializing in a luxurious yet relaxed atmosphere. Although The Racquet Club no longer exists, you can still experience a similar ambiance in the neighboring Racquet Club Estates. This area showcases homes designed by renowned Mid-Century architects William Krisel and Dan Palmer, in partnership with The Alexander Construction Company. The Palm Springs Modern Villa is conveniently located just minutes from The Uptown Design District, featuring a variety of upscale and vintage boutiques, as well as innovative dining options housed in stunning mid-century architecture. A short drive further will take you directly to the bustling heart of Downtown Palm Springs.\n\nFor your safety and the security of the home when it is unoccupied, there are security cameras which monitor the driveway and backyard pool areas. Rest assured, these cameras are not monitored while guests are present. Please note that there are no cameras or recording devices inside of the home.\n\n<strong>Registration Details</strong>\nThe City of Palm Springs ID #054818",
                'booking_fees' => [
                    'cleaning_fee' => 325.0,
                    'pet_fee' => 0.0,
                    'pets_allowed' => false,
                    'pool_heat_fee' => 100.0,
                    'pool_heat_label' => 'Pool heating',
                    'state_tax_rate' => 0.0,
                    'state_tax_label' => '',
                    'city_tax_rate' => 11.5,
                    'city_tax_label' => 'Transient Occupancy Tax',
                    'county_tax_rate' => 1.0,
                    'county_tax_label' => 'Tourism Assessment/Fee tax',
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
                    ['title' => 'Property highlights', 'items' => ['Entire villa', 'Private saltwater pool and hot tub', 'Modern architectural design', 'Iconic retreat setting']],
                    ['title' => 'At a glance', 'items' => ['3 bedrooms', '3 beds', '2.5 baths', 'Game room', '5.0 star Airbnb rating']],
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
    $siteData = hh_site_data();
    $properties = is_array($siteData['properties'] ?? null) ? $siteData['properties'] : [];
    $property = is_array($properties[$slug] ?? null) ? $properties[$slug] : [];
    $feeDefaults = is_array($property['booking_fees'] ?? null) ? $property['booking_fees'] : [];
    $minNights = (int) ($configured['min_nights'] ?? hh_default_min_nights($slug));
    if ($minNights < 1) {
        $minNights = hh_default_min_nights($slug);
    }

    $petsAllowedDefault = (bool) ($feeDefaults['pets_allowed'] ?? false);
    $petsAllowedRaw = $configured['pets_allowed'] ?? $petsAllowedDefault;
    $petsAllowed = $petsAllowedDefault;
    if (is_bool($petsAllowedRaw)) {
        $petsAllowed = $petsAllowedRaw;
    } elseif (is_string($petsAllowedRaw)) {
        $normalized = strtolower(trim($petsAllowedRaw));
        $petsAllowed = in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    } elseif (is_int($petsAllowedRaw) || is_float($petsAllowedRaw)) {
        $petsAllowed = ((float) $petsAllowedRaw) !== 0.0;
    }

    return [
        'min_nights' => $minNights,
        'ical_url' => trim((string) ($configured['ical_url'] ?? '')),
        'cleaning_fee' => max(0, (float) ($configured['cleaning_fee'] ?? ($feeDefaults['cleaning_fee'] ?? 0))),
        'pet_fee' => max(0, (float) ($configured['pet_fee'] ?? ($feeDefaults['pet_fee'] ?? 0))),
        'pets_allowed' => $petsAllowed,
        'pool_heat_fee' => max(0, (float) ($configured['pool_heat_fee'] ?? ($feeDefaults['pool_heat_fee'] ?? 0))),
        'pool_heat_label' => trim((string) ($feeDefaults['pool_heat_label'] ?? 'Pool heating')),
        'state_tax_rate' => max(0, (float) ($configured['state_tax_rate'] ?? ($feeDefaults['state_tax_rate'] ?? 0))),
        'state_tax_label' => trim((string) ($feeDefaults['state_tax_label'] ?? 'State tax')),
        'city_tax_rate' => max(0, (float) ($configured['city_tax_rate'] ?? ($feeDefaults['city_tax_rate'] ?? 0))),
        'city_tax_label' => trim((string) ($feeDefaults['city_tax_label'] ?? 'City tax')),
        'county_tax_rate' => max(0, (float) ($configured['county_tax_rate'] ?? ($feeDefaults['county_tax_rate'] ?? 0))),
        'county_tax_label' => trim((string) ($feeDefaults['county_tax_label'] ?? 'County tax')),
    ];
}
