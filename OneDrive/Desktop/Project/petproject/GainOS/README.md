# GainOS 💪

> **Your Training OS** — A fully offline, single-file fitness tracker built for the gym-obsessed.

![GainOS](https://img.shields.io/badge/GainOS-v2.0-c8f135?style=for-the-badge&labelColor=080810)
![HTML](https://img.shields.io/badge/HTML-Single%20File-orange?style=for-the-badge)
![No Dependencies](https://img.shields.io/badge/Dependencies-Zero-blue?style=for-the-badge)

---

## ✨ Features

### 👤 Multi-Profile System
- Up to 5 named profiles with custom emoji avatars
- **Guest mode** — temporary session, nothing persisted
- Fully isolated data per profile (workouts, meals, weight, PRs, settings)
- Auto-migrates existing solo-user data on first launch
- Switch profiles from the Home screen avatar button

### 🤖 AI-Powered Onboarding
New profiles go through a guided setup:
1. **Goal picker** — Strength / Aesthetics / Fat Loss / Athletic
2. **Stats form** — body weight, training days/week, equipment (Full Gym / Home / Minimal)
3. **Claude AI** generates a complete 7-day split + daily protein target
4. Preview → Regenerate or confirm

### 🏋️ Workout Logger
- Per-day split with collapsible exercise cards
- Pre-fills sets from last session (progressive overload)
- Mark sets done with ✓
- Add / remove sets on the fly
- **Personal record detection** — toasts on new PRs

### ⏱️ Rest Timer
- Appears automatically after marking a set ✓-done
- Floating pill above the nav bar
- Countdown with progress bar, pulses red in last 10 seconds
- Tap to skip

### 📊 Progress Tracking
- Personal record grid (top compound lifts)
- Weekly volume bar chart (Canvas)
- Progressive overload status (Progressing ↑ / Stagnating / Slight dip)

### 🥩 Nutrition (QuickFuel)
- Animated protein ring
- Quick-add preset meals
- Custom meal modal
- **✏️ Tap-to-edit** daily protein target — persists per profile
- **⚡ AI Meal Plan** — enter your ingredients, Claude generates a full-day plan

### 📅 Calendar & Streaks
- Monthly calendar with workout-done / missed / rest-day markers
- Current streak + best streak
- This-week session bar chart

### ⚖️ Body Weight
- Log daily weight, trend chart (12-week)
- Change indicator (+/- since last weigh-in)

### 📝 Per-Exercise Notes
- Text area under each exercise card
- Saves form cues, pain points, or reminders per exercise
- Persisted per profile

---

## 🚀 Getting Started

### Zero install — just open the file:

```bash
git clone https://github.com/AerioJobin/GainOS.git
cd GainOS
# Open GainOS_App.html in Chrome or Edge
```

Or download `GainOS_App.html` and open it directly in your browser.

---

## 🤖 AI Setup (Optional)

GainOS uses the [Anthropic Claude API](https://console.anthropic.com/) for:
- AI Meal Plan generation
- AI-powered split generation during onboarding

When you first use an AI feature, you'll be prompted:
> *"Enter your Anthropic API key (stored for this session only)"*

Your key is stored in `sessionStorage` — it is **never** written to disk or `localStorage`.

Get a key at: https://console.anthropic.com/

---

## 🛠️ Tech Stack

| Layer | Choice |
|-------|--------|
| Structure | HTML5 |
| Styling | Vanilla CSS (custom design system) |
| Logic | Vanilla JavaScript (no frameworks) |
| Storage | `localStorage` (per-profile JSON) |
| Charts | Manual Canvas 2D API |
| AI | Anthropic Claude API (optional) |
| Fonts | Bebas Neue · Syne · DM Mono |

---

## 📋 Default Workout Split

The built-in default split (overridable via AI onboarding):

| Day | Focus |
|-----|-------|
| Sunday | REST |
| Monday | Chest Day — Upper Push |
| Tuesday | Pull Day — Back & Biceps |
| Wednesday | Shoulder Day — Shoulders & Arms |
| Thursday | Aesthetic Day — Weak Points |
| Friday | REST |
| Saturday | Leg Day — Full Legs |

---

## 🐛 Bug Fixes (v2.0)

| # | Fixed |
|---|-------|
| BUG-01 | AI Meal Plan — missing API headers + wrong model name |
| BUG-02 | Streak reset if today's workout not yet saved |
| BUG-03 | Best streak didn't account for rest days |
| BUG-04 | AI modal stacked a new keydown listener on each open |
| BUG-05 | Home stats stale when navigating back |
| BUG-06 | Volume chart grouped by month, not week |
| BUG-07 | Weight history diffs shown in wrong direction |
| BUG-08 | Empty workout save still marked day complete |

---

## 📁 Project Structure

```
GainOS/
└── GainOS_App.html    # The entire app — HTML + CSS + JS in one file
```

---

## 📄 License

MIT — do whatever you want with it.

---

<p align="center">Built with 💪 by <a href="https://github.com/AerioJobin">AerioJobin</a></p>
