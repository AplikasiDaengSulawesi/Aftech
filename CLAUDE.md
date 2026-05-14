# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PT AFTECH Makassar — internal warehouse / production label tracking app. Vanilla PHP (no framework, no Composer), MariaDB/MySQL backend, jQuery/Bootstrap frontend, plus a Flutter mobile client that talks to a JSON REST API under `api/mobile/`. UI language is Indonesian.

## Running locally

No build step. Serve the repo root with any PHP-capable webserver (Apache/Nginx) or PHP's built-in server:

```bash
php -S localhost:8000          # from project root
# open http://localhost:8000/   → redirects to pages/index.php
```

The entry `index.php` redirects into `pages/index.php`, which requires a login (`pages/auth/login.php`). DB credentials are loaded from `.env` (see `.env.example`) via `includes/db_credentials.php`. The connector creates `$db` if missing (see `includes/db.php`).

## Database setup

There is **no ORM and no migration CLI**. Two layers:

1. `db_aftech.sql` — full phpMyAdmin dump used to bootstrap a fresh DB. Import it once: `mysql -u <user> -p <db> < db_aftech.sql`.
2. `includes/migrations/*.sql` — incremental, idempotent migrations. Each file ends with `INSERT INTO schema_migrations ... ON DUPLICATE KEY UPDATE` so re-running is safe. To apply, pipe files in order: `for f in includes/migrations/*.sql; do mysql -u <user> -p <db> < "$f"; done`. Check applied versions with `SELECT * FROM schema_migrations`.

When adding a new schema change: create `includes/migrations/NNN_<name>.sql`, make every DDL `IF NOT EXISTS` / additive, and end with the `schema_migrations` insert.

## Two clients, one PHP backend

Both web (browser session) and mobile (Flutter) hit the same PHP endpoints. `api/config.php::verify_api_access()` is the **single auth gate**: it accepts either an `X-API-Key` header (looked up in the `api_keys` table) or a logged-in PHP session. Any new API endpoint should `include 'config.php'` then call `verify_api_access()` before doing work.

- **Web pages** live in `pages/` and use `includes/header.php` (which calls `session_start()` and redirects unauthenticated users to `pages/auth/login.php`). Page-level RBAC goes through `includes/auth_check.php`: `can_access($slug)` reads `role_permissions`; `admin` bypasses all checks. The sidebar (`includes/sidebar.php`) wraps each link in a `can_access(...)` guard so unauthorized pages disappear from nav too.
- **Mobile endpoints** live in `api/mobile/` and are documented end-to-end in `api/mobile/README.md` (read this before touching mobile endpoints — request/response shapes, status codes, and invariants are spelled out there). Devices register via `request_access-mobile.php`, an admin approves, then `check_access-mobile.php` returns the `api_key`.

## Domain model: how a label flows

The label lifecycle is the central concept and it spans several tables. A label is uniquely identified by `(production_id, label_no)`.

```
production_labels (issued, copies+=N per re-print)
        │
        ├─►  warehouse_items (status: active in warehouse)
        │           │
        │           └─►  distributor_shipments  (status: shipped)
        │                       │
        │                       └─►  shipment_returns  (returned)
        │
        └─►  cancelled_labels (status: cancelled — category: production | warehouse)
```

Key rules baked into the code — preserve them when editing label-handling logic:

- `production_labels.copies` is **never decremented**. It represents lifetime issuance. "Active" is computed: `issued = active + shipped + cancelled + pending`.
- When `app_settings.qc_checker_enabled = 0`, `save_label-mobile.php` auto-transfers each freshly-printed label straight into `warehouse_items` (no QC step). When `= 1`, labels stay in `production_labels` until QC scan moves them.
- Cancellation is **blocked** once a label is in `distributor_shipments` — those must go through the return flow (`shipment_return_batches` + `shipment_returns`, migration 008).
- The QR/barcode payload is always `"{label_no}-{batch}"`. Endpoints that decode QR (e.g. `process_shipment.php::get_batch_data`) split on the first `-`.

Other schema notes:

- Master data (`master_items`, `master_units`, `master_sizes`, `master_machines`, `master_quantities`, `master_shifts`, `master_templates`) feeds dropdowns and the mobile `get_master_data` endpoint.
- `app_settings` is a key/value config table (`qc_checker_enabled` is the main toggle).
- `activity_logs` is the audit trail — every Tambah / Edit / Hapus / Scan / Transfer / Login writes a row. Both web and mobile have endpoints (`api/save_log.php`, `api/mobile/save_log-mobile.php`).
- `input_method` columns (`scan` | `manual` | `campuran`) tag whether stock was barcode-scanned or hand-typed; introduced in migrations 006/007. `campuran` is computed at merge time (`merge_shipment_input_methods` in `process_shipment.php`) when an existing shipment is appended with a different method.
- Cascading FKs: deleting a production batch removes its `qc_scans`, `qc_scan_details`, and `warehouse_items` automatically.

## Frontend conventions (from GEMINI.md, locked)

- **Theme:** Indigo `#1A237E` (primary) / `#3F51B5` (secondary) / `#FFC107` (amber accent) / `#00C853` success / `#D50000` danger. Defined as CSS vars in `includes/header.php` and reused in `pages/auth/login.php`.
- **Pure-AJAX SPA feel:** never call `window.location.reload()`. After a mutation, re-fetch and re-render the specific table via AJAX. Cache list data in `sessionStorage` so re-entering a page paints instantly, then refresh in the background.
- **Skeleton loaders** (not "Memuat data..." text) for every AJAX load — patterns exist inline in each page's `<style>` block (`.skeleton`, `.sk-line`, `.sk-block`).
- **Tables:** use `.table-responsive-md` + `.shadow-hover` with a `.bg-light` thead. Do **not** use `.table-sm`.
- **QC scanner:** uses `html5-qrcode` at 20–25fps, visualized as a "cinema seat map" grid (green = scanned, grey = pending, yellow pulse = last). Status feedback is rendered inline below the camera — do **not** use `Swal.fire` mid-scan (it kills scan speed). Reserve SweetAlert for destructive confirms (delete/transfer).
- **Shared JS helpers** live on `window.AFTECH` in `public/js/aftech-utils.js` (`formatNumber`, `showToast`, `handleAjaxError`, `getUrlParam`, `initDataTable`). Numbers are formatted as Indonesian (`18.569`, dot as thousands separator).
- Note: GEMINI.md references a `public/js/spa_nav.js` SPA navigation engine, but that file is not currently present in the repo — treat the "Zero-Reload Policy" as a per-page AJAX-rerender convention, not a real SPA router.

## Conventions worth respecting

- Timezone is **`Asia/Makassar`** (UTC+8). Set in `includes/header.php` and `api/config.php`, plus `SET time_zone = '+08:00'` on every mysqli connection. Don't introduce code that assumes UTC.
- Two DB drivers coexist: web pages typically use `$pdo` (PDO from `includes/db.php`), API endpoints use `$conn` (mysqli from `api/config.php`). Match the surrounding file.
- Passwords use `password_hash()` / `password_verify()` (see `pages/auth/login.php`).
- The codebase has SQL strings built with `real_escape_string` and direct interpolation in several API files. New queries that take user input should use prepared statements (`$conn->prepare(...)` or `$pdo->prepare(...)`) — the mobile endpoints already do this.
- Source code comments, page titles, and log messages are Indonesian. Keep new user-facing strings and log entries Indonesian to stay consistent.
- `.env` and `GEMINI.md` are gitignored; don't commit them.
