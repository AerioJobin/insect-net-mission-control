<?php
/**
 * Insect NET — Post-Deployment Verification Script
 * Run this on the server after deployment to verify all security features
 * Usage: php verify.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Insect NET — Post-Deployment Verification                  ║\n";
echo "║  Version 1.0 — March 2026                                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Color codes for CLI
class Color {
    const GREEN  = "\033[92m";
    const RED    = "\033[91m";
    const YELLOW = "\033[93m";
    const CYAN   = "\033[96m";
    const RESET  = "\033[0m";
    
    public static function success($msg) { echo self::GREEN . "✓ $msg" . self::RESET . "\n"; }
    public static function error($msg)   { echo self::RED . "✗ $msg" . self::RESET . "\n"; }
    public static function warn($msg)    { echo self::YELLOW . "⚠ $msg" . self::RESET . "\n"; }
    public static function info($msg)    { echo self::CYAN . "ℹ $msg" . self::RESET . "\n"; }
}

$passed = 0;
$failed = 0;

// Test 1: File Existence
echo "1. CHECKING FILE EXISTENCE\n";
echo "───────────────────────────────────────\n";

$requiredFiles = ['config.php', 'login.php', 'logout.php', 'index.php', 'delete_image.php'];
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        Color::success("$file exists");
        $passed++;
    } else {
        Color::error("$file missing");
        $failed++;
    }
}
echo "\n";

// Test 2: PHP Syntax
echo "2. CHECKING PHP SYNTAX\n";
echo "───────────────────────────────────────\n";

foreach ($requiredFiles as $file) {
    $output = shell_exec("php -l $file 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        Color::success("$file — syntax OK");
        $passed++;
    } else {
        Color::error("$file — syntax error: " . trim($output));
        $failed++;
    }
}
echo "\n";

// Test 3: Config Functions
echo "3. CHECKING CONFIG.PHP FUNCTIONS\n";
echo "───────────────────────────────────────\n";

@include('config.php');

$functions = ['generateCSRFToken', 'validateCSRFToken', 'checkAccess', 'verifyPassword', 'hashPassword'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        Color::success("Function $func() defined");
        $passed++;
    } else {
        Color::error("Function $func() missing");
        $failed++;
    }
}
echo "\n";

// Test 4: User Database
echo "4. CHECKING USER CREDENTIALS\n";
echo "───────────────────────────────────────\n";

if (!isset($users)) {
    Color::error("Users database not loaded");
    $failed++;
} else {
    Color::info("Found " . count($users) . " users");
    
    // Check admin user
    if (isset($users['admin']) && isset($users['admin']['password']) && isset($users['admin']['role'])) {
        Color::success("Admin user configured");
        $passed++;
        
        // Verify password
        if (verifyPassword('iisc_admin_2026', $users['admin']['password'])) {
            Color::success("Admin password verification works");
            $passed++;
        } else {
            Color::error("Admin password verification failed");
            $failed++;
        }
    } else {
        Color::error("Admin user not properly configured");
        $failed++;
    }
    
    // Check researcher user
    if (isset($users['researcher']) && isset($users['researcher']['password']) && isset($users['researcher']['role'])) {
        Color::success("Researcher user configured");
        $passed++;
        
        // Verify password
        if (verifyPassword('insect_user_2026', $users['researcher']['password'])) {
            Color::success("Researcher password verification works");
            $passed++;
        } else {
            Color::error("Researcher password verification failed");
            $failed++;
        }
    } else {
        Color::error("Researcher user not properly configured");
        $failed++;
    }
}
echo "\n";

// Test 5: Password Hashing
echo "5. CHECKING PASSWORD HASHING\n";
echo "───────────────────────────────────────\n";

$testPass = "test_password_12345";
$hashed = hashPassword($testPass);

if (strlen($hashed) >= 60) {
    Color::success("Bcrypt hash generated (" . strlen($hashed) . " chars)");
    $passed++;
} else {
    Color::error("Hash too short, may not be bcrypt");
    $failed++;
}

if (verifyPassword($testPass, $hashed)) {
    Color::success("Password verification works");
    $passed++;
} else {
    Color::error("Password verification failed");
    $failed++;
}
echo "\n";

// Test 6: CSRF Tokens
echo "6. CHECKING CSRF PROTECTION\n";
echo "───────────────────────────────────────\n";

@session_start();

$token1 = generateCSRFToken();
if (!empty($token1) && strlen($token1) == 64) {
    Color::success("CSRF token generated (64 hex chars)");
    $passed++;
} else {
    Color::error("CSRF token invalid format");
    $failed++;
}

if (validateCSRFToken($token1)) {
    Color::success("CSRF token validation works");
    $passed++;
} else {
    Color::error("CSRF token validation failed");
    $failed++;
}

if (!validateCSRFToken("invalid_token_123")) {
    Color::success("Invalid tokens rejected");
    $passed++;
} else {
    Color::error("Invalid tokens accepted (security issue!)");
    $failed++;
}
echo "\n";

// Test 7: File Permissions
echo "7. CHECKING FILE PERMISSIONS\n";
echo "───────────────────────────────────────\n";

foreach ($requiredFiles as $file) {
    $perms = substr(sprintf('%o', fileperms($file)), -4);
    if (is_readable($file)) {
        Color::success("$file readable ($perms)");
        $passed++;
    } else {
        Color::error("$file not readable ($perms)");
        $failed++;
    }
}
echo "\n";

// Test 8: Session Configuration
echo "8. CHECKING SESSION CONFIGURATION\n";
echo "───────────────────────────────────────\n";

$sessionTimeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : null;
if ($sessionTimeout == 1800) {
    Color::success("Session timeout: 30 minutes (1800 seconds)");
    $passed++;
} else {
    Color::warn("Session timeout not standard: $sessionTimeout seconds");
}

// Check session storage
$sessionPath = session_save_path();
if (is_writable(dirname($sessionPath) ?: '/tmp')) {
    Color::success("Session storage writable");
    $passed++;
} else {
    Color::error("Session storage not writable");
    $failed++;
}
echo "\n";

// Test 9: Uploads Directory
echo "9. CHECKING UPLOADS DIRECTORY\n";
echo "───────────────────────────────────────\n";

if (is_dir('uploads')) {
    Color::success("uploads/ directory exists");
    $passed++;
    
    if (is_writable('uploads')) {
        Color::success("uploads/ directory writable");
        $passed++;
    } else {
        Color::error("uploads/ directory not writable");
        $failed++;
    }
} else {
    Color::warn("uploads/ directory not found (will be created on first upload)");
}
echo "\n";

// Test 10: HTTP Security Headers (simulation)
echo "10. CHECKING SECURITY BEST PRACTICES\n";
echo "───────────────────────────────────────\n";

// Check for .htaccess
if (file_exists('.htaccess')) {
    Color::success(".htaccess found");
    $passed++;
    $content = file_get_contents('.htaccess');
    if (strpos($content, 'Options -Indexes') !== false) {
        Color::success("Directory listing disabled");
        $passed++;
    } else {
        Color::warn("Consider adding 'Options -Indexes' to .htaccess");
    }
} else {
    Color::warn(".htaccess not found (recommended for security)");
}
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                      VERIFICATION SUMMARY                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100) : 0;

echo "Tests Passed:   " . Color::GREEN . "$passed" . Color::RESET . "\n";
echo "Tests Failed:   " . Color::RED . "$failed" . Color::RESET . "\n";
echo "Total Tests:    $total\n";
echo "Score:          $percentage%\n\n";

if ($failed === 0) {
    Color::success("═══ ALL CHECKS PASSED ═══");
    echo "\nYour deployment is ready! Next steps:\n";
    echo "  1. Clear browser cache/cookies\n";
    echo "  2. Visit: http://" . $_SERVER['HTTP_HOST'] . "/index.php?view=landing\n";
    echo "  3. Test login with provided credentials\n";
    echo "  4. Verify role-based features (delete buttons)\n\n";
    exit(0);
} else {
    Color::error("═══ SOME CHECKS FAILED ═══");
    echo "\nPlease fix the errors above before using in production.\n";
    echo "Check /var/log/apache2/error.log for more details.\n\n";
    exit(1);
}
