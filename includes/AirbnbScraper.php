<?php

class AirbnbScraper {

    private $cacheDir;
    private $cacheTime = 3600;

    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function getListingInfo($url) {
        $hash = md5($url);
        $cacheFile = $this->cacheDir . 'airbnb_' . $hash . '.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTime) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && array_key_exists('source', $cached) && array_key_exists('title', $cached)) {
                return $cached;
            }
        }

        $result = [
            'title' => '',
            'description' => '',
            'listing_type' => '',
            'listing_label' => '',
            'location' => '',
            'bedrooms' => null,
            'beds' => null,
            'baths' => null,
            'reviews_count' => 0,
            'rating' => 'N/A',
            'reviews' => [],
            'lat' => null,
            'lng' => null,
            'source' => 'unverified',
            'message' => 'Verified Airbnb details could not be imported from the public listing page.',
            'url' => $url,
        ];

        $html = $this->fetchListingHtml($url);
        if ($html) {
            $parsed = $this->parseListing($html);
            $result = array_merge($result, $parsed);

            if ($result['title'] !== '' || $result['description'] !== '' || $result['rating'] !== 'N/A') {
                $result['source'] = 'listing-page';
                $result['message'] = 'Listing summary detected from the public Airbnb page.';
            }
        }

        file_put_contents($cacheFile, json_encode($result));

        return $result;
    }

    private function fetchListingHtml($url) {
        if (!function_exists('curl_init')) {
            return false;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 8);

        $html = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($html === false || $httpCode >= 400) {
            return false;
        }

        return $html;
    }

    private function parseListing($html) {
        $result = [
            'title' => $this->decode($this->match('/property="og:title"\s+content="([^"]+)"/i', $html)),
            'description' => '',
            'listing_type' => '',
            'listing_label' => '',
            'location' => '',
            'bedrooms' => null,
            'beds' => null,
            'baths' => null,
            'reviews_count' => 0,
            'rating' => 'N/A',
            'lat' => null,
            'lng' => null,
        ];

        $descriptionMeta = $this->decode($this->match('/name="description"\s+content="([^"]+)"/i', $html));
        if ($descriptionMeta !== '') {
            $parts = explode(' · ', $descriptionMeta);
            if (count($parts) >= 3) {
                $result['listing_label'] = $parts[1];
                $result['description'] = implode(' · ', array_slice($parts, 2));
            } else {
                $result['description'] = $descriptionMeta;
            }
        }

        $this->parseTitleFacts($result['title'], $result);

        $ratingValue = $this->match('/"ratingValue"\s*:\s*"?([\d\.]+)"?/i', $html);
        if ($ratingValue !== '') {
            $result['rating'] = $ratingValue;
        }

        $reviewCount = $this->match('/"reviewCount"\s*:\s*"?(\d+)"?/i', $html);
        if ($reviewCount !== '') {
            $result['reviews_count'] = (int) $reviewCount;
        }

        $latitude = $this->match('/"lat":(-?[\d\.]+)/i', $html);
        $longitude = $this->match('/"lng":(-?[\d\.]+)/i', $html);
        if ($latitude !== '') {
            $result['lat'] = (float) $latitude;
        }
        if ($longitude !== '') {
            $result['lng'] = (float) $longitude;
        }

        return $result;
    }

    private function parseTitleFacts($title, array &$result) {
        if ($title === '') {
            return;
        }

        $parts = array_map('trim', explode('·', $title));
        if (!empty($parts[0]) && preg_match('/^(.+?)\s+in\s+(.+)$/i', $parts[0], $matches)) {
            $result['listing_type'] = trim($matches[1]);
            $result['location'] = trim($matches[2]);
        }

        foreach ($parts as $part) {
            if (preg_match('/★\s*([\d\.]+)/u', $part, $matches)) {
                $result['rating'] = $matches[1];
            }
            if (preg_match('/(\d+(?:\.\d+)?)\s+bedrooms?/i', $part, $matches)) {
                $result['bedrooms'] = (float) $matches[1];
            }
            if (preg_match('/(\d+(?:\.\d+)?)\s+beds?/i', $part, $matches)) {
                $result['beds'] = (float) $matches[1];
            }
            if (preg_match('/(\d+(?:\.\d+)?)\s+baths?/i', $part, $matches)) {
                $result['baths'] = (float) $matches[1];
            }
        }
    }

    private function match($pattern, $html) {
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function decode($value) {
        return html_entity_decode(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }
}
