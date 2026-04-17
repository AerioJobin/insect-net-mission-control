<?php
include('config.php');
checkAccess('admin'); // Only admins can actually run this file
// Simple endpoint to delete an uploaded image by filename.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Accept urlencoded or JSON payloads
$inputFile = $_POST['file'] ?? null;
if ($inputFile === null) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (isset($json['file'])) {
            $inputFile = $json['file'];
        }
    }
}

if (!$inputFile) {
    echo json_encode(['status' => 'error', 'message' => 'No file provided']);
    exit;
}

$file = basename($inputFile); // prevent directory traversal
$path = $upload_dir . $file;

// Only allow image extensions we expect
if (!preg_match('/\.(jpe?g|png)$/i', $file)) {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported file type']);
    exit;
}

if (!file_exists($path)) {
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
    exit;
}

$realBase = realpath($upload_dir);
$realFile = realpath($path);
if ($realBase === false || $realFile === false || strpos($realFile, $realBase) !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file location']);
    exit;
}

if (@unlink($realFile)) {
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unable to delete file']);
}
