<?php
/**
 * PriceLabs API Proxy
 * 
 * This proxy handles PriceLabs API calls from the frontend to avoid CORS issues.
 * The browser cannot call PriceLabs API directly due to CORS restrictions.
 * 
 * Setup:
 * 1. Get your API key from PriceLabs (Settings → API)
 * 2. Get your listing ID from PriceLabs
 * 3. Update the constants below
 * 4. Upload this file to: public_html/api/pricelabs-proxy.php
 */

// ========================================
// CONFIGURATION - UPDATE THESE VALUES
// ========================================

// Get this from PriceLabs → Settings → API
define('PRICELABS_API_KEY', 'A51qunJVnGEdrIOPz9aqPmyoRDGQqVJgRlGq1AsH');

// Get this from your PriceLabs listing URL or dashboard
define('LISTING_ID', '854400091154887268');

// Your PMS type (usually 'airbnb', 'vrbo', or 'direct')
define('PMS_TYPE', 'airbnb');

// ========================================
// API PROXY LOGIC
// ========================================

// Set headers to allow requests from your domain
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // In production, replace * with your domain
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Allow GET for debugging (shows API status)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'ok',
        'message' => 'PriceLabs proxy is running. Use POST to fetch pricing.',
        'config' => [
            'listingId' => LISTING_ID,
            'pmsType' => PMS_TYPE,
            'apiKeySet' => !empty(PRICELABS_API_KEY)
        ]
    ]);
    exit;
}

// Only allow POST requests for actual data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Get request data from frontend
$input = json_decode(file_get_contents('php://input'), true);

// Extract date range if provided, otherwise get next 12 months
$dateFrom = isset($input['dateFrom']) ? $input['dateFrom'] : date('Y-m-d');
$dateTo = isset($input['dateTo']) ? $input['dateTo'] : date('Y-m-d', strtotime('+12 months'));

// Prepare PriceLabs API request
$priceLabsRequest = [
    'listings' => [
        [
            'id' => LISTING_ID,
            'pms' => PMS_TYPE,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'reason' => false // Set to true if you want detailed pricing breakdown
        ]
    ]
];

// Call PriceLabs API
$ch = curl_init('https://api.pricelabs.co/v1/listing_prices');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($priceLabsRequest));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . PRICELABS_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle errors
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to connect to PriceLabs API',
        'details' => $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'PriceLabs API error',
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ]);
    exit;
}

// Parse response
$priceData = json_decode($response, true);

// Check for PriceLabs-specific errors
if (isset($priceData[0]['error'])) {
    $error = $priceData[0]['error'];
    http_response_code(400);
    
    $errorMessages = [
        'LISTING_NOT_PRESENT' => 'Listing not found in PriceLabs. Please connect your listing.',
        'LISTING_NO_DATA' => 'No pricing data available. Please review prices in PriceLabs dashboard.',
        'LISTING_TOGGLE_OFF' => 'Sync is turned OFF. Please enable sync in PriceLabs.'
    ];
    
    echo json_encode([
        'error' => $errorMessages[$error] ?? 'PriceLabs error: ' . $error,
        'errorCode' => $error
    ]);
    exit;
}

// Success - return pricing data
// Simplified format for frontend consumption
$simplifiedData = [];
$bookedCount = 0;
$availableCount = 0;

if (isset($priceData[0]['data']) && is_array($priceData[0]['data'])) {
    foreach ($priceData[0]['data'] as $dayData) {
        // Check availability - a date is unavailable if:
        // - booking_status is set and not empty (e.g., "booked", "blocked")
        // - OR unbookable is set and truthy
        // - OR available field is explicitly false
        $isBooked = false;
        
        // Check booking_status field
        if (isset($dayData['booking_status']) && !empty($dayData['booking_status'])) {
            $isBooked = true;
        }
        
        // Check unbookable field
        if (isset($dayData['unbookable']) && $dayData['unbookable']) {
            $isBooked = true;
        }
        
        // Check available field (some API versions use this)
        if (isset($dayData['available']) && $dayData['available'] === false) {
            $isBooked = true;
        }
        
        // Check status field (alternative field name)
        if (isset($dayData['status']) && !empty($dayData['status']) && $dayData['status'] !== 'available') {
            $isBooked = true;
        }
        
        if ($isBooked) {
            $bookedCount++;
        } else {
            $availableCount++;
        }
        
        $simplifiedData[$dayData['date']] = [
            'price' => intval($dayData['price']),
            'minStay' => isset($dayData['min_stay']) ? intval($dayData['min_stay']) : 1,
            'available' => !$isBooked,
            'weeklyDiscount' => isset($dayData['weekly_discount']) ? floatval($dayData['weekly_discount']) : 1.0,
            'monthlyDiscount' => isset($dayData['monthly_discount']) ? floatval($dayData['monthly_discount']) : 1.0,
        ];
    }
}

// Return formatted response with debug info
echo json_encode([
    'success' => true,
    'currency' => $priceData[0]['currency'] ?? 'USD',
    'lastRefreshed' => $priceData[0]['last_refreshed_at'] ?? null,
    'totalDays' => count($simplifiedData),
    'bookedDays' => $bookedCount,
    'availableDays' => $availableCount,
    'data' => $simplifiedData
]);
?>
