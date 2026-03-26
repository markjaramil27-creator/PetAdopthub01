<?php
// Session configuration: must be first
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
// Site configuration
define('SITE_NAME', 'PetAdoptHub');
// SITE_URL will be derived automatically; the old hard–coded value often caused
// mismatches when the project was placed in a subfolder (e.g. /Coding/Petadopthub).
// The function below constructs a base URL from the current request and strips any
// trailing "/admin" segment so that admin pages still resolve assets correctly.
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base   = preg_replace('#/admin$#', '', $base);
    if ($base === '/') {
        $base = '';
    }
    define('SITE_URL', $protocol . $host . $base);
}
define('ADMIN_EMAIL', 'admin@petadoptHub.com');
// choose a default pet image URL; use a local placeholder if available, otherwise use an online placeholder
// The constant should include the application's base path (site root) so that it works
// even when the project lives in a subdirectory such as /petadopthub.
if (!defined('DEFAULT_PET_IMAGE_URL')) {
    // derive path component from SITE_URL; fall back to empty if parsing fails
    $basePath = '';
    if (defined('SITE_URL') && !empty(SITE_URL)) {
        $p = parse_url(SITE_URL, PHP_URL_PATH);
        $basePath = $p ?: '';
    }
    $basePath = $basePath !== '' ? '/' . trim($basePath, '/') : '';

    $localDefault = $basePath . '/images/default-pet.png';
    if (file_exists(__DIR__ . '/../images/default-pet.png')) {
        // build absolute URL for default image as well
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host   = $_SERVER['HTTP_HOST'];
        define('DEFAULT_PET_IMAGE_URL', $scheme . $host . $localDefault);
    } else {
        // fallback to an external service so we never end up with a broken image
        define('DEFAULT_PET_IMAGE_URL', 'https://via.placeholder.com/300x200?text=No+Image');
    }
}
function getBaseUrl() {
    // kept for backward compatibility; returns the same value as SITE_URL
    return SITE_URL;
}