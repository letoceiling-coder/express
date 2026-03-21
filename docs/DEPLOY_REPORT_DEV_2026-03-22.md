# DEPLOY REPORT (DEV)

**Date:** 2026-03-22  
**Server:** dev.svoihlebekb.ru

---

## 1. SERVER PATH

```
/home/a/arturi51/dev.svoihlebekb.ru/public_html
```

---

## 2. COMMIT

```
183110b618ebfc7449a12e05ee6358f6b3c17db0
```

Short: `183110b` — Deploy: 2026-03-21 21:22:04

---

## 3. BUILD FILES

| File | Size |
|------|------|
| index-DezlneZ6-mn0vu1hs.js | 775,513 bytes (~756 KB) |
| index-BX_Ith0u-mn0vu1hs.css | 86,008 bytes (~84 KB) |

---

## 4. INDEX.HTML

```html
<script type="module" crossorigin src="/frontend/assets/index-DezlneZ6-mn0vu1hs.js"></script>
<link rel="stylesheet" crossorigin href="/frontend/assets/index-BX_Ith0u-mn0vu1hs.css">
```

---

## 5. API STATUS

```
200
```

`curl -s -o /dev/null -w "%{http_code}" https://dev.svoihlebekb.ru/api/v1/products` → **200**

---

## 6. CACHE CLEAR

- php8.2 artisan cache:clear — выполнено
- php8.2 artisan view:clear — выполнено
- php8.2 artisan route:clear — выполнено
- php8.2 artisan config:clear — выполнено

---

## 7. MIGRATIONS

```
2026_03_21_180000_make_telegram_id_nullable_in_orders_table ... DONE
```

---

## 8. FINAL URL

**https://dev.svoihlebekb.ru/?v=183110b**

---

## ✅ RESULT

DEV сервер обновлён на 100%. Фронт собран на сервере, assets новые, index.html обновлён.
