# ОТЧЁТ: Миграция к USER-системе (без ломания Telegram MiniApp)

**Дата:** 2026-03-21

---

## USERS STRUCTURE

### До изменений (миграция `0001_01_01_000000_create_users_table`)

```
id, name, email, email_verified_at, password, remember_token, timestamps
```

**Нет:** `telegram_id`, `phone`

---

## MIGRATIONS

### 1. `2026_03_21_124312_add_telegram_id_to_users_table.php`

```php
$table->unsignedBigInteger('telegram_id')->nullable()->unique()->after('id');
```

### 2. `2026_03_21_124322_add_user_id_to_orders_table.php`

```php
$table->foreignId('user_id')->nullable()->after('telegram_id')->constrained()->nullOnDelete();
```

---

## ORDERS STRUCTURE

### До изменений

- `telegram_id` — есть (не трогали)
- `user_id` — не было

### После миграции

- `telegram_id` — без изменений
- `user_id` — добавлено (nullable, FK → users)

---

## MODELS

### app/Models/User.php

- В `fillable` добавлено: `telegram_id`
- В `casts` добавлено: `'telegram_id' => 'integer'`

### app/Models/Order.php

- В `fillable` добавлено: `user_id`
- В `casts` добавлено: `'user_id' => 'integer'`
- Добавлена связь:

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

---

## OrderController

**Не изменён** — `store()` и `index()` без правок.

---

## ВЫПОЛНЕНИЕ МИГРАЦИЙ

Локально MySQL недоступен. На сервере: `php artisan migrate` вернул "Nothing to migrate" (новые миграции ещё не задеплоены).

**После деплоя выполнить на сервере:**

```bash
cd /home/a/arturi51/hleb/public_html
/usr/local/bin/php8.2 artisan migrate --force
```

---

## ПРОВЕРКА (после миграций)

```bash
php artisan tinker
>>> User::first();
>>> Order::first();
>>> Order::first()->user;  # null для старых заказов
```

---

## ИТОГ

| Действие | Статус |
|----------|--------|
| Миграция telegram_id → users | Создана |
| Миграция user_id → orders | Создана |
| User model | Обновлён |
| Order model | Обновлён |
| OrderController | Не изменён |
| Миграции на сервере | Нужно выполнить после деплоя |

**Связи:** users ↔ telegram_id, orders ↔ user_id. Логика Telegram MiniApp не менялась.
