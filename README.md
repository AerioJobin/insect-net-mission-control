<p align="center">
  <img src="neuronics_logo.png" alt="NeuRonICS Lab" height="80">
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="iisc_logo.jpg" alt="IISc Bangalore" height="100">
</p>

<h1 align="center">🦟 INSECT NET — Mission Control</h1>

<p align="center">
  <strong>IoT-Powered Insect Trap Monitoring &amp; AI Species Identification</strong><br>
  <em>NeuRonICS Lab · Indian Institute of Science (IISc) · Bangalore</em>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white" alt="PHP 8.x">
  <img src="https://img.shields.io/badge/AI-Gemini%202.5%20Flash-4285F4?logo=google&logoColor=white" alt="Gemini AI">
  <img src="https://img.shields.io/badge/Maps-Leaflet.js-199900?logo=leaflet&logoColor=white" alt="Leaflet.js">
  <img src="https://img.shields.io/badge/Hosted-AWS%20EC2-FF9900?logo=amazonaws&logoColor=white" alt="AWS EC2">
  <img src="https://img.shields.io/badge/Auth-bcrypt%20%2B%20CSRF-16a34a" alt="Security">
</p>

---

## 📋 Overview

**Insect NET Mission Control** is a full-stack dashboard for agricultural pest surveillance. It connects autonomous IoT trap devices (INMT) deployed in the field to a cloud dashboard that provides:

- **Real-time fleet monitoring** with 5-second polling, battery tracking, and GPS mapping
- **AI-powered insect classification** using Google Gemini 2.5 Flash (Vision API)
- **Image gallery** with lazy loading, date grouping, and thumbnail optimization
- **Role-based access control** with bcrypt authentication and CSRF protection

The platform is actively deployed at IISc Bangalore for monitoring *Bactrocera* fruit fly populations.

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🛰️ **Fleet Dashboard** | Real-time device cards with status (online/stale/offline), battery voltage, and countdown ring |
| 🗺️ **Live GPS Map** | Leaflet.js + OpenStreetMap with device markers, fullscreen mode, and auto-updating positions |
| 📷 **Image Gallery** | Lazy-loaded thumbnails, date grouping, shimmer placeholders, lightbox with swipe navigation |
| 🤖 **AI Classification** | Gemini 2.5 Flash identifies insect species with confidence scoring and smart caching |
| 📊 **Species Summary** | Aggregated identification data table with CSV export |
| 📈 **Capture Charts** | Per-day bar chart showing image capture frequency (last 14 days) |
| 🔐 **Authentication** | bcrypt password hashing, CSRF tokens, session timeout, login rate limiting |
| 👥 **Admin Panel** | Slide-out user management with password strength meter and role control |
| 🌓 **Dark/Light Theme** | Persistent toggle on every page with FOUC prevention |
| 📱 **Responsive** | 4 breakpoints — desktop, tablet, phone, and small phone |

---

## 🏗️ Architecture

```
┌─────────────────┐     📡 Cellular      ┌──────────────────────────┐
│  🪤 INMT Device │ ──────────────────▶  │  ☁️ AWS EC2 (ap-south-1) │
│  Camera + GPS   │   Images + Telemetry │  Apache + PHP 8.x       │
└─────────────────┘                      │                          │
                                         │  📁 /uploads/ (images)   │
┌─────────────────┐                      │  💾 SQLite (telemetry)   │
│  🪤 INMT Device │ ──────────────────▶  │  📄 JSON (AI cache)     │
│  Camera + GPS   │                      └───────────┬──────────────┘
└─────────────────┘                                  │
                                                     │ 🔗 API Call
                                                     ▼
                                         ┌──────────────────────────┐
                                         │  🤖 Google Gemini 2.5    │
                                         │  Flash Vision API        │
                                         └──────────────────────────┘
```

---

## 📁 Project Structure

```
insect-net-mission-control-main/
├── index.php           # Main dashboard (landing + fleet + device detail + gallery)
├── index.html          # Public landing page with theme support
├── login.php           # Authentication page
├── logout.php          # Session destruction + redirect
├── config.php          # Session config, auth helpers, CSRF, rate limiting
├── classify.php        # Gemini AI classification endpoint (CSRF-protected)
├── image_detail.php    # Individual image AI analysis page
├── thumbnail.php       # On-demand thumbnail generator (GD library)
├── delete_image.php    # Admin-only image deletion endpoint
├── clear_cache.php     # Clear AI classification cache (CSRF-protected)
├── admin_api.php       # Admin user management REST API
├── 404.html            # Custom branded error page
├── .htaccess           # Apache security config & access rules
├── users.json          # User credentials (bcrypt hashed, blocked from web)
├── neuronics_logo.png  # NeuRonICS Lab logo
├── iisc_logo.jpg       # IISc Bangalore logo
└── uploads/            # Trap images + JSON cache + SQLite telemetry
    ├── thumbs/         # Auto-generated thumbnails (300px)
    ├── cam1_*.jpg       # Device 1 captures
    ├── cam1_*.json      # AI classification results
    └── insect_net.sqlite # Telemetry database
```

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Server** | Apache on AWS EC2 (t2.micro, ap-south-1) |
| **Backend** | PHP 8.x (vanilla, no framework) |
| **Frontend** | Vanilla HTML / CSS / JavaScript |
| **Maps** | Leaflet.js 1.9.4 + OpenStreetMap |
| **AI** | Google Gemini 2.5 Flash (Vision API) |
| **Auth** | PHP Sessions + bcrypt (cost 12) + CSRF tokens |
| **Storage** | Filesystem (images) + JSON (cache/users) + SQLite (telemetry) |
| **Thumbnails** | PHP GD library (on-demand, cached) |
| **Fonts** | Google Fonts: Space Mono, Inter, Outfit |

---

## 🚀 Setup & Deployment

### Prerequisites

- PHP 8.x with GD extension enabled
- Apache with `mod_rewrite` and `mod_headers`
- Google Gemini API key

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/AerioJobin/insect-net-mission-control.git
   cd insect-net-mission-control
   ```

2. **Configure API key** — Create a `.env.local.php` file in the project root:
   ```php
   <?php
   putenv('GEMINI_API_KEY=your_api_key_here');
   $_ENV['GEMINI_API_KEY'] = 'your_api_key_here';
   ```

3. **Set up user credentials** — Edit `users.json` (passwords must be bcrypt hashed):
   ```json
   {
     "admin": {
       "password": "$2y$12$...",
       "role": "admin"
     }
   }
   ```
   Use PHP to generate a hash: `php -r "echo password_hash('your_password', PASSWORD_BCRYPT, ['cost' => 12]);"`

4. **Set directory permissions**
   ```bash
   chmod 755 uploads/
   chmod 644 users.json
   ```

5. **Deploy to Apache** — Point your Apache DocumentRoot to the project directory and ensure `.htaccess` overrides are enabled:
   ```apache
   <Directory /var/www/html/your-project>
       AllowOverride All
   </Directory>
   ```

---

## 🔒 Security Features

- **bcrypt password hashing** (cost factor 12)
- **CSRF tokens** on all state-changing endpoints (login, classify, clear cache, admin API)
- **Session timeout** (30 minutes of inactivity)
- **Login rate limiting** (5 attempts, 5-minute lockout)
- **`.htaccess` protections** — blocks `users.json`, `.sqlite`, `.env`, `.git`, shell scripts
- **`uploads/.htaccess`** — blocks PHP execution, JSON/SQLite access; only allows image files
- **Security headers** — `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`
- **Path traversal prevention** — `basename()` and `realpath()` validation on all file operations

---

## 🤖 AI Classification

The platform uses **Google Gemini 2.5 Flash** for insect identification:

- **Smart caching** — Fresh results only overwrite cache if confidence ≥ existing
- **3-attempt retry** with exponential backoff for 503/429 errors
- **Mock fallback** — Returns labeled mock data on persistent API failure (never cached)
- **Force Fresh** — Override confidence comparison and always save new result
- **Confidence tracking** — Shows ▲/▼ pills for confidence changes between analyses

---

## 📊 API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `classify.php` | POST | User + CSRF | AI species classification |
| `clear_cache.php` | POST | User + CSRF | Delete cached classification |
| `delete_image.php` | POST | Admin | Delete an uploaded image |
| `admin_api.php` | POST | Admin + CSRF | User management (CRUD) |
| `thumbnail.php` | GET | User | On-demand thumbnail generation |
| `get_status.php` | GET | User | Device telemetry polling |

---

## 🎨 Design System

| Token | Light | Dark |
|-------|-------|------|
| `--primary` | `#8A2245` | `#8A2245` |
| `--accent` | `#c44569` | `#c44569` |
| `--bg` | `#FDFBF7` | `#0e0c11` |
| `--surface` | `#FFFFFF` | `#19161f` |
| `--text` | `#4E4247` | `#e8e0ec` |

**Typography:** Space Mono (headers) · Inter (body) · Outfit (buttons)

---

## 📄 License

This project is developed by the **NeuRonICS Lab** at the **Indian Institute of Science (IISc), Bangalore**.

---

<p align="center">
  <em>© 2026 NeuRonICS Lab, IISc Bangalore · INSECT NET Mission Control v2.0</em>
</p>
