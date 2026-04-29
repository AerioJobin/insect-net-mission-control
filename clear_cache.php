<?php
include('config.php');
checkAccess('user');
header('Content-Type: application/json; charset=utf-8');

// ═══ CSRF VALIDATION ═══
$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['cleared' => false, 'error' => 'Invalid security token. Please refresh the page.']);
    exit;
}

$imageFile = basename((string)($_POST['image'] ?? ''));
$jsonPath  = "uploads/" . pathinfo($imageFile, PATHINFO_FILENAME) . ".json";

if (!$imageFile) {
    http_response_code(400);
    echo json_encode(['error' => 'No image specified']);
    exit;
}

if (!is_file($jsonPath)) {
    echo json_encode(['cleared' => false, 'message' => 'No cache found for this image']);
    exit;
}

if (unlink($jsonPath)) {
    echo json_encode(['cleared' => true, 'message' => 'Cache cleared successfully']);
} else {
    http_response_code(500);
    echo json_encode(['cleared' => false, 'message' => 'Failed to delete cache file']);
}
