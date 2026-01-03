# План разработки админ-панели для управления каталогом товаров

## ⚠️ ВАЖНО: Структура проекта

**Админ-панель** создается в **Vue 3** (`resources/js/pages/admin`) - это раздел `/admin/*`  
**MiniApp для пользователей** - это **React** (`frontend/`) - это раздел `/` (корень)

**Все страницы админ-панели должны быть в Vue, а не React!**

**⚠️ ИСПРАВЛЕНИЯ В ПЛАНЕ:**
- Все упоминания `frontend/src/pages/admin/*.tsx` в пунктах 3.x следует заменить на `resources/js/pages/admin/*.vue`
- Валидация форм в Vue должна использовать встроенные методы или библиотеки для Vue (не react-hook-form/zod)
- Использовать существующий компонент `Media.vue` вместо создания нового `MediaSelector.tsx`
- Роуты настраиваются в `resources/js/router/index.js` (Vue Router), а не в React Router

## Обзор проекта
Админ-панель для управления полным жизненным циклом товаров, категорий, заказов в **Vue 3 приложении** (`resources/js`).  
Проект состоит из двух частей:
- **Vue админ-панель** (`/admin/*`) - управление каталогом, заказами, настройками
- **React MiniApp** (`/frontend`) - интерфейс для пользователей Telegram бота

## Технологии
- **Админ-панель:** Vue 3 + TypeScript + Vite
- **MiniApp:** React 18 + TypeScript
- **Backend:** Laravel 11 + PHP 8.2+
- **API:** Laravel Backend (`/api/v1/*`)
- **Медиа:** Использование медиа-библиотеки через API `/api/v1/media`
- **База данных:** MySQL/PostgreSQL (Laravel)
- **Интеграция:** Telegram Bot API, Telegram Mini App API

---

## Этап 1: Подготовка инфраструктуры

### Пункт 1.1: Создание API утилит для админ-панели
**Статус:** ✅ Создан план

**Задачи:**
- Создать `frontend/src/api/admin.ts` с базовыми функциями для работы с Laravel API
- Реализовать функции для работы с медиа-библиотекой
- Настроить базовые утилиты для валидации
- Подключить обработку ошибок и токены авторизации

**Файлы для создания/изменения:**
- `frontend/src/api/admin.ts` (новый)
- `frontend/src/api/index.ts` (обновить)

**API Endpoints для использования:**
- `/api/v1/media` - получение списка медиа-файлов
- `/api/v1/media/{id}` - получение конкретного медиа-файла
- `/api/v1/folders` - работа с папками медиа

**Детали реализации:**
- Использовать fetch или axios для запросов
- Добавить interceptors для добавления токена авторизации
- Реализовать функции: `getMediaFiles()`, `getMediaById()`, `selectMediaFile()`

---

### Пункт 1.2: Создание компонента выбора медиа из медиа-библиотеки
**Статус:** ✅ Создан план

**Задачи:**
- Создать компонент `MediaSelector.tsx` для выбора фото/видео из медиа-библиотеки
- Реализовать модальное окно с просмотром медиа-файлов
- Добавить возможность фильтрации по типу (фото/видео)
- Реализовать выбор одного или нескольких файлов
- Интеграция с API `/api/v1/media`

**Файлы для создания:**
- `frontend/src/components/admin/MediaSelector.tsx` (новый)

**Функциональность:**
- Отображение сетки медиа-файлов
- Фильтрация по типу (photo, video, document)
- Поиск по названию файла
- Выбор файла с предпросмотром
- Возврат выбранных файлов через callback
- Отображение миниатюр изображений
- Для видео - отображение иконки плейера

**Props компонента:**
```typescript
interface MediaSelectorProps {
  open: boolean;
  onClose: () => void;
  onSelect: (media: MediaFile) => void;
  multiple?: boolean;
  allowedTypes?: ('photo' | 'video' | 'document')[];
  currentSelection?: string[]; // IDs выбранных файлов
}
```

---

## Этап 2: Backend API для управления каталогом

### Пункт 2.1: Создание Laravel API для категорий
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `Category` в Laravel
- Создать миграцию для таблицы `categories`
- Создать контроллер `CategoryController` с полным CRUD
- Добавить валидацию для создания и обновления категорий
- Добавить роуты в `routes/api.php`

**Файлы для создания/изменения:**
- `app/Models/Category.php` (новый)
- `database/migrations/XXXX_create_categories_table.php` (новый)
- `app/Http/Controllers/Api/v1/CategoryController.php` (новый)
- `app/Http/Requests/CategoryRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/categories` - список всех категорий
- `GET /api/v1/categories/{id}` - детали категории
- `POST /api/v1/categories` - создание категории
- `PUT /api/v1/categories/{id}` - обновление категории
- `DELETE /api/v1/categories/{id}` - удаление категории

**Поля категории:**
- `id` (UUID)
- `name` (string, required, unique)
- `slug` (string, unique, auto-generate из name)
- `description` (text, nullable)
- `image_id` (integer, nullable, FK к media)
- `sort_order` (integer, default 0)
- `is_active` (boolean, default true)
- `created_at`, `updated_at`

**Валидация:**
- При создании: `name` обязательное, уникальное, мин 2 символа, макс 255
- При обновлении: `name` обязательное, уникальное (кроме текущей), мин 2, макс 255
- `image_id` должен существовать в таблице media (если указан)
- `sort_order` число, >= 0

---

### Пункт 2.2: Создание Laravel API для товаров/продуктов
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `Product` в Laravel
- Создать миграцию для таблицы `products`
- Создать контроллер `ProductController` с полным CRUD
- Добавить валидацию для создания и обновления товаров
- Добавить роуты в `routes/api.php`
- Создать связь с категориями и медиа

**Файлы для создания/изменения:**
- `app/Models/Product.php` (новый)
- `database/migrations/XXXX_create_products_table.php` (новый)
- `app/Http/Controllers/Api/v1/ProductController.php` (новый)
- `app/Http/Requests/ProductRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/products` - список товаров (с фильтрацией и пагинацией)
- `GET /api/v1/products/{id}` - детали товара
- `POST /api/v1/products` - создание товара
- `PUT /api/v1/products/{id}` - обновление товара
- `DELETE /api/v1/products/{id}` - удаление товара

**Поля товара:**
- `id` (UUID)
- `name` (string, required)
- `slug` (string, unique, auto-generate из name)
- `description` (text, nullable)
- `short_description` (string, nullable)
- `price` (decimal 10,2, required)
- `compare_price` (decimal 10,2, nullable) - цена до скидки
- `category_id` (UUID, nullable, FK к categories)
- `sku` (string, unique, nullable) - артикул
- `barcode` (string, nullable) - штрих-код
- `stock_quantity` (integer, default 0) - остаток на складе
- `is_available` (boolean, default true)
- `is_weight_product` (boolean, default false) - весовой товар
- `weight` (decimal 8,2, nullable) - вес в кг
- `image_id` (integer, nullable, FK к media) - главное изображение
- `gallery_ids` (json, nullable) - массив ID изображений из медиа
- `video_id` (integer, nullable, FK к media) - видео товара
- `sort_order` (integer, default 0)
- `meta_title` (string, nullable)
- `meta_description` (text, nullable)
- `created_at`, `updated_at`

**Валидация:**
- При создании: `name` обязательное, мин 2 символа, макс 255
- `price` обязательное, число >= 0
- `category_id` должен существовать в categories (если указан)
- `image_id`, `video_id`, элементы `gallery_ids` должны существовать в media
- `stock_quantity` число >= 0
- `sku` уникальный (если указан)
- `barcode` уникальный (если указан)

---

### Пункт 2.3: Создание Laravel API для истории изменений товаров
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `ProductHistory` для отслеживания изменений товаров
- Создать миграцию для таблицы `product_history`
- Реализовать автоматическое логирование изменений через события модели Product
- Создать контроллер для получения истории товара

**Файлы для создания/изменения:**
- `app/Models/ProductHistory.php` (новый)
- `database/migrations/XXXX_create_product_history_table.php` (новый)
- `app/Observers/ProductObserver.php` (новый) - для автоматического логирования
- `app/Http/Controllers/Api/v1/ProductHistoryController.php` (новый)
- `app/Providers/AppServiceProvider.php` (обновить - зарегистрировать Observer)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/products/{id}/history` - история изменений товара

**Поля истории:**
- `id` (UUID)
- `product_id` (UUID, FK к products)
- `user_id` (integer, nullable, FK к users) - кто внес изменение
- `action` (string) - 'created', 'updated', 'deleted', 'restored'
- `field_name` (string, nullable) - какое поле изменилось
- `old_value` (text, nullable) - старое значение (JSON для объектов)
- `new_value` (text, nullable) - новое значение (JSON для объектов)
- `changes` (json, nullable) - все изменения одним объектом
- `created_at`

**Логируемые события:**
- Создание товара
- Изменение любого поля товара
- Удаление товара
- Восстановление товара
- Изменение цены
- Изменение остатка на складе
- Изменение статуса доступности

---

### Пункт 2.4: Расширение Laravel API для заказов (управление)
**Статус:** ✅ Создан план

**Задачи:**
- Расширить модель `Order` в Laravel (если не существует)
- Создать/обновить миграцию для таблицы `orders`
- Создать контроллер `OrderController` с полным управлением заказами
- Добавить валидацию для обновления заказов
- Добавить фильтрацию и поиск заказов
- Добавить роуты в `routes/api.php`

**Файлы для создания/изменения:**
- `app/Models/Order.php` (новый или обновить)
- `database/migrations/XXXX_create_orders_table.php` (новый или обновить)
- `app/Http/Controllers/Api/v1/OrderController.php` (новый)
- `app/Http/Requests/UpdateOrderRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/orders` - список заказов (с фильтрацией, поиском, пагинацией)
- `GET /api/v1/orders/{id}` - детали заказа
- `PUT /api/v1/orders/{id}` - обновление заказа
- `PUT /api/v1/orders/{id}/status` - изменение статуса заказа
- `DELETE /api/v1/orders/{id}` - удаление заказа (soft delete)

**Дополнительные поля для заказа (если нужно расширить):**
- `delivery_type` (string) - 'courier', 'pickup', 'self_delivery'
- `delivery_cost` (decimal 10,2, default 0)
- `delivery_date` (date, nullable)
- `delivery_time_from` (time, nullable)
- `delivery_time_to` (time, nullable)
- `notes` (text, nullable) - внутренние заметки
- `manager_id` (integer, nullable, FK к users) - назначенный менеджер

**Валидация:**
- Статус заказа из списка допустимых значений
- Невозможность изменить статус "delivered" или "cancelled" на другие статусы
- Проверка корректности сумм заказа
- Валидация данных доставки

---

### Пункт 2.5: Создание Laravel API для управления доставками
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `Delivery` для отслеживания доставок
- Создать миграцию для таблицы `deliveries`
- Создать контроллер `DeliveryController` с управлением доставками
- Связать доставки с заказами
- Добавить статусы доставки

**Файлы для создания/изменения:**
- `app/Models/Delivery.php` (новый)
- `database/migrations/XXXX_create_deliveries_table.php` (новый)
- `app/Http/Controllers/Api/v1/DeliveryController.php` (новый)
- `app/Http/Requests/DeliveryRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/deliveries` - список доставок
- `GET /api/v1/deliveries/{id}` - детали доставки
- `POST /api/v1/deliveries` - создание записи о доставке
- `PUT /api/v1/deliveries/{id}` - обновление доставки
- `PUT /api/v1/deliveries/{id}/status` - изменение статуса доставки
- `GET /api/v1/orders/{orderId}/delivery` - доставка для заказа

**Поля доставки:**
- `id` (UUID)
- `order_id` (UUID, FK к orders)
- `delivery_type` (string) - 'courier', 'pickup', 'self_delivery'
- `status` (string) - 'pending', 'assigned', 'in_transit', 'delivered', 'failed', 'returned'
- `courier_name` (string, nullable)
- `courier_phone` (string, nullable)
- `delivery_address` (text, required)
- `delivery_date` (date, nullable)
- `delivery_time_from` (time, nullable)
- `delivery_time_to` (time, nullable)
- `delivered_at` (timestamp, nullable)
- `delivery_cost` (decimal 10,2, default 0)
- `notes` (text, nullable)
- `tracking_number` (string, nullable, unique)
- `created_at`, `updated_at`

**Валидация:**
- `order_id` обязательное, должен существовать
- `delivery_type` из списка допустимых значений
- `status` из списка допустимых значений
- `delivery_date` должна быть в будущем или сегодня
- `tracking_number` уникальный (если указан)

---

### Пункт 2.6: Создание Laravel API для управления оплатами
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `Payment` для отслеживания оплат заказов
- Создать миграцию для таблицы `payments`
- Создать контроллер `PaymentController` с управлением платежами
- Связать платежи с заказами
- Добавить статусы и методы оплаты

**Файлы для создания/изменения:**
- `app/Models/Payment.php` (новый)
- `database/migrations/XXXX_create_payments_table.php` (новый)
- `app/Http/Controllers/Api/v1/PaymentController.php` (новый)
- `app/Http/Requests/PaymentRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/payments` - список платежей
- `GET /api/v1/payments/{id}` - детали платежа
- `POST /api/v1/payments` - создание записи о платеже
- `PUT /api/v1/payments/{id}` - обновление платежа
- `PUT /api/v1/payments/{id}/status` - изменение статуса платежа
- `POST /api/v1/payments/{id}/refund` - возврат платежа
- `GET /api/v1/orders/{orderId}/payments` - платежи для заказа

**Поля платежа:**
- `id` (UUID)
- `order_id` (UUID, FK к orders)
- `payment_method` (string) - 'card', 'cash', 'online', 'bank_transfer', 'other'
- `payment_provider` (string, nullable) - 'stripe', 'paypal', 'yookassa', 'sberbank', etc.
- `status` (string) - 'pending', 'processing', 'succeeded', 'failed', 'refunded', 'partially_refunded', 'cancelled'
- `amount` (decimal 10,2, required) - сумма платежа
- `currency` (string, default 'RUB')
- `transaction_id` (string, nullable, unique) - ID транзакции в платежной системе
- `provider_response` (json, nullable) - ответ от платежной системы
- `paid_at` (timestamp, nullable)
- `refunded_amount` (decimal 10,2, default 0)
- `refunded_at` (timestamp, nullable)
- `notes` (text, nullable)
- `created_at`, `updated_at`

**Валидация:**
- `order_id` обязательное, должен существовать
- `payment_method` из списка допустимых значений
- `status` из списка допустимых значений
- `amount` обязательное, число > 0
- `transaction_id` уникальный (если указан)
- `refunded_amount` <= `amount`
- Невозможность возврата больше суммы платежа

---

### Пункт 2.7: Создание Laravel API для управления возвратами
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `Return` для отслеживания возвратов товаров
- Создать миграцию для таблицы `returns`
- Создать контроллер `ReturnController` с управлением возвратами
- Связать возвраты с заказами и товарами
- Добавить статусы возврата и причины

**Файлы для создания/изменения:**
- `app/Models/Return.php` (новый)
- `database/migrations/XXXX_create_returns_table.php` (новый)
- `app/Http/Controllers/Api/v1/ReturnController.php` (новый)
- `app/Http/Requests/ReturnRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/returns` - список возвратов
- `GET /api/v1/returns/{id}` - детали возврата
- `POST /api/v1/returns` - создание заявки на возврат
- `PUT /api/v1/returns/{id}` - обновление возврата
- `PUT /api/v1/returns/{id}/status` - изменение статуса возврата
- `POST /api/v1/returns/{id}/approve` - одобрение возврата
- `POST /api/v1/returns/{id}/reject` - отклонение возврата
- `GET /api/v1/orders/{orderId}/returns` - возвраты для заказа

**Поля возврата:**
- `id` (UUID)
- `order_id` (UUID, FK к orders)
- `return_number` (string, unique) - номер возврата (RET-YYYYMMDD-XXX)
- `status` (string) - 'pending', 'approved', 'rejected', 'in_transit', 'received', 'refunded', 'cancelled'
- `reason` (text, required) - причина возврата
- `reason_type` (string) - 'defect', 'wrong_item', 'not_as_described', 'changed_mind', 'other'
- `items` (json, required) - массив товаров для возврата с количеством
- `total_amount` (decimal 10,2, required) - сумма возврата
- `refund_method` (string, nullable) - 'original', 'store_credit', 'exchange'
- `refund_status` (string, nullable) - 'pending', 'processing', 'completed', 'failed'
- `refunded_at` (timestamp, nullable)
- `notes` (text, nullable) - внутренние заметки
- `customer_notes` (text, nullable) - заметки от клиента
- `processed_by` (integer, nullable, FK к users) - кто обработал возврат
- `processed_at` (timestamp, nullable)
- `created_at`, `updated_at`

**Валидация:**
- `order_id` обязательное, должен существовать
- `reason` обязательное, мин 10 символов
- `reason_type` из списка допустимых значений
- `status` из списка допустимых значений
- `items` должен содержать хотя бы один товар
- Количество возвращаемых товаров не может превышать количество в заказе
- `total_amount` >= 0 и <= суммы заказа

---

### Пункт 2.8: Создание Laravel API для управления претензиями
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `Complaint` для отслеживания претензий клиентов
- Создать миграцию для таблицы `complaints`
- Создать контроллер `ComplaintController` с управлением претензиями
- Связать претензии с заказами
- Добавить категории претензий и статусы обработки

**Файлы для создания/изменения:**
- `app/Models/Complaint.php` (новый)
- `database/migrations/XXXX_create_complaints_table.php` (новый)
- `app/Http/Controllers/Api/v1/ComplaintController.php` (новый)
- `app/Http/Requests/ComplaintRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/complaints` - список претензий
- `GET /api/v1/complaints/{id}` - детали претензии
- `POST /api/v1/complaints` - создание претензии
- `PUT /api/v1/complaints/{id}` - обновление претензии
- `PUT /api/v1/complaints/{id}/status` - изменение статуса претензии
- `POST /api/v1/complaints/{id}/comments` - добавление комментария к претензии
- `GET /api/v1/orders/{orderId}/complaints` - претензии для заказа

**Поля претензии:**
- `id` (UUID)
- `order_id` (UUID, nullable, FK к orders)
- `complaint_number` (string, unique) - номер претензии (COMP-YYYYMMDD-XXX)
- `type` (string, required) - 'quality', 'delivery', 'service', 'payment', 'other'
- `priority` (string, default 'medium') - 'low', 'medium', 'high', 'urgent'
- `status` (string) - 'new', 'in_progress', 'resolved', 'rejected', 'closed'
- `subject` (string, required) - тема претензии
- `description` (text, required) - описание проблемы
- `customer_name` (string, nullable)
- `customer_phone` (string, nullable)
- `customer_email` (string, nullable)
- `attachments` (json, nullable) - массив ID файлов из медиа-библиотеки
- `assigned_to` (integer, nullable, FK к users) - назначенный сотрудник
- `resolution` (text, nullable) - решение по претензии
- `resolved_at` (timestamp, nullable)
- `resolved_by` (integer, nullable, FK к users)
- `closed_at` (timestamp, nullable)
- `created_at`, `updated_at`

**Валидация:**
- `type` из списка допустимых значений
- `priority` из списка допустимых значений
- `status` из списка допустимых значений
- `subject` обязательное, мин 5 символов, макс 255
- `description` обязательное, мин 20 символов
- `attachments` должны существовать в медиа-библиотеке
- `order_id` должен существовать (если указан)

---

### Пункт 2.9: Создание Laravel API для управления отзывами
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `Review` для управления отзывами клиентов
- Создать миграцию для таблицы `reviews`
- Создать контроллер `ReviewController` с управлением отзывами
- Связать отзывы с заказами и товарами
- Добавить модерацию отзывов

**Файлы для создания/изменения:**
- `app/Models/Review.php` (новый)
- `database/migrations/XXXX_create_reviews_table.php` (новый)
- `app/Http/Controllers/Api/v1/ReviewController.php` (новый)
- `app/Http/Requests/ReviewRequest.php` (новый - валидация)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/reviews` - список отзывов (с фильтрацией)
- `GET /api/v1/reviews/{id}` - детали отзыва
- `POST /api/v1/reviews` - создание отзыва
- `PUT /api/v1/reviews/{id}` - обновление отзыва
- `PUT /api/v1/reviews/{id}/status` - изменение статуса (модерация)
- `DELETE /api/v1/reviews/{id}` - удаление отзыва
- `GET /api/v1/products/{productId}/reviews` - отзывы для товара
- `GET /api/v1/orders/{orderId}/review` - отзыв для заказа

**Поля отзыва:**
- `id` (UUID)
- `order_id` (UUID, nullable, FK к orders)
- `product_id` (UUID, nullable, FK к products)
- `rating` (integer, required) - оценка от 1 до 5
- `title` (string, nullable) - заголовок отзыва
- `comment` (text, required) - текст отзыва
- `customer_name` (string, required)
- `customer_phone` (string, nullable)
- `customer_email` (string, nullable)
- `status` (string, default 'pending') - 'pending', 'approved', 'rejected', 'hidden'
- `is_verified_purchase` (boolean, default false) - подтвержденная покупка
- `helpful_count` (integer, default 0) - количество "полезно"
- `photos` (json, nullable) - массив ID изображений из медиа-библиотеки
- `response` (text, nullable) - ответ компании на отзыв
- `responded_at` (timestamp, nullable)
- `responded_by` (integer, nullable, FK к users)
- `moderated_at` (timestamp, nullable)
- `moderated_by` (integer, nullable, FK к users)
- `created_at`, `updated_at`

**Валидация:**
- `rating` обязательное, число от 1 до 5
- `comment` обязательное, мин 10 символов, макс 2000 символов
- `customer_name` обязательное, мин 2 символа
- `status` из списка допустимых значений
- `photos` должны существовать в медиа-библиотеке
- `order_id` или `product_id` должны быть указаны
- Один клиент может оставить только один отзыв на заказ/товар

---

### Пункт 2.10: Создание Laravel API для настроек платежной системы ЮКасса
**Статус:** ✅ Создан план

**Задачи:**
- Создать модель `PaymentSettings` для хранения настроек ЮКасса
- Создать миграцию для таблицы `payment_settings`
- Создать контроллер `PaymentSettingsController` для управления настройками
- Реализовать безопасное хранение секретных ключей
- Добавить валидацию настроек
- Создать сервис для работы с API ЮКасса

**Файлы для создания/изменения:**
- `app/Models/PaymentSettings.php` (новый)
- `database/migrations/XXXX_create_payment_settings_table.php` (новый)
- `app/Http/Controllers/Api/v1/PaymentSettingsController.php` (новый)
- `app/Http/Requests/PaymentSettingsRequest.php` (новый - валидация)
- `app/Services/Payment/YooKassaService.php` (новый - сервис для работы с ЮКасса)
- `routes/api.php` (обновить)

**API Endpoints:**
- `GET /api/v1/payment-settings` - получить настройки (без секретных ключей)
- `GET /api/v1/payment-settings/yookassa` - получить настройки ЮКасса
- `PUT /api/v1/payment-settings/yookassa` - обновить настройки ЮКасса
- `POST /api/v1/payment-settings/yookassa/test` - тестирование подключения
- `POST /api/v1/payment-settings/yookassa/webhook` - webhook от ЮКасса

**Поля настроек ЮКасса:**
- `id` (UUID)
- `shop_id` (string, required) - ID магазина в ЮКасса
- `secret_key` (encrypted, required) - секретный ключ (шифруется)
- `is_test_mode` (boolean, default true) - тестовый режим
- `is_enabled` (boolean, default false) - включена ли интеграция
- `webhook_url` (string, nullable) - URL для webhook
- `payment_methods` (json, nullable) - разрешенные методы оплаты
- `auto_capture` (boolean, default true) - автоматическое подтверждение платежей
- `description_template` (string, nullable) - шаблон описания платежа
- `test_shop_id` (string, nullable) - ID магазина для тестов
- `test_secret_key` (encrypted, nullable) - секретный ключ для тестов
- `last_test_at` (timestamp, nullable) - дата последнего теста
- `last_test_result` (json, nullable) - результат последнего теста
- `created_at`, `updated_at`

**Валидация:**
- `shop_id` обязательное, не пустое
- `secret_key` обязательное, мин 20 символов
- `webhook_url` валидный URL (если указан)
- Проверка формата shop_id и secret_key через тестовый запрос к API ЮКасса

**Функциональность YooKassaService:**
- Создание платежа
- Получение статуса платежа
- Подтверждение платежа (capture)
- Отмена платежа (cancel)
- Возврат платежа (refund)
- Обработка webhook от ЮКасса
- Проверка подписи webhook

---

### Пункт 2.11: Интеграция с Telegram ботом для MiniApp
**Статус:** ✅ Создан план

**Задачи:**
- Создать API endpoints для взаимодействия MiniApp с ботом
- Реализовать валидацию Telegram initData
- Создать сервис для отправки уведомлений через бота
- Интегрировать создание заказов из MiniApp с уведомлениями в боте
- Настроить обработку событий от бота (изменение статусов заказов)

**Файлы для создания/изменения:**
- `app/Services/Telegram/TelegramMiniAppService.php` (новый)
- `app/Http/Controllers/Api/v1/TelegramController.php` (новый)
- `app/Http/Middleware/ValidateTelegramInitData.php` (новый - middleware для валидации)
- `routes/api.php` (обновить)

**API Endpoints:**
- `POST /api/v1/telegram/validate-init-data` - валидация initData от Telegram
- `POST /api/v1/telegram/notify-order` - отправка уведомления о новом заказе в бот
- `GET /api/v1/telegram/user-info` - получение информации о пользователе из initData

**Функциональность TelegramMiniAppService:**
- Валидация initData от Telegram Mini App
- Получение данных пользователя (telegram_id, username, first_name)
- Создание/обновление пользователя в БД при входе в MiniApp
- Отправка уведомлений в бот о статусах заказов
- Отправка уведомлений о новых заказах администраторам
- Связь заказов с telegram_id пользователя

**Интеграция с BotController:**
- При создании заказа через MiniApp - отправка уведомления в бот
- При изменении статуса заказа - уведомление клиента через бота
- При создании доставки - уведомление о статусе доставки
- При оплате - уведомление об успешной оплате

**Поля для связи с ботом в Order:**
- `telegram_id` (bigint, required) - ID пользователя Telegram
- `bot_id` (integer, nullable, FK к bots) - ID бота, через который создан заказ
- `notification_sent` (boolean, default false) - отправлено ли уведомление

---

## Этап 3: Frontend страницы админ-панели (Vue 3, отдельные страницы, не попапы)

### Пункт 3.1: Создание страницы управления категориями (CRUD)
**Статус:** ✅ Создан план

**Задачи:**
- Обновить существующую страницу `Categories.vue` или создать новую
- Убрать диалоги (Dialog), сделать отдельные страницы для создания/редактирования
- Реализовать интеграцию с Laravel API `/api/v1/categories`
- Добавить валидацию форм (использовать валидацию Vue или создать свою)
- Интегрировать выбор изображения через компонент Media (существующий)

**Файлы для создания/изменения:**
- `resources/js/pages/admin/Categories.vue` (обновить)
- `resources/js/pages/admin/CategoryCreate.vue` (новый)
- `resources/js/pages/admin/CategoryEdit.vue` (новый)
- `resources/js/utils/api.js` (обновить - добавить функции для категорий)
- `resources/js/router/index.js` (обновить - добавить роуты)

**Роуты:**
- `/admin/categories` - список категорий
- `/admin/categories/create` - создание категории
- `/admin/categories/:id/edit` - редактирование категории

**Функциональность страницы списка:**
- Таблица/карточки категорий с изображениями
- Поиск по названию
- Фильтрация по статусу (активные/неактивные)
- Сортировка по названию, дате создания, порядку сортировки
- Кнопка "Создать категорию" ведет на `/admin/categories/create`
- Кнопка "Редактировать" ведет на `/admin/categories/:id/edit`
- Модальное окно подтверждения удаления (можно оставить)

**Функциональность страницы создания:**
- Форма с полями: name, description, image (выбор из медиа), sort_order, is_active
- Валидация: name обязательное, уникальное, мин 2 символа
- Кнопка "Выбрать изображение" открывает MediaSelector
- Кнопка "Сохранить" с валидацией
- Кнопка "Отмена" возвращает на список

**Функциональность страницы редактирования:**
- Загрузка данных категории по ID
- Форма с предзаполненными данными
- Валидация при сохранении
- Отображение истории изменений (если есть)

---

### Пункт 3.2: Создание страницы управления товарами (CRUD)
**Статус:** ✅ Создан план

**Задачи:**
- Обновить существующую страницу `AdminProducts.tsx` или создать новую
- Убрать диалоги, сделать отдельные страницы для создания/редактирования
- Реализовать интеграцию с Laravel API `/api/v1/products`
- Добавить полную валидацию форм
- Интегрировать выбор изображений и видео через MediaSelector
- Добавить отображение истории изменений товара

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/AdminProducts.tsx` (обновить)
- `frontend/src/pages/admin/ProductCreate.tsx` (новый)
- `frontend/src/pages/admin/ProductEdit.tsx` (новый)
- `frontend/src/pages/admin/ProductHistory.tsx` (новый - история товара)
- `frontend/src/api/admin.ts` (обновить - добавить функции для товаров)
- `frontend/src/App.tsx` (обновить - добавить роуты)

**Роуты:**
- `/admin/products` - список товаров
- `/admin/products/create` - создание товара
- `/admin/products/:id/edit` - редактирование товара
- `/admin/products/:id/history` - история изменений товара

**Функциональность страницы списка:**
- Таблица/карточки товаров с изображениями
- Поиск по названию, артикулу, штрих-коду
- Фильтрация по категории, статусу доступности, наличию на складе
- Сортировка по названию, цене, дате создания, остатку
- Кнопка "Создать товар" ведет на `/admin/products/create`
- Кнопка "Редактировать" ведет на `/admin/products/:id/edit`
- Кнопка "История" ведет на `/admin/products/:id/history`
- Модальное окно подтверждения удаления

**Функциональность страницы создания:**
- Форма с полями:
  - Основная информация: name, description, short_description, category
  - Цены: price, compare_price
  - Склад: sku, barcode, stock_quantity, is_available
  - Характеристики: is_weight_product, weight
  - Медиа: image (главное), gallery (массив изображений), video
  - SEO: meta_title, meta_description
  - Прочее: sort_order
- Валидация всех полей согласно требованиям backend
- Выбор изображений через MediaSelector (multiple для галереи)
- Выбор видео через MediaSelector (только видео)
- Кнопка "Сохранить" с валидацией
- Кнопка "Отмена" возвращает на список

**Функциональность страницы редактирования:**
- Загрузка данных товара по ID
- Форма с предзаполненными данными
- Все поля как в создании
- Валидация при сохранении
- Отображение текущих изображений с возможностью удаления

**Функциональность страницы истории:**
- Список всех изменений товара с датами
- Фильтрация по типу изменения (created, updated, deleted)
- Отображение кто и когда внес изменения
- Отображение измененных полей с старыми и новыми значениями

---

### Пункт 3.3: Создание страницы управления заказами
**Статус:** ✅ Создан план

**Задачи:**
- Обновить существующую страницу `AdminOrders.tsx`
- Убрать диалоги, сделать отдельную страницу для детального просмотра/редактирования
- Реализовать интеграцию с Laravel API `/api/v1/orders`
- Добавить фильтрацию, поиск, сортировку
- Отображение связанных данных: доставка, платежи, возвраты, претензии, отзывы

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/AdminOrders.tsx` (обновить)
- `frontend/src/pages/admin/OrderDetail.tsx` (новый - детальная страница заказа)
- `frontend/src/api/admin.ts` (обновить - добавить функции для заказов)
- `frontend/src/App.tsx` (обновить - добавить роут)

**Роуты:**
- `/admin/orders` - список заказов
- `/admin/orders/:id` - детальная информация о заказе

**Функциональность страницы списка:**
- Таблица заказов с основными полями
- Поиск по номеру заказа, телефону, адресу доставки
- Фильтрация по статусу, дате создания, сумме
- Сортировка по дате, сумме, статусу
- Пагинация
- Кнопка "Просмотр" ведет на `/admin/orders/:id`
- Быстрое изменение статуса прямо в таблице

**Функциональность страницы деталей заказа:**
- Полная информация о заказе
- Редактирование полей заказа (статус, заметки, менеджер)
- Список товаров в заказе
- Вкладки/секции:
  - Основная информация
  - Доставка (с ссылкой на страницу доставки)
  - Платежи (с ссылкой на страницу платежей)
  - Возвраты (если есть)
  - Претензии (если есть)
  - Отзыв (если есть)
- История изменений заказа
- Кнопка "Сохранить изменения"
- Кнопка "Назад к списку"

---

### Пункт 3.4: Создание страницы управления доставками
**Статус:** ✅ Создан план

**Задачи:**
- Создать страницу управления доставками
- Реализовать интеграцию с Laravel API `/api/v1/deliveries`
- Создать отдельные страницы для создания и редактирования доставки
- Добавить полную валидацию

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/AdminDeliveries.tsx` (новый)
- `frontend/src/pages/admin/DeliveryCreate.tsx` (новый)
- `frontend/src/pages/admin/DeliveryEdit.tsx` (новый)
- `frontend/src/api/admin.ts` (обновить - добавить функции для доставок)
- `frontend/src/App.tsx` (обновить - добавить роуты)

**Роуты:**
- `/admin/deliveries` - список доставок
- `/admin/deliveries/create` - создание доставки (с привязкой к заказу)
- `/admin/deliveries/:id/edit` - редактирование доставки

**Функциональность страницы списка:**
- Таблица доставок с основными полями
- Поиск по номеру заказа, трекинг-номеру, адресу, курьеру
- Фильтрация по типу доставки, статусу, дате
- Сортировка по дате создания, дате доставки, статусу
- Пагинация
- Кнопка "Создать доставку" ведет на `/admin/deliveries/create?orderId=XXX`
- Кнопка "Редактировать" ведет на `/admin/deliveries/:id/edit`
- Быстрое изменение статуса

**Функциональность страницы создания:**
- Форма с полями:
  - order_id (выбор заказа или передается через query параметр)
  - delivery_type (courier, pickup, self_delivery)
  - delivery_address (автозаполнение из заказа или ввод вручную)
  - delivery_date, delivery_time_from, delivery_time_to
  - courier_name, courier_phone
  - delivery_cost
  - tracking_number
  - notes
- Валидация всех полей
- Автоматическое заполнение адреса из заказа
- Кнопка "Сохранить" с валидацией
- Кнопка "Отмена" возвращает на список

**Функциональность страницы редактирования:**
- Загрузка данных доставки по ID
- Форма с предзаполненными данными
- Все поля как в создании
- Изменение статуса с отметкой времени (delivered_at при статусе "delivered")
- Валидация при сохранении
- Кнопка "Сохранить изменения"
- Кнопка "Назад к списку"

---

### Пункт 3.5: Создание страницы управления платежами
**Статус:** ✅ Создан план

**Задачи:**
- Создать страницу управления платежами
- Реализовать интеграцию с Laravel API `/api/v1/payments`
- Создать отдельные страницы для создания и редактирования платежа
- Добавить функционал возврата платежа
- Добавить полную валидацию

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/AdminPayments.tsx` (новый)
- `frontend/src/pages/admin/PaymentCreate.tsx` (новый)
- `frontend/src/pages/admin/PaymentEdit.tsx` (новый)
- `frontend/src/api/admin.ts` (обновить - добавить функции для платежей)
- `frontend/src/App.tsx` (обновить - добавить роуты)

**Роуты:**
- `/admin/payments` - список платежей
- `/admin/payments/create` - создание платежа (с привязкой к заказу)
- `/admin/payments/:id/edit` - редактирование платежа

**Функциональность страницы списка:**
- Таблица платежей с основными полями
- Поиск по номеру заказа, transaction_id, сумме
- Фильтрация по методу оплаты, статусу, платежной системе
- Фильтрация по дате
- Сортировка по дате создания, сумме, статусу
- Пагинация
- Кнопка "Создать платеж" ведет на `/admin/payments/create?orderId=XXX`
- Кнопка "Редактировать" ведет на `/admin/payments/:id/edit`
- Быстрое изменение статуса
- Индикатор возвращенных платежей

**Функциональность страницы создания:**
- Форма с полями:
  - order_id (выбор заказа или передается через query параметр)
  - payment_method (card, cash, online, bank_transfer, other)
  - payment_provider (stripe, paypal, yookassa, sberbank, etc.)
  - amount (автозаполнение из заказа или ввод вручную)
  - currency (default RUB)
  - transaction_id
  - status (pending, processing, succeeded, failed)
  - notes
- Валидация всех полей
- Автоматическое заполнение суммы из заказа
- Кнопка "Сохранить" с валидацией
- Кнопка "Отмена" возвращает на список

**Функциональность страницы редактирования:**
- Загрузка данных платежа по ID
- Форма с предзаполненными данными
- Все поля как в создании
- Секция возврата платежа (если статус succeeded):
  - Возврат полной суммы
  - Частичный возврат (с указанием суммы)
  - Причина возврата
- Отображение истории возвратов (refunded_amount, refunded_at)
- Валидация: невозможность вернуть больше суммы платежа
- Кнопка "Сохранить изменения"
- Кнопка "Назад к списку"

---

### Пункт 3.6: Создание страницы управления возвратами
**Статус:** ✅ Создан план

**Задачи:**
- Создать страницу управления возвратами
- Реализовать интеграцию с Laravel API `/api/v1/returns`
- Создать отдельные страницы для создания и редактирования возврата
- Добавить функционал одобрения/отклонения возврата
- Добавить полную валидацию

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/AdminReturns.tsx` (новый)
- `frontend/src/pages/admin/ReturnCreate.tsx` (новый)
- `frontend/src/pages/admin/ReturnEdit.tsx` (новый)
- `frontend/src/api/admin.ts` (обновить - добавить функции для возвратов)
- `frontend/src/App.tsx` (обновить - добавить роуты)

**Роуты:**
- `/admin/returns` - список возвратов
- `/admin/returns/create` - создание возврата (с привязкой к заказу)
- `/admin/returns/:id/edit` - редактирование возврата

**Функциональность страницы списка:**
- Таблица возвратов с основными полями
- Поиск по номеру возврата, номеру заказа
- Фильтрация по статусу, типу причины, методу возврата
- Фильтрация по дате
- Сортировка по дате создания, сумме, статусу
- Пагинация
- Кнопка "Создать возврат" ведет на `/admin/returns/create?orderId=XXX`
- Кнопка "Редактировать" ведет на `/admin/returns/:id/edit`
- Быстрое одобрение/отклонение возврата
- Индикатор обработанных возвратов

**Функциональность страницы создания:**
- Форма с полями:
  - order_id (выбор заказа или передается через query параметр)
  - reason_type (defect, wrong_item, not_as_described, changed_mind, other)
  - reason (текст причины)
  - items (выбор товаров из заказа с количеством для возврата)
  - total_amount (автоматический расчет)
  - refund_method (original, store_credit, exchange)
  - customer_notes (заметки от клиента)
  - notes (внутренние заметки)
- Валидация всех полей
- Выбор товаров из заказа с проверкой количества
- Автоматический расчет суммы возврата
- Кнопка "Сохранить" с валидацией
- Кнопка "Отмена" возвращает на список

**Функциональность страницы редактирования:**
- Загрузка данных возврата по ID
- Форма с предзаполненными данными
- Все поля как в создании
- Изменение статуса (pending, approved, rejected, in_transit, received, refunded)
- Кнопки одобрения/отклонения возврата
- Отображение истории обработки (processed_by, processed_at)
- Секция возврата денег (refund_status, refunded_at)
- Валидация при сохранении
- Кнопка "Сохранить изменения"
- Кнопка "Назад к списку"

---

### Пункт 3.7: Создание страницы управления претензиями
**Статус:** ✅ Создан план

**Задачи:**
- Создать страницу управления претензиями
- Реализовать интеграцию с Laravel API `/api/v1/complaints`
- Создать отдельные страницы для создания и редактирования претензии
- Добавить функционал обработки претензий
- Добавить полную валидацию

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/AdminComplaints.tsx` (новый)
- `frontend/src/pages/admin/ComplaintCreate.tsx` (новый)
- `frontend/src/pages/admin/ComplaintEdit.tsx` (новый)
- `frontend/src/api/admin.ts` (обновить - добавить функции для претензий)
- `frontend/src/App.tsx` (обновить - добавить роуты)

**Роуты:**
- `/admin/complaints` - список претензий
- `/admin/complaints/create` - создание претензии
- `/admin/complaints/:id/edit` - редактирование претензии

**Функциональность страницы списка:**
- Таблица претензий с основными полями
- Поиск по номеру претензии, номеру заказа, теме
- Фильтрация по типу, приоритету, статусу
- Фильтрация по дате
- Сортировка по дате создания, приоритету, статусу
- Пагинация
- Кнопка "Создать претензию" ведет на `/admin/complaints/create`
- Кнопка "Редактировать" ведет на `/admin/complaints/:id/edit`
- Быстрое изменение статуса и приоритета
- Индикатор необработанных претензий

**Функциональность страницы создания:**
- Форма с полями:
  - order_id (выбор заказа, опционально)
  - type (quality, delivery, service, payment, other)
  - priority (low, medium, high, urgent)
  - subject (тема претензии)
  - description (описание проблемы)
  - customer_name, customer_phone, customer_email
  - attachments (выбор файлов из медиа-библиотеки)
  - assigned_to (выбор сотрудника)
- Валидация всех полей
- Выбор файлов через MediaSelector
- Кнопка "Сохранить" с валидацией
- Кнопка "Отмена" возвращает на список

**Функциональность страницы редактирования:**
- Загрузка данных претензии по ID
- Форма с предзаполненными данными
- Все поля как в создании
- Изменение статуса (new, in_progress, resolved, rejected, closed)
- Секция решения: resolution, resolved_at, resolved_by
- Комментарии к претензии (список комментариев)
- Добавление нового комментария
- Назначение исполнителя (assigned_to)
- Валидация при сохранении
- Кнопка "Сохранить изменения"
- Кнопка "Назад к списку"

---

### Пункт 3.8: Создание страницы управления отзывами
**Статус:** ✅ Создан план

**Задачи:**
- Создать страницу управления отзывами
- Реализовать интеграцию с Laravel API `/api/v1/reviews`
- Создать отдельные страницы для создания и редактирования отзыва
- Добавить функционал модерации отзывов
- Добавить полную валидацию

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/AdminReviews.tsx` (новый)
- `frontend/src/pages/admin/ReviewCreate.tsx` (новый)
- `frontend/src/pages/admin/ReviewEdit.tsx` (новый)
- `frontend/src/api/admin.ts` (обновить - добавить функции для отзывов)
- `frontend/src/App.tsx` (обновить - добавить роуты)

**Роуты:**
- `/admin/reviews` - список отзывов
- `/admin/reviews/create` - создание отзыва
- `/admin/reviews/:id/edit` - редактирование отзыва

**Функциональность страницы списка:**
- Таблица отзывов с основными полями
- Поиск по названию товара, имени клиента
- Фильтрация по товару, рейтингу, статусу модерации
- Фильтрация по дате
- Сортировка по дате создания, рейтингу, статусу
- Пагинация
- Кнопка "Создать отзыв" ведет на `/admin/reviews/create`
- Кнопка "Редактировать" ведет на `/admin/reviews/:id/edit`
- Быстрое одобрение/отклонение отзыва
- Индикатор непромодерированных отзывов

**Функциональность страницы создания:**
- Форма с полями:
  - order_id (выбор заказа, опционально)
  - product_id (выбор товара, опционально)
  - rating (1-5 звезд)
  - title (заголовок отзыва)
  - comment (текст отзыва)
  - customer_name, customer_phone, customer_email
  - photos (выбор изображений из медиа-библиотеки)
  - is_verified_purchase (галочка)
  - status (pending, approved, rejected, hidden)
- Валидация всех полей
- Выбор изображений через MediaSelector
- Кнопка "Сохранить" с валидацией
- Кнопка "Отмена" возвращает на список

**Функциональность страницы редактирования:**
- Загрузка данных отзыва по ID
- Форма с предзаполненными данными
- Все поля как в создании
- Изменение статуса модерации (pending, approved, rejected, hidden)
- Секция ответа компании: response, responded_at, responded_by
- Отображение количества "полезно" (helpful_count)
- Отображение фотографий с возможностью удаления
- Валидация при сохранении
- Кнопка "Сохранить изменения"
- Кнопка "Назад к списку"

---

## Этап 4: Интеграция и настройка

### Пункт 4.1: Обновление меню админ-панели
**Статус:** ✅ Создан план

**Задачи:**
- Обновить компонент `AdminLayout.tsx` с новыми пунктами меню
- Добавить все новые разделы в навигацию
- Организовать меню по группам для удобства

**Файлы для изменения:**
- `frontend/src/components/admin/AdminLayout.tsx` (обновить)

**Пункты меню:**
- Каталог:
  - Категории (`/admin/categories`)
  - Товары (`/admin/products`)
- Заказы:
  - Заказы (`/admin/orders`)
  - Доставки (`/admin/deliveries`)
  - Платежи (`/admin/payments`)
- Обратная связь:
  - Возвраты (`/admin/returns`)
  - Претензии (`/admin/complaints`)
  - Отзывы (`/admin/reviews`)
- Настройки:
  - Платежи (`/admin/settings/payments/yookassa`)

**Иконки для пунктов меню:**
- Категории: Tags
- Товары: Package
- Заказы: ClipboardList
- Доставки: Truck
- Платежи: CreditCard
- Возвраты: RotateCcw
- Претензии: AlertTriangle
- Отзывы: Star
- Настройки: Settings

---

### Пункт 4.2: Настройка валидации форм
**Статус:** ✅ Создан план

**Задачи:**
- Создать схемы валидации с помощью zod для всех форм
- Настроить react-hook-form для всех страниц создания/редактирования
- Создать переиспользуемые компоненты валидации
- Добавить обработку ошибок валидации с бэкенда

**Файлы для создания/изменения:**
- `frontend/src/lib/validations/category.ts` (новый)
- `frontend/src/lib/validations/product.ts` (новый)
- `frontend/src/lib/validations/order.ts` (новый)
- `frontend/src/lib/validations/delivery.ts` (новый)
- `frontend/src/lib/validations/payment.ts` (новый)
- `frontend/src/lib/validations/return.ts` (новый)
- `frontend/src/lib/validations/complaint.ts` (новый)
- `frontend/src/lib/validations/review.ts` (новый)

**Требования к валидации:**
- Все обязательные поля должны проверяться
- Уникальность полей (name, sku, barcode) проверяется через API
- Числовые поля с ограничениями (цена >= 0, рейтинг 1-5)
- Даты в правильном формате
- Email и телефон в правильном формате
- Текст с ограничениями по длине
- Отображение понятных сообщений об ошибках на русском языке

---

### Пункт 4.3: Обработка ошибок и уведомлений
**Статус:** ✅ Создан план

**Задачи:**
- Настроить единую обработку ошибок API
- Добавить уведомления об успешных операциях
- Обработать ошибки валидации с бэкенда
- Добавить индикаторы загрузки

**Файлы для создания/изменения:**
- `frontend/src/lib/errors.ts` (новый) - утилиты для обработки ошибок
- `frontend/src/api/admin.ts` (обновить) - обработка ошибок в API функциях
- Все страницы создания/редактирования (обновить) - использование toast уведомлений

**Функциональность:**
- Отображение toast уведомлений при успешных операциях
- Отображение ошибок валидации в формах
- Обработка сетевых ошибок
- Обработка ошибок авторизации (редирект на логин)
- Индикаторы загрузки при отправке форм и загрузке данных

---

### Пункт 4.4: Тестирование и проверка
**Статус:** ✅ Создан план

**Задачи:**
- Проверить все CRUD операции для всех сущностей
- Протестировать валидацию на всех формах
- Проверить работу фильтрации и поиска
- Проверить работу с медиа-библиотекой
- Проверить связность данных (заказы -> доставки, платежи и т.д.)
- Проверить отображение истории товаров
- Проверить работу на мобильных устройствах

**Чеклист проверки:**
- [ ] Создание, редактирование, удаление категорий
- [ ] Создание, редактирование, удаление товаров
- [ ] Выбор изображений и видео из медиа-библиотеки
- [ ] История изменений товаров отображается корректно
- [ ] Управление заказами работает полностью
- [ ] Создание и редактирование доставок
- [ ] Создание и редактирование платежей
- [ ] Возврат платежей работает
- [ ] Создание и обработка возвратов
- [ ] Создание и обработка претензий
- [ ] Создание и модерация отзывов
- [ ] Все формы валидируются корректно
- [ ] Фильтрация и поиск работают на всех страницах
- [ ] Пагинация работает корректно
- [ ] Все ссылки в меню ведут на правильные страницы
- [ ] Адаптивность для мобильных устройств

---

### Пункт 4.5: Создание страницы настроек платежной системы ЮКасса
**Статус:** ✅ Создан план

**Задачи:**
- Создать страницу настроек ЮКасса в админ-панели
- Реализовать интеграцию с Laravel API `/api/v1/payment-settings/yookassa`
- Добавить форму для ввода настроек
- Добавить функционал тестирования подключения
- Добавить документацию по настройке
- Обеспечить безопасное хранение секретных ключей

**Файлы для создания/изменения:**
- `frontend/src/pages/admin/PaymentSettings.tsx` (новый)
- `frontend/src/pages/admin/YooKassaSettings.tsx` (новый - отдельная страница или секция)
- `frontend/src/api/admin.ts` (обновить - добавить функции для настроек)
- `frontend/src/lib/validations/yookassa.ts` (новый - валидация настроек)
- `frontend/src/App.tsx` (обновить - добавить роут)

**Роуты:**
- `/admin/settings/payments` - общая страница настроек платежей
- `/admin/settings/payments/yookassa` - настройки ЮКасса

**Функциональность страницы настроек ЮКасса:**
- Форма с полями:
  - **Основные настройки:**
    - Shop ID (ID магазина)
    - Secret Key (секретный ключ, поле с маскировкой)
    - Включить интеграцию (переключатель)
  - **Режим работы:**
    - Тестовый режим (переключатель)
    - Test Shop ID (для тестового режима)
    - Test Secret Key (для тестового режима)
  - **Дополнительные настройки:**
    - Автоматическое подтверждение платежей
    - URL для webhook (автозаполнение или ввод вручную)
    - Разрешенные методы оплаты (чекбоксы)
    - Шаблон описания платежа
- Кнопка "Сохранить настройки" с валидацией
- Кнопка "Тестировать подключение" - проверка корректности настроек
- Отображение результата тестирования
- Секция документации:
  - Как получить Shop ID и Secret Key
  - Как настроить webhook
  - Ссылки на документацию ЮКасса
  - Примеры настроек
- Индикатор статуса подключения (подключено/не подключено)
- Отображение даты последнего теста и результата

**Валидация:**
- Shop ID обязательное, не пустое
- Secret Key обязательное, мин 20 символов
- Webhook URL валидный URL (если указан)
- Все поля проверяются перед сохранением

**Безопасность:**
- Secret Key отображается как `****` с кнопкой "Показать/Скрыть"
- При редактировании показывать последние 4 символа ключа для подтверждения
- Не отправлять Secret Key на фронтенд, если он не изменен
- Использовать HTTPS для передачи данных

---

### Пункт 4.6: Интеграция React MiniApp с Telegram ботом
**Статус:** ✅ Создан план

**Задачи:**
- Обновить React MiniApp для работы с Telegram initData
- Реализовать валидацию и авторизацию через Telegram
- Интегрировать создание заказов с отправкой в бот
- Добавить получение данных пользователя из Telegram
- Настроить обработку платежей через ЮКасса из MiniApp

**Файлы для создания/изменения:**
- `frontend/src/lib/telegram.ts` (обновить - добавить валидацию initData)
- `frontend/src/api/index.ts` (обновить - добавить API для работы с заказами)
- `frontend/src/pages/miniapp/CheckoutPage.tsx` (обновить - интеграция с ботом)
- `frontend/src/pages/miniapp/OrderSuccessPage.tsx` (обновить - уведомления)

**Функциональность:**
- При запуске MiniApp - валидация initData через API
- Сохранение telegram_id пользователя в контексте/сторе
- При создании заказа - передача telegram_id на бэкенд
- Автоматическая отправка уведомления в бот о новом заказе
- Получение информации о заказах пользователя по telegram_id
- Отображение статусов заказов с уведомлениями от бота

**Интеграция с платежами:**
- При создании заказа через MiniApp - создание платежа в ЮКасса
- Получение ссылки на оплату от бэкенда
- Редирект на страницу оплаты ЮКасса
- Обработка возврата после оплаты (success/failure)
- Обновление статуса заказа после успешной оплаты

---

## Этап 5: Предложения по улучшению проекта

### Пункт 5.1: Улучшение архитектуры и производительности
**Статус:** 💡 Рекомендация

**Предложения:**
1. Кэширование данных (Redis для категорий, товаров, настроек)
2. Оптимизация запросов (Eager Loading, индексы БД, пагинация)
3. Очереди для тяжелых операций (webhook, уведомления, отчеты)
4. API Rate Limiting для защиты от злоупотреблений

---

### Пункт 5.2: Улучшение UX/UI
**Статус:** 💡 Рекомендация

**Предложения:**
1. Dashboard с аналитикой (графики продаж, статистика)
2. Глобальный поиск по всем сущностям с автодополнением
3. Bulk операции (массовое изменение статусов, редактирование)
4. Уведомления в реальном времени (WebSockets)
5. Экспорт данных в Excel/CSV

---

### Пункт 5.3: Безопасность и надежность
**Статус:** 💡 Рекомендация

**Предложения:**
1. Аудит действий (логирование всех действий админов)
2. Backup и восстановление (автоматическое резервное копирование)
3. Двухфакторная аутентификация для администраторов
4. Усиленная валидация входных данных

---

### Пункт 5.4: Интеграции и автоматизация
**Статус:** 💡 Рекомендация

**Предложения:**
1. Интеграция с маркетплейсами (Яндекс.Маркет, Wildberries)
2. Автоматизация процессов (изменение статусов, уведомления)
3. Email/Telegram/SMS уведомления
4. Автоматические отчеты

---

### Пункт 5.5: Дополнительные функции
**Статус:** 💡 Рекомендация

**Предложения:**
1. Управление складом (история движения, резервирование)
2. Скидки и промокоды
3. Многовалютность
4. Расширенная система ролей и прав доступа

---

## Итоговый список файлов для создания/изменения

### Backend (Laravel):
1. **Модели:**
   - `app/Models/Category.php` (новый)
   - `app/Models/Product.php` (новый)
   - `app/Models/ProductHistory.php` (новый)
   - `app/Models/Order.php` (новый или обновить)
   - `app/Models/Delivery.php` (новый)
   - `app/Models/Payment.php` (новый)
   - `app/Models/Return.php` (новый)
   - `app/Models/Complaint.php` (новый)
   - `app/Models/Review.php` (новый)
   - `app/Models/PaymentSettings.php` (новый)

2. **Миграции:**
   - `database/migrations/XXXX_create_categories_table.php` (новый)
   - `database/migrations/XXXX_create_products_table.php` (новый)
   - `database/migrations/XXXX_create_product_history_table.php` (новый)
   - `database/migrations/XXXX_create_orders_table.php` (новый или обновить)
   - `database/migrations/XXXX_create_deliveries_table.php` (новый)
   - `database/migrations/XXXX_create_payments_table.php` (новый)
   - `database/migrations/XXXX_create_returns_table.php` (новый)
   - `database/migrations/XXXX_create_complaints_table.php` (новый)
   - `database/migrations/XXXX_create_reviews_table.php` (новый)
   - `database/migrations/XXXX_create_payment_settings_table.php` (новый)

3. **Контроллеры:**
   - `app/Http/Controllers/Api/v1/CategoryController.php` (новый)
   - `app/Http/Controllers/Api/v1/ProductController.php` (новый)
   - `app/Http/Controllers/Api/v1/ProductHistoryController.php` (новый)
   - `app/Http/Controllers/Api/v1/OrderController.php` (новый)
   - `app/Http/Controllers/Api/v1/DeliveryController.php` (новый)
   - `app/Http/Controllers/Api/v1/PaymentController.php` (новый)
   - `app/Http/Controllers/Api/v1/ReturnController.php` (новый)
   - `app/Http/Controllers/Api/v1/ComplaintController.php` (новый)
   - `app/Http/Controllers/Api/v1/ReviewController.php` (новый)
   - `app/Http/Controllers/Api/v1/PaymentSettingsController.php` (новый)
   - `app/Http/Controllers/Api/v1/TelegramController.php` (новый)

4. **Сервисы:**
   - `app/Services/Payment/YooKassaService.php` (новый)
   - `app/Services/Telegram/TelegramMiniAppService.php` (новый)

5. **Request классы (валидация):**
   - `app/Http/Requests/CategoryRequest.php` (новый)
   - `app/Http/Requests/ProductRequest.php` (новый)
   - `app/Http/Requests/UpdateOrderRequest.php` (новый)
   - `app/Http/Requests/DeliveryRequest.php` (новый)
   - `app/Http/Requests/PaymentRequest.php` (новый)
   - `app/Http/Requests/ReturnRequest.php` (новый)
   - `app/Http/Requests/ComplaintRequest.php` (новый)
   - `app/Http/Requests/ReviewRequest.php` (новый)
   - `app/Http/Requests/PaymentSettingsRequest.php` (новый)

6. **Observer:**
   - `app/Observers/ProductObserver.php` (новый)

7. **Middleware:**
   - `app/Http/Middleware/ValidateTelegramInitData.php` (новый)

8. **Обновления:**
   - `routes/api.php` (обновить - добавить все новые роуты)
   - `app/Providers/AppServiceProvider.php` (обновить - зарегистрировать Observer)

### Frontend - Админ-панель (Vue 3):
**ВНИМАНИЕ:** Все файлы админ-панели находятся в `resources/js/`, а НЕ в `frontend/src/`

1. **API утилиты:**
   - `resources/js/utils/api.js` (обновить - добавить функции для всех сущностей)

2. **Компоненты:**
   - Использовать существующий компонент `resources/js/pages/admin/Media.vue` для выбора медиа

3. **Страницы - Категории:**
   - `resources/js/pages/admin/Categories.vue` (обновить)
   - `resources/js/pages/admin/CategoryCreate.vue` (новый)
   - `resources/js/pages/admin/CategoryEdit.vue` (новый)

**ПРИМЕЧАНИЕ:** Все страницы админ-панели должны быть в формате `.vue` в папке `resources/js/pages/admin/`, а НЕ `.tsx` в `frontend/src/`

4. **Страницы - Товары:**
   - `frontend/src/pages/admin/AdminProducts.tsx` (обновить)
   - `frontend/src/pages/admin/ProductCreate.tsx` (новый)
   - `frontend/src/pages/admin/ProductEdit.tsx` (новый)
   - `frontend/src/pages/admin/ProductHistory.tsx` (новый)

5. **Страницы - Заказы:**
   - `frontend/src/pages/admin/AdminOrders.tsx` (обновить)
   - `frontend/src/pages/admin/OrderDetail.tsx` (новый)

6. **Страницы - Доставки:**
   - `frontend/src/pages/admin/AdminDeliveries.tsx` (новый)
   - `frontend/src/pages/admin/DeliveryCreate.tsx` (новый)
   - `frontend/src/pages/admin/DeliveryEdit.tsx` (новый)

7. **Страницы - Платежи:**
   - `frontend/src/pages/admin/AdminPayments.tsx` (новый)
   - `frontend/src/pages/admin/PaymentCreate.tsx` (новый)
   - `frontend/src/pages/admin/PaymentEdit.tsx` (новый)

8. **Страницы - Возвраты:**
   - `frontend/src/pages/admin/AdminReturns.tsx` (новый)
   - `frontend/src/pages/admin/ReturnCreate.tsx` (новый)
   - `frontend/src/pages/admin/ReturnEdit.tsx` (новый)

9. **Страницы - Претензии:**
   - `frontend/src/pages/admin/AdminComplaints.tsx` (новый)
   - `frontend/src/pages/admin/ComplaintCreate.tsx` (новый)
   - `frontend/src/pages/admin/ComplaintEdit.tsx` (новый)

10. **Страницы - Отзывы:**
    - `frontend/src/pages/admin/AdminReviews.tsx` (новый)
    - `frontend/src/pages/admin/ReviewCreate.tsx` (новый)
    - `frontend/src/pages/admin/ReviewEdit.tsx` (новый)

11. **Страницы - Настройки платежей:**
    - `frontend/src/pages/admin/PaymentSettings.tsx` (новый)
    - `frontend/src/pages/admin/YooKassaSettings.tsx` (новый)

12. **Валидация:**
    - `frontend/src/lib/validations/category.ts` (новый)
    - `frontend/src/lib/validations/product.ts` (новый)
    - `frontend/src/lib/validations/order.ts` (новый)
    - `frontend/src/lib/validations/delivery.ts` (новый)
    - `frontend/src/lib/validations/payment.ts` (новый)
    - `frontend/src/lib/validations/return.ts` (новый)
    - `frontend/src/lib/validations/complaint.ts` (новый)
    - `frontend/src/lib/validations/review.ts` (новый)
    - `frontend/src/lib/validations/yookassa.ts` (новый)

13. **Утилиты:**
    - `frontend/src/lib/errors.ts` (новый)

14. **Обновления:**
    - `frontend/src/components/admin/AdminLayout.tsx` (обновить - меню, добавить "Настройки")
    - `frontend/src/App.tsx` (обновить - роуты)

---

**Дата создания плана:** 2025-01-20  
**Дата обновления:** 2025-01-20  
**Статус:** ✅ План полностью готов к реализации  
**Всего пунктов плана:** 34 (29 основных + 5 рекомендаций)  
**Добавлено:** 
- ✅ Интеграция ЮКасса
- ✅ Интеграция с Telegram ботом для MiniApp
- ✅ Предложения по улучшению  
**Исправлено:**
- ✅ Админ-панель должна быть на Vue 3 (resources/js), а не React
- ✅ Все файлы админ-панели в формате .vue, не .tsx

---




