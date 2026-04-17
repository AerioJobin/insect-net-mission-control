<?php
include('config.php');

$error = '';
$success = '';
$logged_out = isset($_GET['logged_out']);
$expired = isset($_GET['expired']);
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!validateCSRFToken($token)) {
        $error = "Security token expired. Please try again.";
    } elseif (!empty($user) && !empty($pass)) {
        // Use password_verify for secure password checking
        if (isset($users[$user]) && verifyPassword($pass, $users[$user]['password'])) {
            $_SESSION['username'] = $user;
            $_SESSION['role'] = $users[$user]['role'];
            $_SESSION['last_activity'] = time();
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } else {        
        $error = "Please fill in all fields";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insect NET — Login</title>
    <meta name="description" content="INMT Mission Control Dashboard — NeuRonICS Lab, IISc Bangalore">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ═══ DESIGN TOKENS ═══ */
        :root {
            --primary: #8A2245;
            --secondary: #6b1a36;
            --accent: #c44569;
            --bg: #FDFBF7;
            --surface: #FFFFFF;
            --surface2: #F7F3F5;
            --border: #dee2e6;
            --text: #4E4247;
            --text-dim: #6c757d;
            --shadow: rgba(0, 0, 0, 0.07);
            --shadow-md: rgba(0, 0, 0, 0.13);
            --on-bg: #dcfce7;
            --on-fg: #16a34a;
            --st-bg: #fef9c3;
            --st-fg: #92400e;
            --off-bg: #fee2e2;
            --off-fg: #ef4444;
            --radius: 12px;
            --tr: 0.22s ease;
        }

        [data-theme="dark"] {
            --bg: #0e0c11;
            --surface: #19161f;
            --surface2: #231f2b;
            --border: #38334a;
            --text: #e8e0ec;
            --text-dim: #9a8fa8;
            --shadow: rgba(0, 0, 0, 0.45);
            --shadow-md: rgba(0, 0, 0, 0.65);
            --on-bg: #052e16;
            --on-fg: #4ade80;
            --st-bg: #1c1500;
            --st-fg: #fbbf24;
            --off-bg: #1f0808;
            --off-fg: #f87171;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--tr), color var(--tr);
            overflow: hidden;
            position: relative;
        }

        /* Background orbs */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
        }

        .orb1 {
            width: 480px;
            height: 480px;
            background: rgba(138, 34, 69, 0.15);
            top: -120px;
            left: -120px;
            animation: floatA 9s ease-in-out infinite;
        }

        .orb2 {
            width: 380px;
            height: 380px;
            background: rgba(196, 69, 105, 0.1);
            bottom: -80px;
            right: -80px;
            animation: floatA 11s ease-in-out infinite reverse;
        }

        @keyframes floatA {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(28px, 18px); }
        }

        /* Theme toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--surface);
            cursor: pointer;
            font-size: 1.05em;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 12px var(--shadow);
            transition: all var(--tr);
        }

        .theme-toggle:hover {
            background: var(--surface2);
            transform: scale(1.1);
        }

        /* Toast container */
        #toast-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .toast {
            background: #1a1425;
            color: #ede8f5;
            padding: 11px 16px;
            border-radius: 10px;
            font-size: 0.82em;
            display: flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            animation: toastIn 0.3s ease forwards;
            pointer-events: all;
            max-width: 280px;
            border-left: 3px solid var(--accent);
        }

        .toast.leaving {
            animation: toastOut 0.3s ease forwards;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateX(-16px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes toastOut {
            from { opacity: 1; transform: none; }
            to { opacity: 0; transform: translateX(-16px); }
        }

        /* Login container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 44px 36px;
            box-shadow: 0 20px 60px var(--shadow-md);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: none; }
        }

        /* Branding section */
        .login-branding {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 32px;
        }

        .login-logo {
            height: 48px;
            object-fit: contain;
            opacity: 0.9;
            transition: opacity var(--tr);
        }

        .login-logo:hover {
            opacity: 1;
        }

        .logo-divider {
            width: 1px;
            height: 40px;
            background: linear-gradient(180deg, transparent, var(--border), transparent);
        }

        .login-title {
            font-family: 'Space Mono', monospace;
            font-size: 1.8em;
            color: var(--primary);
            letter-spacing: 3px;
            text-align: center;
            margin-bottom: 8px;
        }

        .login-subtitle {
            font-size: 0.75em;
            color: var(--text-dim);
            letter-spacing: 2px;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        /* Form fields */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.8em;
            font-weight: 600;
            color: var(--text-dim);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface2);
            color: var(--text);
            font-family: inherit;
            font-size: 0.95em;
            transition: all var(--tr);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(196, 69, 105, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-dim);
            opacity: 0.6;
        }

        /* Error message */
        .form-error {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #fca5a5;
            border-radius: 10px;
            color: #991b1b;
            font-size: 0.85em;
            margin-bottom: 18px;
            animation: shake 0.3s ease;
        }

        [data-theme="dark"] .form-error {
            background: rgba(127, 29, 29, 0.2);
            border-color: #dc2626;
            color: #fecaca;
        }

        /* Success message */
        .form-success {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #86efac;
            border-radius: 10px;
            color: #166534;
            font-size: 0.85em;
            margin-bottom: 18px;
            animation: slideDown 0.3s ease;
        }

        [data-theme="dark"] .form-success {
            background: rgba(20, 83, 45, 0.2);
            border-color: #4ade80;
            color: #86efac;
        }

        /* Warning message */
        .form-warning {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid #fcd34d;
            border-radius: 10px;
            color: #92400e;
            font-size: 0.85em;
            margin-bottom: 18px;
            animation: slideDown 0.3s ease;
        }

        [data-theme="dark"] .form-warning {
            background: rgba(120, 53, 15, 0.2);
            border-color: #fbbf24;
            color: #fcd34d;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: none; }
        }

        /* Submit button */
        .form-submit {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95em;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all var(--tr);
            box-shadow: 0 4px 16px rgba(138, 34, 69, 0.25);
            text-transform: uppercase;
        }

        .form-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(138, 34, 69, 0.35);
        }

        .form-submit:active {
            transform: translateY(0);
        }

        /* Demo credentials hint */
        .demo-hint {
            margin-top: 24px;
            padding: 12px 14px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.75em;
            color: var(--text-dim);
            line-height: 1.6;
        }

        .demo-hint strong {
            color: var(--primary);
        }

        .demo-cred {
            font-family: 'Space Mono', monospace;
            margin: 6px 0;
        }

        .demo-label {
            display: block;
            margin-top: 8px;
            margin-bottom: 4px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <!-- Background orbs -->
    <div class="bg-orb orb1"></div>
    <div class="bg-orb orb2"></div>

    <!-- Theme toggle -->
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>

    <!-- Toast container -->
    <div id="toast-container"></div>

    <!-- Login container -->
    <div class="login-container">
        <div class="login-panel">
            <!-- Branding -->
            <div class="login-branding">
                <img src="assets/neuronics_logo.png" class="login-logo" onerror="this.style.display='none'" alt="NeuRonICS">
                <div class="logo-divider"></div>
                <img src="assets/iisc_logo.jpg" class="login-logo" onerror="this.style.display='none'" alt="IISc">
            </div>

            <h1 class="login-title">INSECT NET</h1>
            <p class="login-subtitle">Mission Control — Secure Login</p>

            <!-- Logged out message -->
            <?php if ($logged_out): ?>
                <div class="form-success">
                    <span>✓</span>
                    <span>Successfully logged out. See you next time!</span>
                </div>
            <?php endif; ?>

            <!-- Session expired message -->
            <?php if ($expired): ?>
                <div class="form-warning">
                    <span>⏱</span>
                    <span>Your session expired due to inactivity. Please log in again.</span>
                </div>
            <?php endif; ?>

            <!-- Error message -->
            <?php if ($error): ?>
                <div class="form-error">
                    <span>⚠</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login form -->
            <form method="POST" id="loginForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="Enter your username"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="form-submit">🔐 Sign In</button>
            </form>

            <!-- Demo credentials -->
            <div class="demo-hint">
                <strong>📌 Demo Credentials:</strong>
                <div class="demo-label">Admin Account:</div>
                <div class="demo-cred">admin / iisc_admin_2026</div>
                <div class="demo-label">User Account:</div>
                <div class="demo-cred">researcher / insect_user_2026</div>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle
        (function () {
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
            const btn = document.getElementById('themeToggle');
            btn.textContent = saved === 'dark' ? '☀️' : '🌙';
            btn.addEventListener('click', () => {
                const cur = document.documentElement.getAttribute('data-theme');
                const next = cur === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                btn.textContent = next === 'dark' ? '☀️' : '🌙';
            });
        })();

        // Toast notification
        function showToast(msg, type) {
            const tc = document.getElementById('toast-container');
            const t = document.createElement('div');
            t.className = 'toast';
            t.textContent = msg;
            if (type === 'error') t.style.borderLeftColor = '#ef4444';
            else if (type === 'success') t.style.borderLeftColor = '#4ade80';
            tc.appendChild(t);
            setTimeout(() => { t.classList.add('leaving'); setTimeout(() => t.remove(), 320); }, 4000);
        }

        // Form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) {
                e.preventDefault();
                showToast('Please fill in all fields', 'error');
            }
        });

        // Auto-focus first field
        document.getElementById('username').focus();
    </script>
</body>
</html>
