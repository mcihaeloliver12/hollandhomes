<?php

class IcalAvailability {

    private $cacheDir;
    private $cacheTtl = 900;

    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function getBlockedDates($icalUrl, $slug = '', $forceRefresh = false) {
        $icalUrl = trim((string) $icalUrl);
        if ($icalUrl === '') {
            return [
                'source' => 'not-configured',
                'blocked_dates' => [],
                'message' => 'No iCal URL is configured for this listing.',
            ];
        }

        $cacheKey = 'ical_' . md5($slug . '|' . $icalUrl) . '.json';
        $cacheFile = $this->cacheDir . $cacheKey;
        $cached = null;

        if (!$forceRefresh && file_exists($cacheFile) && (time() - (int) filemtime($cacheFile)) < $this->cacheTtl) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['blocked_dates']) && is_array($cached['blocked_dates'])) {
                return $cached;
            }
        } elseif (file_exists($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
        }

        $icalText = $this->downloadIcal($icalUrl);
        if ($icalText === '') {
            if (is_array($cached) && isset($cached['blocked_dates']) && is_array($cached['blocked_dates']) && count($cached['blocked_dates']) > 0) {
                $cached['source'] = 'stale-cache';
                $cached['message'] = 'Using cached iCal availability because the latest refresh failed.';
                return $cached;
            }

            $result = [
                'source' => 'unavailable',
                'blocked_dates' => [],
                'message' => 'Unable to fetch iCal feed.',
            ];
            file_put_contents($cacheFile, json_encode($result));
            return $result;
        }

        $result = [
            'source' => 'live',
            'blocked_dates' => $this->parseBlockedDates($icalText),
            'message' => 'Live iCal availability loaded.',
        ];
        file_put_contents($cacheFile, json_encode($result));

        return $result;
    }

    private function downloadIcal($icalUrl) {
        if (!function_exists('curl_init')) {
            return '';
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $icalUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'HollandHomes/1.0');

        $content = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (!is_string($content) || $content === '' || $httpCode >= 400) {
            return '';
        }

        return $content;
    }

    private function parseBlockedDates($icalText) {
        $lines = preg_split('/\r\n|\n|\r/', $icalText);
        $unfolded = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $first = substr($line, 0, 1);
            if (($first === ' ' || $first === "\t") && !empty($unfolded)) {
                $unfolded[count($unfolded) - 1] .= substr($line, 1);
                continue;
            }
            $unfolded[] = $line;
        }

        $blocked = [];
        $inEvent = false;
        $event = [];

        foreach ($unfolded as $line) {
            $upperLine = strtoupper($line);

            if ($upperLine === 'BEGIN:VEVENT') {
                $inEvent = true;
                $event = [];
                continue;
            }

            if ($upperLine === 'END:VEVENT') {
                $this->appendEventDates($event, $blocked);
                $inEvent = false;
                $event = [];
                continue;
            }

            if (!$inEvent) {
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = strtoupper(trim($parts[0]));
            $value = trim($parts[1]);

            if (strpos($key, 'DTSTART') === 0) {
                $event['start'] = $value;
                continue;
            }
            if (strpos($key, 'DTEND') === 0) {
                $event['end'] = $value;
                continue;
            }
            if (strpos($key, 'STATUS') === 0) {
                $event['status'] = strtoupper($value);
            }
        }

        $blocked = array_values(array_unique($blocked));
        sort($blocked);

        return $blocked;
    }

    private function appendEventDates(array $event, array &$blocked) {
        $status = strtoupper((string) ($event['status'] ?? ''));
        if ($status === 'CANCELLED') {
            return;
        }

        if (!isset($event['start'])) {
            return;
        }

        $start = $this->parseIcalDate((string) $event['start']);
        if ($start === null) {
            return;
        }

        $end = isset($event['end']) ? $this->parseIcalDate((string) $event['end']) : null;
        if ($end === null || $end <= $start) {
            $end = $start->modify('+1 day');
        }

        for ($cursor = $start; $cursor < $end; $cursor = $cursor->modify('+1 day')) {
            $blocked[] = $cursor->format('Y-m-d');
        }
    }

    private function parseIcalDate($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timezone = new DateTimeZone('UTC');

        if (preg_match('/^\d{8}$/', $value) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Ymd', $value, $timezone);
            return $date ?: null;
        }

        if (preg_match('/^\d{8}T\d{6}Z$/', $value) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Ymd\THis\Z', $value, $timezone);
            return $date ? $date->setTime(0, 0, 0) : null;
        }

        if (preg_match('/^\d{8}T\d{6}$/', $value) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Ymd\THis', $value, $timezone);
            return $date ? $date->setTime(0, 0, 0) : null;
        }

        return null;
    }
}
