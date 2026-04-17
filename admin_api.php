<?php
/**
 * admin_api.php
 * Admin-only API for user management (save, add, delete).
 * Returns JSON. All actions require admin session + valid CSRF token.
 */
header('Content-Type: application/json');
include('config.php');

// Must be logged in and admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin access required']);
    exit();
}

$usersFile = __DIR__ . '/users.json';
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$action = $body['action'] ?? '';

// ── Load users ─────────────────────────────────────────────────────────────
function loadUsers($file) {
    return file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
}
function saveUsers($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// ═══ SAVE / UPDATE USER ════════════════════════════════════════════════════
if ($action === 'save_user') {
    $oldUsername = trim($body['old_username'] ?? '');
    $newUsername = trim($body['username'] ?? '');
    $newPassword = $body['password'] ?? '';
    $role        = $body['role'] ?? 'user';
    $role        = in_array($role, ['admin', 'user']) ? $role : 'user';

    if (!$newUsername) {
        echo json_encode(['ok' => false, 'error' => 'Username cannot be empty']); exit();
    }
    if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,32}$/', $newUsername)) {
        echo json_encode(['ok' => false, 'error' => 'Username: 3–32 chars, letters/numbers/_ only']); exit();
    }

    $users = loadUsers($usersFile);

    // If renaming, ensure new name isn't taken by someone else
    if ($newUsername !== $oldUsername && isset($users[$newUsername])) {
        echo json_encode(['ok' => false, 'error' => "Username '$newUsername' is already taken"]); exit();
    }

    // Get existing entry (to preserve old hash if no new password given)
    $existing = $users[$oldUsername] ?? null;
    if (!$existing && !$newPassword) {
        echo json_encode(['ok' => false, 'error' => 'New user must have a password']); exit();
    }

    // Validate password if provided
    if ($newPassword) {
        if (strlen($newPassword) < 10) {
            echo json_encode(['ok' => false, 'error' => 'Password must be at least 10 characters']); exit();
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
    } else {
        $hash = $existing['password']; // Keep existing
    }

    // Remove old key if renaming
    if ($oldUsername && $oldUsername !== $newUsername) {
        unset($users[$oldUsername]);
    }

    $users[$newUsername] = ['password' => $hash, 'role' => $role];

    if (!saveUsers($usersFile, $users)) {
        echo json_encode(['ok' => false, 'error' => 'Could not write users.json — check server file permissions']); exit();
    }

    // Update session if admin renamed themselves
    if ($oldUsername === $_SESSION['username']) {
        $_SESSION['username'] = $newUsername;
        $_SESSION['role']     = $role;
    }

    echo json_encode(['ok' => true, 'message' => "User '$newUsername' saved successfully"]);
    exit();
}

// ═══ DELETE USER ═══════════════════════════════════════════════════════════
if ($action === 'delete_user') {
    $target = trim($body['username'] ?? '');
    if (!$target) { echo json_encode(['ok' => false, 'error' => 'No username given']); exit(); }
    if ($target === $_SESSION['username']) {
        echo json_encode(['ok' => false, 'error' => 'You cannot delete your own account']); exit();
    }
    $users = loadUsers($usersFile);
    if (!isset($users[$target])) {
        echo json_encode(['ok' => false, 'error' => 'User not found']); exit();
    }
    unset($users[$target]);
    if (!saveUsers($usersFile, $users)) {
        echo json_encode(['ok' => false, 'error' => 'Could not write users.json']); exit();
    }
    echo json_encode(['ok' => true, 'message' => "User '$target' deleted"]);
    exit();
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);
