# План внедрения изменений в проект «Свой Хлеб»

## Общая структура проекта

- **Backend**: Laravel (PHP) с API endpoints
- **Mini App Frontend**: React (TypeScript) с shadcn/ui компонентами
- **Admin Panel Frontend**: React (TypeScript) для настроек "О нас", Vue.js для остальных разделов админки

---

## A) MINI APP (React) — ПРАВКИ UI/UX

### A1) Страница «О нас» — улучшение структуры и интерактивности

#### Текущее состояние
- ✅ Страница существует: `frontend/src/pages/miniapp/AboutPage.tsx`
- ✅ Баннер (cover_image_url) уже реализован
- ✅ Телефон кликабельный, открывает tel: ссылку
- ✅ Адрес с иконкой MapPin
- ✅ Кнопка Яндекс.Карт
- ❌ Нет кнопки копирования телефона
- ❌ Нет "Показать больше/Скрыть" для description и bullets
- ❌ Нет блока "Поддержка" с Telegram
- ❌ Нет поля support_telegram_url в БД

#### Шаги реализации

**1. Backend: Добавить поле support_telegram_url в таблицу about_page**

**Файл**: `database/migrations/XXXX_XX_XX_add_support_telegram_url_to_about_page.php`
- Добавить колонку `support_telegram_url` (string, nullable)

**Файл**: `app/Models/AboutPage.php`
- Добавить `support_telegram_url` в `$fillable`
- Обновить значение по умолчанию в методе `getPage()`:
  ```php
  'support_telegram_url' => 'https://t.me/+79826824368',
  ```

**Файл**: `app/Http/Controllers/Api/v1/AboutPageController.php`
- Добавить `support_telegram_url` в валидацию метода `update()`
- Добавить поле в ответы методов `show()` и `getAdmin()`

**2. Frontend Mini App: Обновить компонент AboutPage**

**Файл**: `frontend/src/pages/miniapp/AboutPage.tsx`

Изменения:
- Добавить `support_telegram_url` в интерфейс `AboutPageData`
- Добавить состояние для "Показать больше/Скрыть" (useState)
- Добавить функцию копирования телефона в буфер обмена (с toast уведомлением)
- Реализовать сворачивание/разворачивание description (первые 4-6 строк)
- Реализовать сворачивание/разворачивание bullets (первые 4-6 пунктов)
- Добавить блок "Поддержка" с кнопкой Telegram (используя `openTelegramLink` из `@/lib/telegram`)
- Добавить кнопку "Скопировать" рядом с телефоном
- Добавить плейсхолдер для cover_image_url если картинка не загружена
- Добавить иконку Telegram (из lucide-react)

**3. Frontend API: Обновить тип данных**

**Файл**: `frontend/src/api/index.ts`
- Типы уже должны автоматически подхватить новое поле из API

---

### A2) Уведомление «Добавлено в корзину» — перенос вверх

#### Текущее состояние
- ✅ Используется shadcn/ui Toast (Radix UI)
- ✅ Toast вызывается через `toast.success()` в `ProductCard.tsx` и `ProductDetailPage.tsx`
- ❌ Позиционирование: снизу экрана (по умолчанию в `toast.tsx`)

#### Шаги реализации

**Файл**: `frontend/src/components/ui/toast.tsx`

Изменение в компоненте `ToastViewport` (строка 17):
```tsx
// БЫЛО:
className={cn(
  "fixed top-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:bottom-0 sm:right-0 sm:top-auto sm:flex-col md:max-w-[420px]",
  className,
)}

// ДОЛЖНО БЫТЬ (для верхнего позиционирования):
className={cn(
  "fixed top-0 z-[100] flex max-h-screen w-full flex-col p-4 sm:right-0 md:max-w-[420px]",
  className,
)}
```

**Файл**: `frontend/src/components/ui/toast.tsx` (строка 26)

Изменение в `toastVariants` для анимации сверху:
```tsx
// БЫЛО:
data-[state=open]:slide-in-from-top-full data-[state=open]:sm:slide-in-from-bottom-full

// ДОЛЖНО БЫТЬ:
data-[state=open]:slide-in-from-top-full
```

**Проверить в**: `frontend/src/components/miniapp/ProductCard.tsx` и `frontend/src/pages/miniapp/ProductDetailPage.tsx`
- Убедиться, что duration установлен (1.5-2.5 секунды) - уже установлен 2000ms

---

## B) ЛОГИКА ДОСТАВКИ И ОГРАНИЧЕНИЯ ПО МИНИМАЛЬНОЙ СУММЕ

### B1) Минимальный заказ на ДОСТАВКУ: 3000 ₽

#### Шаги реализации

**1. Backend: Добавить поле min_delivery_order_total_rub в delivery_settings**

**Файл**: `database/migrations/XXXX_XX_XX_add_min_delivery_order_total_rub_to_delivery_settings.php`
```php
Schema::table('delivery_settings', function (Blueprint $table) {
    $table->decimal('min_delivery_order_total_rub', 10, 2)->default(3000)->after('is_enabled');
});
```

**Файл**: `app/Models/DeliverySetting.php`
- Добавить `min_delivery_order_total_rub` в `$fillable`
- Добавить в `$casts`: `'min_delivery_order_total_rub' => 'decimal:2'`
- Обновить метод `getSettings()` для установки значения по умолчанию

**Файл**: `app/Http/Controllers/Api/v1/DeliverySettingsController.php`
- Добавить `min_delivery_order_total_rub` в валидацию метода `updateSettings()`
- Добавить в ответ метода `getSettings()`

**Файл**: `app/Http/Requests/DeliverySettingsRequest.php`
- Добавить правило валидации: `'min_delivery_order_total_rub' => 'nullable|numeric|min:0'`

**2. Backend: Добавить проверку при создании заказа**

**Файл**: `app/Http/Controllers/Api/v1/OrderController.php`
- В методе `store()` после валидации добавить проверку:
  ```php
  // Проверка минимального заказа для доставки
  if ($request->get('delivery_type') === 'courier') {
      $deliverySettings = DeliverySetting::getSettings();
      $minDeliveryTotal = $deliverySettings->min_delivery_order_total_rub ?? 3000;
      
      if ($request->get('total_amount') < $minDeliveryTotal) {
          return response()->json([
              'message' => "Минимальный заказ на доставку — {$minDeliveryTotal} ₽. Добавьте товаров еще на " . ($minDeliveryTotal - $request->get('total_amount')) . " ₽",
              'error' => 'min_delivery_order_total',
              'min_amount' => $minDeliveryTotal,
              'current_amount' => $request->get('total_amount'),
              'required_amount' => $minDeliveryTotal - $request->get('total_amount'),
          ], 422);
      }
  }
  ```
- Добавить импорт: `use App\Models\DeliverySetting;`

**3. Frontend Mini App: Добавить проверку на шаге оформления**

**Файл**: `frontend/src/pages/miniapp/CheckoutPage.tsx`

Изменения:
- Добавить состояние для `minDeliveryOrderTotal` (useState)
- Загрузить `min_delivery_order_total_rub` из API настроек доставки при монтировании (если еще не загружается)
- Добавить проверку перед переходом к следующему шагу:
  - Если `deliveryType === 'courier'` и `totalAmount < minDeliveryOrderTotal`:
    - Показать alert/плашку с сообщением
    - Disable кнопку "Далее/Оформить"
    - Показать "Добавьте еще на X ₽" (X = min - totalAmount)
    - Показать кнопку "Переключиться на самовывоз"
- Добавить проверку при изменении типа доставки или суммы корзины

**4. Frontend API: Добавить min_delivery_order_total_rub в ответ**

**Файл**: `frontend/src/api/index.ts`
- Убедиться, что `deliverySettingsAPI.getSettings()` возвращает `min_delivery_order_total_rub`

---

## C) ADMIN PANEL — НАСТРОЙКИ И РЕДАКТОР «О НАС»

### C1) Настройки доставки: добавить min_delivery_order_total_rub

#### Шаги реализации

**1. Frontend Admin Panel (React): Обновить DeliverySettings**

**Файл**: `frontend/src/pages/admin/DeliverySettings.tsx`

Изменения:
- Добавить поле `min_delivery_order_total_rub` в состояние `formData` (default: 3000)
- Загрузить значение из API при `loadSettings()`
- Добавить в форму после блока "is_enabled":
  ```tsx
  <div>
    <Label htmlFor="min_delivery_order_total_rub">
      Минимальный заказ для доставки (₽) *
    </Label>
    <Input
      id="min_delivery_order_total_rub"
      type="number"
      min="0"
      step="0.01"
      placeholder="3000"
      value={formData.min_delivery_order_total_rub}
      onChange={(e) => setFormData({ 
        ...formData, 
        min_delivery_order_total_rub: parseFloat(e.target.value) || 0 
      })}
      className="mt-1"
      required
    />
    <p className="mt-1 text-sm text-muted-foreground">
      Минимальная сумма заказа для оформления доставки курьером
    </p>
  </div>
  ```
- Добавить в `handleSubmit()` для отправки на сервер

**2. Проверить Vue.js админку (если используется)**

**Файл**: `resources/js/pages/admin/DeliverySettings.vue`
- Аналогично добавить поле в форму (если этот файл еще используется)

---

### C2) Раздел «О нас» в админке: добавить поле support_telegram_url

#### Шаги реализации

**1. Frontend Admin Panel (React): Обновить AdminAbout**

**Файл**: `frontend/src/pages/admin/AdminAbout.tsx`

Изменения:
- Добавить поле `support_telegram_url` в состояние `formData`
- Загрузить значение из API в `loadData()`
- Добавить в форму после блока "Yandex Maps":
  ```tsx
  {/* Support Telegram */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Поддержка</CardTitle>
              <CardDescription>
                Ссылка на Telegram для поддержки клиентов
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div>
                <Label htmlFor="support_telegram_url">URL Telegram поддержки</Label>
                <Input
                  id="support_telegram_url"
                  type="url"
                  placeholder="https://t.me/+79826824368"
                  value={formData.support_telegram_url}
                  onChange={(e) => setFormData({ ...formData, support_telegram_url: e.target.value })}
                  className="mt-1"
                />
                <p className="mt-1 text-sm text-muted-foreground">
                  Ссылка будет отображаться в блоке "Поддержка" на странице "О нас"
                </p>
              </div>
            </CardContent>
          </Card>
  ```
- Добавить в `handleSubmit()` для отправки на сервер

---

## Последовательность выполнения

### Этап 1: Backend изменения
1. ✅ Создать миграцию для `support_telegram_url` в `about_page`
2. ✅ Обновить модель `AboutPage`
3. ✅ Обновить контроллер `AboutPageController`
4. ✅ Создать миграцию для `min_delivery_order_total_rub` в `delivery_settings`
5. ✅ Обновить модель `DeliverySetting`
6. ✅ Обновить контроллер `DeliverySettingsController`
7. ✅ Обновить `DeliverySettingsRequest`
8. ✅ Добавить проверку в `OrderController::store()`

### Этап 2: Frontend Mini App
1. ✅ Обновить `AboutPage.tsx` (копирование телефона, "Показать больше", блок поддержки)
2. ✅ Обновить `toast.tsx` (позиционирование вверху)
3. ✅ Обновить `CheckoutPage.tsx` (проверка минимальной суммы доставки)

### Этап 3: Frontend Admin Panel
1. ✅ Обновить `AdminAbout.tsx` (поле support_telegram_url)
2. ✅ Обновить `DeliverySettings.tsx` (поле min_delivery_order_total_rub)

### Этап 4: Тестирование
1. ✅ Проверить страницу "О нас" в Mini App:
   - Баннер с плейсхолдером
   - Копирование телефона
   - "Показать больше/Скрыть" для description и bullets
   - Блок "Поддержка" с Telegram
2. ✅ Проверить уведомления "Добавлено в корзину" (верхнее позиционирование)
3. ✅ Проверить минимальный заказ для доставки:
   - Оформление заказа < 3000₽ с доставкой (должна быть ошибка)
   - Оформление заказа >= 3000₽ с доставкой (должно работать)
   - Самовывоз без ограничений
4. ✅ Проверить админку:
   - Редактирование "О нас" с полем support_telegram_url
   - Редактирование настроек доставки с min_delivery_order_total_rub

---

## Важные замечания

1. **Совместимость**: Все изменения должны быть обратно совместимы. Если поля отсутствуют в БД — использовать значения по умолчанию.

2. **Валидация**: На фронтенде и бэкенде должны быть одинаковые проверки.

3. **Телефон для копирования**: Использовать нативный API `navigator.clipboard.writeText()` с fallback для старых браузеров.

4. **Toast позиционирование**: Убедиться, что toast не перекрывает заголовок Mini App (safe-area-top).

5. **Минимальный заказ**: Проверка должна быть на фронтенде (для UX) и на бэкенде (для безопасности).

6. **Миграции**: Создавать с актуальной датой в имени файла.

7. **Значения по умолчанию**: 
   - `support_telegram_url`: `'https://t.me/+79826824368'`
   - `min_delivery_order_total_rub`: `3000`
   - `yandex_maps_url`: `'https://yandex.ru/maps/-/CLRQaBlB'` (если еще не установлено)

---

## Файлы для создания/изменения

### Создать:
- `database/migrations/XXXX_XX_XX_add_support_telegram_url_to_about_page.php`
- `database/migrations/XXXX_XX_XX_add_min_delivery_order_total_rub_to_delivery_settings.php`

### Изменить:
**Backend:**
- `app/Models/AboutPage.php`
- `app/Http/Controllers/Api/v1/AboutPageController.php`
- `app/Models/DeliverySetting.php`
- `app/Http/Controllers/Api/v1/DeliverySettingsController.php`
- `app/Http/Requests/DeliverySettingsRequest.php`
- `app/Http/Controllers/Api/v1/OrderController.php`

**Frontend Mini App:**
- `frontend/src/pages/miniapp/AboutPage.tsx`
- `frontend/src/components/ui/toast.tsx`
- `frontend/src/pages/miniapp/CheckoutPage.tsx`

**Frontend Admin Panel:**
- `frontend/src/pages/admin/AdminAbout.tsx`
- `frontend/src/pages/admin/DeliverySettings.tsx`

---

## Критерии готовности

✅ Все миграции выполнены  
✅ Все API endpoints возвращают новые поля  
✅ Страница "О нас" отображает все новые функции  
✅ Toast уведомления показываются сверху  
✅ Минимальный заказ для доставки работает на фронтенде и бэкенде  
✅ Админка позволяет редактировать все новые поля  
✅ Все изменения протестированы

