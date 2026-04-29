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
    <link rel="icon" type="image/png" href="neuronics_logo.png">
    <link rel="shortcut icon" type="image/png" href="neuronics_logo.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="dashboard.css?v=<?= filemtime('dashboard.css') ?>">
</head>

<body>

    <!-- Theme toggle -->
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>
    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>
    <div class="sr-only" id="srStatus" aria-live="polite"></div>

    <!-- Shared JS: theme + toasts + timeSince -->
    <script>
        var CSRF_TOKEN = <?php echo json_encode(generateCSRFToken()); ?>;
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
                <img src="neuronics_logo.png" style="height:76px;" onerror="this.style.display='none'" alt="NeuRonICS">
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
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <div class="user-chip admin-avatar-btn" onclick="openAdminPanel()" title="Admin Settings">
                                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <span class="user-role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="user-chip">
                                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <span class="user-role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                            </div>
                        <?php endif; ?>
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
                    $lastCaptureStr = $lastTs ? date('M j, Y', $lastTs) : null;
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
                        <?php if ($lastCaptureStr): ?>
                            <p class="device-meta">Last capture: <strong><?= $lastCaptureStr ?></strong></p>
                        <?php endif; ?>
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
        $sortMode = $_GET['sort'] ?? 'date';
        $camId = str_replace('device', 'cam', $device);
        $allFiles = glob($upload_dir . '*.{jpg,png,jpeg}', GLOB_BRACE) ?: [];
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
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <div class="user-chip admin-avatar-btn" onclick="openAdminPanel()" title="Admin Settings">
                                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <span class="user-role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="user-chip">
                                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <span class="user-role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                            </div>
                        <?php endif; ?>
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
            // Per-day species chart (last 14 days)
            $dailySpecies = [];
            
            // First loop: gather all valid dates (last 14 days of activity)
            foreach ($deviceFiles as $f) {
                $day = date('Y-m-d', filemtime($f));
                if (!isset($dailySpecies[$day])) {
                    $dailySpecies[$day] = ['Total Images' => 0];
                }
                $dailySpecies[$day]['Total Images']++;
            }
            krsort($dailySpecies);
            $dailySpecies = array_slice($dailySpecies, 0, 14, true);
            ksort($dailySpecies); // sort chronologically

            // Second loop: aggregate species counts
            foreach ($deviceFiles as $f) {
                $day = date('Y-m-d', filemtime($f));
                if (!isset($dailySpecies[$day])) continue; // only last 14 days
                
                $base = basename($f);
                $jsonPath = $upload_dir . pathinfo($base, PATHINFO_FILENAME) . '.json';
                if (!is_file($jsonPath)) continue;
                $j = json_decode(file_get_contents($jsonPath), true);
                if (!$j || empty($j['species'])) continue;
                
                if (!empty($j['breakdown']) && is_array($j['breakdown'])) {
                    foreach ($j['breakdown'] as $item) {
                        $sp = $item['species'] ?? 'Unknown';
                        $count = isset($item['count']) ? (int)$item['count'] : 1;
                        if (!isset($dailySpecies[$day][$sp])) $dailySpecies[$day][$sp] = 0;
                        $dailySpecies[$day][$sp] += $count;
                    }
                } else {
                    $sp = $j['species'] ?? 'Unknown';
                    $count = isset($j['total_count']) ? (int)$j['total_count'] : 1;
                    if (!isset($dailySpecies[$day][$sp])) $dailySpecies[$day][$sp] = 0;
                    $dailySpecies[$day][$sp] += $count;
                }
            }
            echo '<script>const CHART_DATA = ' . json_encode($dailySpecies) . ';</script>';
            ?>
            
            <?php if (!empty($dailySpecies)): ?>
                <div class="day-chart" style="padding: 20px 24px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 22px; box-shadow: 0 4px 20px var(--shadow);">
                    <div class="day-chart-header" style="margin-bottom: 20px;">
                        <h4 style="font-family: 'Space Mono', monospace; font-size: 0.72em; letter-spacing: 2px; text-transform: uppercase; color: var(--text-dim);">📈 Pest Population Trends (Last 14 Days)</h4>
                    </div>
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // ── Species Summary ──────────────────────────────────────────
            $speciesSummary = [];
            $csvRows = [['Image', 'Species', 'Common Name', 'Confidence (%)', 'Date', 'Insect Count']];
            foreach ($deviceFiles as $f) {
                $base = basename($f);
                $jsonPath = $upload_dir . pathinfo($base, PATHINFO_FILENAME) . '.json';
                if (!is_file($jsonPath))
                    continue;
                $j = json_decode(file_get_contents($jsonPath), true);
                if (!$j || empty($j['species']))
                    continue;
                
                $date = date('M j, Y', filemtime($f));
                $overallConf = isset($j['confidence']) ? round(floatval($j['confidence']) * 100) : null;
                $seenSpeciesInImage = [];

                if (!empty($j['breakdown']) && is_array($j['breakdown'])) {
                    foreach ($j['breakdown'] as $item) {
                        $sp = $item['species'] ?? 'Unknown';
                        $cn = $item['common_name'] ?? '';
                        $count = isset($item['count']) ? (int)$item['count'] : 1;

                        if (!isset($speciesSummary[$sp])) {
                            $speciesSummary[$sp] = ['imgCount' => 0, 'insectCount' => 0, 'common' => $cn, 'maxConf' => 0, 'latest' => ''];
                        }
                        
                        $speciesSummary[$sp]['insectCount'] += $count;
                        if (!isset($seenSpeciesInImage[$sp])) {
                            $speciesSummary[$sp]['imgCount']++;
                            $seenSpeciesInImage[$sp] = true;
                        }
                        
                        if ($overallConf !== null && $overallConf > $speciesSummary[$sp]['maxConf'])
                            $speciesSummary[$sp]['maxConf'] = $overallConf;
                        $speciesSummary[$sp]['latest'] = $date;
                        $csvRows[] = [$base, $sp, $cn, $overallConf ?? '', $date, $count];
                    }
                } else {
                    $sp = $j['species'] ?? 'Unknown';
                    $cn = $j['common_name'] ?? '';
                    $count = isset($j['total_count']) ? (int)$j['total_count'] : 1;

                    if (!isset($speciesSummary[$sp])) {
                        $speciesSummary[$sp] = ['imgCount' => 0, 'insectCount' => 0, 'common' => $cn, 'maxConf' => 0, 'latest' => ''];
                    }
                    $speciesSummary[$sp]['insectCount'] += $count;
                    $speciesSummary[$sp]['imgCount']++;
                    
                    if ($overallConf !== null && $overallConf > $speciesSummary[$sp]['maxConf'])
                        $speciesSummary[$sp]['maxConf'] = $overallConf;
                    $speciesSummary[$sp]['latest'] = $date;
                    $csvRows[] = [$base, $sp, $cn, $overallConf ?? '', $date, $count];
                }
            }
            arsort($speciesSummary);
            ?>
            <?php if (!empty($speciesSummary)): ?>
                <div class="species-summary-card">
                    <div class="species-summary-header">
                        <h4>🔬 Species Identified</h4>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span class="chart-total-badge"><?= count($speciesSummary) ?> species · <?= count($csvRows) - 1 ?>
                                images</span>
                            <button class="csv-export-btn" onclick="exportCSV()" title="Download as CSV">⬇ Export CSV</button>
                        </div>
                    </div>
                    <div class="species-table-wrap">
                        <table class="species-table">
                            <thead>
                                <tr>
                                    <th>Species</th>
                                    <th>Common Name</th>
                                    <th>Total Insects</th>
                                    <th>Images Found In</th>
                                    <th>Best Confidence</th>
                                    <th>Latest</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($speciesSummary as $sp => $info): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($sp) ?></strong></td>
                                        <td class="dim"><?= htmlspecialchars($info['common']) ?: '<em>—</em>' ?></td>
                                        <td><span class="sp-count" style="background:var(--primary);color:#fff;padding:2px 10px;border-radius:99px;"><?= $info['insectCount'] ?></span></td>
                                        <td><?= $info['imgCount'] ?></td>
                                        <td>
                                            <?php if ($info['maxConf']): ?>
                                                <div class="conf-bar-wrap">
                                                    <div class="conf-bar" style="width:<?= $info['maxConf'] ?>%"></div>
                                                    <span class="conf-label"><?= $info['maxConf'] ?>%</span>
                                                </div>
                                            <?php else: ?>—<?php endif; ?>
                                        </td>
                                        <td class="dim"><?= htmlspecialchars($info['latest']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <script>
                        const CSV_DATA = <?= json_encode($csvRows) ?>;
                        function exportCSV() {
                            const lines = CSV_DATA.map(r => r.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(','));
                            const blob = new Blob([lines.join('\n')], { type: 'text/csv' });
                            const a = document.createElement('a');
                            a.href = URL.createObjectURL(blob);
                            a.download = 'insect_net_<?= addslashes($device) ?>_species_<?= date('Y-m-d') ?>.csv';
                            a.click();
                        }
                    </script>
                </div>
            <?php endif; ?>

            <div class="gallery-controls">
                <div style="display:flex; align-items:center; gap: 14px; flex-wrap:wrap;">
                    <strong id="galleryCount"></strong>
                    <button class="csv-export-btn" id="batchAnalyseBtn" onclick="startBatchAnalyse()" style="background: linear-gradient(135deg, #059669, #10b981);">🚀 Batch Analyse Unprocessed</button>
                </div>
                <div class="gallery-filter-row">
                    <input type="text" id="dateFilter" placeholder="Filter by date (e.g. Mar 26)" class="date-filter-input"
                        oninput="filterGallery()" autocomplete="off">
                    <button class="filter-clear-btn"
                        onclick="document.getElementById('dateFilter').value='';filterGallery()"
                        title="Clear filter">✕</button>
                    <form method="get" style="display:contents;">
                        <input type="hidden" name="view" value="dashboard">
                        <input type="hidden" name="device" value="<?= htmlspecialchars($device) ?>">
                        <select name="sort" id="sortSelect" onchange="this.form.submit()" class="sort-select">
                            <option value="date" <?= (!isset($_GET['sort']) || $_GET['sort'] === 'date') ? 'selected' : '' ?>>
                                Group by Date</option>
                            <option value="latest" <?= (isset($_GET['sort']) && $_GET['sort'] === 'latest') ? 'selected' : '' ?>>Latest First</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="image-gallery" id="imageGallery">
                <div class="gallery-loading">
                    <div class="spinner"></div><span>Loading images&hellip;</span>
                </div>
            </div>
            
            <div id="galleryPagination" style="display:none; justify-content:center; align-items:center; gap:16px; margin-top:24px; padding-bottom:20px;">
                <button class="csv-export-btn" id="pagePrevBtn" onclick="changePage(-1)" style="background:var(--surface2); color:var(--text); border:1px solid var(--border);">← Prev</button>
                <span id="pageIndicator" style="font-size:0.9em; font-weight:600; color:var(--text-dim);">Page 1</span>
                <button class="csv-export-btn" id="pageNextBtn" onclick="changePage(1)" style="background:var(--surface2); color:var(--text); border:1px solid var(--border);">Next →</button>
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
            // Create a flat array of images with their timestamps for JS processing
            $flatImages = [];
            foreach ($deviceFiles as $f) {
                $flatImages[] = [
                    'src' => $f,
                    'name' => basename($f),
                    'timestamp' => filemtime($f),
                    'dateStr' => date('F j, Y', filemtime($f)),
                    'identified' => is_file($upload_dir . pathinfo(basename($f), PATHINFO_FILENAME) . '.json')
                ];
            }
            // Sort by timestamp descending
            usort($flatImages, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
            
            echo 'const ALL_IMAGES = ' . json_encode($flatImages) . ';';
            echo 'const SORT_MODE = "' . $sortMode . '";';
            ?>

            let currentPage = 1;
            const PER_PAGE = 50;
            let filteredImages = ALL_IMAGES; // initially all

            const gallery = document.getElementById('imageGallery');
            const galleryCount = document.getElementById('galleryCount');
            const paginator = document.getElementById('galleryPagination');
            
            function changePage(delta) {
                currentPage += delta;
                buildGallery();
            }

            function buildGallery() {
                try {
                    gallery.innerHTML = '';
                    if (!filteredImages.length) {
                        if (galleryCount) galleryCount.textContent = '0 images';
                        paginator.style.display = 'none';
                        gallery.innerHTML = '<div class="gallery-empty">No images found.</div>';
                        return;
                    }
                    
                    if (galleryCount) galleryCount.textContent = `${filteredImages.length} image${filteredImages.length !== 1 ? 's' : ''}`;
                    
                    const totalPages = Math.ceil(filteredImages.length / PER_PAGE);
                    if (currentPage < 1) currentPage = 1;
                    if (currentPage > totalPages) currentPage = totalPages;
                    
                    document.getElementById('pageIndicator').textContent = `Page ${currentPage} of ${totalPages}`;
                    document.getElementById('pagePrevBtn').disabled = currentPage === 1;
                    document.getElementById('pageNextBtn').disabled = currentPage === totalPages;
                    paginator.style.display = totalPages > 1 ? 'flex' : 'none';
                    
                    const startIdx = (currentPage - 1) * PER_PAGE;
                    const pageItems = filteredImages.slice(startIdx, startIdx + PER_PAGE);
                    
                    let grouped = {};
                    if (SORT_MODE === 'latest') {
                        grouped['All'] = pageItems;
                    } else {
                        pageItems.forEach(item => {
                            if(!grouped[item.dateStr]) grouped[item.dateStr] = [];
                            grouped[item.dateStr].push(item);
                        });
                    }
                    
                    Object.keys(grouped).forEach(dateKey => {
                        if (dateKey !== 'All') {
                            const h = document.createElement('div');
                            h.className = 'date-heading'; h.textContent = dateKey; gallery.appendChild(h);
                        }
                        grouped[dateKey].forEach(item => {
                            const card = document.createElement('div'); card.className = 'image-container';
                            const wrap = document.createElement('div'); wrap.className = 'img-wrapper';
                            const img = document.createElement('img'); img.dataset.src = 'thumbnail.php?file=' + encodeURIComponent(item.name) + '&w=300'; img.alt = item.name;
                            img.addEventListener('load', () => { img.classList.add('loaded'); wrap.classList.add('img-ready'); });
                            img.addEventListener('error', () => { wrap.classList.add('img-ready'); img.style.display = 'none'; });
                            img.addEventListener('click', () => openLightbox(item.src));
                            wrap.appendChild(img); card.appendChild(wrap);

                            // ✓ Analysed badge if JSON cache exists
                            if (item.identified) {
                                const badge = document.createElement('div');
                                badge.className = 'identified-badge';
                                badge.innerHTML = '✓ Analysed';
                                wrap.appendChild(badge);
                            }

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
                            dl.className = 'download-btn';
                            dl.href = item.src;
                            dl.download = item.name;
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
            document.getElementById('imageGallery').addEventListener('click', function (e) {
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

                    const formData = new URLSearchParams({ image: filename, csrf_token: CSRF_TOKEN });
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

            function openLightbox(src) { curIdx = filteredImages.findIndex(i => i.src === src); showAt(curIdx); lightbox.classList.add('open'); lightbox.setAttribute('aria-hidden', 'false'); }
            function showAt(i) {
                if (!filteredImages.length) return;
                curIdx = (i + filteredImages.length) % filteredImages.length;
                lbImg.src = filteredImages[curIdx].src;
                lbCap.textContent = `${filteredImages[curIdx].name}  (${curIdx + 1} / ${filteredImages.length})`;
                const dlBtn = document.getElementById('lightboxDownload');
                if (dlBtn) { dlBtn.href = filteredImages[curIdx].src; dlBtn.download = filteredImages[curIdx].name; }
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

            // ── Touch swipe for mobile ────────────────────────────────
            let swipeStartX = 0, swipeStartY = 0;
            lightbox.addEventListener('touchstart', e => {
                swipeStartX = e.touches[0].clientX;
                swipeStartY = e.touches[0].clientY;
            }, { passive: true });
            lightbox.addEventListener('touchend', e => {
                const dx = e.changedTouches[0].clientX - swipeStartX;
                const dy = Math.abs(e.changedTouches[0].clientY - swipeStartY);
                if (Math.abs(dx) < 40 || dy > 60) return; // too small or vertical
                if (dx < 0) showAt(curIdx + 1); // swipe left → next
                else showAt(curIdx - 1); // swipe right → prev
            }, { passive: true });

            // ── Date filter ──────────────────────────────────────────
            function filterGallery() {
                const q = (document.getElementById('dateFilter').value || '').trim().toLowerCase();
                if (!q) {
                    filteredImages = ALL_IMAGES;
                } else {
                    filteredImages = ALL_IMAGES.filter(item => {
                        return item.name.toLowerCase().includes(q) || item.dateStr.toLowerCase().includes(q);
                    });
                }
                currentPage = 1;
                buildGallery();
            }

            // ── Batch Analyse ─────────────────────────────────────────
            async function startBatchAnalyse() {
                const unanalyzed = filteredImages.filter(img => !img.identified);
                if (!unanalyzed.length) {
                    showToast("No unprocessed images found in the current view.", "info");
                    return;
                }
                
                if (!confirm(`Found ${unanalyzed.length} unprocessed images. Start batch analysis? This may take some time.`)) return;
                
                const btn = document.getElementById('batchAnalyseBtn');
                btn.disabled = true;
                let successCount = 0;
                
                for (let i = 0; i < unanalyzed.length; i++) {
                    const img = unanalyzed[i];
                    btn.textContent = `🚀 Analyzing ${i+1} of ${unanalyzed.length}...`;
                    try {
                        const formData = new URLSearchParams({ image: img.name, csrf_token: CSRF_TOKEN });
                        const res = await fetch('classify.php', { method: 'POST', body: formData });
                        if (res.ok) {
                            img.identified = true; // update local cache
                            successCount++;
                        }
                    } catch (e) {
                        console.error("Batch error on " + img.name, e);
                    }
                    // Wait 2.5s between requests to avoid rate limits
                    if (i < unanalyzed.length - 1) {
                        await new Promise(r => setTimeout(r, 2500));
                    }
                }
                
                btn.textContent = "🚀 Batch Analyse Unprocessed";
                btn.disabled = false;
                showToast(`Batch analysis complete. Successfully processed ${successCount} out of ${unanalyzed.length} images.`, "info");
                
                buildGallery(); // re-render to show badges
            }

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
                    }).catch(() => {
                        clearTimeout(timeout);
                        // Silently handle — device is offline or endpoint unavailable
                        const upEl = document.getElementById('deviceLastUpdated');
                        if (upEl) upEl.textContent = `Last updated: ${new Date().toLocaleTimeString()} · Device offline`;
                    });
            }
            // Poll less aggressively — every 30s — since devices are often offline
            setInterval(fetchStatus, 30000);
            fetchStatus();
        </script>

    <?php endif; ?>

    <?php if ($_SESSION["role"] === "admin"): ?>
        <div class="admin-panel-overlay" id="adminOverlay" onclick="closeAdminPanel()"></div>
        <div class="admin-panel" id="adminPanel">
            <div class="admin-panel-header">
                <div>
                    <p class="admin-panel-title">&#9881; Admin Settings</p><strong style="font-size:1em;">User
                        Management</strong>
                </div>
                <button class="admin-panel-close" onclick="closeAdminPanel()">&#x2715;</button>
            </div>
            <div class="admin-panel-body">
                <p class="admin-section-title">My Profile</p>
                <div class="user-card" id="myProfileCard">
                    <div class="admin-field">
                        <label class="admin-label">Current Password <span
                                style="font-weight:400;opacity:0.6;text-transform:none;letter-spacing:0">(required to
                                change)</span></label>
                        <input class="admin-input" type="password" id="profileOldPw" placeholder="Enter current password"
                            autocomplete="current-password">
                    </div>
                    <div class="admin-field">
                        <label class="admin-label">New Password</label>
                        <input class="admin-input" type="password" id="profileNewPw" placeholder="At least 10 characters"
                            autocomplete="new-password" oninput="profileStrength(this.value)">
                        <div style="height:4px;background:var(--border);border-radius:99px;margin-top:6px;">
                            <div id="profileStrBar"
                                style="height:100%;border-radius:99px;width:0;transition:width 0.3s,background 0.3s;"></div>
                        </div>
                    </div>
                    <div class="admin-field">
                        <label class="admin-label">Confirm New Password</label>
                        <input class="admin-input" type="password" id="profileConfirmPw" placeholder="Re-enter new password"
                            autocomplete="new-password">
                    </div>
                    <div class="user-card-actions">
                        <button class="btn-save-user" onclick="changeMyPassword()">&#128274; Update My Password</button>
                    </div>
                </div>
                <p class="admin-section-title">Accounts</p>
                <div id="adminUserList"></div>
                <button class="btn-add-user" onclick="addUserCard()">+ Add New User</button>
                <div class="admin-msg" id="adminMsg"></div>
            </div>
        </div>
        <script>
            var CURRENT_USER = <?php echo json_encode($_SESSION["username"]); ?>;
            var CSRF_TOKEN = <?php echo json_encode(generateCSRFToken()); ?>;
            var INIT_USERS = <?php echo json_encode(array_map(function ($u) use ($users) {
                return ["username" => $u, "role" => $users[$u]["role"]]; }, array_keys($users))); ?>;
            function openAdminPanel() { document.getElementById("adminOverlay").classList.add("open"); document.getElementById("adminPanel").classList.add("open"); renderUsers(INIT_USERS); }
            function closeAdminPanel() { document.getElementById("adminOverlay").classList.remove("open"); document.getElementById("adminPanel").classList.remove("open"); }
            document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeAdminPanel(); });
            function showAdminMsg(msg, ok) { var el = document.getElementById("adminMsg"); el.textContent = msg; el.className = "admin-msg " + (ok ? "ok" : "err"); el.style.display = "block"; clearTimeout(el._t); el._t = setTimeout(function () { el.style.display = "none"; }, 4500); }
            function renderUsers(users) { var list = document.getElementById("adminUserList"); list.innerHTML = ""; users.forEach(function (u) { list.appendChild(makeUserCard(u.username, u.role, false)); }); }
            function escH(s) { return String(s).replace(/[&]/g, "&amp;").replace(/[<]/g, "&lt;").replace(/[>]/g, "&gt;").replace(/["]/g, "&quot;"); }
            function makeUserCard(username, role, isNew) {
                var card = document.createElement("div"); card.className = "user-card"; var isSelf = username === CURRENT_USER;
                var delBtn = !isSelf ? "<button class='btn-del-user' onclick='deleteUser(this,\"" + escH(username) + "\")'>&#128465;</button>" : "";
                card.innerHTML = "<div class='user-card-header'><span class='user-card-name'>" + (isNew ? "New User" : "&#128100; " + escH(username)) + "</span><span class='user-card-role'>" + escH(role) + "</span></div>" +
                    "<div class='admin-field'><label class='admin-label'>Username</label>" +
                    "<input class='admin-input' type='text' value='" + escH(username) + "' placeholder='username' autocomplete='off' data-original='" + escH(username) + "'></div>" +
                    "<div class='admin-field'><label class='admin-label'>New Password <span style='font-weight:400;opacity:0.6;text-transform:none;letter-spacing:0'>" + (isNew ? "(required)" : "leave blank to keep") + "</span></label>" +
                    "<input class='admin-input' type='password' placeholder='" + (isNew ? "Set a password" : "Type to change") + "' autocomplete='new-password'></div>" +
                    "<div class='admin-field'><label class='admin-label'>Role</label>" +
                    "<select class='admin-select'><option value='user' " + (role === "user" ? "selected" : "") + " >user</option><option value='admin' " + (role === "admin" ? "selected" : "") + " >admin</option></select></div>" +
                    "<div class='user-card-actions'><button class='btn-save-user' onclick='saveUser(this)'>&#128190; Save Changes</button>" + delBtn + "</div>";
                return card;
            }
            function profileStrength(pw) { var s = 0; if (pw.length >= 10) s++; if (pw.length >= 16) s++; if (/[A-Z]/.test(pw)) s++; if (/[0-9]/.test(pw)) s++; if (/[^A-Za-z0-9]/.test(pw)) s++; var b = document.getElementById("profileStrBar"); var c = ["transparent", "#ef4444", "#f59e0b", "#3b82f6", "#22c55e", "#16a34a"][Math.min(s, 5)]; var w = ["0%", "25%", "50%", "75%", "90%", "100%"][Math.min(s, 5)]; b.style.width = w; b.style.background = c; }
            async function changeMyPassword() {
                var old = document.getElementById("profileOldPw").value;
                var np = document.getElementById("profileNewPw").value;
                var cp = document.getElementById("profileConfirmPw").value;
                if (!old || !np) { showAdminMsg("Fill in current and new password", false); return; }
                if (np.length < 10) { showAdminMsg("New password must be at least 10 characters", false); return; }
                if (np !== cp) { showAdminMsg("Passwords do not match", false); return; }
                var btn = document.querySelector("#myProfileCard .btn-save-user");
                btn.disabled = true; btn.textContent = "Saving...";
                try {
                    var res = await fetch("admin_api.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ action: "save_user", old_username: CURRENT_USER, username: CURRENT_USER, password: np, role: "admin", verify_current: old, csrf_token: CSRF_TOKEN }) });
                    var d = await res.json();
                    if (d.ok) { showAdminMsg("Password updated successfully!", true); document.getElementById("profileOldPw").value = ""; document.getElementById("profileNewPw").value = ""; document.getElementById("profileConfirmPw").value = ""; document.getElementById("profileStrBar").style.width = "0"; }
                    else { showAdminMsg(d.error || "Failed to update password", false); }
                } catch (e) { showAdminMsg("Network error", false); }
                btn.disabled = false; btn.textContent = "\u{1F512} Update My Password";
            }
            function addUserCard() { var list = document.getElementById("adminUserList"); var card = makeUserCard("", "user", true); list.appendChild(card); card.querySelector(".admin-input").focus(); card.scrollIntoView({ behavior: "smooth", block: "nearest" }); }
            async function saveUser(btn) {
                var card = btn.closest(".user-card"); var inputs = card.querySelectorAll(".admin-input");
                var newUsername = inputs[0].value.trim(); var oldUsername = inputs[0].dataset.original || "";
                var newPassword = inputs[1].value; var newRole = card.querySelector(".admin-select").value;
                if (!newUsername) { showAdminMsg("Username cannot be empty", false); return; }
                btn.disabled = true; btn.textContent = "Saving...";
                try {
                    var res = await fetch("admin_api.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ action: "save_user", old_username: oldUsername, username: newUsername, password: newPassword, role: newRole, csrf_token: CSRF_TOKEN }) });
                    var d = await res.json();
                    if (d.ok) {
                        showAdminMsg(d.message, true); inputs[0].dataset.original = newUsername; inputs[1].value = ""; card.querySelector(".user-card-name").textContent = "&#128100; " + newUsername; card.querySelector(".user-card-role").textContent = newRole;
                        if (oldUsername === CURRENT_USER) { document.querySelectorAll(".user-name").forEach(function (el) { el.textContent = newUsername; }); document.querySelectorAll(".user-avatar").forEach(function (el) { el.textContent = newUsername[0].toUpperCase(); }); }
                    }
                    else { showAdminMsg(d.error || "Save failed", false); }
                } catch (e) { showAdminMsg("Network error", false); }
                btn.disabled = false; btn.textContent = "&#128190; Save Changes";
            }
            async function deleteUser(btn, username) {
                if (!confirm("Delete user \"" + username + "\"? This cannot be undone.")) return;
                btn.disabled = true;
                try {
                    var res = await fetch("admin_api.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ action: "delete_user", username: username, csrf_token: CSRF_TOKEN }) });
                    var d = await res.json();
                    if (d.ok) { showAdminMsg(d.message, true); btn.closest(".user-card").remove(); }
                    else { showAdminMsg(d.error || "Delete failed", false); btn.disabled = false; }
                } catch (e) { showAdminMsg("Network error", false); btn.disabled = false; }
            }
        </script>
    <?php endif; ?>

</body>

</html>
