<?php
include('config.php');
checkAccess('user');

$error   = '';
$success = '';
$isAdmin = ($_SESSION['role'] === 'admin');

// Load and save the users array from config.php — we'll rewrite the hash lines
$configPath = __DIR__ . '/config.php';
$configRaw  = file_get_contents($configPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target  = $isAdmin ? (trim($_POST['target_user'] ?? $_SESSION['username'])) : $_SESSION['username'];
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Admins changing other accounts don't need to supply old password
    $skipOldCheck = $isAdmin && $target !== $_SESSION['username'];

    if (!isset($users[$target])) {
        $error = 'User not found.';
    } elseif (!$skipOldCheck && !verifyPassword($oldPass, $users[$target]['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 10) {
        $error = 'New password must be at least 10 characters.';
    } elseif ($newPass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $newHash = hashPassword($newPass);
        // Rewrite the hash for $target in config.php
        $pattern     = "/('password'\s*=>\s*)'[^']*'(\s*,\s*\/\/.*?(?=\n))/";
        $lines       = explode("\n", $configRaw);
        $inUserBlock = false;
        $replaced    = false;
        $newLines    = [];
        foreach ($lines as $line) {
            if (preg_match("/'\Q$target\E'\s*=>\s*\[/", $line)) $inUserBlock = true;
            if ($inUserBlock && !$replaced && preg_match("/'password'\s*=>/", $line)) {
                // Replace just the hash value
                $line    = preg_replace("/'([^']{20,})'/", "'" . $newHash . "'", $line, 1);
                // Update comment
                $line    = preg_replace('/\/\/.*$/', '// Updated: ' . date('Y-m-d'), $line);
                $replaced = true;
            }
            if ($inUserBlock && str_contains($line, ']')) $inUserBlock = false;
            $newLines[] = $line;
        }
        if ($replaced && file_put_contents($configPath, implode("\n", $newLines)) !== false) {
            $success = "Password for <strong>$target</strong> updated successfully.";
        } else {
            $error = 'Could not write to config.php — check file permissions on the server.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — INSECT NET</title>
    <link rel="icon" type="image/png" href="neuronics_logo.png">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');</script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8A2245; --secondary: #6b1a36; --accent: #c44569;
            --bg: #FDFBF7; --surface: #FFFFFF; --surface2: #F7F3F5;
            --border: #dee2e6; --text: #4E4247; --text-dim: #6c757d;
            --shadow: rgba(0,0,0,0.07); --radius: 16px; --tr: 0.22s ease;
        }
        [data-theme="dark"] {
            --bg: #0e0c11; --surface: #19161f; --surface2: #231f2b;
            --border: #38334a; --text: #e8e0ec; --text-dim: #9a8fa8;
            --shadow: rgba(0,0,0,0.45);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg); color: var(--text);
            min-height: 100vh; display: flex; flex-direction: column;
            align-items: center; justify-content: center; padding: 24px;
            background-image: radial-gradient(var(--border) 1px, transparent 1px);
            background-size: 28px 28px;
            transition: background var(--tr), color var(--tr);
        }
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 24px; padding: 40px 44px; width: 100%; max-width: 460px;
            box-shadow: 0 16px 48px var(--shadow);
        }
        .card-title {
            font-family: 'Space Mono', monospace; font-size: 0.72em;
            letter-spacing: 3px; text-transform: uppercase; color: var(--text-dim);
            margin-bottom: 4px;
        }
        h1 {
            font-family: 'Outfit', sans-serif; font-size: 1.6em;
            font-weight: 700; margin-bottom: 28px; color: var(--text);
        }
        .form-group { margin-bottom: 18px; }
        label {
            display: block; font-size: 0.72em; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--text-dim); margin-bottom: 8px;
        }
        input[type="password"], select {
            width: 100%; padding: 12px 16px;
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 10px; color: var(--text); font-size: 0.95em;
            font-family: 'Inter', sans-serif; outline: none;
            transition: border-color var(--tr);
        }
        input[type="password"]:focus, select:focus { border-color: var(--primary); }
        .alert {
            padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;
            font-size: 0.88em; font-weight: 600; display: flex; align-items: center; gap: 10px;
        }
        .alert-error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #ef4444; }
        .alert-success { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.3); color: #4ade80; }
        .hint { font-size: 0.76em; color: var(--text-dim); margin-top: 5px; }
        .btn-submit {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff; border: none; border-radius: 10px;
            font-family: 'Outfit', sans-serif; font-size: 1em; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; margin-top: 8px;
            box-shadow: 0 6px 20px rgba(138,34,69,0.3);
            transition: transform var(--tr), box-shadow var(--tr);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(138,34,69,0.4); }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 20px; font-size: 0.84em; color: var(--text-dim);
            text-decoration: none; transition: color var(--tr);
        }
        .back-link:hover { color: var(--primary); }
        .strength-bar-wrap { height: 4px; background: var(--border); border-radius: 99px; margin-top: 8px; }
        .strength-bar { height: 100%; border-radius: 99px; transition: width 0.3s, background 0.3s; width: 0; }
        .strength-label { font-size: 0.7em; margin-top: 4px; font-weight: 600; }
        .theme-toggle {
            position: fixed; top: 16px; right: 16px; width: 38px; height: 38px;
            border-radius: 50%; border: 1px solid var(--border);
            background: var(--surface); cursor: pointer; font-size: 1em;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 12px var(--shadow); transition: all var(--tr);
        }
        .theme-toggle:hover { background: var(--surface2); transform: scale(1.1); }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>

    <div class="card">
        <p class="card-title">INSECT NET · Security</p>
        <h1>🔑 Change Password</h1>

        <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= $success ?></div>
        <?php endif; ?>

        <form method="POST" id="pwForm">
            <?php if ($isAdmin): ?>
            <div class="form-group">
                <label for="target_user">Change password for</label>
                <select name="target_user" id="target_user" onchange="toggleOldPass()">
                    <?php foreach ($users as $uname => $_): ?>
                    <option value="<?= htmlspecialchars($uname) ?>"
                        <?= $uname === $_SESSION['username'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($uname) ?><?= $uname === $_SESSION['username'] ? ' (you)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group" id="oldPassGroup">
                <label for="old_password">Current password</label>
                <input type="password" name="old_password" id="old_password" placeholder="Enter current password" autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="new_password">New password</label>
                <input type="password" name="new_password" id="new_password" placeholder="At least 10 characters" autocomplete="new-password" oninput="checkStrength(this.value)">
                <div class="strength-bar-wrap"><div class="strength-bar" id="strengthBar"></div></div>
                <p class="strength-label" id="strengthLabel"></p>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm new password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password" autocomplete="new-password">
                <p class="hint" id="matchHint"></p>
            </div>

            <button type="submit" class="btn-submit">Update Password</button>
        </form>

        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        // Theme
        (function () {
            const saved = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
            const btn = document.getElementById('themeToggle');
            btn.textContent = saved === 'dark' ? '☀️' : '🌙';
            btn.addEventListener('click', () => {
                const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                btn.textContent = next === 'dark' ? '☀️' : '🌙';
            });
        })();

        // Admin: hide "current password" when changing another user's password
        function toggleOldPass() {
            const sel = document.getElementById('target_user');
            const grp = document.getElementById('oldPassGroup');
            if (!sel || !grp) return;
            const isSelf = sel.value === <?= json_encode($_SESSION['username']) ?>;
            grp.style.display = isSelf ? '' : 'none';
        }
        toggleOldPass();

        // Password strength meter
        function checkStrength(pw) {
            const bar   = document.getElementById('strengthBar');
            const label = document.getElementById('strengthLabel');
            let score = 0;
            if (pw.length >= 10) score++;
            if (pw.length >= 16) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;
            const levels = [
                { w: '0%',   bg: 'transparent', t: '' },
                { w: '25%',  bg: '#ef4444',      t: 'Weak' },
                { w: '50%',  bg: '#f59e0b',      t: 'Fair' },
                { w: '75%',  bg: '#3b82f6',      t: 'Good' },
                { w: '90%',  bg: '#22c55e',      t: 'Strong' },
                { w: '100%', bg: '#16a34a',      t: 'Very Strong' },
            ];
            const l = levels[Math.min(score, 5)];
            bar.style.width      = l.w;
            bar.style.background = l.bg;
            label.textContent    = l.t;
            label.style.color    = l.bg;
        }

        // Confirm match hint
        document.getElementById('confirm_password').addEventListener('input', function () {
            const hint = document.getElementById('matchHint');
            const np   = document.getElementById('new_password').value;
            if (!this.value) { hint.textContent = ''; return; }
            if (this.value === np) {
                hint.textContent = '✓ Passwords match'; hint.style.color = '#4ade80';
            } else {
                hint.textContent = '✗ Does not match'; hint.style.color = '#ef4444';
            }
        });
    </script>
</body>
</html>
