<?php
include('config.php');
checkAccess('user');
header('Content-Type: application/json; charset=utf-8');

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
