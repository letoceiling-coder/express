# PROJECT AUDIT — Full State Documentation

**Date:** 2026-03-21  
**Audit Type:** Full project audit — verified data only, no assumptions

---

## STEP 0 — Context Validation

### Project Root
- **Absolute path:** `c:\OSPanel\domains\express` (Windows)
- **Git repo:** Yes, at `C:/OSPanel/domains/express`
- **Branch:** main

### Frontend Folder
- **Path:** `c:\OSPanel\domains\express\frontend`
- **Type:** React + TypeScript + Vite
- **Package:** `vite_react_shadcn_ts` (from `frontend/package.json`)
- **Build output:** `../public/frontend` (from `frontend/vite.config.ts`)

### Backend Folder
- **Path:** `c:\OSPanel\domains\express` (Laravel root)
- **Framework:** Laravel (PHP)
- **Entry:** `artisan`, `public/index.php`

### Environment
- **Local workspace:** Windows (OSPanel)
- **Environment variable:** From `.env` — **NOT VERIFIED** (file filtered)
- **Note:** `.env.example` exists but is filtered by globalignore

### Project Structure (Top-Level)

```
express/
├── app/                    # Laravel application
├── bootstrap/
├── config/
├── database/
│   └── migrations/
├── frontend/               # React SPA source
│   ├── src/
│   ├── package.json
│   └── vite.config.ts
├── public/
│   ├── build/              # Vue admin assets
│   ├── frontend/           # React build output
│   │   ├── assets/
│   │   └── index.html
│   └── index.php
├── resources/
│   └── views/
│       └── react.blade.php # Serves React app
├── routes/
│   ├── api.php
│   └── web.php
├── artisan
├── composer.json
├── package.json            # Root: Vue admin + build scripts
└── vite.config.js          # Vue admin
```

---

## STEP 8 — Current Problems (Verified)

### 1. WebLayout.tsx — Missing store destructuring
- **File:** `frontend/src/components/web/WebLayout.tsx`
- **Line 60:** Uses `orderMode` and `setOrderMode`
- **Problem:** `useOrderModeStore` is imported (line 10) but values are **never extracted**
- **Expected:** `const { orderMode, setOrderMode } = useOrderModeStore();` or similar
- **Impact:** Runtime ReferenceError when rendering desktop header

### 2. DEV vs PROD deploy flow mismatch
- **PROD:** Receives automatic deploy via `php artisan deploy` → POST to DEPLOY_SERVER_URL
- **DEV:** Does NOT receive automatic deploy; requires manual SSH + commands
- **Source:** `DEPLOY_REPORT_DEV.md`, `Deploy.php`, `DeployController.php`

### 3. Frontend build location
- **Deploy flow:** Build is done **locally** (Deploy.php `npm run build:all`)
- **Output committed to git:** `public/build` (Vue), `public/frontend` (React)
- **Server:** Gets code via `git pull` / `git reset --hard` — no build on server
- **DeployController:** Does NOT run `npm run build` on server

### 4. Orders API — initData requirement
- **Endpoint:** `GET /api/v1/orders`, `POST /api/v1/orders`
- **Middleware:** `telegram.initdata` (ValidateTelegramInitData)
- **Web with Bearer token:** Passes (lines 34–36 of ValidateTelegramInitData.php)
- **Without auth:** Returns 403 "Для доступа к заказам необходим initData от Telegram Mini App"
- **Verified:** `ValidateTelegramInitData.php` checks `Authorization: Bearer` first

---

## STEP 10 — Final Report

### Confirmed Working
1. **Routing:** `isTelegramWebApp()` determines Web vs MiniApp layout
2. **Auth (Web):** `POST /api/auth/send-code`, `POST /api/auth/verify-code` exist
3. **Auth mock (dev):** Code `123456` bypasses SMS in `local`/`development`/`dev` env (AuthController.php:266)
4. **Orders (Web):** Bearer token allows `GET /api/v1/orders` (user_id filter)
5. **Orders (MiniApp):** initData via `X-Telegram-Init-Data` header
6. **TrustedTelegramContextService:** Priority: initData → auth()->user() → telegram_id fallback (dev only)
7. **Database:** `orders.telegram_id` nullable, `orders.user_id` exists (migrations verified)
8. **React build:** Output to `public/frontend`, served via `react.blade.php`

### Broken / Mismatch
1. **WebLayout.tsx:** `orderMode` / `setOrderMode` used but not extracted from `useOrderModeStore`
2. **DEV deploy:** Manual only; no automation
3. **PROD path (Beget):** `/home/a/arturi51/hleb/public_html` (from PROD_SNAPSHOT_REPORT)

### Where Mismatch Occurs
- **Mobile on Web:** Uses `MiniAppHeader` + `BottomNavigation` inside WebLayout (shared components)
- **Desktop on Web:** Uses custom header in WebLayout; DeliveryModeToggle references undefined variables

### Must Be Fixed Next (No Code Yet)
1. Add `const { orderMode, setOrderMode } = useOrderModeStore();` (or equivalent) in WebLayout.tsx
2. Document DEV deploy procedure for stakeholders
3. Confirm DEPLOY_SERVER_URL in .env points to intended server (PROD vs DEV)
