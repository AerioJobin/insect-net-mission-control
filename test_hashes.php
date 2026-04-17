<?php
// Test script to verify bcrypt hashes
// Run this on the server or locally with PHP installed

// Test credentials from config.php
$testCases = [
    ['username' => 'admin', 'password' => 'iisc_admin_2026', 'hash' => '$2y$10$dX6.K8v5VF0HvWkNv7ZKQOJqQQmEyIiBEq7w0kTK7b7HJz7VYGk1m'],
    ['username' => 'researcher', 'password' => 'insect_user_2026', 'hash' => '$2y$10$v7OzBvN2QvYq7hH5xK4UPeyY.GvZN9m7.Nq1K3p9L2wM8zR5dJ7tC'],
];

echo "═══════════════════════════════════════════════════════════\n";
echo "BCRYPT HASH VERIFICATION TEST\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($testCases as $test) {
    $result = password_verify($test['password'], $test['hash']);
    $status = $result ? '✓ PASS' : '✗ FAIL';
    
    echo "{$status} | {$test['username']}\n";
    echo "  Password: {$test['password']}\n";
    echo "  Hash: {$test['hash']}\n";
    echo "  Verified: " . ($result ? 'YES' : 'NO') . "\n\n";
}

// Generate FRESH hashes in case existing ones are wrong
echo "═══════════════════════════════════════════════════════════\n";
echo "GENERATING FRESH BCRYPT HASHES (use if tests above fail)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$freshCreds = [
    'admin' => 'iisc_admin_2026',
    'researcher' => 'insect_user_2026'
];

foreach ($freshCreds as $user => $pass) {
    $newHash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
    echo "\$users['{$user}']['password'] = '{$newHash}';\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "HOW TO USE: Copy fresh hashes above into config.php if tests fail\n";
echo "═══════════════════════════════════════════════════════════\n";
?>
