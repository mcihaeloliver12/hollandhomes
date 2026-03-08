<?php
/**
 * Review sync script — run via cron or manually.
 *
 * Cron example (once per week, Sunday at 3 AM):
 *   0 3 * * 0 php /path/to/sync_reviews.php
 *
 * Manual (force sync even if not due):
 *   php sync_reviews.php --force
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/OutscraperReviews.php';

$force = in_array('--force', $argv ?? [], true);

$reviewer = new OutscraperReviews(OUTSCRAPER_API_KEY);
$result = $reviewer->syncAll($force);

if (php_sapi_name() === 'cli') {
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    session_start();
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: dashboard.php');
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}
