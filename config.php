<?php
// ═══ SESSION SECURITY CONFIGURATION ═══
// Set secure session parameters BEFORE starting the session
ini_set('session.cookie_httponly', 1);    // Prevent JS access to session cookie
ini_set('session.cookie_samesite', 'Lax'); // CSRF protection via SameSite
ini_set('session.use_strict_mode', 1);     // Reject uninitialized session IDs
ini_set('session.use_only_cookies', 1);    // Don't allow session ID in URL

session_start();

// ═══ OPTIONAL LOCAL SECRETS (SERVER-ONLY) ═══
// Create a private file `webdash/.env.local.php` on the server:
//   <?php
//   putenv('GEMINI_API_KEY=YOUR_KEY');
//   $_ENV['GEMINI_API_KEY'] = 'YOUR_KEY';
// This keeps API keys out of tracked source code.
if (file_exists(__DIR__ . '/.env.local.php')) {
    require __DIR__ . '/.env.local.php';
}

// ═══ SESSION CONFIGURATION ═══
define('SESSION_TIMEOUT', 1800); // 30 minutes (in seconds)

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start(); // Start a clean session for the redirect
    header("Location: login.php?expired=1");
    exit();
}

// Update last activity time
if (isset($_SESSION['username'])) {
    $_SESSION['last_activity'] = time();
}

// ═══ USER DATABASE (loaded from users.json for runtime editability) ═══
$usersFile = __DIR__ . '/users.json';
$users = file_exists($usersFile) ? (json_decode(file_get_contents($usersFile), true) ?? []) : [];

// ═══ CSRF TOKEN GENERATOR ═══
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ═══ CSRF TOKEN VALIDATOR ═══
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ═══ ACCESS CONTROL FUNCTION ═══
function checkAccess($requiredRole = 'user') {
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }
    
    // Check if session has expired
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        session_start();
        header("Location: login.php?expired=1");
        exit();
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Check role
    if ($requiredRole == 'admin' && $_SESSION['role'] != 'admin') {
        http_response_code(403);
        die("<h1>Access Denied</h1><p>Admin privileges required.</p><a href='index.php'>← Back to Dashboard</a>");
    }
}

// ═══ PASSWORD VERIFICATION ═══
function verifyPassword($plainPassword, $hashedPassword) {
    return password_verify($plainPassword, $hashedPassword);
}

// ═══ PASSWORD HASHING (for generating new hashes) ═══
function hashPassword($plainPassword) {
    return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]); // Increased from 10 to 12
}

// ═══ LOGIN RATE LIMITING ═══
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 300); // 5 minutes

function checkLoginRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = &$_SESSION[$key];
    
    // Reset if lockout period has passed
    if (time() - $data['first_attempt'] > LOGIN_LOCKOUT_SECONDS) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }
    
    return $data['count'] < MAX_LOGIN_ATTEMPTS;
}

function recordLoginAttempt() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $_SESSION[$key]['count']++;
}

function resetLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

function getRemainingLockoutTime() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$key])) return 0;
    
    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    $remaining = LOGIN_LOCKOUT_SECONDS - $elapsed;
    
    return max(0, $remaining);
}
?>
