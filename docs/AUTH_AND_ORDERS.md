# AUTH AND ORDERS SYSTEM

**Date:** 2026-03-21  
**Source:** Verified from controllers, routes, frontend API, middleware

---

## STEP 5 — Auth System

### How Auth Works Now

| Context | Method |
|---------|--------|
| **Telegram MiniApp** | initData from `window.Telegram.WebApp.initData` — no phone/SMS |
| **Web** | Phone + SMS code (IQSMS) → Sanctum token |
| **Admin** | Email + password (Laravel AuthController login/register) |

### Backend — Auth Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/auth/send-code` | POST | Send SMS code to phone |
| `/api/auth/verify-code` | POST | Verify code, return `{ user, token }` |
| `/api/auth/register` | POST | Admin: email/password register |
| `/api/auth/login` | POST | Admin: email/password login |
| `/api/auth/logout` | POST | Requires Bearer token |
| `/api/auth/user` | GET | Requires Bearer token |
| `/api/auth/forgot-password` | POST | Admin |
| `/api/auth/reset-password` | POST | Admin |

**Controller:** `App\Http\Controllers\Api\AuthController`

### verifyCode Logic (Verified)

1. Validates `phone`, `code` (6 digits by default)
2. **Mock bypass (dev):** If `APP_ENV` in `['local','development','dev']` AND `code === '123456'` → skip SMS check
3. Otherwise: lookup SmsCode, check expiry, attempts, match
4. On success: find/create User by phone, create Sanctum token, return `{ user, token }`

### Frontend — Auth

| Store | File | Purpose |
|-------|------|---------|
| **authStore** | `frontend/src/store/authStore.ts` | Zustand + persist: `token`, `user`, `setAuth`, `logout`, `isAuthenticated` |

**localStorage:**
- Key: `auth` (Zustand persist name)
- Contents: `{ token, user }` (partialized)

**Auth API:**
- `authAPI.sendCode(phone)` → POST `/api/auth/send-code`
- `authAPI.verifyCode(phone, code)` → POST `/api/auth/verify-code`

**AuthModal:**
- `frontend/src/components/web/AuthModal.tsx`
- Uses `authAPI.sendCode`, `authAPI.verifyCode`
- On success: `authStore.setAuth(token, user)`

---

## STEP 6 — Orders System

### How Orders Are Fetched

| Source | Method |
|--------|--------|
| **Telegram MiniApp** | `X-Telegram-Init-Data` header → validated → filter by `telegram_id` |
| **Web (Bearer)** | `Authorization: Bearer {token}` → filter by `user_id` |
| **Admin** | Bearer + admin role → full access (optional telegram_id filter) |

### Endpoints

| Endpoint | Method | Middleware | Auth |
|----------|--------|------------|------|
| `/api/v1/orders` | GET | telegram.initdata | initData OR Bearer |
| `/api/v1/orders` | POST | telegram.initdata | initData OR Bearer |
| `/api/v1/orders/{id}/cancel` | POST | telegram.initdata | initData OR Bearer |

### Request Flow (OrderController + TrustedTelegramContextService)

1. **ValidateTelegramInitData** (middleware):
   - If `Authorization: Bearer` → pass (no initData needed)
   - If `auth()->user()` → pass
   - If initData present → validate, merge `_telegram_user`
   - Else (no initData, no auth) → in production: 403

2. **TrustedTelegramContextService::resolve:**
   - Priority 1: `_telegram_user` from validated initData
   - Priority 2: `auth()->user()` (Sanctum) — for Web
   - Priority 3: `telegram_id` query param — **only in local/dev**

3. **OrderController::index:**
   - If admin + `telegram_id` param → filter by telegram_id
   - If `auth()->user()` (web) → filter by `user_id`
   - Else → use resolved context (telegram_id or user_id)

4. **OrderController::store:**
   - `telegram_id` from context (null for web users)
   - `user_id` from `auth()->user()?->id`
   - Order can have `telegram_id` nullable, `user_id` for web

### Restrictions

- **403:** No initData, no Bearer, no auth — message: "Для доступа к заказам необходим initData от Telegram Mini App"
- **401:** Invalid initData or invalid token

### Frontend — Orders API

| Method | Behavior |
|--------|----------|
| `ordersAPI.getMyOrders()` | If Web → `getByUserId()`; else → `getByTelegramId()` |
| `ordersAPI.getByUserId()` | GET `/api/v1/orders` with Bearer (no telegram_id) |
| `ordersAPI.getByTelegramId()` | GET `/api/v1/orders?telegram_id=...` with initData |
| `ordersAPI.create()` | POST `/api/v1/orders` — sends `telegram_id` if MiniApp, else omitted |

---

## STEP 7 — Database

### Orders Table (From Migrations)

| Migration | Change |
|-----------|--------|
| `2026_01_03_193857_create_orders_table` | Base orders table |
| `2026_03_21_124322_add_user_id_to_orders_table` | `user_id` nullable, foreign to users |
| `2026_03_21_180000_make_telegram_id_nullable_in_orders_table` | `telegram_id` → nullable |

**Schema facts:**
- `orders.telegram_id` — **nullable** (for web orders)
- `orders.user_id` — **exists**, nullable, foreign to users

### Last Migrations (Chronological)

1. `2026_03_21_180000_make_telegram_id_nullable_in_orders_table.php`
2. `2026_03_21_170000_create_banners_table.php`
3. `2026_03_21_160000_create_sms_settings_table.php`
4. `2026_03_21_150001_create_sms_codes_table.php`
5. `2026_03_21_150000_add_phone_to_users_table.php`
6. `2026_03_21_125859_fix_kitchen_timing_fields_columns.php`
7. `2026_03_21_124322_add_user_id_to_orders_table.php`
8. `2026_03_21_124312_add_telegram_id_to_users_table.php`
