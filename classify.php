<?php
include('config.php');
// checkAccess('user'); // Uncomment this once you verify the AI works
set_time_limit(120);
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    die(json_encode(['error' => 'POST required']));
}

$imageFile = basename((string)($_POST['image'] ?? ''));
$imagePath = "uploads/" . $imageFile;
$jsonPath  = "uploads/" . pathinfo($imageFile, PATHINFO_FILENAME) . ".json";
$forceFresh = !empty($_POST['force']) && $_POST['force'] === '1';

if (!$imageFile || !is_file($imagePath)) {
    die(json_encode(['error' => 'Image not found']));
}

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    die(json_encode(['error' => 'API Key missing. Check .env.local.php']));
}

$imageData = base64_encode(file_get_contents($imagePath));

$payload = [
    "contents" => [[
        "parts" => [
            ["text" => "Identify the insects stuck on this trap. IGNORE the central wooden block. Focus on identifying if these are fruit flies (Bactrocera). Return ONLY a JSON object: { \"species\": \"...\", \"common_name\": \"...\", \"confidence\": 0.95, \"description\": \"...\" }"],
            ["inlineData" => ["mimeType" => "image/jpeg", "data" => $imageData]]
        ]
    ]]
];

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($apiKey);

$maxRetries = 3;
$attempt    = 0;
$httpCode   = 500;
$response   = '';
$isMock     = false;

while ($attempt < $maxRetries) {
    $attempt++;
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER,  ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT,        90);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) break;
    if ($httpCode === 503 || $httpCode === 429) { sleep($attempt * 2); continue; }
    break;
}

if ($httpCode !== 200) {
    if ($httpCode === 503 || $httpCode === 429) {
        // Mock fallback — mark it so we never cache this
        $isMock = true;
        $aiText = '{"species": "Bactrocera (genus) [MOCK: API UNAVAILABLE]", "common_name": "Fruit Fly", "confidence": 0.85, "description": "Mock description generated due to Google API high demand (503/429 error)."}';
        http_response_code(200);
    } else {
        http_response_code($httpCode ?: 500);
        echo json_encode(['error' => 'Google API Error', 'details' => json_decode($response, true) ?: $response]);
        exit;
    }
} else {
    $result = json_decode($response, true);
    $aiText = '';

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $aiText = $result['candidates'][0]['content']['parts'][0]['text'];
    } elseif (isset($result['promptFeedback']['blockReason'])) {
        $reason = $result['promptFeedback']['blockReason'];
        http_response_code(400);
        echo json_encode(['error' => 'Image blocked by Safety Filters', 'details' => "Reason: $reason"]);
        exit;
    } elseif (isset($result['error'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Gemini API Database Error', 'details' => $result['error']]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Unexpected API Response form', 'details' => $result]);
        exit;
    }

    // Extract JSON from any surrounding text
    $aiText = trim(str_ireplace(['```json', '```'], '', $aiText));
    if (isset($aiText[0]) && $aiText[0] !== '{') {
        $start = strpos($aiText, '{');
        $end   = strrpos($aiText, '}');
        if ($start !== false && $end !== false) {
            $aiText = substr($aiText, $start, $end - $start + 1);
        }
    }
}

// Decode fresh result
$freshData       = json_decode($aiText, true);
$freshConfidence = isset($freshData['confidence']) ? (float)$freshData['confidence'] : 0.0;

// Read existing cached confidence (for comparison info in response)
$cachedConfidence = null;
if (is_file($jsonPath)) {
    $cachedRaw  = file_get_contents($jsonPath);
    $cachedData = json_decode($cachedRaw, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($cachedData['confidence'])) {
        $cachedConfidence = (float)$cachedData['confidence'];
    }
}

// Determine winner
$finalData = $freshData;
$source    = 'fresh';
$confChange = null; // null = no prior cache

if ($cachedConfidence !== null) {
    $confChange = round(($freshConfidence - $cachedConfidence) * 100); // e.g. +2, -5, 0
    if (!$forceFresh && $cachedConfidence > $freshConfidence) {
        // Cached result is strictly better — keep it
        $cachedData = json_decode(file_get_contents($jsonPath), true);
        $finalData  = $cachedData;
        $source     = 'cached';
        $confChange = round(($freshConfidence - $cachedConfidence) * 100); // negative
    }
}

// Build response (source + conf change meta included for frontend, NOT stored in cache)
$response = array_merge($finalData, [
    'source'      => $source,
    'conf_change' => $confChange,
    'is_mock'     => $isMock,
    'old_conf'    => $cachedConfidence,
]);

// Persist to cache only when fresh wins AND result is real (not a mock fallback)
if ($source === 'fresh' && !$isMock) {
    file_put_contents($jsonPath, json_encode($freshData)); // pure data, no meta fields
}

echo json_encode($response);