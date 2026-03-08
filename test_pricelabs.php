<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/PriceLabsAPI.php';
$token = PRICELABS_API_TOKEN;
$urls = [
    'https://api.pricelabs.co/v1/listings?api_key=' . urlencode($token),
    'https://api.pricelabs.co/v1/listing?api_key=' . urlencode($token)
];

foreach ($urls as $url) {
    echo "Testing $url...\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpcode\n";
    echo "Response: " . substr($response, 0, 500) . "\n\n";
}
