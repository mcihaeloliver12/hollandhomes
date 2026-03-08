<?php

class PriceLabsAPI {

    private $token;
    private $cacheDir;
    private $cacheTime = 3600;

    public function __construct($token) {
        $this->token = $token;
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function getListings() {
        $cacheFile = $this->cacheDir . 'pricelabs_listings.json';

        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            $age = time() - (int) filemtime($cacheFile);
            if (is_array($cached) && array_key_exists('source', $cached)) {
                $isLive = ($cached['source'] ?? '') === 'live';
                $fallbackCacheTtl = 60;
                if (($isLive && $age < $this->cacheTime) || (!$isLive && $age < $fallbackCacheTtl)) {
                    return $cached;
                }
            }
        }

        $url = 'https://api.pricelabs.co/v1/listings?' . http_build_query([
            'api_key' => $this->token,
        ]);
        $response = false;
        $httpcode = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            if ($curlError) {
                error_log('PriceLabs API curl error: ' . $curlError);
            }
        }

        $result = [
            'status' => $httpcode,
            'data' => null,
            'source' => 'fallback',
            'message' => 'PriceLabs live listing data was not available, so fallback pricing is being displayed.',
        ];

        if ($httpcode === 200 && $response) {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                $list = [];
                if (isset($decoded['listings']) && is_array($decoded['listings'])) {
                    $list = $decoded['listings'];
                } elseif (array_is_list($decoded)) {
                    $list = $decoded;
                }
                if (!empty($list)) {
                    $result['data'] = $list;
                    $result['source'] = 'live';
                    $result['message'] = 'Live PriceLabs data retrieved successfully.';
                }
            }
        }

        if (!is_array($result['data'])) {
            $result['data'] = [
                ['id' => 'chalet', 'price' => 450, 'min_stay' => 2, 'currency' => 'USD'],
                ['id' => 'home', 'price' => 300, 'min_stay' => 3, 'currency' => 'USD'],
                ['id' => 'villa', 'price' => 1200, 'min_stay' => 4, 'currency' => 'USD'],
            ];
        }

        file_put_contents($cacheFile, json_encode($result));
        return $result;
    }

    public function getListingDefaults($propertyId) {
        $listings = $this->getListings();
        $baseListing = ['price' => 0, 'min_stay' => null, 'currency' => 'USD', 'source' => $listings['source'] ?? 'fallback'];
        $idMap = [
            'chalet' => '608635403291162192',
            'home' => '49479938',
            'villa' => '1278298080134872974',
        ];

        if (isset($listings['data']) && is_array($listings['data'])) {
            foreach ($listings['data'] as $listing) {
                $listingId = (string) ($listing['id'] ?? '');
                if ($listingId !== '' && isset($idMap[$propertyId]) && $listingId === $idMap[$propertyId]) {
                    return [
                        'price' => (float) ($listing['base'] ?? $listing['recommended_base_price'] ?? 0),
                        // PriceLabs "min" is the minimum price floor, not minimum nights.
                        'min_stay' => null,
                        'currency' => 'USD',
                        'source' => $listings['source'] ?? 'fallback',
                        'id' => $listingId,
                        'name' => (string) ($listing['name'] ?? ''),
                    ];
                }
            }
        }

        if ($propertyId === 'chalet') {
            return ['price' => 450, 'min_stay' => null, 'currency' => 'USD', 'source' => 'fallback'];
        }
        if ($propertyId === 'home') {
            return ['price' => 300, 'min_stay' => null, 'currency' => 'USD', 'source' => 'fallback'];
        }
        if ($propertyId === 'villa') {
            return ['price' => 1200, 'min_stay' => null, 'currency' => 'USD', 'source' => 'fallback'];
        }

        return $baseListing;
    }

    public function getPricingData($listingId, $startDate, $endDate) {
        $cacheKey = 'pricelabs_pricing_' . md5($listingId . $startDate . $endDate);
        $cacheFile = $this->cacheDir . $cacheKey . '.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTime) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $url = 'https://api.pricelabs.co/v1/pricing?' . http_build_query([
            'api_key' => $this->token,
            'listing_id' => $listingId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $result = ['source' => 'fallback', 'data' => []];

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            if ($curlError) {
                error_log('PriceLabs pricing API curl error: ' . $curlError);
            }

            if ($httpcode === 200 && $response) {
                $decoded = json_decode($response, true);
                if (is_array($decoded)) {
                    $result['data'] = $decoded;
                    $result['source'] = 'live';
                }
            }
        }

        file_put_contents($cacheFile, json_encode($result));
        return $result;
    }
}
