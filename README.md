# Healthy Blog Platform

Modern habit + wellness tracking dashboard built with a lightweight PHP backend and a Materialize / Chart.js frontend.

## Feature Highlights

- Secure authentication (CSRF + session hardening + basic rate limiting)
- Daily health entry logging (hydration, sleep, steps, meals, smoking, alcohol, subjective flags)
- Weighted 0–100 Health Score with categorical ranges (Critical → Excellent)
- Dynamic advice engine (severity colored)
- Progress visualization: goals vs actual, deficits (negative bars), lifestyle pie, BMI trend
- Unified milestone progress list (formerly badges) with streak & performance detection
- BMI persistence + trend chart
- Export (JSON / CSV) of raw entries

## Directory Overview

| Path                    | Purpose                                               |
| ----------------------- | ----------------------------------------------------- |
| `index.html`            | Landing + entry form launch                           |
| `php/`                  | API + auth endpoints + dashboard                      |
| `assets/app.js`         | Site-wide UI enhancements (modals, search, lazy load) |
| `js/test_form_modal.js` | Health form handling + submission logic               |
| `css/`                  | Styling (core + dashboard/achievements)               |
| `docs/`                 | Architecture, security, data model & TODO notes       |

## Key Documentation

See the `docs/` folder:

- `ARCHITECTURE_NOTES.txt` – Layering, patterns, extensibility
- `SECURITY_NOTES.txt` – Current controls & hardening roadmap
- `DATA_MODEL_NOTES.txt` – Tables, indexes, health score math
- `TODO_NEXT.txt` – Prioritized future work

## Setup (Windows / PowerShell)

1. Ensure PHP is installed: `php -v`
2. Start local server from project root:

```powershell
php -S localhost:8080 -t .
```

3. Open http://localhost:8080/ in a browser.

## Database Initialization

1. Create a MySQL database (utf8mb4).
2. Add credentials to `php/config.php` (define DB_HOST, DB_NAME, DB_USER, DB_PASS).
3. Apply schema (example `schema_normalized.sql`) if using normalized columns; fallback JSON storage works without it.

## Health Score Model (High Level)

Positive components (max 70): hydration, sleep quality (shape function), steps, meals density.
Negative penalties (max -30): smoking, alcohol, drugs, fatigue, hydration deficit.
Bounded 0–100 → category badge shown in dashboard summary.

## Progress / Milestones

Each milestone row displays:

- Icon + label
- Adaptive color progress bar (red → yellow → green → blue optimal)
- Percentage + Completed tag when done
  Data extracted from current + historical entries; earned completions persisted in `user_badges` for celebration & idempotency.

## Security Summary

- CSRF token required for mutating endpoints (see `csrf.php`)
- Sessions configured with secure attributes in `session.php`
- Prepared statements via PDO across endpoints
- Minimal leakage of internal errors (JSON responses)

More detail: `docs/SECURITY_NOTES.txt`.

## Extending

Add a metric → update `api_save_entry.php`, schema (optional), and dashboard builders (charts + progress).
Add a goal → extend `api_goals_*`, the goals form, and progress bar rendering.
Add a milestone → update milestone catalog in `dashboard.php` + optional DB badge key.

## Exports

Use buttons in dashboard (JSON / CSV). CSV includes baseline columns for analysis in spreadsheets.

## Roadmap (Abbreviated)

See `docs/TODO_NEXT.txt` for full list.
Short-term candidates: recalculation endpoint, tooltip legend, weekly summaries, Docker environment.

## License / Attribution

Internal project; add license file if distributing.

## Contact

Author: Healthy Blog Team (2025)
For issues / ideas: create a ticket or update `TODO_NEXT.txt`.
