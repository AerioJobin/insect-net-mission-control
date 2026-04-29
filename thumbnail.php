<?php
/**
 * thumbnail.php — On-demand thumbnail generator
 * 
 * Usage: thumbnail.php?file=cam1_xxx.jpg&w=300
 * 
 * Generates a downsized JPEG thumbnail on first request, caches it to
 * uploads/thumbs/, and serves it directly on subsequent requests.
 * Requires the PHP GD extension (standard on most hosting).
 */
include('config.php');
checkAccess('user');

$file = basename((string)($_GET['file'] ?? ''));
$maxW = min(max((int)($_GET['w'] ?? 300), 50), 800); // clamp 50-800px

if (!$file) {
    http_response_code(400);
    die('No file specified');
}

$srcPath = __DIR__ . '/uploads/' . $file;
if (!is_file($srcPath)) {
    http_response_code(404);
    die('Image not found');
}

// Only process image files
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
    http_response_code(400);
    die('Unsupported format');
}

// Thumbs directory
$thumbDir = __DIR__ . '/uploads/thumbs/';
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

// Cache key includes width for flexibility
$thumbFile = $thumbDir . pathinfo($file, PATHINFO_FILENAME) . "_w{$maxW}.jpg";

// Serve cached thumbnail if it exists and is newer than original
if (is_file($thumbFile) && filemtime($thumbFile) >= filemtime($srcPath)) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400'); // 24h browser cache
    header('Content-Length: ' . filesize($thumbFile));
    readfile($thumbFile);
    exit;
}

// Generate thumbnail using GD
if (!function_exists('imagecreatefromjpeg')) {
    // GD not available — fall back to serving original
    header('Content-Type: image/jpeg');
    readfile($srcPath);
    exit;
}

// Load source image
if ($ext === 'png') {
    $src = @imagecreatefrompng($srcPath);
} else {
    $src = @imagecreatefromjpeg($srcPath);
}

if (!$src) {
    // Can't decode — serve original
    header('Content-Type: image/jpeg');
    readfile($srcPath);
    exit;
}

$origW = imagesx($src);
$origH = imagesy($src);

// If image is already smaller than target, serve as-is
if ($origW <= $maxW) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    readfile($srcPath);
    imagedestroy($src);
    exit;
}

// Calculate proportional height
$newW = $maxW;
$newH = (int) round($origH * ($maxW / $origW));

// Create resized image
$thumb = imagecreatetruecolor($newW, $newH);

// Preserve quality with resampling
imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

// Save to cache (quality 80 — good balance of size vs quality)
imagejpeg($thumb, $thumbFile, 80);

// Serve the thumbnail
header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . filesize($thumbFile));
readfile($thumbFile);

// Cleanup
imagedestroy($src);
imagedestroy($thumb);
