<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}
$properties = ['Chalet', 'Home', 'Villa'];

function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0';
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');   
    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

foreach ($properties as $prop) {
    echo "<h1>$prop</h1>";
    $dir = __DIR__ . "/$prop/Photos/";
    $files = scandir($dir);
    echo "<div style='display:flex; flex-wrap:wrap; gap:10px;'>";
    foreach ($files as $f) {
        if ($f == '.' || $f == '..') continue;
        if (strpos($f, '.DS_Store') !== false) continue;
        echo "<div style='width:200px;'>";
        echo "<img src='$prop/Photos/$f' style='width:100%; height:150px; object-fit:cover;'/>";
        echo "<p style='font-size:10px; word-break:break-all;'>$f</p>";
        $size = formatBytes(filesize($dir . $f));
        echo "<p style='font-size:10px;'>Size: $size</p>";
        echo "</div>";
    }
    echo "</div>";
}
?>
