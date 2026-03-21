# INFRASTRUCTURE — Servers and Deploy Process

**Date:** 2026-03-21  
**Data source:** Codebase, existing reports (DEPLOY_REPORT_DEV, PROD_SNAPSHOT, DEV_DEPLOY_SCRIPT)

---

## STEP 1 — Servers (Real Data)

### DEV SERVER

| Property | Value |
|----------|-------|
| **Domain** | dev.svoihlebekb.ru |
| **IP** | 45.130.41.69 (verified via Resolve-DnsName) |
| **SSH user** | arturi51 |
| **SSH host** | arturi51.beget.tech |
| **Project path** | `/home/a/arturi51/dev.svoihlebekb.ru/public_html` |
| **Provider** | Beget |

**Exact SSH command:**
```bash
ssh arturi51@arturi51.beget.tech
```

**Alternative (by IP):**
```bash
ssh arturi51@45.130.41.69
```

**Exact deploy commands (from DEV_DEPLOY_SCRIPT.sh):**
```bash
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html

git fetch origin
git reset --hard origin/main

cd frontend
node node_modules/vite/bin/vite.js build

cd ..
php8.2 artisan migrate --force
php8.2 artisan cache:clear
php8.2 artisan view:clear
php8.2 artisan route:clear
```

**Note:** If `php8.2` is not in PATH, use `php` or full path to PHP 8.2.

---

### PROD SERVER

| Property | Value |
|----------|-------|
| **Domain** | svoihlebekb.ru |
| **IP** | NOT VERIFIED (not resolved in audit) |
| **Project path** | `/home/a/arturi51/hleb/public_html` |
| **Provider** | Beget |
| **SSH** | Same pattern as DEV: `arturi51@arturi51.beget.tech` |

**Differences from DEV:**
- Different path: `hleb/public_html` vs `dev.svoihlebekb.ru/public_html`
- PROD receives automatic deploy via POST to `/api/deploy`
- DEV does NOT receive automatic deploy

---

## STEP 2 — Deploy Process (Exact Flow)

### How Code Gets to Server

1. **Local machine:**
   - Run `php artisan deploy` (or `php artisan deploy --skip-build` to skip frontend build)
   - Command: `Deploy.php` (App\Console\Commands\Deploy)

2. **Deploy command steps (verified in Deploy.php):**
   - **Step 1:** `npm run build:all` (builds Vue admin + React frontend) — unless `--skip-build`
   - **Step 2:** `git add .` (includes `public/build`, `public/frontend`)
   - **Step 3:** `git commit -m "Deploy: ..."`
   - **Step 4:** `git push origin main`
   - **Step 5:** POST request to `{DEPLOY_SERVER_URL}/api/deploy` with `X-Deploy-Token`

3. **Server (DeployController.php):**
   - Receives POST at `/api/deploy`
   - Executes: `git fetch origin`, `git reset --hard origin/{branch}`
   - Does NOT run `npm run build` — expects pre-built assets from git
   - Runs: `composer install`, `php artisan migrate --force`, cache clear, optimize

### Is Frontend Built Locally or on Server?

**Answer:** **Locally.** Build is done on developer machine, committed to git, pushed. Server only pulls.

**Exception (DEV):** DEV uses manual script that runs `node node_modules/vite/bin/vite.js build` **on server** (because DEV may not receive automatic deploy with latest commits).

### Where Build Is Stored

- **Vue admin:** `public/build/` (manifest.json, assets)
- **React app:** `public/frontend/` (index.html, assets/index-*.js, index-*.css)

**Paths on disk:**
- `c:\OSPanel\domains\express\public\build\`
- `c:\OSPanel\domains\express\public\frontend\`

### How Laravel Connects to Frontend

- **Blade view:** `resources/views/react.blade.php`
- **Logic:** Reads `public/frontend/index.html` or globs `public/frontend/assets/index-*.js`, `index-*.css`
- **Output:** Injects `<link>` and `<script>` tags for React app
- **Base path:** `/frontend/` (from `frontend/vite.config.ts` base)

**Routes (web.php):**
- `/` and `/{any}` (excluding admin, api, storage, etc.) → `view('react')`
- `/frontend/assets/{path}` → serves from `public/frontend/assets/`

---

## Environment Variables (Referenced in Code)

| Variable | Purpose |
|----------|---------|
| `DEPLOY_SERVER_URL` | URL for POST deploy trigger (e.g. https://svoihlebekb.ru) |
| `DEPLOY_TOKEN` | Token for X-Deploy-Token header |
| `APP_ENV` | local, development, dev, production |
| `PHP_PATH` | Optional PHP binary path on server |
| `COMPOSER_PATH` | Optional Composer path on server |

**Note:** Actual values in `.env` are NOT VERIFIED (file filtered).
