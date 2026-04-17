<?php
include('config.php');
checkAccess('user');

$imageFile = basename((string)($_GET['image'] ?? ''));
$device    = $_GET['device'] ?? 'device1';
$imagePath = 'uploads/' . $imageFile;
$jsonPath  = 'uploads/' . pathinfo($imageFile, PATHINFO_FILENAME) . '.json';

if (!$imageFile || !is_file($imagePath)) {
    header('Location: index.php?view=dashboard');
    exit;
}

// Load cached result if it exists
$cached          = null;
$cacheTimestamp  = null;
if (is_file($jsonPath)) {
    $raw     = file_get_contents($jsonPath);
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['species'])) {
        $cached         = $decoded;
        $cacheTimestamp = date('F j, Y — H:i', filemtime($jsonPath));
    }
}

$captureTime = date('F j, Y — H:i', filemtime($imagePath));
$backUrl     = 'index.php?view=dashboard&device=' . urlencode($device);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($imageFile) ?> — INSECT NET</title>
    <meta name="description" content="AI Identification result for <?= htmlspecialchars($imageFile) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8A2245; --secondary: #6b1a36; --accent: #c44569;
            --bg: #FDFBF7; --surface: #FFFFFF; --surface2: #F7F3F5;
            --border: #dee2e6; --text: #4E4247; --text-dim: #6c757d;
            --shadow: rgba(0,0,0,0.07); --shadow-md: rgba(0,0,0,0.13); --shadow-glow: rgba(138, 34, 69, 0.25);
            --radius: 16px; --tr: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        [data-theme="dark"] {
            --bg: #09090b; --surface: #141417; --surface2: #1e1e24;
            --border: #2a2a32; --text: #ededf0; --text-dim: #a1a1aa;
            --shadow: rgba(0,0,0,0.45); --shadow-md: rgba(0,0,0,0.65); --shadow-glow: rgba(196, 69, 105, 0.35);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg); color: var(--text);
            min-height: 100vh; padding: 32px 24px;
            transition: background var(--tr), color var(--tr);
            background-image: radial-gradient(var(--border) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        .page { max-width: 1100px; margin: 0 auto; position: relative; z-index: 1; }

        /* Header */
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 32px; gap: 16px; flex-wrap: wrap;
            background: var(--surface); padding: 16px 20px; border-radius: var(--radius);
            border: 1px solid var(--border); box-shadow: 0 4px 24px var(--shadow);
        }
        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--surface2); border: 1px solid var(--border);
            color: var(--primary); font-family: 'Outfit', sans-serif;
            font-size: 0.9em; font-weight: 700; padding: 10px 20px;
            border-radius: 99px; text-decoration: none; letter-spacing: 0.5px;
            transition: all var(--tr); box-shadow: 0 2px 8px var(--shadow);
        }
        .back-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); transform: translateY(-2px); }
        .page-title {
            font-family: 'Outfit', sans-serif; font-size: clamp(1em, 2vw, 1.2em); font-weight: 700;
            color: var(--text); letter-spacing: 1px; text-transform: uppercase;
        }
        .theme-toggle {
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 50%; width: 44px; height: 44px; cursor: pointer;
            font-size: 1.2em; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px var(--shadow); transition: all var(--tr);
        }
        .theme-toggle:hover { transform: rotate(15deg) scale(1.1); background: var(--border); }

        /* Main Layout */
        .detail-layout {
            display: grid; grid-template-columns: 1.15fr 420px; gap: 28px; align-items: start;
        }
        @media (max-width: 900px) { .detail-layout { grid-template-columns: 1fr; } }

        /* Image Panel */
        .img-panel {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
            box-shadow: 0 12px 32px var(--shadow-md);
            display: flex; flex-direction: column;
        }
        .img-panel img {
            width: 100%; display: block; max-height: 70vh; object-fit: contain; background: #000;
        }
        .img-meta {
            padding: 18px 24px; border-top: 1px solid var(--border);
            display: flex; flex-direction: column; gap: 6px;
        }
        .img-filename {
            font-family: 'Space Mono', monospace; font-size: 0.85em; font-weight: 700;
            color: var(--text); word-break: break-all;
        }
        .img-time { font-size: 0.85em; color: var(--text-dim); }
        .img-actions { display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
        .btn-download {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--surface2); border: 1px solid var(--border);
            color: var(--text); font-size: 0.85em; font-weight: 600;
            padding: 8px 18px; border-radius: 99px; text-decoration: none;
            transition: all var(--tr);
        }
        .btn-download:hover { background: #16a34a; color: #fff; border-color: #16a34a; box-shadow: 0 6px 16px rgba(22, 163, 74, 0.3); transform: translateY(-2px); }

        /* AI Panel */
        .ai-panel { display: flex; flex-direction: column; gap: 20px; }
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 26px;
            box-shadow: 0 8px 24px var(--shadow); position: relative; overflow: hidden;
        }
        .card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
            background: linear-gradient(180deg, var(--primary), var(--accent));
        }
        .card-title {
            font-family: 'Outfit', sans-serif; font-size: 0.85em; font-weight: 700;
            letter-spacing: 2px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 20px;
            display: flex; align-items: center; gap: 12px;
        }
        .card-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* Species result */
        .species-name {
            font-family: 'Outfit', sans-serif; font-size: clamp(1.4em, 3vw, 1.8em); font-weight: 700;
            color: var(--primary); letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .common-name {
            font-size: 1.05em; color: var(--text-dim); font-weight: 500; margin-bottom: 24px;
        }

        /* Confidence bar */
        .conf-row { margin-bottom: 20px; }
        .conf-label {
            display: flex; justify-content: space-between; font-size: 0.85em; font-weight: 700;
            color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;
        }
        .conf-change {
            font-size: 0.8em; font-weight: 700; padding: 2px 8px; border-radius: 99px;
            margin-left: 8px; display: inline-block;
        }
        .conf-change.up   { background: #dcfce7; color: #16a34a; }
        .conf-change.down { background: #fee2e2; color: #dc2626; }
        .conf-change.same { background: var(--surface2); color: var(--text-dim); }
        [data-theme="dark"] .conf-change.up   { background: #052e16; color: #4ade80; }
        [data-theme="dark"] .conf-change.down { background: #1f0808; color: #f87171; }
        .conf-bar-track {
            height: 10px; background: var(--surface2); border-radius: 99px; overflow: hidden; box-shadow: inset 0 2px 4px var(--shadow);
        }
        .conf-bar-fill {
            height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 1s cubic-bezier(0.34,1.56,0.64,1); position: relative;
        }
        .conf-bar-fill::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
            animation: shine 2s infinite;
        }
        @keyframes shine { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

        /* Description */
        .desc-text { font-size: 0.95em; line-height: 1.8; color: var(--text); font-weight: 400; }

        /* Cache timestamp */
        .cache-ts {
            font-size: 0.78em; color: var(--text-dim); margin-top: 16px;
            display: flex; align-items: center; gap: 6px; font-style: italic;
        }

        /* Mock warning */
        .mock-warning {
            background: #fef9c3; border: 1px solid #fde68a; border-radius: 10px;
            padding: 12px 16px; font-size: 0.82em; color: #78350f; font-weight: 600;
            display: flex; align-items: center; gap: 8px; margin-top: 12px;
        }
        [data-theme="dark"] .mock-warning { background: #1c1500; border-color: #92400e; color: #fbbf24; }

        /* Action buttons */
        .btn-identify {
            width: 100%; padding: 18px; background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff; border: none; border-radius: var(--radius);
            font-family: 'Outfit', sans-serif; font-size: 1.05em; font-weight: 700; letter-spacing: 1.5px;
            cursor: pointer; box-shadow: 0 8px 24px var(--shadow-glow); transition: all var(--tr); text-transform: uppercase;
        }
        .btn-identify:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 12px 32px var(--shadow-glow); }
        .btn-identify:disabled { opacity: 0.7; cursor: not-allowed; transform: none; filter: grayscale(50%); }
        .btn-row { display: flex; gap: 12px; }
        .btn-secondary {
            flex-shrink: 0; padding: 18px 20px; background: var(--surface2);
            color: var(--text-dim); border: 1px solid var(--border); border-radius: var(--radius);
            font-family: 'Outfit', sans-serif; font-size: 0.9em; font-weight: 700; letter-spacing: 1px;
            cursor: pointer; transition: all var(--tr); text-transform: uppercase; white-space: nowrap;
        }
        .btn-secondary:hover:not(:disabled) { background: var(--border); color: var(--text); transform: translateY(-2px); }
        .btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-danger:hover:not(:disabled) { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
        [data-theme="dark"] .btn-danger:hover:not(:disabled) { background: #1f0808; color: #f87171; border-color: #7f1d1d; }

        /* Status badges */
        .status-tag {
            display: inline-block; font-size: 0.75em; font-weight: 700;
            padding: 4px 12px; border-radius: 20px; letter-spacing: 1px; text-transform: uppercase;
        }
        .status-cached { background: #dcfce7; color: #16a34a; }
        .status-fresh  { background: #e0f2fe; color: #0369a1; box-shadow: 0 0 12px rgba(3, 105, 161, 0.3); }
        .status-kept   { background: #fef9c3; color: #a16207; box-shadow: 0 0 12px rgba(161, 98, 7, 0.25); }
        [data-theme="dark"] .status-cached { background: #052e16; color: #4ade80; }
        [data-theme="dark"] .status-fresh  { background: #0c1a2e; color: #38bdf8; box-shadow: 0 0 12px rgba(56, 189, 248, 0.2); }
        [data-theme="dark"] .status-kept   { background: #1c1500; color: #fbbf24; box-shadow: 0 0 12px rgba(251, 191, 36, 0.2); }

        /* Error */
        .error-box {
            background: #fee2e2; border: 1px solid #fca5a5; border-radius: var(--radius); padding: 20px;
            color: #991b1b; font-size: 0.9em; line-height: 1.6; display: flex; flex-direction: column; gap: 8px;
        }
        [data-theme="dark"] .error-box { background: #1f0808; border-color: #7f1d1d; color: #f87171; }

        /* Toasts */
        #toast-container {
            position: fixed; top: 24px; right: 24px; z-index: 9999;
            display: flex; flex-direction: column; gap: 12px; pointer-events: none;
        }
        .toast {
            background: var(--surface); color: var(--text); padding: 14px 20px; border-radius: 12px;
            font-size: 0.9em; font-weight: 600; display: flex; align-items: center; gap: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2); animation: toastIn 0.4s cubic-bezier(0.34,1.56,0.64,1) forwards;
            pointer-events: all; max-width: 320px; border: 1px solid var(--border); border-left: 4px solid var(--primary);
        }
        .toast.success { border-left-color: #16a34a; }
        .toast.error   { border-left-color: #ef4444; }
        .toast.info    { border-left-color: #f59e0b; }
        .toast.leaving { animation: toastOut 0.3s ease forwards; }
        @keyframes toastIn  { from { opacity: 0; transform: translateX(100%) scale(0.9); } to { opacity: 1; transform: none; } }
        @keyframes toastOut { from { opacity: 1; transform: none; } to { opacity: 0; transform: translateX(100%); } }

        /* Inline button loading state — no fullscreen overlay */
        .btn-identify {
            width: 100%; padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff; border: none; border-radius: var(--radius);
            font-family: 'Outfit', sans-serif; font-size: 1.05em; font-weight: 700; letter-spacing: 1.5px;
            cursor: pointer; box-shadow: 0 8px 24px var(--shadow-glow); transition: all var(--tr);
            text-transform: uppercase;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-identify:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 12px 32px var(--shadow-glow); }
        .btn-identify:disabled { opacity: 0.85; cursor: not-allowed; transform: none; }

        /* spinner inside button */
        .btn-spinner {
            width: 16px; height: 16px;
            border: 2.5px solid rgba(255,255,255,0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: btnSpin 1.5s linear infinite;
            flex-shrink: 0;
        }
        @keyframes btnSpin { to { transform: rotate(360deg); } }

        /* shimmer sweep across button while loading */
        .btn-identify.loading {
            background: linear-gradient(90deg,
                var(--secondary) 0%,
                var(--accent)    35%,
                var(--secondary) 65%,
                var(--accent)    100%);
            background-size: 250% 100%;
            animation: btnShimmer 1.4s linear infinite;
            letter-spacing: 1px;
        }
        @keyframes btnShimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    <!-- loadingOverlay removed: loading state is now inline in the button -->

    <div class="page">
        <div class="top-bar">
            <a href="<?= htmlspecialchars($backUrl) ?>" class="back-btn">← Back to Gallery</a>
            <span class="page-title">Species Identification</span>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
        </div>

        <div class="detail-layout">
            <!-- Left: Image -->
            <div class="img-panel">
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($imageFile) ?>">
                <div class="img-meta">
                    <span class="img-filename"><?= htmlspecialchars($imageFile) ?></span>
                    <span class="img-time">Captured: <?= $captureTime ?></span>
                    <div class="img-actions">
                        <a href="<?= htmlspecialchars($imagePath) ?>" download="<?= htmlspecialchars($imageFile) ?>" class="btn-download">Save Image</a>
                    </div>
                </div>
            </div>

            <!-- Right: AI Panel -->
            <div class="ai-panel">
                <?php if ($cached): ?>
                <!-- Cached result (hidden by JS after re-run) -->
                <div class="card" id="cachedResultCard">
                    <div class="card-title">
                        AI Identification
                        <span class="status-tag status-cached">Cached</span>
                    </div>
                    <div class="species-name"><?= htmlspecialchars($cached['species'] ?? 'Unknown') ?></div>
                    <div class="common-name"><?= htmlspecialchars($cached['common_name'] ?? '') ?></div>
                    <?php
                    $conf    = $cached['confidence'] ?? null;
                    $confPct = $conf ? (int)round((float)$conf * 100) : null;
                    ?>
                    <?php if ($confPct): ?>
                    <div class="conf-row">
                        <div class="conf-label"><span>Confidence</span><span><?= $confPct ?>%</span></div>
                        <div class="conf-bar-track">
                            <div class="conf-bar-fill" id="confBar" style="width:0%"></div>
                        </div>
                    </div>
                    <script>setTimeout(() => { const b = document.getElementById('confBar'); if(b) b.style.width = '<?= $confPct ?>%'; }, 100);</script>
                    <?php endif; ?>
                    <?php if ($cacheTimestamp): ?>
                    <div class="cache-ts">🕐 Analyzed: <?= htmlspecialchars($cacheTimestamp) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($cached['description'])): ?>
                <div class="card" id="cachedDescCard">
                    <div class="card-title">Description</div>
                    <div class="desc-text"><?= htmlspecialchars($cached['description']) ?></div>
                </div>
                <?php endif; ?>

                <div class="btn-row">
                    <button class="btn-identify" id="rerunBtn" style="flex:1">Re-run Analysis</button>
                    <button class="btn-secondary" id="forceBtn" title="Ignore confidence, always save fresh result">Force Fresh</button>
                    <button class="btn-secondary btn-danger" id="clearBtn" title="Delete cached result">🗑</button>
                </div>

                <?php else: ?>
                <!-- No result yet -->
                <div class="card" id="cachedResultCard">
                    <div class="card-title">AI Identification</div>
                    <p style="color:var(--text-dim);font-size:0.95em;line-height:1.7;">
                        No analysis yet. Click below to identify the insects in this trap image using Gemini AI.
                    </p>
                </div>
                <button class="btn-identify" id="rerunBtn">Identify Species</button>
                <?php endif; ?>

                <!-- Fresh result injected here by JS -->
                <div id="aiResultArea"></div>
            </div>
        </div>
    </div>

    <script>
        const IMG_FILE   = <?= json_encode($imageFile) ?>;
        const HAS_CACHE  = <?= $cached ? 'true' : 'false' ?>;
        const OLD_CONF   = <?= $confPct ?? 'null' ?>;  // PHP-rendered old confidence %

        document.addEventListener('DOMContentLoaded', () => {
            // Theme
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
            const themeBtn = document.getElementById('themeToggle');
            if (themeBtn) {
                themeBtn.innerHTML = saved === 'dark' ? '&#9728;&#65039;' : '&#127769;';
                themeBtn.addEventListener('click', () => {
                    const cur  = document.documentElement.getAttribute('data-theme');
                    const next = cur === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', next);
                    localStorage.setItem('theme', next);
                    themeBtn.innerHTML = next === 'dark' ? '&#9728;&#65039;' : '&#127769;';
                });
            }

            // Re-run button
            const rerunBtn = document.getElementById('rerunBtn');
            if (rerunBtn) {
                rerunBtn.removeAttribute('onclick');
                rerunBtn.addEventListener('click', () => runIdentify(false));
            }

            // Force Fresh button
            const forceBtn = document.getElementById('forceBtn');
            if (forceBtn) {
                forceBtn.addEventListener('click', () => runIdentify(true));
            }

            // Clear Cache button
            const clearBtn = document.getElementById('clearBtn');
            if (clearBtn) {
                clearBtn.addEventListener('click', clearCache);
            }
        });

        // ── Toasts ──────────────────────────────────────────────────────────────
        function showToast(msg, type = 'success') {
            const tc = document.getElementById('toast-container');
            if (!tc) return;
            const t  = document.createElement('div');
            t.className = 'toast ' + type;
            t.textContent = msg;
            tc.appendChild(t);
            setTimeout(() => { t.classList.add('leaving'); setTimeout(() => t.remove(), 320); }, 4500);
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        }

        // overlay helpers removed — no longer needed

        // ── setAnalyzing state (inline button loading) ──────────────────────────
        function setAnalyzing(on) {
            ['rerunBtn','forceBtn','clearBtn'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = on;
            });
            const rb = document.getElementById('rerunBtn');
            if (rb) {
                if (on) {
                    rb.classList.add('loading');
                    rb.innerHTML = '<span class="btn-spinner"></span> Analysing…';
                } else {
                    rb.classList.remove('loading');
                    rb.textContent = HAS_CACHE ? 'Re-run Analysis' : 'Identify Species';
                }
            }
            const fb = document.getElementById('forceBtn');
            if (fb && HAS_CACHE) fb.textContent = on ? '…' : 'Force Fresh';
        }

        // ── Build confidence change pill ─────────────────────────────────────────
        function confChangePill(change) {
            if (change === null || change === undefined) return '';
            const cls   = change > 0 ? 'up' : change < 0 ? 'down' : 'same';
            const arrow = change > 0 ? '▲' : change < 0 ? '▼' : '—';
            const label = change !== 0 ? `${arrow} ${Math.abs(change)}%` : `${arrow} Same`;
            return `<span class="conf-change ${cls}">${label}</span>`;
        }

        // ── Build result HTML ────────────────────────────────────────────────────
        function buildResultHTML(data) {
            const species    = data.species    || 'Unknown';
            const common     = data.common_name || '';
            const confRaw    = data.confidence  ? parseFloat(data.confidence) : null;
            const confPct    = confRaw ? Math.round(confRaw * 100) : null;
            const desc       = data.description || '';
            const keptCache  = data.source === 'cached';
            const confChange = (data.conf_change !== null && data.conf_change !== undefined) ? data.conf_change : null;
            const isMock     = data.is_mock === true;
            const now        = new Date().toLocaleString('en-IN', { year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit' });

            const badgeClass = keptCache ? 'status-kept' : 'status-fresh';
            const badgeLabel = keptCache ? '⭐ Kept Cache' : 'Fresh';

            let html = `
            <div class="card">
                <div class="card-title">AI Identification <span class="status-tag ${badgeClass}">${badgeLabel}</span></div>
                <div class="species-name">${escapeHtml(species)}</div>
                <div class="common-name">${escapeHtml(common)}</div>`;

            if (confPct !== null) {
                const pill = confChangePill(confChange);
                html += `
                <div class="conf-row">
                    <div class="conf-label">
                        <span>Confidence${pill}</span>
                        <span>${confPct}%</span>
                    </div>
                    <div class="conf-bar-track"><div class="conf-bar-fill" id="newConfBar" style="width:0%"></div></div>
                </div>`;
            }

            if (!keptCache) {
                html += `<div class="cache-ts">🕐 Analyzed: ${escapeHtml(now)}</div>`;
            }

            if (isMock) {
                html += `<div class="mock-warning">⚠️ API was unavailable — this is mock data and was NOT saved to cache.</div>`;
            }

            html += `</div>`;

            if (desc) {
                html += `<div class="card"><div class="card-title">Description</div><div class="desc-text">${escapeHtml(desc)}</div></div>`;
            }

            return { html, confPct };
        }

        // ── Main identify ────────────────────────────────────────────────────────
        function runIdentify(forceFresh = false) {
            setAnalyzing(true);
            // no overlay: loading shown in button itself

            const area = document.getElementById('aiResultArea');
            if (area) area.innerHTML = ''; // clear previous injected result

            fetch('classify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'image=' + encodeURIComponent(IMG_FILE) + (forceFresh ? '&force=1' : '')
            })
            .then(async r => {
                const txt = await r.text();
                try {
                    const p = JSON.parse(txt);
                    if (!r.ok && !p.error) p.error = `HTTP ${r.status}`;
                    return p;
                } catch(e) {
                    return { error: 'Non-JSON response', details: txt };
                }
            })
            .then(data => {
                setAnalyzing(false);

                if (data && data.error) {
                    const det = typeof data.details === 'object' ? JSON.stringify(data.details, null, 2) : String(data.details || '');
                    if (area) area.innerHTML = `<div class="error-box"><strong>${escapeHtml(data.error)}</strong>${det ? `<br><br><code style="font-size:0.85em;white-space:pre-wrap;">${escapeHtml(det)}</code>` : ''}</div>`;
                    showToast('Analysis failed: ' + data.error, 'error');
                    return;
                }

                const keptCache = data && data.source === 'cached';
                const isMock    = data && data.is_mock === true;

                if (isMock) {
                    showToast('API unavailable — mock result shown, cache not updated.', 'info');
                } else if (keptCache) {
                    showToast('Cache kept — existing result has higher confidence.', 'info');
                } else if (data.conf_change === 0) {
                    showToast('Analysis updated — same confidence as before.', 'success');
                } else {
                    showToast('Fresh result saved!', 'success');
                }

                // Hide PHP-rendered cached cards (no more duplicate blocks)
                ['cachedResultCard', 'cachedDescCard'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.transition = 'opacity 0.3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 320); }
                });

                const { html, confPct } = buildResultHTML(data);
                if (area) {
                    area.innerHTML = html;
                    // Auto-scroll to result
                    setTimeout(() => area.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
                }
                if (confPct) {
                    setTimeout(() => { const b = document.getElementById('newConfBar'); if(b) b.style.width = confPct + '%'; }, 200);
                }
            })
            .catch(err => {
                setAnalyzing(false);
                const area = document.getElementById('aiResultArea');
                if (area) area.innerHTML = `<div class="error-box"><strong>Network error</strong><br>${escapeHtml(err.message)}</div>`;
                showToast('Network error', 'error');
                console.error(err);
            });
        }

        // ── Clear cache ──────────────────────────────────────────────────────────
        function clearCache() {
            if (!confirm('Delete the cached analysis for this image? This cannot be undone.')) return;
            const clearBtn = document.getElementById('clearBtn');
            if (clearBtn) clearBtn.disabled = true;

            fetch('clear_cache.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'image=' + encodeURIComponent(IMG_FILE)
            })
            .then(r => r.json())
            .then(data => {
                if (data.cleared) {
                    showToast('Cache cleared — analysis reset.', 'info');
                    // Replace the cached block with the "no analysis yet" state
                    const rc = document.getElementById('cachedResultCard');
                    const dc = document.getElementById('cachedDescCard');
                    if (dc) dc.remove();
                    if (rc) {
                        rc.innerHTML = `
                            <div class="card-title">AI Identification</div>
                            <p style="color:var(--text-dim);font-size:0.95em;line-height:1.7;">
                                Cache deleted. Click Re-run Analysis to identify this specimen again.
                            </p>`;
                    }
                    // Hide force/clear buttons, update re-run label
                    const fb = document.getElementById('forceBtn');
                    if (fb) fb.style.display = 'none';
                    if (clearBtn) clearBtn.style.display = 'none';
                    const rb = document.getElementById('rerunBtn');
                    if (rb) rb.innerText = 'Identify Species';
                } else {
                    showToast(data.message || 'Could not clear cache.', 'error');
                    if (clearBtn) clearBtn.disabled = false;
                }
            })
            .catch(() => {
                showToast('Network error while clearing cache.', 'error');
                if (clearBtn) clearBtn.disabled = false;
            });
        }
    </script>
</body>
</html>
