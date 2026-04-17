<?php
/**
 * SETUP SCRIPT - Generate Valid Bcrypt Hashes
 * 
 * Run this script ONCE to generate correct password hashes
 * It will display the hashes to paste into config.php
 * 
 * Usage:
 * 1. Upload this file to your server or run locally
 * 2. Open in browser: http://your-server/setup_credentials.php
 * 3. Copy the generated hashes
 * 4. Paste them into config.php
 * 5. DELETE this file after
 */

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "  <meta charset='UTF-8'>";
echo "  <title>Credential Setup</title>";
echo "  <style>";
echo "    body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }";
echo "    .container { max-width: 800px; margin: 0 auto; }";
echo "    .hash { ";
echo "      background: #000; ";
echo "      padding: 10px; ";
echo "      margin: 10px 0; ";
echo "      border-left: 3px solid #c44569; ";
echo "      word-break: break-all; ";
echo "    }";
echo "    .label { font-weight: bold; color: #8A2245; }";
echo "    .warning { color: #ff6b6b; font-size: 12px; margin-top: 20px; }";
echo "    code { background: #333; padding: 2px 6px; }";
echo "  </style>";
echo "</head>";
echo "<body>";
echo "  <div class='container'>";
echo "    <h2>🔐 Bcrypt Hash Generator</h2>";
echo "    <p>Generated hashes for config.php:</p>";

// Credentials to hash
$credentials = [
    'admin' => 'iisc_admin_2026',
    'researcher' => 'insect_user_2026'
];

// Generate hashes with cost=10 (matches your config.php)
foreach ($credentials as $username => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    echo "    <div>";
    echo "      <p class='label'>User: {$username}</p>";
    echo "      <p>Password: <code>{$password}</code></p>";
    echo "      <p>Bcrypt Hash:</p>";
    echo "      <div class='hash'>\$users['{$username}']['password'] = '{$hash}';</div>";
    
    // Verify the hash works
    $verified = password_verify($password, $hash);
    echo "      <p>Verified: <span style='color: " . ($verified ? "#00ff00;" : "#ff0000;") . "'>";
    echo ($verified ? "✓ YES" : "✗ NO") . "</span></p>";
    echo "    </div>";
}

echo "    <div class='warning'>";
echo "      ⚠️  <strong>SECURITY:</strong>";
echo "      <br>1. Copy all hashes above into config.php";
echo "      <br>2. Replace the existing password hashes";
echo "      <br>3. Delete this file immediately after";
echo "      <br>4. Never share these hashes or passwords";
echo "    </div>";
echo "  </div>";
echo "</body>";
echo "</html>";
?>
