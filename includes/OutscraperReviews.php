<?php

class OutscraperReviews {

    private $apiKey;
    private $dataFile;
    private $syncIntervalSeconds = 604800; // 7 days
    private $reviewsPerProperty = 4;

    private $propertyListingIds = [
        'chalet' => '608635403291162192',
        'home'   => '49479938',
        'villa'  => '1278298080134872974',
    ];

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->dataFile = __DIR__ . '/../cache/reviews.json';

        $cacheDir = dirname($this->dataFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
    }

    public function loadReviews() {
        if (!file_exists($this->dataFile)) {
            return ['last_sync' => 0, 'properties' => []];
        }

        $decoded = json_decode((string) file_get_contents($this->dataFile), true);
        if (!is_array($decoded)) {
            return ['last_sync' => 0, 'properties' => []];
        }

        return $decoded;
    }

    public function needsSync() {
        $data = $this->loadReviews();
        $lastSync = (int) ($data['last_sync'] ?? 0);
        return (time() - $lastSync) >= $this->syncIntervalSeconds;
    }

    public function syncAll($force = false) {
        if (!$force && !$this->needsSync()) {
            return ['synced' => false, 'reason' => 'Sync not due yet.'];
        }

        if (trim($this->apiKey) === '') {
            return ['synced' => false, 'reason' => 'No Outscraper API key configured.'];
        }

        $data = $this->loadReviews();
        $results = [];

        foreach ($this->propertyListingIds as $slug => $listingId) {
            $reviews = $this->fetchReviewsFromApi($listingId);
            if ($reviews !== false) {
                $data['properties'][$slug] = $reviews;
                $results[$slug] = count($reviews) . ' reviews fetched';
            } else {
                $results[$slug] = 'API call failed — kept existing reviews';
            }
        }

        $data['last_sync'] = time();
        $this->saveReviews($data);

        return ['synced' => true, 'results' => $results];
    }

    public function getPropertyReviews($slug) {
        $data = $this->loadReviews();
        return $data['properties'][$slug] ?? [];
    }

    public function getAllRecentReviews($limit = 6) {
        $data = $this->loadReviews();
        $all = [];

        foreach ($data['properties'] ?? [] as $slug => $reviews) {
            foreach ($reviews as $review) {
                $review['property_slug'] = $slug;
                $all[] = $review;
            }
        }

        usort($all, function ($a, $b) {
            $dateA = strtotime($a['date'] ?? '1970-01-01');
            $dateB = strtotime($b['date'] ?? '1970-01-01');
            return $dateB - $dateA;
        });

        return array_slice($all, 0, $limit);
    }

    public function getLastSyncTime() {
        $data = $this->loadReviews();
        return (int) ($data['last_sync'] ?? 0);
    }

    private function fetchReviewsFromApi($listingId) {
        if (!function_exists('curl_init')) {
            error_log('OutscraperReviews: curl not available');
            return false;
        }

        $url = 'https://api.app.outscraper.com/airbnb/reviews?' . http_build_query([
            'query' => 'https://www.airbnb.com/rooms/' . $listingId,
            'limit' => $this->reviewsPerProperty,
            'sort'  => 'newest',
            'async' => 'false',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'X-API-KEY: ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('OutscraperReviews curl error: ' . $curlError);
            return false;
        }

        if ($httpCode !== 200 || !$response) {
            error_log('OutscraperReviews HTTP ' . $httpCode . ' for listing ' . $listingId);
            return false;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            error_log('OutscraperReviews invalid JSON for listing ' . $listingId);
            return false;
        }

        return $this->normalizeReviews($decoded);
    }

    private function normalizeReviews(array $apiResponse) {
        $reviews = [];

        // Outscraper returns data in varying shapes; handle nested or flat arrays
        $items = [];
        if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
            // { "data": [ [...reviews...] ] } or { "data": [ {review}, ... ] }
            foreach ($apiResponse['data'] as $entry) {
                if (is_array($entry) && !isset($entry['reviewer_name'])) {
                    // Nested: each entry is an array of reviews
                    $items = array_merge($items, $entry);
                } else {
                    $items[] = $entry;
                }
            }
        } elseif (isset($apiResponse[0]) && is_array($apiResponse[0])) {
            // Top-level is array of review groups
            foreach ($apiResponse as $group) {
                if (is_array($group)) {
                    foreach ($group as $item) {
                        if (is_array($item)) {
                            $items[] = $item;
                        }
                    }
                }
            }
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $review = [
                'author'  => trim((string) ($item['reviewer_name'] ?? $item['author'] ?? $item['name'] ?? 'Guest')),
                'rating'  => (float) ($item['rating'] ?? $item['stars'] ?? $item['review_rating'] ?? 5),
                'date'    => trim((string) ($item['date'] ?? $item['review_date'] ?? $item['created_at'] ?? '')),
                'text'    => trim((string) ($item['comments'] ?? $item['text'] ?? $item['review_text'] ?? $item['body'] ?? '')),
            ];

            if ($review['text'] !== '') {
                $reviews[] = $review;
            }
        }

        return array_slice($reviews, 0, $this->reviewsPerProperty);
    }

    private function saveReviews(array $data) {
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
