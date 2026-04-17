    <?php
    include('config.php');
    checkAccess('user'); // Anyone logged in can view
    // ── Config & Helpers ─────────────────────────────────────────
    $view = $_GET['view'] ?? 'landing';
    $device = $_GET['device'] ?? null;
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir))
        mkdir($upload_dir, 0777, true);

    function lastSeenLabel($ts)
    {
        if (!$ts)
            return 'Never';
        $d = time() - $ts;
        if ($d < 60)
            return 'Just now';
        if ($d < 3600)
            return floor($d / 60) . ' min ago';
        if ($d < 86400)
            return floor($d / 3600) . ' hr ago';
        return floor($d / 86400) . ' days ago';
    }
    function deviceStatusClass($ts)
    {
        if (!$ts)
            return 'offline';
        $d = time() - $ts;
        if ($d < 300)
            return 'online';
        if ($d < 3600)
            return 'stale';
        return 'offline';
    }
    function deviceStatusLabel($ts)
    {
        if (!$ts)
            return 'Offline';
        $d = time() - $ts;
        if ($d < 300)
            return 'Online';
        if ($d < 3600)
            return 'Stale';
        return 'Offline';
    }
    ?><!DOCTYPE html>
    <html lang="en" data-theme="light">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Insect NET — Mission Control</title>
        <meta name="description" content="INMT Mission Control Dashboard — NeuRonICS Lab, IISc Bangalore">
        <link
            href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;700&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
        <style>
            /* ═══ DESIGN TOKENS — matches login.php palette ═══ */
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
                --shadow-glow: rgba(138, 34, 69, 0.25);
                --on-bg: #dcfce7;
                --on-fg: #16a34a;
                --st-bg: #fef9c3;
                --st-fg: #92400e;
                --off-bg: #fee2e2;
                --off-fg: #ef4444;
                --radius: 16px;
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
                --shadow-glow: rgba(196, 69, 105, 0.35);
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
                line-height: 1.6;
                transition: background var(--tr), color var(--tr);
                background-image: radial-gradient(var(--border) 1px, transparent 1px);
                background-size: 32px 32px;
            }

            /* ═══ THEME TOGGLE ═══ */
            .theme-toggle {
                position: fixed;
                top: 16px;
                right: 16px;
                z-index: 900;
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

            /* ═══ TOASTS ═══ */
            #toast-container {
                position: fixed;
                top: 20px;
                right: 64px;
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
                from {
                    opacity: 0;
                    transform: translateX(16px)
                }

                to {
                    opacity: 1;
                    transform: none
                }
            }

            @keyframes toastOut {
                from {
                    opacity: 1;
                    transform: none
                }

                to {
                    opacity: 0;
                    transform: translateX(16px)
                }
            }

            /* ═══ LAYOUT ═══ */
            .container {
                max-width: 1600px;
                margin: 0 auto;
                padding: 20px;
            }

            /* ═══ HEADER ═══ */
            .header {
                background: linear-gradient(135deg, var(--surface) 0%, var(--surface2) 100%);
                border: 1px solid var(--border);
                padding: 24px 28px;
                border-radius: 16px;
                margin-bottom: 24px;
                box-shadow: 0 8px 32px var(--shadow);
            }

            .header-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 16px;
            }

            .header-left {
                display: flex;
                align-items: center;
                gap: 18px;
            }

            .header-logos {
                display: flex;
                align-items: center;
                gap: 12px;
                background: var(--surface2);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 8px 14px;
            }

            .header-logos img {
                height: 36px;
                width: auto;
                object-fit: contain;
                vertical-align: middle;
                display: block;
            }

            .logo-divider {
                width: 1px;
                height: 28px;
                background: linear-gradient(180deg, transparent, var(--border), transparent);
                flex-shrink: 0;
            }

            .header h1 {
                font-family: 'Space Mono', monospace;
                font-size: clamp(1.3em, 3.5vw, 2em);
                color: var(--accent);
                letter-spacing: 4px;
            }

            .header-subtitle {
                font-size: 0.72em;
                color: var(--text-dim);
                letter-spacing: 2px;
                margin-top: 2px;
            }

            /* Refresh indicator */
            .refresh-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.78em;
                color: var(--text-dim);
            }

            .refresh-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--on-fg);
                flex-shrink: 0;
            }

            .refresh-dot.pulsing {
                animation: rdPulse 0.7s ease;
            }

            @keyframes rdPulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7)
                }

                70% {
                    box-shadow: 0 0 0 8px rgba(74, 222, 128, 0)
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(74, 222, 128, 0)
                }
            }

            .refresh-ring {
                width: 26px;
                height: 26px;
            }

            .refresh-ring svg {
                transform: rotate(-90deg);
                display: block;
            }

            .refresh-ring circle {
                fill: none;
                stroke: var(--accent);
                stroke-width: 2.5;
                stroke-dasharray: 69;
                stroke-dashoffset: 0;
                stroke-linecap: round;
                transition: stroke-dashoffset 1s linear;
            }

            /* ═══ USER CHIP ═══ */
            .user-chip {
                display: flex;
                align-items: center;
                gap: 10px;
                background: var(--surface2);
                border: 1px solid var(--border);
                border-radius: 99px;
                padding: 5px 14px 5px 6px;
                box-shadow: 0 2px 8px var(--shadow);
            }

            .user-avatar {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary), var(--accent));
                color: #fff;
                font-size: 0.8em;
                font-weight: 800;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                letter-spacing: 0;
            }

            .user-name {
                font-size: 0.85em;
                font-weight: 700;
                color: var(--text);
                line-height: 1;
            }

            .user-role-badge {
                font-size: 0.62em;
                font-weight: 700;
                letter-spacing: 0.8px;
                text-transform: uppercase;
                background: rgba(138,34,69,0.12);
                color: var(--primary);
                border-radius: 4px;
                padding: 2px 6px;
                line-height: 1;
            }

            [data-theme="dark"] .user-role-badge {
                background: rgba(196,69,105,0.18);
                color: var(--accent);
            }

            .logout-btn {
                background: linear-gradient(135deg, var(--primary), var(--accent));
                color: #fff;
                border: none;
                border-radius: 8px;
                padding: 7px 16px;
                font-size: 0.78em;
                font-weight: 700;
                cursor: pointer;
                transition: all var(--tr);
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 10px rgba(138,34,69,0.25);
                white-space: nowrap;
            }

            .logout-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 18px rgba(138, 34, 69, 0.38);
            }

            .logout-btn:active {
                transform: translateY(0);
            }

            /* ═══ USER MENU wrapper ═══ */
            .user-menu {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .device-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 18px;
                margin-bottom: 24px;
            }

            .device-card {
                background: var(--surface);
                border: 1px solid var(--border);
                border-left: 4px solid var(--border);
                padding: 18px;
                border-radius: var(--radius);
                cursor: pointer;
                transition: transform var(--tr), box-shadow var(--tr), border-color var(--tr);
            }

            .device-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 32px var(--shadow-md);
            }

            .device-card.status-online {
                border-left-color: var(--on-fg);
            }

            .device-card.status-stale {
                border-left-color: var(--st-fg);
            }

            .device-card.status-offline {
                border-left-color: var(--off-fg);
            }

            .device-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 10px;
            }

            .device-icon {
                width: 34px;
                height: 34px;
                border-radius: 8px;
                background: linear-gradient(135deg, var(--primary), var(--accent));
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.95em;
                flex-shrink: 0;
            }

            .device-card h3 {
                font-size: 0.95em;
                font-weight: 600;
                margin: 2px 0;
            }

            .device-meta {
                font-size: 0.8em;
                color: var(--text-dim);
                margin-top: 3px;
            }

            .device-status {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 2px 9px;
                border-radius: 20px;
                font-size: 0.7em;
                font-weight: 700;
            }

            .device-status::before {
                content: '●';
                font-size: 0.8em;
            }

            .online {
                background: var(--on-bg);
                color: var(--on-fg);
            }

            .stale {
                background: var(--st-bg);
                color: var(--st-fg);
            }

            .offline {
                background: var(--off-bg);
                color: var(--off-fg);
            }

            .device-battery-bar {
                margin-top: 11px;
                height: 4px;
                background: var(--surface2);
                border-radius: 4px;
                overflow: hidden;
            }

            .device-battery-fill {
                height: 100%;
                width: 0%;
                border-radius: 4px;
                background: linear-gradient(90deg, var(--primary), var(--accent));
                transition: width 0.5s ease;
            }

            /* ═══ MAP SECTION ═══ */
            .map-section {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 8px 32px var(--shadow);
            }

            .map-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 14px 18px;
                border-bottom: 1px solid var(--border);
            }

            .map-header h3 {
                font-size: 0.85em;
                font-weight: 700;
                letter-spacing: 1.5px;
            }

            .last-updated {
                font-size: 0.74em;
                color: var(--text-dim);
            }

            .map-expand-btn {
                background: var(--surface2);
                border: 1px solid var(--border);
                border-radius: 6px;
                padding: 4px 10px;
                font-size: 0.72em;
                cursor: pointer;
                color: var(--text-dim);
                transition: all var(--tr);
            }

            .map-expand-btn:hover {
                background: var(--border);
                color: var(--text);
            }

            #map {
                height: 320px;
            }

            #mapFullscreen {
                position: fixed;
                inset: 0;
                z-index: 5000;
                display: none;
            }

            #mapFullscreen.open {
                display: block;
            }

            #mapFsClose {
                position: absolute;
                top: 14px;
                right: 14px;
                z-index: 5001;
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 8px;
                padding: 7px 14px;
                font-size: 0.82em;
                cursor: pointer;
                box-shadow: 0 4px 16px var(--shadow);
            }

            #mapFs {
                width: 100%;
                height: 100vh;
            }

            /* ═══ DEVICE DETAIL ═══ */
            .back-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 18px;
                text-decoration: none;
                color: var(--primary);
                font-weight: 600;
                font-size: 0.88em;
                transition: gap var(--tr);
            }

            .back-btn:hover {
                gap: 10px;
            }

            .panel-row {
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(250px, 1fr);
                gap: 16px;
                margin-bottom: 12px;
            }

            @media(max-width:768px) {
                .panel-row {
                    grid-template-columns: 1fr;
                }
            }

            .status-panel {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                padding: 18px;
                display: flex;
                flex-direction: column;
                gap: 14px;
                box-shadow: 0 4px 16px var(--shadow);
            }

            .status-row {
                display: flex;
                align-items: center;
                gap: 10px;
                width: 100%;
            }

            .status-label {
                font-family: 'Space Mono', monospace;
                letter-spacing: 1px;
                font-size: 0.74em;
                color: var(--text-dim);
                text-transform: uppercase;
                min-width: 70px;
            }

            .status-track {
                flex: 1;
                height: 8px;
                background: var(--surface2);
                border-radius: 999px;
                overflow: hidden;
            }

            .status-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(90deg, var(--primary), var(--accent));
                transition: width 0.5s ease;
            }

            .status-text {
                font-size: 0.78em;
                color: var(--text);
                font-weight: 600;
                min-width: 86px;
                text-align: right;
            }

            /* ═══ GALLERY ═══ */
            .gallery-controls {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 14px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .gallery-controls select {
                padding: 7px 11px;
                border-radius: 8px;
                border: 1px solid var(--border);
                background: var(--surface);
                color: var(--text);
                font-size: 0.82em;
                cursor: pointer;
            }

            .gallery-controls select:focus {
                outline: none;
                border-color: var(--accent);
            }

            .image-gallery {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 14px;
            }

            .image-container {
                background: var(--surface);
                border: 1px solid var(--border);
                padding: 9px;
                border-radius: var(--radius);
                position: relative;
                transition: border-color var(--tr), box-shadow var(--tr);
            }

            .image-container:hover {
                border-color: var(--accent);
                box-shadow: 0 4px 20px var(--shadow-md);
            }

            .img-wrapper {
                position: relative;
                width: 100%;
                padding-bottom: 75%;
                background: var(--surface2);
                border-radius: 8px;
                overflow: hidden;
                margin-bottom: 7px;
            }

            .img-wrapper img {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 8px;
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .img-wrapper img.loaded {
                opacity: 1;
            }

            .img-wrapper::before {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, var(--surface2) 25%, var(--border) 50%, var(--surface2) 75%);
                background-size: 200% 100%;
                animation: shimmer 1.4s infinite;
                border-radius: 8px;
                transition: opacity 0.3s;
            }

            .img-wrapper.img-ready::before {
                opacity: 0;
                pointer-events: none;
            }

            @keyframes shimmer {
                0% {
                    background-position: 200% 0
                }

                100% {
                    background-position: -200% 0
                }
            }

            .delete-btn {
                position: absolute;
                top: 7px;
                right: 7px;
                border: none;
                border-radius: 6px;
                padding: 3px 8px;
                cursor: pointer;
                font-size: 0.7em;
                font-weight: 700;
                z-index: 10;
                opacity: 0;
                transition: opacity 0.2s;
                white-space: nowrap;
                background: rgba(220, 38, 38, 0.85);
                color: #fff;
            }

            .image-container:hover .delete-btn {
                opacity: 1;
            }

            .delete-btn.confirm-mode {
                background: #7f1d1d;
            }

            .image-container.deleting {
                opacity: 0.4;
                pointer-events: none;
                transition: opacity 0.3s;
            }

            .download-btn {
                position: absolute;
                bottom: 7px;
                right: 7px;
                border: none;
                border-radius: 6px;
                padding: 3px 8px;
                cursor: pointer;
                font-size: 0.7em;
                font-weight: 700;
                z-index: 10;
                opacity: 0;
                transition: opacity 0.2s;
                white-space: nowrap;
                background: rgba(22, 163, 74, 0.82);
                color: #fff;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }

            .image-container:hover .download-btn {
                opacity: 1;
            }

            /* ═══ ON-DEMAND AI CLASSIFICATION ═══ */
            .btn-ai {
                position: absolute;
                bottom: 7px;
                left: 7px;
                border: none;
                border-radius: 6px;
                padding: 4px 10px;
                cursor: pointer;
                font-size: 0.68em;
                font-weight: 700;
                z-index: 10;
                opacity: 0;
                transition: opacity 0.2s, transform 0.2s, box-shadow 0.2s;
                white-space: nowrap;
                background: linear-gradient(135deg, var(--primary), var(--accent));
                color: #fff;
                letter-spacing: 0.4px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                box-shadow: 0 2px 8px rgba(138,34,69,0.35);
            }

            .image-container:hover .btn-ai {
                opacity: 1;
            }

            .btn-ai:hover:not(:disabled) {
                transform: translateY(-1px);
                box-shadow: 0 4px 14px rgba(138,34,69,0.45);
            }

            .btn-ai:disabled {
                cursor: not-allowed;
                background: linear-gradient(135deg, var(--secondary), #9b3060);
            }

            /* Spinner inside button */
            .btn-ai-spinner {
                width: 10px;
                height: 10px;
                border: 2px solid rgba(255,255,255,0.35);
                border-top-color: #fff;
                border-radius: 50%;
                animation: spin 0.7s linear infinite;
                flex-shrink: 0;
            }

            /* Shimmer pulse while loading */
            .btn-ai.loading {
                background: linear-gradient(90deg,
                    var(--secondary) 0%,
                    var(--accent) 40%,
                    var(--secondary) 80%,
                    var(--accent) 100%);
                background-size: 200% 100%;
                animation: aiShimmer 1.2s linear infinite;
            }

            @keyframes aiShimmer {
                0%   { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }

            .ai-badge {
                position: absolute;
                bottom: 44px;
                left: 7px;
                right: 7px;
                background: rgba(22, 163, 74, 0.92);
                color: #052e16;
                border: 1px solid rgba(22, 163, 74, 0.35);
                border-radius: 8px;
                padding: 8px 10px;
                font-size: 0.78em;
                font-weight: 700;
                display: none;
                z-index: 9;
                box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
                word-break: break-word;
            }

            .ai-badge em {
                font-style: italic;
                font-weight: 700;
            }

            /* ═════════════════════════════════ */

            /* ═══ DAY CHART ═══ */
            .day-chart {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                padding: 18px 22px;
                margin-bottom: 16px;
                box-shadow: 0 4px 16px var(--shadow);
            }

            .day-chart-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .day-chart h4 {
                font-size: 0.75em;
                font-weight: 700;
                letter-spacing: 1.5px;
                color: var(--text-dim);
                text-transform: uppercase;
                margin: 0;
            }

            .chart-total-badge {
                font-size: 0.72em;
                font-weight: 700;
                background: rgba(138,34,69,0.1);
                color: var(--primary);
                border-radius: 99px;
                padding: 2px 10px;
            }

            [data-theme="dark"] .chart-total-badge {
                background: rgba(196,69,105,0.18);
                color: var(--accent);
            }

            /* bar chart — bars grow from bottom */
            .chart-bars {
                display: flex;
                align-items: flex-end;
                gap: 6px;
                height: 80px;          /* fixed height — bars are absolute px */
                padding-bottom: 0;
            }

            .chart-col {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-end;  /* keep label at bottom */
                gap: 3px;
                position: relative;
                height: 100%;
            }

            .chart-col:hover .chart-tooltip {
                opacity: 1;
                transform: translateX(-50%) translateY(-4px);
            }

            .chart-tooltip {
                position: absolute;
                bottom: calc(100% + 2px);
                left: 50%;
                transform: translateX(-50%) translateY(0);
                background: var(--text);
                color: var(--surface);
                font-size: 0.65em;
                font-weight: 700;
                padding: 3px 7px;
                border-radius: 5px;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.15s ease, transform 0.15s ease;
                z-index: 20;
            }

            /* the actual bar — height set via inline style in px */
            .chart-bar {
                width: 100%;
                border-radius: 4px 4px 2px 2px;
                background: linear-gradient(180deg, var(--accent) 0%, var(--primary) 100%);
                min-height: 3px;
                flex-shrink: 0;
                transition: height 0.55s cubic-bezier(0.34,1.56,0.64,1);
            }

            .chart-col:hover .chart-bar {
                filter: brightness(1.15);
            }

            .chart-count {
                font-size: 0.6em;
                font-weight: 800;
                color: var(--primary);
                line-height: 1;
                flex-shrink: 0;
            }

            .chart-label {
                font-size: 0.55em;
                color: var(--text-dim);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
                text-align: center;
                flex-shrink: 0;
            }


            .img-filename {
                font-size: 0.7em;
                color: var(--text-dim);
                word-break: break-all;
            }

            .date-heading {
                grid-column: 1/-1;
                font-family: 'Space Mono', monospace;
                padding: 7px 0;
                border-bottom: 1px dashed var(--border);
                color: var(--primary);
                font-weight: 700;
                font-size: 0.82em;
                margin-top: 14px;
            }

            .gallery-empty {
                grid-column: 1/-1;
                text-align: center;
                padding: 60px 20px;
                color: var(--text-dim);
            }

            .gallery-loading {
                grid-column: 1/-1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                padding: 40px;
                color: var(--text-dim);
            }

            .spinner {
                width: 20px;
                height: 20px;
                border: 3px solid var(--border);
                border-top-color: var(--primary);
                border-radius: 50%;
                animation: spin 0.7s linear infinite;
            }

            @keyframes spin {
                to {
                    transform: rotate(360deg)
                }
            }

            /* ═══ LIGHTBOX ═══ */
            .lightbox {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.92);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                padding: 16px;
            }

            .lightbox.open {
                display: flex;
            }

            .lightbox-panel {
                position: relative;
                background: #0f0f0f;
                border-radius: 12px;
                padding: 8px;
                max-width: min(94vw, 900px);
            }

            .lightbox-panel img {
                max-width: 100%;
                max-height: 74vh;
                border-radius: 8px;
                display: block;
            }

            .lightbox-close {
                position: absolute;
                top: -13px;
                right: -13px;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: 2px solid rgba(255, 255, 255, 0.25);
                background: #1a1a1a;
                color: #fff;
                font-size: 0.9em;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
                transition: background 0.2s, border-color 0.2s;
            }

            .lightbox-close:hover {
                background: #ef4444;
                border-color: #ef4444;
            }

            .lightbox-nav-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 9px 4px 4px;
            }

            .lightbox-nav {
                border: none;
                background: rgba(255, 255, 255, 0.13);
                color: #fff;
                padding: 5px 14px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 0.9em;
                transition: background 0.2s;
            }

            .lightbox-nav:hover {
                background: rgba(255, 255, 255, 0.22);
            }

            #lightboxCap {
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.76em;
                text-align: center;
                flex: 1;
                padding: 0 10px;
            }

            /* ═══ LANDING ═══ */
            .landing {
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .landing-grid {
                position: absolute;
                inset: 0;
                background-image: linear-gradient(var(--border) 1px, transparent 1px), linear-gradient(90deg, var(--border) 1px, transparent 1px);
                background-size: 44px 44px;
                opacity: 0.35;
                pointer-events: none;
            }

            .landing-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(90px);
                pointer-events: none;
            }

            .orb1 {
                width: 480px;
                height: 480px;
                background: rgba(138, 34, 69, 0.18);
                top: -120px;
                left: -120px;
                animation: floatA 9s ease-in-out infinite;
            }

            .orb2 {
                width: 380px;
                height: 380px;
                background: rgba(196, 69, 105, 0.12);
                bottom: -80px;
                right: -80px;
                animation: floatA 11s ease-in-out infinite reverse;
            }

            @keyframes floatA {

                0%,
                100% {
                    transform: translate(0, 0)
                }

                50% {
                    transform: translate(28px, 18px)
                }
            }

            .brand-card {
                display: flex;
                align-items: center;
                gap: 20px;
                padding: 18px 26px;
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 16px;
                box-shadow: 0 10px 40px var(--shadow);
                margin-bottom: 32px;
                position: relative;
                z-index: 1;
            }

            .brand-divider {
                width: 1px;
                align-self: stretch;
                background: linear-gradient(180deg, transparent, var(--border), transparent);
            }

            .landing h1 {
                font-family: 'Space Mono', monospace;
                font-size: clamp(2em, 6vw, 4.2em);
                color: var(--primary);
                letter-spacing: 8px;
                position: relative;
                z-index: 1;
            }

            .landing-sub {
                letter-spacing: 3px;
                margin-bottom: 36px;
                color: var(--text-dim);
                font-size: clamp(0.72em, 2vw, 0.9em);
                position: relative;
                z-index: 1;
            }

            .cta-btn {
                background: linear-gradient(135deg, var(--primary), var(--accent));
                color: #fff;
                padding: 14px 44px;
                border-radius: 30px;
                text-decoration: none;
                font-weight: 700;
                letter-spacing: 1.5px;
                font-size: 0.88em;
                position: relative;
                z-index: 1;
                box-shadow: 0 6px 24px rgba(138, 34, 69, 0.35);
                transition: transform var(--tr), box-shadow var(--tr);
            }

            .cta-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(138, 34, 69, 0.45);
            }

            /* ═══ MISC ═══ */
            .last-updated-row {
                display: flex;
                justify-content: flex-end;
                margin: -2px 0 14px;
            }

            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
            }
        </style>
    </head>

    <body>

        <!-- Theme toggle -->
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
        <div id="toast-container" aria-live="polite" aria-atomic="true"></div>
        <div class="sr-only" id="srStatus" aria-live="polite"></div>

        <!-- Shared JS: theme + toasts + timeSince -->
        <script>
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

            function showToast(msg, type) {
                const tc = document.getElementById('toast-container');
                const t = document.createElement('div');
                t.className = 'toast';
                t.textContent = msg;
                if (type === 'error') t.style.borderLeftColor = '#ef4444';
                else if (type === 'warn') t.style.borderLeftColor = '#fbbf24';
                else if (type === 'info') t.style.borderLeftColor = '#60a5fa';
                tc.appendChild(t);
                setTimeout(() => { t.classList.add('leaving'); setTimeout(() => t.remove(), 320); }, 4000);
            }

            function timeSince(ts) {
                const d = Math.floor(Date.now() / 1000) - ts;
                if (d < 60) return 'Just now';
                if (d < 3600) return `${Math.floor(d / 60)} min ago`;
                if (d < 86400) return `${Math.floor(d / 3600)} hr ago`;
                return `${Math.floor(d / 86400)} days ago`;
            }
        </script>

        <?php if ($view === 'landing'): ?>
            <!-- ══════════ LANDING ══════════ -->
            <div class="landing">
                <div class="landing-grid"></div>
                <div class="landing-orb orb1"></div>
                <div class="landing-orb orb2"></div>
                <div class="brand-card">
                    <img src="neuronics_logo.png" style="height:76px;" onerror="this.style.display='none'"
                        alt="NeuRonICS">
                    <div class="brand-divider"></div>
                    <img src="iisc_logo.jpg" style="height:120px;" onerror="this.style.display='none'" alt="IISc">
                </div>
                <h1>INSECT NET</h1>
                <p class="landing-sub">MISSION CONTROL &mdash; NeuRonICS LAB &middot; IISc</p>
                <a href="index.php?view=dashboard" class="cta-btn">ENTER DASHBOARD</a>
            </div>

        <?php elseif ($view === 'dashboard' && !$device): ?>
            <!-- ══════════ FLEET DASHBOARD ══════════ -->
            <div class="container">
                <div class="header">
                    <div class="header-content">
                        <div class="header-left">
                            <div class="header-logos">
                                <img src="neuronics_logo.png" onerror="this.style.display='none'" alt="NeuRonICS">
                                <div class="logo-divider"></div>
                                <img src="iisc_logo.jpg" onerror="this.style.display='none'" alt="IISc">
                            </div>
                            <div>
                                <h1>INSECT NET</h1>
                                <div class="header-subtitle">MISSION CONTROL &middot; GLOBAL FLEET</div>
                            </div>
                        </div>
                        <div class="refresh-indicator">
                            <div class="refresh-dot" id="refreshDot"></div>
                            <span id="fleetLastUpdated">Connecting&hellip;</span>
                            <div class="refresh-ring" title="Next poll in 5s">
                                <svg width="26" height="26" viewBox="0 0 26 26">
                                    <circle cx="13" cy="13" r="11" id="cRing" />
                                </svg>
                            </div>
                        </div>
                        <div class="user-menu">
                            <div class="user-chip">
                                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <span class="user-role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                            </div>
                            <a href="logout.php" class="logout-btn">🚪 Logout</a>
                        </div>
                    </div>
                </div>

                <div class="device-grid">
                    <?php
                    $deviceList = [
                        'cam1' => ['label' => 'INMT Device 1', 'key' => 'device1'],
                        'cam2' => ['label' => 'INMT Device 2', 'key' => 'device2'],
                    ];
                    foreach ($deviceList as $camId => $info):
                        $files = glob($upload_dir . $camId . '_*.{jpg,jpeg,png}', GLOB_BRACE) ?: [];
                        $lastTs = $files ? max(array_map('filemtime', $files)) : null;
                        $imgCount = count($files);
                        $statusClass = deviceStatusClass($lastTs);
                        $statusLabel = deviceStatusLabel($lastTs);
                        $lastSeenStr = lastSeenLabel($lastTs);
                        ?>
                        <div class="device-card status-<?= $statusClass ?>"
                            onclick="location.href='index.php?view=dashboard&device=<?= $info['key'] ?>'">
                            <div class="device-card-header">
                                <div class="device-icon">📡</div>
                                <div id="status-<?= $camId ?>" class="device-status <?= $statusClass ?>"><?= $statusLabel ?></div>
                            </div>
                            <h3><?= $info['label'] ?></h3>
                            <p id="battery-<?= $camId ?>" class="device-meta">Battery: <span>--</span></p>
                            <p class="device-meta">Last seen: <span id="lastseen-<?= $camId ?>"><?= $lastSeenStr ?></span></p>
                            <p class="device-meta"><?= $imgCount ?> image<?= $imgCount !== 1 ? 's' : '' ?> stored</p>
                            <div class="device-battery-bar">
                                <div id="battbar-<?= $camId ?>" class="device-battery-fill"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="map-section">
                    <div class="map-header">
                        <h3>&#8998; FLEET MAP</h3>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span id="fleetMapUpdated" class="last-updated">--</span>
                            <button class="map-expand-btn" onclick="openFsMap()">&#10063; Expand</button>
                        </div>
                    </div>
                    <div id="map"></div>
                </div>
            </div>

            <div id="mapFullscreen">
                <button id="mapFsClose" onclick="closeFsMap()">&#x2715; Close</button>
                <div id="mapFs"></div>
            </div>

            <script>
                const DEVICES = ['cam1', 'cam2'];
                const DEF_LOC = { cam1: { lat: 13.0187, lng: 77.5708 }, cam2: { lat: 13.0127, lng: 77.5677 } };
                const POLL = 5000;
                let markers = {}, fsMap = null;

                const map = L.map('map').setView([DEF_LOC.cam1.lat, DEF_LOC.cam1.lng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

                // ── Countdown ring ───────────────────────────────────────────
                const cRing = document.getElementById('cRing');
                const CIRC = 2 * Math.PI * 11;
                if (cRing) { cRing.style.strokeDasharray = CIRC; }
                let pollStart = Date.now();
                (function animRing() {
                    if (cRing) cRing.style.strokeDashoffset = CIRC * (((Date.now() - pollStart) % POLL) / POLL);
                    requestAnimationFrame(animRing);
                })();

                function pulseDot() {
                    const d = document.getElementById('refreshDot');
                    if (!d) return;
                    d.classList.add('pulsing');
                    setTimeout(() => d.classList.remove('pulsing'), 700);
                }

                const prevStatus = {};
                function updateFleet() {
                    pollStart = Date.now(); pulseDot();
                    const now = new Date().toLocaleTimeString();
                    const u1 = document.getElementById('fleetLastUpdated');
                    const u2 = document.getElementById('fleetMapUpdated');
                    if (u1) u1.textContent = `Updated ${now}`;
                    if (u2) u2.textContent = `Last updated: ${now}`;

                    DEVICES.forEach(id => {
                        fetch(`get_status.php?device_id=${id}`)
                            .then(r => r.json()).then(data => {
                                const names = { cam1: 'INMT Device 1', cam2: 'INMT Device 2' };
                                const labels = { online: 'Online', stale: 'Stale', offline: 'Offline' };
                                let newStatus = 'offline', lat = NaN, lng = NaN, batt = '--';

                                if (data.status === 'success' && data.latest) {
                                    const d = data.latest;
                                    const ts = d.timestamp ? parseInt(d.timestamp) : null;
                                    const v = parseFloat(d.battery_voltage);
                                    lat = parseFloat(d.gps_latitude);
                                    lng = parseFloat(d.gps_longitude);
                                    if (!isNaN(v)) {
                                        batt = v.toFixed(2);
                                        const sp = document.querySelector(`#battery-${id} span`);
                                        if (sp) sp.textContent = `${batt}V`;
                                        const pct = Math.max(0, Math.min(100, ((v - 3.3) / 0.9) * 100));
                                        const bb = document.getElementById(`battbar-${id}`);
                                        if (bb) bb.style.width = `${pct}%`;
                                    }
                                    if (ts) {
                                        const ls = document.getElementById(`lastseen-${id}`);
                                        if (ls) ls.textContent = timeSince(ts);
                                    }
                                    const diff = ts ? (Date.now() / 1000 - ts) : Infinity;
                                    newStatus = diff < 300 ? 'online' : diff < 3600 ? 'stale' : 'offline';
                                }
                                if (isNaN(lat) || isNaN(lng)) { lat = DEF_LOC[id].lat; lng = DEF_LOC[id].lng; }

                                const statEl = document.getElementById(`status-${id}`);
                                const card = statEl?.closest('.device-card');
                                if (statEl) { statEl.textContent = labels[newStatus]; statEl.className = `device-status ${newStatus}`; }
                                if (card) { card.className = `device-card status-${newStatus}`; }

                                if (prevStatus[id] && prevStatus[id] !== newStatus) {
                                    const ic = { online: '🟢', stale: '🟡', offline: '🔴' };
                                    showToast(`${ic[newStatus]} ${names[id]} — ${labels[newStatus]}`, newStatus === 'online' ? 'success' : 'warn');
                                }
                                prevStatus[id] = newStatus;
                                updateMarker(id, lat, lng, batt, labels[newStatus], newStatus);
                            }).catch(() => {
                                const def = DEF_LOC[id];
                                if (def) updateMarker(id, def.lat, def.lng, '--', 'Offline', 'offline');
                            });
                    });
                }

                function updateMarker(id, lat, lng, batt, label, sc) {
                    const names = { cam1: 'INMT Device 1', cam2: 'INMT Device 2' };
                    const colors = { online: '#16a34a', stale: '#92400e', offline: '#ef4444' };
                    const link = `index.php?view=dashboard&device=${id.replace('cam', 'device')}`;
                    const popup = `<div style="text-align:center;font-family:Inter,sans-serif;min-width:130px;">
            <strong style="color:#8A2245;">${names[id]}</strong><br>
            <span style="color:${colors[sc]};font-weight:700;">&#9679; ${label}</span><br>
            <small>Batt: ${batt}V</small><br><br>
            <a href="${link}" style="background:#8A2245;color:#fff;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:11px;">VIEW</a>
        </div>`;
                    if (!markers[id]) {
                        markers[id] = L.marker([lat, lng]).addTo(map);
                        markers[id].bindTooltip(names[id], { direction: 'top', offset: [0, -10] });
                        markers[id].bindPopup(popup);
                        markers[id].on('click', () => { window.location.href = link; });
                    } else {
                        markers[id].setLatLng([lat, lng]);
                        markers[id].setPopupContent(popup);
                    }
                }

                function openFsMap() {
                    document.getElementById('mapFullscreen').classList.add('open');
                    if (!fsMap) {
                        fsMap = L.map('mapFs').setView([DEF_LOC.cam1.lat, DEF_LOC.cam1.lng], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(fsMap);
                        Object.entries(markers).forEach(([id, m]) => {
                            L.marker(m.getLatLng()).addTo(fsMap).bindPopup(m.getPopup()?.getContent() || id);
                        });
                    }
                    setTimeout(() => fsMap?.invalidateSize(), 200);
                }
                function closeFsMap() { document.getElementById('mapFullscreen').classList.remove('open'); }

                setInterval(updateFleet, POLL);
                updateFleet();
            </script>

        <?php elseif ($view === 'dashboard' && $device):
            // ── Resolve device files EARLY so chart + gallery both use them ──
            $sortMode    = $_GET['sort'] ?? 'date';
            $camId       = str_replace('device', 'cam', $device);
            $allFiles    = glob($upload_dir . '*.{jpg,png,jpeg}', GLOB_BRACE) ?: [];
            $deviceFiles = array_values(array_filter($allFiles, fn($f) => strpos(basename($f), $camId) !== false));
        ?>
            <!-- ══════════ DEVICE VIEW ══════════ -->
            <div class="container">
                <a href="index.php?view=dashboard" class="back-btn">&larr; Back to Fleet</a>
                <div class="header">
                    <div class="header-content">
                        <div class="header-left">
                            <div class="header-logos">
                                <img src="neuronics_logo.png" onerror="this.style.display='none'" alt="NeuRonICS">
                                <div class="logo-divider"></div>
                                <img src="iisc_logo.jpg" onerror="this.style.display='none'" alt="IISc">
                            </div>
                            <div>
                                <h1><?= strtoupper($device) ?></h1>
                                <div class="header-subtitle">DEVICE DASHBOARD</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <p class="device-meta" id="deviceLastSeen">Last seen: --</p>
                            <p class="device-meta" id="deviceBattery">Battery: --</p>
                        </div>
                        <div class="user-menu">
                            <div class="user-chip">
                                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <span class="user-role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                            </div>
                            <a href="logout.php" class="logout-btn">🚪 Logout</a>
                        </div>
                    </div>
                </div>

                <div class="panel-row">
                    <div class="map-section">
                        <div class="map-header">
                            <h3>&#8998; LIVE LOCATION</h3>
                            <button class="map-expand-btn" onclick="openFsMap()">&#10063; Expand</button>
                        </div>
                        <div id="map"></div>
                    </div>
                    <div class="status-panel">
                        <div class="status-row">
                            <div class="status-label">Modem</div>
                            <div class="status-track">
                                <div id="waveshareStatusFill" class="status-fill"></div>
                            </div>
                            <div id="waveshareStatusText" class="status-text">Offline</div>
                        </div>
                        <p class="device-meta">GPS: <span id="deviceGPS">--</span></p>
                    </div>
                </div>
                <div class="last-updated-row">
                    <span id="deviceLastUpdated" class="last-updated">Last updated: --</span>
                </div>

                <?php
        // Per-day capture chart (last 14 days)
        $dayCounts = [];
        foreach ($deviceFiles as $f) {
            $day = date('M j', filemtime($f));
            $dayCounts[$day] = ($dayCounts[$day] ?? 0) + 1;
        }
        ksort($dayCounts);
        $chartDays = array_slice($dayCounts, -14, 14, true);
        $maxCount  = max(array_values($chartDays) ?: [1]);
        ?>
        <?php if (!empty($chartDays)): ?>
        <div class="day-chart">
            <div class="day-chart-header">
                <h4>📷 Captures per Day</h4>
                <span class="chart-total-badge"><?= array_sum($chartDays) ?> total</span>
            </div>
            <div class="chart-bars">
                <?php
                $BAR_MAX_PX = 56; // max bar height in pixels
                foreach ($chartDays as $day => $count):
                    $barPx = max(4, (int)round(($count / $maxCount) * $BAR_MAX_PX));
                    $delay = (int)(($barPx / $BAR_MAX_PX) * 200);
                ?>
                <div class="chart-col">
                    <div class="chart-tooltip"><?= (int)$count ?> on <?= htmlspecialchars($day) ?></div>
                    <span class="chart-count"><?= (int)$count ?></span>
                    <div class="chart-bar" style="height:<?= $barPx ?>px;transition-delay:<?= $delay ?>ms"></div>
                    <span class="chart-label"><?= date('M j', strtotime($day)) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="gallery-controls">
                    <strong id="galleryCount"></strong>
                    <form method="get">
                        <input type="hidden" name="view" value="dashboard">
                        <input type="hidden" name="device" value="<?= htmlspecialchars($device) ?>">
                        <select name="sort" onchange="this.form.submit()">
                            <option value="date" <?= (!isset($_GET['sort']) || $_GET['sort'] === 'date') ? 'selected' : '' ?>>Group by
                                Date</option>
                            <option value="latest" <?= (isset($_GET['sort']) && $_GET['sort'] === 'latest') ? 'selected' : '' ?>>Latest
                                First</option>
                        </select>
                    </form>
                </div>

                <div class="image-gallery" id="imageGallery">
                    <div class="gallery-loading">
                        <div class="spinner"></div><span>Loading images&hellip;</span>
                    </div>
                </div>
            </div>

            <!-- Lightbox -->
            <div id="lightbox" class="lightbox" aria-hidden="true">
                <div class="lightbox-panel" role="dialog" aria-modal="true" aria-label="Image preview">
                    <button id="lightboxClose" class="lightbox-close" type="button" aria-label="Close">&times;</button>
                    <img id="lightboxImg" src="" alt="">
                    <div class="lightbox-nav-bar">
                        <button id="lightboxPrev" class="lightbox-nav" type="button">&larr; Prev</button>
                        <p id="lightboxCap"></p>
                        <button id="lightboxNext" class="lightbox-nav" type="button">Next &rarr;</button>
                <a id="lightboxDownload" class="lightbox-nav" style="text-decoration:none;" download>&#8659; Save</a>
                    </div>
                </div>
            </div>

            <!-- Full-screen map -->
            <div id="mapFullscreen">
                <button id="mapFsClose" onclick="closeFsMap()">&#x2715; Close</button>
                <div id="mapFs"></div>
            </div>

            <script>
                <?php
                // $sortMode, $camId and $deviceFiles are already computed above
                if ($sortMode === 'latest') {
                    usort($deviceFiles, fn($a, $b) => filemtime($b) - filemtime($a));
                    $galleryData = [['date' => null, 'files' => array_map(fn($f) => ['src' => $f, 'name' => basename($f)], $deviceFiles)]];
                } else {
                    $grouped = [];
                    foreach ($deviceFiles as $f) {
                        $grouped[date('F j, Y', filemtime($f))][] = $f;
                    }
                    krsort($grouped);
                    $galleryData = [];
                    foreach ($grouped as $day => $df) {
                        usort($df, fn($a, $b) => filemtime($b) - filemtime($a));
                        $galleryData[] = ['date' => $day, 'files' => array_map(fn($f) => ['src' => $f, 'name' => basename($f)], $df)];
                    }
                }
                echo 'const GALLERY_DATA = ' . json_encode($galleryData) . ';';
                echo 'const TOTAL_IMAGES = ' . count($deviceFiles) . ';';
                ?>

                const gallery = document.getElementById('imageGallery');
                const galleryCount = document.getElementById('galleryCount');
                if (galleryCount) galleryCount.textContent = `${TOTAL_IMAGES} image${TOTAL_IMAGES !== 1 ? 's' : ''}`;
                let allImages = [];
                GALLERY_DATA.forEach(g => g.files.forEach(f => allImages.push(f)));

                function buildGallery() {
                    try {
                        gallery.innerHTML = '';
                        if (!TOTAL_IMAGES) {
                            gallery.innerHTML = '<div class="gallery-empty">No images found for this device.</div>'; return;
                        }
                        GALLERY_DATA.forEach(group => {
                            if (group.date) {
                                const h = document.createElement('div');
                                h.className = 'date-heading'; h.textContent = group.date; gallery.appendChild(h);
                            }
                            group.files.forEach(item => {
                                const card = document.createElement('div'); card.className = 'image-container';
                                const wrap = document.createElement('div'); wrap.className = 'img-wrapper';
                                const img = document.createElement('img'); img.dataset.src = item.src; img.alt = item.name;
                                img.addEventListener('load', () => { img.classList.add('loaded'); wrap.classList.add('img-ready'); });
                                img.addEventListener('error', () => { wrap.classList.add('img-ready'); img.style.display = 'none'; });
                                img.addEventListener('click', () => openLightbox(item.src));
                                wrap.appendChild(img); card.appendChild(wrap);

                                // View Details link → image_detail.php
                                const detailBtn = document.createElement('a');
                                detailBtn.className = 'btn-ai';
                                detailBtn.href = `image_detail.php?image=${encodeURIComponent(item.name)}&device=<?= addslashes($device) ?>`;
                                detailBtn.innerHTML = '🔬 View Details';
                                // Loading state on hover (shows it's interactive)
                                detailBtn.addEventListener('click', () => {
                                    detailBtn.classList.add('loading');
                                    const sp = document.createElement('span');
                                    sp.className = 'btn-ai-spinner';
                                    detailBtn.innerHTML = '';
                                    detailBtn.appendChild(sp);
                                    detailBtn.append(' Loading…');
                                });
                                card.appendChild(detailBtn);

                                // Inline 2-click confirm delete
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                const del = document.createElement('button');
                                del.className = 'delete-btn'; del.textContent = '🗑 Delete';
                                let pending = false;
                                del.addEventListener('click', e => {
                                    e.stopPropagation();
                                    if (!pending) {
                                        pending = true; del.textContent = '⚠ Confirm?'; del.classList.add('confirm-mode');
                                        setTimeout(() => { pending = false; del.textContent = '🗑 Delete'; del.classList.remove('confirm-mode'); }, 2500);
                                    } else { deleteImage(item.name, card); }
                                });
                                card.appendChild(del);
                                <?php endif; ?>

                    // Download button
                    const dl = document.createElement('a');
                    dl.className  = 'download-btn';
                    dl.href       = item.src;
                    dl.download   = item.name;
                    dl.textContent = '⬇ Save';
                    card.appendChild(dl);

                    const fn = document.createElement('p'); fn.className = 'img-filename'; fn.textContent = item.name;
                                card.appendChild(fn); gallery.appendChild(card);
                            });
                        });
                        initLazyLoad();
                    } catch (e) {
                        console.error(e);
                        gallery.innerHTML = '<div class="gallery-empty">Error loading gallery — please refresh.</div>';
                    }
                }

                function initLazyLoad() {
                    const imgs = gallery.querySelectorAll('img[data-src]'); if (!imgs.length) return;
                    if ('IntersectionObserver' in window) {
                        const obs = new IntersectionObserver((entries, o) => {
                            entries.forEach(en => { if (en.isIntersecting) { const i = en.target; i.src = i.dataset.src; delete i.dataset.src; o.unobserve(i); } });
                        }, { rootMargin: '200px' });
                        imgs.forEach(i => obs.observe(i));
                    } else { imgs.forEach(i => { i.src = i.dataset.src; }); }
                }
                buildGallery();

                // Toggle "See description" in AI badges
                document.getElementById('imageGallery').addEventListener('click', function(e) {
                    const btn = e.target.closest('.ai-desc-toggle');
                    if (!btn) return;
                    const body = btn.nextElementSibling;
                    if (!body) return;
                    const open = body.style.display !== 'none';
                    body.style.display = open ? 'none' : 'block';
                    btn.textContent = open ? '▶ See description' : '▲ Hide';
                });

                const aiPending = new Set();

                function escapeHtml(str) {
                    return String(str).replace(/[&<>"']/g, m => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    }[m]));
                }

                function identifyInsect(filename, badgeEl, btnEl) {
                    if (!filename || !badgeEl) return;
                    if (aiPending.has(filename)) return;
                    aiPending.add(filename);

                    try {
                        badgeEl.style.display = 'block';
                        badgeEl.innerHTML = '<em>Analyzing...</em>';
                        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '⏳ Analyzing...'; }

                        const formData = new URLSearchParams({ image: filename });
                        fetch('classify.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(async r => {
                                const txt = await r.text();
                                try {
                                    const data = JSON.parse(txt);
                                    if (!r.ok && data && !data.error) data.error = `HTTP ${r.status}`;
                                    return data;
                                } catch (e) {
                                    return { error: 'Non-JSON response', details: txt, status: r.status };
                                }
                            })
                            .then(data => {
                                if (data?.error) {
                                    const details = data.details ? (typeof data.details === 'object' ? JSON.stringify(data.details, null, 2) : String(data.details)) : '';
                                    badgeEl.innerHTML = `
                                        <div style="color:#ef4444;font-weight:900;">AI Error</div>
                                        <div style="margin-top:2px;opacity:0.95;font-weight:700;">${escapeHtml(data.error)}</div>
                                        ${details ? `<pre style="margin-top:6px;white-space:pre-wrap;word-break:break-word;background:rgba(0,0,0,0.15);padding:8px;border-radius:8px;color:#b91c1c;max-height:180px;overflow:auto;">${escapeHtml(details)}</pre>` : ''}
                                    `;
                                    if (btnEl) { btnEl.disabled = false; btnEl.textContent = '🔍 Identify Species'; }
                                    return;
                                }

                                const species = data.species ?? 'Unknown';
                                const common = data.common_name ?? '';
                                const confRaw = data.confidence ?? '';
                                const confStr = confRaw === null || confRaw === undefined ? '' : String(confRaw);
                                const confDisplay = !confStr ? '' : (confStr.includes('%') ? confStr : `${confStr}%`);
                                const desc = data.description ?? '';
                                const confPct = confRaw ? Math.round(parseFloat(confRaw) * 100) : null;

                                badgeEl.innerHTML = `
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <strong style="font-size:1.05em;">${escapeHtml(species)}</strong>
                                        ${common ? `<span style="opacity:0.8;font-size:0.85em;">${escapeHtml(common)}</span>` : ''}
                                        ${confPct ? `<span style="background:rgba(255,255,255,0.2);border-radius:20px;padding:1px 8px;font-size:0.8em;font-weight:700;">${confPct}%</span>` : ''}
                                    </div>
                                    ${desc ? `<div style="margin-top:5px;"><button class="ai-desc-toggle" style="background:none;border:none;color:inherit;opacity:0.85;font-size:0.78em;cursor:pointer;padding:0;font-weight:700;letter-spacing:0.5px;">▶ See description</button><div class="ai-desc-body" style="display:none;margin-top:5px;font-size:0.82em;opacity:0.92;line-height:1.5;font-weight:500;">${escapeHtml(desc)}</div></div>` : ''}
                                `;
                            })
                            .catch(err => {
                                console.error(err);
                                badgeEl.style.display = 'block';
                                badgeEl.innerHTML = "<span style='color:#ef4444;font-weight:800;'>AI Error</span>";
                                showToast('AI classification failed', 'error');
                            })
                            .finally(() => {
                                aiPending.delete(filename);
                                if (btnEl) { btnEl.disabled = false; btnEl.textContent = '🔍 Identify Species'; }
                            });
                    } catch (err) {
                        aiPending.delete(filename);
                        throw err;
                    }
                }

                function deleteImage(filename, card) {
                    card.classList.add('deleting');
                    fetch('delete_image.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ file: filename }).toString() })
                        .then(r => r.json()).then(d => {
                            if (d.status === 'ok') {
                                card.style.transition = 'opacity 0.3s,transform 0.3s'; card.style.opacity = '0'; card.style.transform = 'scale(0.95)';
                                setTimeout(() => {
                                    card.remove(); allImages = allImages.filter(i => i.name !== filename);
                                    const rem = gallery.querySelectorAll('.image-container').length;
                                    if (galleryCount) galleryCount.textContent = `${rem} image${rem !== 1 ? 's' : ''}`;
                                    document.querySelectorAll('.date-heading').forEach(h => { let n = h.nextElementSibling; if (!n || n.classList.contains('date-heading')) h.remove(); });
                                    if (!rem) gallery.innerHTML = '<div class="gallery-empty">No images found for this device.</div>';
                                    showToast('🗑 Image deleted', 'info');
                                }, 300);
                            } else { card.classList.remove('deleting'); showToast('⚠ Delete failed: ' + (d.message || 'Unknown error'), 'error'); }
                        }).catch(err => { card.classList.remove('deleting'); showToast('⚠ Network error — could not delete.', 'error'); console.error(err); });
                }

                // ── Lightbox ─────────────────────────────────────────────────
                const lightbox = document.getElementById('lightbox');
                const lbImg = document.getElementById('lightboxImg');
                const lbCap = document.getElementById('lightboxCap');
                let curIdx = -1;

                function openLightbox(src) { curIdx = allImages.findIndex(i => i.src === src); showAt(curIdx); lightbox.classList.add('open'); lightbox.setAttribute('aria-hidden', 'false'); }
                function showAt(i) {
        if (!allImages.length) return;
        curIdx = (i + allImages.length) % allImages.length;
        lbImg.src = allImages[curIdx].src;
        lbCap.textContent = `${allImages[curIdx].name}  (${curIdx+1} / ${allImages.length})`;
        const dlBtn = document.getElementById('lightboxDownload');
        if (dlBtn) { dlBtn.href = allImages[curIdx].src; dlBtn.download = allImages[curIdx].name; }
    }
                function closeLightbox() { lightbox.classList.remove('open'); lightbox.setAttribute('aria-hidden', 'true'); lbImg.src = ''; }

                lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
                document.getElementById('lightboxClose').addEventListener('click', e => { e.stopPropagation(); closeLightbox(); });
                document.getElementById('lightboxPrev').addEventListener('click', e => { e.stopPropagation(); showAt(curIdx - 1); });
                document.getElementById('lightboxNext').addEventListener('click', e => { e.stopPropagation(); showAt(curIdx + 1); });
                document.addEventListener('keydown', e => {
                    if (!lightbox.classList.contains('open')) return;
                    if (e.key === 'ArrowRight') showAt(curIdx + 1);
                    if (e.key === 'ArrowLeft') showAt(curIdx - 1);
                    if (e.key === 'Escape') closeLightbox();
                });

                // ── Device map + status ───────────────────────────────────────
                const deviceId = "<?= addslashes(str_replace('device', 'cam', $device)) ?>";
                const deviceDef = { cam1: { lat: 13.0187, lng: 77.5708 }, cam2: { lat: 13.0127, lng: 77.5677 } };
                const baseLoc = deviceDef[deviceId] || { lat: 13.0127, lng: 77.5677 };
                let dMap = L.map('map').setView([baseLoc.lat, baseLoc.lng], 15);
                let dMarker = L.marker([baseLoc.lat, baseLoc.lng]).addTo(dMap);
                let dFsMap = null;
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(dMap);

                function openFsMap() {
                    document.getElementById('mapFullscreen').classList.add('open');
                    if (!dFsMap) {
                        dFsMap = L.map('mapFs').setView([baseLoc.lat, baseLoc.lng], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(dFsMap);
                        L.marker(dMarker.getLatLng()).addTo(dFsMap);
                    }
                    setTimeout(() => dFsMap?.invalidateSize(), 200);
                }
                function closeFsMap() { document.getElementById('mapFullscreen').classList.remove('open'); }

                function fetchStatus() {
                    const controller = new AbortController();
                    const timeout = setTimeout(() => controller.abort(), 4000);
                    fetch(`get_status.php?device_id=${deviceId}`, { signal: controller.signal })
                        .then(r => { clearTimeout(timeout); return r.json(); })
                        .then(data => {
                            if (data.status === 'success' && data.latest) {
                                const d = data.latest, ts = d.timestamp ? parseInt(d.timestamp) : null;
                                document.getElementById('waveshareStatusFill').style.width = '100%';
                                document.getElementById('waveshareStatusText').textContent = 'Link Active';
                                const v = parseFloat(d.battery_voltage);
                                const batEl = document.getElementById('deviceBattery');
                                if (!isNaN(v) && batEl) batEl.textContent = `Battery: ${v.toFixed(2)}V`;
                                const lsEl = document.getElementById('deviceLastSeen');
                                if (ts && lsEl) lsEl.textContent = `Last seen: ${timeSince(ts)}`;
                                const lat = parseFloat(d.gps_latitude), lng = parseFloat(d.gps_longitude);
                                if (!isNaN(lat) && !isNaN(lng)) {
                                    dMarker.setLatLng([lat, lng]); dMap.setView([lat, lng]);
                                    const gEl = document.getElementById('deviceGPS');
                                    if (gEl) gEl.textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                                    if (dFsMap) dFsMap.setView([lat, lng]);
                                }
                            } else {
                                document.getElementById('waveshareStatusFill').style.width = '0%';
                                document.getElementById('waveshareStatusText').textContent = 'No Signal';
                            }
                            const upEl = document.getElementById('deviceLastUpdated');
                            if (upEl) upEl.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
                        }).catch(err => {
                            clearTimeout(timeout);
                            // Silently handle missing get_status.php — devices may be offline
                            const upEl = document.getElementById('deviceLastUpdated');
                            if (upEl) upEl.textContent = `Last updated: ${new Date().toLocaleTimeString()} — Device offline`;
                        });
                }
                // Poll less aggressively — every 30s — since devices are often offline
                setInterval(fetchStatus, 30000);
                fetchStatus();
            </script>

        <?php endif; ?>
    </body>

    </html>