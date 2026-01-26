# Документация структуры фронтенд приложения Express CMS

## Общая информация

**Технологический стек:**
- React 18.3.1
- TypeScript 5.8.3
- Vite 5.4.19 (сборщик)
- React Router DOM 6.30.1 (роутинг)
- Zustand 5.0.9 (управление состоянием)
- TanStack React Query 5.83.0 (кеширование запросов)
- Tailwind CSS 3.4.17 (стилизация)
- shadcn/ui (компоненты UI на базе Radix UI)
- Telegram WebApp API (интеграция с Telegram)

**Структура проекта:**
```
frontend/
├── src/
│   ├── api/              # API клиенты
│   ├── components/       # React компоненты
│   │   ├── miniapp/     # Компоненты мини-приложения
│   │   ├── admin/       # Компоненты админ-панели
│   │   └── ui/          # UI компоненты (shadcn/ui)
│   ├── hooks/           # React хуки
│   ├── lib/             # Утилиты и библиотеки
│   ├── pages/           # Страницы приложения
│   │   ├── miniapp/     # Пользовательские страницы
│   │   └── admin/       # Страницы админ-панели
│   ├── store/           # Zustand stores
│   ├── types/           # TypeScript типы
│   ├── App.tsx          # Главный компонент приложения
│   └── main.tsx         # Точка входа
├── public/              # Статические файлы
└── index.html           # HTML шаблон
```

---

## 1. Структура роутинга

### 1.1 Пользовательские маршруты (Mini App)

```typescript
/                          → CatalogPage (Каталог товаров)
/search                    → SearchPage (Поиск)
/product/:productId        → ProductDetailPage (Детали товара)
/cart                      → CartPage (Корзина)
/checkout                  → CheckoutPage (Оформление заказа)
/order-success/:orderId    → OrderSuccessPage (Успешное оформление)
/orders                    → OrdersPage (Список заказов)
/orders/:orderId           → OrderDetailPage (Детали заказа)
/about                     → AboutPage (О нас)
/legal-documents           → LegalDocumentsPage (Юридические документы)
/call                      → CallPage (Страница звонка)
```

### 1.2 Админские маршруты

```typescript
/admin                     → AdminDashboard (Главная панель)
/admin/orders              → AdminOrders (Управление заказами)
/admin/products            → AdminProducts (Управление товарами)
/admin/categories          → AdminCategories (Управление категориями)
/admin/about               → AdminAbout (Редактирование страницы "О нас")
/admin/settings/payments/yookassa → YooKassaSettings (Настройки ЮКассы)
/admin/settings/delivery   → DeliverySettings (Настройки доставки)
```

---

## 2. Пользовательские страницы (Mini App)

### 2.1 CatalogPage (`/`)

**Описание:** Главная страница каталога товаров с категориями и фильтрацией.

**Структура HTML:**
```html
<div className="min-h-screen bg-background pb-28">
  <!-- Хедер -->
  <MiniAppHeader title="Свой Хлеб" />
  
  <!-- Sticky блок: Переключатель доставки + Категории -->
  <div className="sticky top-14 z-30 bg-background border-b border-border">
    <DeliveryModeToggle />  <!-- Переключатель: Доставка/Самовывоз -->
    <CategoryTabs />         <!-- Горизонтальные табы категорий -->
  </div>
  
  <!-- Сетка товаров -->
  <div className="grid grid-cols-2 gap-2 sm:gap-3">
    <ProductCard /> <!-- Карточки товаров в сетке 2x2 -->
  </div>
  
  <!-- Индикатор прогресса доставки (фиксированный внизу) -->
  <DeliveryProgressIndicator />
  
  <!-- Плавающая кнопка корзины (если есть товары) -->
  <button>Корзина ({totalItems}) · {totalAmount} ₽</button>
  
  <!-- Навигация внизу -->
  <BottomNavigation />
</div>
```

**События:**
- `DeliveryModeToggle.onChange` → Сохраняет режим в `localStorage`, обновляет состояние
- `CategoryTabs.onCategoryChange` → Обновляет URL параметр `?cat=`, фильтрует товары, сбрасывает скролл
- `ProductCard.onClick` → Переход на `/product/:productId` с сохранением позиции скролла
- Скролл → Сохраняет позицию в `catalogStore.scrollY` для восстановления при возврате

**Используемые компоненты:**
- `MiniAppHeader` - Заголовок с кнопками навигации
- `DeliveryModeToggle` - Переключатель доставка/самовывоз
- `CategoryTabs` - Горизонтальные табы категорий
- `ProductCard` - Карточка товара (сетка 2x2)
- `DeliveryProgressIndicator` - Прогресс-бар минимальной суммы доставки
- `BottomNavigation` - Нижняя навигация

**Store:**
- `useCartStore` - Состояние корзины (totalItems, totalAmount)
- `useCatalogStore` - Состояние каталога (activeCategoryId, scrollY)

**API вызовы:**
- `useProducts()` - Загрузка товаров и категорий
- `deliverySettingsAPI.getSettings()` - Настройки доставки (минимальная сумма, бесплатная доставка)

---

### 2.2 ProductDetailPage (`/product/:productId`)

**Описание:** Детальная страница товара с описанием, изображением и управлением количеством.

**Структура HTML:**
```html
<div className="min-h-screen bg-background pb-28">
  <!-- Фиксированный хедер с кнопкой "Назад" -->
  <header className="sticky top-0 z-50">
    <button onClick={handleBack}>←</button>
  </header>
  
  <!-- Квадратное изображение товара 1:1 -->
  <div className="aspect-square w-full bg-muted">
    <OptimizedImage src={product.imageUrl} />
  </div>
  
  <!-- Информация о товаре -->
  <div className="px-4 py-4">
    <span>Категория</span>
    <h1>{product.name}</h1>
    <p>{product.description}</p>
    {product.isWeightProduct && <p>⚠️ Весовой товар</p>}
    <span className="text-2xl font-bold">{product.price} ₽</span>
  </div>
  
  <!-- Фиксированная панель внизу -->
  <div className="fixed bottom-0 left-0 right-0 z-40">
    {quantityInCart > 0 ? (
      <!-- Кнопки +/-, если товар в корзине -->
      <div>
        <button onClick={handleDecrement}>-</button>
        <span>{quantityInCart}</span>
        <button onClick={handleIncrement}>+</button>
      </div>
    ) : (
      <!-- Кнопка добавления в корзину -->
      <div>
        <button onClick={handleDecrement} disabled={localQuantity <= 1}>-</button>
        <span>{localQuantity}</span>
        <button onClick={handleIncrement}>+</button>
        <button onClick={handleAddToCart}>Добавить в корзину</button>
      </div>
    )}
  </div>
</div>
```

**События:**
- `handleBack` → `navigate(-1)` - Возврат на предыдущую страницу с сохранением контекста
- `handleIncrement` → Увеличивает `localQuantity` или добавляет в корзину через `addItem()`
- `handleDecrement` → Уменьшает `localQuantity` или `updateQuantity()` в корзине
- `handleAddToCart` → Добавляет товар в корзину через `addItem()`, показывает toast, сбрасывает `localQuantity`

**API вызовы:**
- `productsAPI.getById(productId)` - Загрузка данных товара
- `categoriesAPI.getAll()` - Загрузка категорий для отображения названия категории

**Store:**
- `useCartStore` - Добавление/обновление товаров в корзине
- `useCatalogStore` - Сохранение позиции скролла

---

### 2.3 CartPage (`/cart`)

**Описание:** Страница корзины с товарами, управлением количеством и итоговой суммой.

**Структура HTML:**
```html
<div className="flex flex-col h-screen bg-background overflow-hidden">
  <MiniAppHeader title="Корзина" />
  
  <!-- Скроллируемая область с товарами -->
  <div className="flex-1 overflow-y-auto px-4">
    <!-- Заголовок с количеством и кнопкой "Очистить" -->
    <div className="flex justify-between py-3">
      <span>{getItemsText(items.length)}</span>
      <button onClick={handleClearCart}>Очистить</button>
    </div>
    
    <!-- Список товаров -->
    <div className="space-y-3">
      <CartItem /> <!-- Для каждого товара в корзине -->
    </div>
  </div>
  
  <!-- Фиксированная панель внизу с итогами -->
  <div className="fixed bottom-14 left-0 right-0 z-40 border-t">
    <div className="bg-secondary p-3">
      <div>Товары ({items.length})</div>
      <div>Итого: {totalAmount} ₽</div>
    </div>
    
    {isDeliveryBlocked && (
      <p>Минимум {minDeliveryOrderTotal} ₽ для доставки</p>
    )}
    
    <button 
      onClick={() => navigate('/checkout')}
      disabled={isDeliveryBlocked}
    >
      Оформить заказ
    </button>
  </div>
  
  <BottomNavigation />
</div>
```

**События:**
- `handleClearCart` → Показывает подтверждение через `showTelegramConfirm()`, при подтверждении очищает корзину через `clearCart()`
- `CartItem` → Клик по карточке → Переход на `/product/:productId`
- `CartItem` → Кнопка удаления → `removeItem(productId)`
- `CartItem` → Кнопки +/- → `updateQuantity(productId, quantity)`
- Кнопка "Оформить заказ" → Переход на `/checkout` с передачей `orderMode` в state

**Состояние пустой корзины:**
```html
<div>
  <ShoppingBag icon />
  <h2>Корзина пуста</h2>
  <button onClick={() => navigate('/')}>Перейти в каталог</button>
</div>
```

**Store:**
- `useCartStore` - Все операции с корзиной (items, getTotalAmount, clearCart)

**API вызовы:**
- `deliverySettingsAPI.getSettings()` - Получение минимальной суммы для доставки

---

### 2.4 CheckoutPage (`/checkout`)

**Описание:** Многошаговая форма оформления заказа (4 шага).

**Структура HTML:**
```html
<div className="min-h-screen bg-background pb-32">
  <MiniAppHeader title="Оформление заказа" showBack />
  
  <!-- Индикатор прогресса (4 шага) -->
  <div className="flex items-center justify-center gap-2">
    <div className="step-circle">1/2/3/4</div>
    <div className="progress-line"></div>
  </div>
  <p>Шаг {step} из 4</p>
  
  <!-- Контент шага -->
  {step === 1 && (
    <!-- ШАГ 1: Контактные данные -->
    <div>
      <h2>Контактные данные</h2>
      <input 
        type="tel" 
        value={formData.phone}
        onChange={handlePhoneChange}
        placeholder="+7 (___) ___-__-__"
      />
      <input 
        type="text"
        value={formData.name}
        placeholder="Имя (опционально)"
      />
    </div>
  )}
  
  {step === 2 && (
    <!-- ШАГ 2: Адрес и время доставки -->
    <div>
      <h2>Адрес и время доставки</h2>
      <select value={formData.deliveryType}>
        <option value="courier">Курьер</option>
        <option value="pickup">Самовывоз</option>
      </select>
      
      {formData.deliveryType === 'courier' && (
        <div>
          <input 
            type="text"
            value={formData.address}
            onChange={handleAddressChange}
            placeholder="Адрес доставки"
          />
          {isCalculatingDelivery && <p>Проверка адреса...</p>}
          {deliveryValidation?.valid && (
            <p>Стоимость доставки: {deliveryCost} ₽</p>
          )}
        </div>
      )}
      
      <!-- Календарь выбора даты -->
      <Popover>
        <Calendar 
          selected={formData.deliveryDate}
          disabled={(date) => date < today || date > maxDate}
        />
      </Popover>
      
      <!-- Выбор временного интервала -->
      <select value={formData.deliveryTimeSlot}>
        {getTimeSlotsForDate(formData.deliveryDate).map(slot => (
          <option value={slot}>{slot}</option>
        ))}
      </select>
      
      <textarea 
        value={formData.comment}
        placeholder="Комментарий к заказу"
      />
    </div>
  )}
  
  {step === 3 && (
    <!-- ШАГ 3: Способ оплаты -->
    <div>
      <h2>Способ оплаты</h2>
      {availablePaymentMethods.map(method => (
        <div 
          onClick={() => setFormData({ ...formData, paymentMethod: method })}
          className={isSelected ? 'selected' : ''}
        >
          <div>Radio button</div>
          <h3>{method.name}</h3>
          {method.description && <p>{method.description}</p>}
          {discountInfo && <div>Скидка: -{discountInfo.discount} ₽</div>}
        </div>
      ))}
    </div>
  )}
  
  {step === 4 && (
    <!-- ШАГ 4: Подтверждение -->
    <div>
      <h2>Подтверждение заказа</h2>
      <!-- Состав заказа -->
      <div>
        {items.map(item => (
          <div>
            {item.product.name} × {item.quantity}
            {(item.product.price * item.quantity)} ₽
          </div>
        ))}
      </div>
      
      <!-- Детали доставки -->
      <div>
        <div>Тип: {formData.deliveryType === 'courier' ? 'Курьер' : 'Самовывоз'}</div>
        <div>Адрес: {deliveryValidation?.address || formData.address}</div>
        <div>Время: {formData.deliveryDate}, {formData.deliveryTimeSlot}</div>
        <div>Телефон: {formData.phone}</div>
      </div>
      
      <!-- Способ оплаты -->
      <div>{formData.paymentMethod.name}</div>
      
      <!-- Итоговая сумма -->
      <div>
        <div>Товары: {totalAmount} ₽</div>
        {discountInfo && <div>Скидка: -{discountInfo.discount} ₽</div>}
        {deliveryCost > 0 && <div>Доставка: {deliveryCost} ₽</div>}
        <div>К оплате: {grandTotal} ₽</div>
      </div>
      
      <button onClick={() => navigate('/legal-documents')}>
        Политика конфиденциальности и оферта
      </button>
    </div>
  )}
  
  <!-- Фиксированные кнопки навигации -->
  <div className="fixed bottom-0 left-0 right-0 z-40">
    <button onClick={handleBack}>{step === 1 ? 'Отмена' : 'Назад'}</button>
    {step < 4 ? (
      <button onClick={handleNext}>Далее</button>
    ) : (
      <button onClick={handleSubmit} disabled={isLoading}>
        {isLoading ? 'Обработка...' : getPaymentButtonText()}
      </button>
    )}
  </div>
</div>
```

**События:**

**Шаг 1:**
- `handlePhoneChange` → Форматирует телефон с маской `+7 (XXX) XXX-XX-XX`, обновляет `formData.phone`
- Валидация при `handleNext` → Проверяет, что телефон содержит 11 цифр

**Шаг 2:**
- `handleAddressChange` → Обновляет `formData.address`, сбрасывает `deliveryValidation`
- Debounce 1 секунда → Вызывает `calculateDeliveryCost()` для расчета стоимости доставки
- `calculateDeliveryCost` → Вызывает `deliverySettingsAPI.calculateCost()`, обновляет `deliveryCost` и `deliveryValidation`
- Выбор даты в календаре → Обновляет `formData.deliveryDate`, сбрасывает `deliveryTimeSlot`
- Выбор времени → Обновляет `formData.deliveryTimeSlot`
- Валидация при `handleNext` → Проверяет адрес (для курьера), дату и время доставки

**Шаг 3:**
- Клик по способу оплаты → Загружает информацию о скидке через `paymentMethodsAPI.getById()`, обновляет `formData.paymentMethod` и `discountInfo`
- Валидация при `handleNext` → Проверяет, что способ оплаты выбран

**Шаг 4:**
- `handleSubmit` → 
  1. Создает заказ через `createOrder(orderData)`
  2. Очищает корзину через `clearCart()`
  3. Если способ оплаты `cash` → Переходит на `/orders/:orderId?success=true`
  4. Если способ оплаты `yookassa` → Создает платеж через `paymentAPI.createYooKassaPayment()`, перенаправляет на `confirmationUrl` для оплаты

**Автозаполнение:**
- При монтировании → Загружает последний заказ через `ordersAPI.getByTelegramId()` и заполняет телефон, имя и адрес

**API вызовы:**
- `ordersAPI.getByTelegramId()` - Загрузка прошлых заказов для автозаполнения
- `paymentMethodsAPI.getAll()` - Загрузка способов оплаты
- `paymentMethodsAPI.getById(id, totalAmount)` - Получение информации о скидке
- `deliverySettingsAPI.getSettings()` - Настройки доставки (город, минимальная сумма, минимальное время подготовки)
- `deliverySettingsAPI.calculateCost(address, totalAmount)` - Расчет стоимости доставки
- `ordersAPI.create()` - Создание заказа
- `paymentAPI.createYooKassaPayment()` - Создание платежа ЮКассы

**Store:**
- `useCartStore` - Данные корзины (items, getTotalAmount, clearCart)
- `useOrders` - Создание заказа через `createOrder()`

**Подключенные плагины:**
- `react-day-picker` + `date-fns` - Календарь для выбора даты
- `@radix-ui/react-popover` - Popover для календаря
- `@radix-ui/react-calendar` - Компонент календаря

---

### 2.5 OrdersPage (`/orders`)

**Описание:** Список всех заказов пользователя с фильтрацией по статусу.

**Структура HTML:**
```html
<div className="min-h-screen bg-background pb-20">
  <MiniAppHeader title="Мои заказы" />
  
  <!-- Фильтры по статусу -->
  <div className="px-4 py-3 border-b">
    <div className="flex gap-2 overflow-x-auto">
      {statusFilters.map(filter => (
        <button 
          onClick={() => setStatusFilter(filter.value)}
          className={isActive ? 'active' : ''}
        >
          {filter.label}
        </button>
      ))}
    </div>
  </div>
  
  <!-- Список заказов -->
  <div className="space-y-3 px-4 py-4">
    {filteredOrders.map(order => (
      <OrderCard 
        order={order}
        onClick={() => navigate(`/orders/${order.orderId}`)}
        onPayment={() => handlePayment(order)}
        onCancel={() => handleCancel(order)}
      />
    ))}
  </div>
  
  <!-- Ссылка на документы -->
  <button onClick={() => navigate('/legal-documents')}>
    Политика конфиденциальности и оферта
  </button>
  
  <BottomNavigation />
</div>
```

**События:**
- `setStatusFilter` → Фильтрует заказы по выбранному статусу
- `OrderCard.onClick` → Переход на `/orders/:orderId`
- `handlePayment` → Создает платеж через `paymentAPI.createYooKassaPayment()`, перенаправляет на страницу оплаты
- `handleCancel` → Показывает подтверждение, вызывает `ordersAPI.cancelOrder()`, обновляет список

**Фильтры:**
- `all` - Все заказы
- `pending_payment` - Ожидают оплаты (paymentStatus === 'pending' && status !== 'cancelled')
- `preparing` - В работе
- `in_transit` - В доставке
- `delivered` - Завершён

**Состояния:**
- Загрузка → Показывает `Loader2` спиннер
- Ошибка → Показывает сообщение об ошибке с кнопкой "Попробовать снова"
- Пусто → Показывает иконку и кнопку "Перейти в каталог"

**API вызовы:**
- `useOrders().loadOrders()` - Загрузка заказов (при монтировании)
- `paymentAPI.createYooKassaPayment()` - Создание платежа
- `ordersAPI.cancelOrder(orderId)` - Отмена заказа

**Store:**
- `useOrders` - Состояние заказов (orders, loading, error, loadOrders)

---

### 2.6 OrderDetailPage (`/orders/:orderId`)

**Описание:** Детальная информация о заказе с возможностью оплаты и отмены.

**Структура HTML:**
```html
<div className="min-h-screen bg-background pb-44">
  <MiniAppHeader title={`Заказ #${order.orderId}`} showBack />
  
  <!-- Алерты об оплате -->
  {showPaymentSuccess && (
    <div className="alert-success">
      Оплата успешно выполнена!
    </div>
  )}
  
  {showPaymentError && (
    <div className="alert-error">
      Ошибка при оплате
    </div>
  )}
  
  <!-- Информация о заказе -->
  <div className="px-4 py-4 space-y-4">
    <!-- Номер заказа и статус -->
    <div>
      <p>Номер заказа</p>
      <h2>#{order.orderId}</h2>
      <StatusBadge status={order.status} />
    </div>
    
    <!-- Статус заказа -->
    <div>
      <Clock icon />
      <h3>Статус заказа</h3>
      <p>{ORDER_STATUS_LABELS[order.status]}</p>
      {order.deliveryTime && <p>Ожидаемая доставка: {order.deliveryTime}</p>}
    </div>
    
    <!-- Детали доставки -->
    <div>
      <h3>Доставка</h3>
      <MapPin /> {order.deliveryAddress}
      <Clock /> {order.deliveryTime}
      <Phone /> {order.phone}
      {order.comment && <MessageSquare /> {order.comment}}
    </div>
    
    <!-- Состав заказа -->
    <div>
      <Package icon />
      <h3>Состав заказа</h3>
      {order.items.map(item => (
        <div>
          <OptimizedImage src={item.productImage} />
          <div>
            <p>{item.productName}</p>
            <p>{item.quantity} × {item.unitPrice} ₽</p>
          </div>
          <span>{item.total} ₽</span>
        </div>
      ))}
    </div>
    
    <!-- Информация об оплате -->
    <div>
      <CreditCard />
      <span>Статус оплаты</span>
      <span>{PAYMENT_STATUS_LABELS[order.paymentStatus]}</span>
      <div>
        <span>Итого</span>
        <span>{order.totalAmount} ₽</span>
      </div>
    </div>
  </div>
  
  <!-- Фиксированные кнопки действий -->
  <div className="fixed bottom-14">
    {isOrderUnpaid(order) && canCancelOrder(order) ? (
      <!-- Кнопки для неоплаченных заказов -->
      <button onClick={() => handleCancel()}>Отменить заказ</button>
      <button onClick={() => handlePayment()}>Оплатить</button>
    ) : (
      <!-- Кнопки для оплаченных заказов -->
      <button onClick={handleContactSupport}>Поддержка</button>
      {order.status === 'delivered' ? (
        <button onClick={handleRepeatOrder}>Повторить</button>
      ) : (
        <button onClick={() => navigate('/')}>В каталог</button>
      )}
    )}
  </div>
  
  <BottomNavigation />
</div>
```

**События:**
- При монтировании → Загружает заказ через `getOrderById(orderId)` или берет из кеша `orders`
- Обработка URL параметров:
  - `?payment=success` → Проверяет статус оплаты, показывает алерт успеха
  - `?payment=error` → Показывает алерт ошибки
  - `?action=pay` → Автоматически вызывает `handlePayment()`
  - `?action=cancel` → Автоматически вызывает `handleCancel()`
- `handlePayment` → Создает платеж через `paymentAPI.createYooKassaPayment()`, перенаправляет на страницу оплаты
- `handleCancel` → Показывает подтверждение, вызывает `ordersAPI.cancelOrder()`, обновляет заказ
- `handleContactSupport` → Открывает Telegram ссылку поддержки через `openTelegramLink()`
- `handleRepeatOrder` → Загружает каждый товар через `productsAPI.getById()`, добавляет в корзину через `addItem()`, переходит в `/cart`

**API вызовы:**
- `getOrderById(orderId)` - Загрузка данных заказа
- `ordersAPI.cancelOrder(orderId)` - Отмена заказа
- `paymentAPI.createYooKassaPayment()` - Создание платежа
- `aboutAPI.get()` - Загрузка настроек поддержки (Telegram URL)
- `productsAPI.getById()` - Загрузка товаров для повторения заказа

**Store:**
- `useOrders` - Получение заказа через `getOrderById()`
- `useCartStore` - Добавление товаров в корзину при повторении заказа

---

### 2.7 SearchPage (`/search`)

**Описание:** Страница поиска товаров с подсказками и результатами.

**Структура HTML:**
```html
<div className="flex flex-col h-screen bg-background">
  <MiniAppHeader title="Поиск" showBack showSearch={false} />
  
  <!-- Поле поиска -->
  <div className="px-4 pt-3 pb-2 border-b">
    <div className="relative">
      <Search icon />
      <Input 
        ref={inputRef}
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Введите название блюда"
      />
      {query && (
        <button onClick={handleClear}>✕</button>
      )}
    </div>
  </div>
  
  <!-- Результаты -->
  <div className="flex-1 overflow-y-auto px-4">
    {!debouncedQuery && (
      <!-- Пустое состояние -->
      <div>
        <Search icon />
        <p>Начните вводить название блюда</p>
      </div>
    )}
    
    {suggestions.length > 0 && (
      <!-- Подсказки (первые 10 результатов) -->
      <div>
        <h3>Подсказки</h3>
        {suggestions.map(product => (
          <button onClick={() => navigate(`/product/${product.id}`)}>
            <span>{product.name}</span>
            <span>{product.price} ₽</span>
          </button>
        ))}
      </div>
    )}
    
    {debouncedQuery && searchResults.length > 0 && (
      <!-- Результаты поиска в сетке 2x2 -->
      <div>
        <h3>Результаты поиска ({searchResults.length})</h3>
        <div className="grid grid-cols-2 gap-2">
          {searchResults.map(product => (
            <ProductCard 
              product={product}
              onClick={() => navigate(`/product/${product.id}`)}
            />
          ))}
        </div>
      </div>
    )}
    
    {debouncedQuery && searchResults.length === 0 && (
      <!-- Нет результатов -->
      <div>
        <Search icon />
        <p>Ничего не найдено</p>
      </div>
    )}
  </div>
  
  <BottomNavigation />
</div>
```

**События:**
- При монтировании → Автофокус на поле ввода с задержкой 100ms
- `onChange` → Обновляет `query`, debounce 200ms обновляет `debouncedQuery`
- `handleClear` → Очищает `query` и `debouncedQuery`
- `handleSuggestionClick` → Переход на `/product/:productId`
- Клик по `ProductCard` → Переход на `/product/:productId`

**Алгоритм поиска:**
1. Нормализация строки (lowercase, trim, убирает лишние пробелы)
2. Приоритеты совпадений:
   - Priority 1: Название начинается с запроса
   - Priority 2: Запрос встречается в названии
   - Priority 3: Запрос встречается в описании
3. Сортировка: по приоритету → по позиции совпадения → по названию
4. Лимит результатов: 50

**Подсказки:**
- Показываются только если `debouncedQuery.length >= 2`
- Показываются первые 10 результатов из `searchResults`

**Store:**
- `useProducts` - Получение всех товаров для поиска

---

### 2.8 AboutPage (`/about`)

**Описание:** Информационная страница о компании с контактами, адресом и описанием.

**Структура HTML:**
```html
<div className="min-h-screen bg-background pb-20">
  <MiniAppHeader title="О нас" />
  
  <div className="space-y-6 px-4 py-6">
    <!-- Карусель изображений -->
    <div className="relative h-48">
      {cover_images.length === 1 ? (
        <OptimizedImage src={cover_images[0]} />
      ) : (
        <Carousel>
          {cover_images.map(image => (
            <CarouselItem>
              <OptimizedImage src={image} />
            </CarouselItem>
          ))}
        </Carousel>
      )}
    </div>
    
    <!-- Заголовок -->
    {data.title && <h1>{data.title}</h1>}
    
    <!-- Быстрые действия (4 кнопки в ряд) -->
    <div className="grid grid-cols-4 gap-2">
      {data.phone && (
        <button onClick={() => handlePhoneClick(data.phone)}>
          <Phone icon />
          <span>Телефон</span>
        </button>
      )}
      
      {(data.address || data.yandex_maps_url) && (
        <button onClick={() => handleMapsClick(yandex_maps_url)}>
          <MapPin icon />
          <span>Адрес</span>
        </button>
      )}
      
      {data.support_telegram_url && (
        <button onClick={() => handleSupportClick(support_telegram_url)}>
          <MessageCircle icon />
          <span>Поддержка</span>
        </button>
      )}
      
      <button onClick={() => navigate('/legal-documents')}>
        <FileText icon />
        <span>Документы</span>
      </button>
    </div>
    
    <!-- Описание (с кнопкой "Показать больше") -->
    {data.description && (
      <div>
        <p>{showFullDescription ? data.description : truncatedDescription}</p>
        {data.description.split('\n').length > 4 && (
          <button onClick={() => setShowFullDescription(!showFullDescription)}>
            {showFullDescription ? 'Скрыть' : 'Показать больше'}
          </button>
        )}
      </div>
    )}
    
    <!-- Информационные карточки (bullets) -->
    {data.bullets && data.bullets.slice(0, 3).map(bullet => (
      <div className="card">
        <p>{bullet}</p>
      </div>
    ))}
  </div>
  
  <BottomNavigation />
</div>
```

**События:**
- `handlePhoneClick` → 
  - iOS: Показывает popup с кнопками "Скопировать", "Позвонить", "Отмена" через `tg.showPopup()`
  - Android/другие: Открывает `tel:` ссылку напрямую
- `handleMapsClick` → Открывает Яндекс.Карты через `tg.openLink()` или `window.open()`
- `handleSupportClick` → Открывает Telegram ссылку через `openTelegramLink()`
- `handleCopyPhone` → Копирует номер в буфер обмена, показывает toast
- `setShowFullDescription` → Переключает между полным и обрезанным описанием (первые 4 строки)

**API вызовы:**
- `aboutAPI.get()` - Загрузка данных страницы "О нас"

**Подключенные плагины:**
- `embla-carousel-react` - Карусель для изображений (через shadcn/ui Carousel)

---

### 2.9 LegalDocumentsPage (`/legal-documents`)

**Описание:** Список юридических документов (политика конфиденциальности, оферта).

**Структура HTML:**
```html
<div className="min-h-screen bg-background pb-20">
  <MiniAppHeader title="Документы" />
  
  <div className="px-4 py-4 space-y-2">
    {documents.map(document => (
      <button 
        onClick={() => handleDocumentClick(document)}
        className="card"
      >
        <FileText icon />
        <div>
          <h3>{DOCUMENT_TYPE_LABELS[document.type] || document.title}</h3>
          {document.url && (
            <p>
              <ExternalLink icon />
              Внешняя ссылка
            </p>
          )}
        </div>
        <ChevronRight icon />
      </button>
    ))}
  </div>
  
  <!-- Dialog для просмотра документа -->
  <Dialog open={isDialogOpen}>
    <DialogContent>
      <DialogTitle>{selectedDocument.title}</DialogTitle>
      <div dangerouslySetInnerHTML={{ __html: selectedDocument.content }} />
    </DialogContent>
  </Dialog>
  
  <BottomNavigation />
</div>
```

**События:**
- `handleDocumentClick` → 
  - Если есть `document.url` → Открывает во внешнем окне через `window.open()`
  - Если есть `document.content` → Показывает в модальном окне (Dialog)

**API вызовы:**
- `legalDocumentsAPI.getAll()` - Загрузка списка документов

**Подключенные плагины:**
- `@radix-ui/react-dialog` - Модальное окно для просмотра документа

---

### 2.10 CallPage (`/call`)

**Описание:** Страница для совершения звонка (открывается из AboutPage для iOS).

**Структура HTML:**
```html
<div className="min-h-screen bg-background flex items-center justify-center">
  <div className="max-w-md space-y-6 text-center">
    <div className="w-20 h-20 rounded-full bg-primary/10">
      <Phone icon />
    </div>
    
    <div>
      <p>Номер телефона</p>
      <p className="text-3xl font-bold">{formatPhone(phone)}</p>
    </div>
    
    <a href={`tel:${phone}`}>
      <Button>
        <Phone icon />
        Позвонить
      </Button>
    </a>
    
    <p>Нажмите кнопку "Позвонить" для вызова</p>
  </div>
</div>
```

**События:**
- При монтировании → Получает параметр `phone` из URL через `useSearchParams()`
- Клик по кнопке → Открывает `tel:` ссылку

**Форматирование телефона:**
- Формат: `+7 (XXX) XXX-XX-XX`
- Убирает все нецифровые символы, нормализует 8 → 7

---

### 2.11 OrderSuccessPage (`/order-success/:orderId`)

**Описание:** Страница успешного оформления заказа (используется редко, обычно редирект идет на OrderDetailPage).

**Структура HTML:**
```html
<div className="min-h-screen bg-background flex flex-col items-center justify-center">
  <div className="w-24 h-24 rounded-full bg-primary/10">
    <CheckCircle2 icon />
  </div>
  
  <h1>Заказ оформлен!</h1>
  <p>Номер вашего заказа</p>
  <p className="text-xl font-bold">{orderId}</p>
  
  <p>Мы свяжемся с вами для подтверждения заказа</p>
  
  <div className="flex flex-col gap-3">
    <button onClick={() => navigate('/orders')}>
      Мои заказы
    </button>
    <button onClick={() => navigate('/')}>
      Вернуться в каталог
    </button>
  </div>
</div>
```

**События:**
- Кнопка "Мои заказы" → Переход на `/orders`
- Кнопка "Вернуться в каталог" → Переход на `/`

---

## 3. Общие компоненты

### 3.1 MiniAppHeader

**Описание:** Заголовок страницы с навигацией.

**Структура HTML:**
```html
<header className="sticky top-0 z-50 flex h-14 items-center justify-between border-b">
  <!-- Левая часть -->
  <div className="min-w-[80px]">
    {showBack ? (
      <button onClick={() => navigate(-1)}>
        <ChevronLeft />
      </button>
    ) : showThemeToggle && (
      <button onClick={toggleTheme}>
        {theme === 'dark' ? <Sun /> : <Moon />}
      </button>
    )}
  </div>
  
  <!-- Центр: Заголовок -->
  <h1>{title}</h1>
  
  <!-- Правая часть -->
  <div className="min-w-[80px]">
    {showSearch && (
      <button onClick={() => navigate('/search')}>
        <Search />
      </button>
    )}
  </div>
</header>
```

**Props:**
- `title?: string` - Заголовок (по умолчанию "Свой Хлеб")
- `showBack?: boolean` - Показывать кнопку "Назад"
- `showSearch?: boolean` - Показывать кнопку поиска (по умолчанию true)
- `showThemeToggle?: boolean` - Показывать переключатель темы (по умолчанию true)

**События:**
- Кнопка "Назад" → `navigate(-1)`
- Переключатель темы → `toggleTheme()` из `useTheme()`
- Кнопка поиска → `navigate('/search')`

---

### 3.2 BottomNavigation

**Описание:** Нижняя навигация с иконками.

**Структура HTML:**
```html
<nav className="fixed bottom-0 left-0 right-0 z-50 border-t bg-background">
  <div className="flex h-14 items-center justify-around">
    {navItems.map(item => (
      <button 
        onClick={() => navigate(item.path)}
        className={isActive ? 'active' : ''}
      >
        <div className="relative">
          <Icon />
          {showBadge && item.showBadge && totalItems > 0 && (
            <span className="badge">{totalItems > 99 ? '99+' : totalItems}</span>
          )}
        </div>
        <span>{item.label}</span>
      </button>
    ))}
  </div>
</nav>
```

**Пункты навигации:**
1. `/` - Каталог (Home icon)
2. `/cart` - Корзина (ShoppingCart icon, с бейджем количества)
3. `/orders` - Заказы (ClipboardList icon)
4. `/about` - О нас (Info icon)

**События:**
- Клик по пункту → `navigate(item.path)`
- Активный пункт определяется по `location.pathname`

**Store:**
- `useCartStore` - Получение `totalItems` для бейджа на корзине

---

### 3.3 ProductCard

**Описание:** Карточка товара в двух вариантах: grid (2 колонки) и list (список).

**Структура HTML (Grid):**
```html
<div className="card rounded-xl" onClick={onClick}>
  <!-- Квадратное изображение 1:1 -->
  <div className="aspect-square bg-muted">
    <OptimizedImage src={product.imageUrl} />
  </div>
  
  <!-- Информация -->
  <div className="p-2.5">
    <h3 className="line-clamp-2">{product.name}</h3>
    <p className="line-clamp-1 text-xs">{product.description}</p>
    
    <div className="flex justify-between items-center mt-auto">
      <span className="text-sm font-bold">{product.price} ₽</span>
      
      {quantity > 0 ? (
        <!-- Кнопки +/- -->
        <div className="flex items-center gap-0.5">
          <button onClick={handleDecrement}>-</button>
          <span>{quantity}</span>
          <button onClick={handleIncrement}>+</button>
        </div>
      ) : (
        <!-- Кнопка добавления -->
        <button onClick={handleAddToCart}>
          <Plus />
        </button>
      )}
    </div>
  </div>
</div>
```

**Структура HTML (List):**
```html
<div className="flex gap-3 border-b py-3" onClick={onClick}>
  <!-- Изображение 88x88 -->
  <div className="h-[88px] w-[88px] rounded-xl">
    <OptimizedImage src={product.imageUrl} />
  </div>
  
  <!-- Информация -->
  <div className="flex-1">
    <h3 className="line-clamp-2">{product.name}</h3>
    <p className="line-clamp-1 text-xs">{product.description}</p>
    <span>{product.price} ₽</span>
  </div>
  
  <!-- Кнопки управления -->
  {quantity > 0 ? (
    <div className="flex items-center gap-1">
      <button onClick={handleDecrement}>-</button>
      <span>{quantity}</span>
      <button onClick={handleIncrement}>+</button>
    </div>
  ) : (
    <button onClick={handleAddToCart}>+</button>
  )}
</div>
```

**Props:**
- `product: Product` - Данные товара
- `onClick?: () => void` - Обработчик клика по карточке
- `variant?: 'grid' | 'list'` - Вариант отображения (по умолчанию 'grid')

**События:**
- Клик по карточке → `onClick()` (обычно переход на `/product/:productId`)
- `handleAddToCart` → Добавляет товар в корзину через `addItem()`, показывает toast
- `handleIncrement` → `addItem(product)`
- `handleDecrement` → `updateQuantity(product.id, quantity - 1)`

**Store:**
- `useCartStore` - Проверка наличия товара в корзине, добавление/обновление

---

### 3.4 CartItem

**Описание:** Элемент корзины с управлением количеством.

**Структура HTML:**
```html
<div className="card" onClick={handleCardClick}>
  <!-- Изображение 60x60 -->
  <div className="h-[60px] w-[60px] rounded-lg">
    <OptimizedImage src={item.product.imageUrl} />
  </div>
  
  <!-- Информация -->
  <div className="flex-1">
    <div className="flex justify-between">
      <h3 className="line-clamp-2">{item.product.name}</h3>
      <button onClick={(e) => { e.stopPropagation(); removeItem(item.product.id); }}>
        <Trash2 />
      </button>
    </div>
    
    <div className="flex justify-between mt-2">
      <!-- Кнопки управления количеством -->
      <div className="flex items-center gap-1">
        <button onClick={(e) => { e.stopPropagation(); updateQuantity(item.product.id, item.quantity - 1); }}>
          <Minus />
        </button>
        <span>{item.quantity}</span>
        <button onClick={(e) => { e.stopPropagation(); updateQuantity(item.product.id, item.quantity + 1); }}>
          <Plus />
        </button>
      </div>
      
      <!-- Итоговая цена -->
      <span className="font-bold">{(item.product.price * item.quantity)} ₽</span>
    </div>
  </div>
</div>
```

**События:**
- Клик по карточке → Переход на `/product/:productId`
- Кнопка удаления → `removeItem(productId)`
- Кнопки +/- → `updateQuantity(productId, quantity)`
- Все обработчики используют `e.stopPropagation()` чтобы не срабатывал клик по карточке

**Store:**
- `useCartStore` - Все операции с товаром в корзине

---

### 3.5 CategoryTabs

**Описание:** Горизонтальные табы категорий с горизонтальным скроллом.

**Структура HTML:**
```html
<div className="flex gap-2 overflow-x-auto px-4 py-2.5">
  <!-- Кнопка "Все" -->
  <button 
    onClick={() => onCategoryChange(null)}
    className={activeCategory === null ? 'active' : ''}
  >
    Все
  </button>
  
  <!-- Табы категорий -->
  {categories.map(category => (
    <button
      onClick={() => onCategoryChange(category.id)}
      className={activeCategory === category.id ? 'active' : ''}
    >
      {category.name}
    </button>
  ))}
</div>
```

**Props:**
- `categories: Category[]` - Список категорий
- `activeCategory: string | null` - ID активной категории
- `onCategoryChange: (categoryId: string | null) => void` - Обработчик смены категории

**События:**
- Клик по табу → Вызывает `onCategoryChange(categoryId)`

---

### 3.6 DeliveryModeToggle

**Описание:** Переключатель режима доставки (Доставка/Самовывоз).

**Структура HTML:**
```html
<div className="flex rounded-full bg-secondary p-1">
  <button
    onClick={() => onChange('delivery')}
    className={value === 'delivery' ? 'active' : ''}
  >
    Доставка
  </button>
  <button
    onClick={() => onChange('pickup')}
    className={value === 'pickup' ? 'active' : ''}
  >
    Самовывоз
  </button>
</div>
```

**Props:**
- `value: 'pickup' | 'delivery'` - Текущее значение
- `onChange: (value: 'pickup' | 'delivery') => void` - Обработчик изменения

**События:**
- Клик по кнопке → Вызывает `onChange()` с новым значением

---

### 3.7 DeliveryProgressIndicator

**Описание:** Прогресс-бар для отображения прогресса до минимальной суммы бесплатной доставки.

**Логика:**
- Показывается только если `orderMode === 'delivery'`
- Отображает прогресс от текущей суммы корзины до `freeDeliveryThreshold`
- Если `freeDeliveryThreshold` не задан, не показывается

---

### 3.8 OrderCard

**Описание:** Карточка заказа в списке заказов.

**Отображает:**
- Номер заказа
- Статус заказа (бейдж)
- Статус оплаты
- Итоговая сумма
- Дата создания
- Кнопки действий (Оплатить, Отменить)

---

### 3.9 OptimizedImage

**Описание:** Компонент для оптимизированной загрузки изображений с поддержкой WebP.

**Логика:**
- Поддерживает WebP формат
- Варианты размеров: thumbnail, medium, large
- Lazy loading
- Плейсхолдер при загрузке

---

## 4. Store (Zustand)

### 4.1 cartStore

**Описание:** Состояние корзины с персистентностью в localStorage.

**Методы:**
- `addItem(product, quantity = 1)` - Добавить товар или увеличить количество
- `removeItem(productId)` - Удалить товар из корзины
- `updateQuantity(productId, quantity)` - Обновить количество (при quantity <= 0 удаляет)
- `clearCart()` - Очистить корзину
- `getTotalItems()` - Получить общее количество товаров
- `getTotalAmount()` - Получить итоговую сумму

**Персистентность:**
- Сохраняется в localStorage с ключом `cart-storage`

---

### 4.2 catalogStore

**Описание:** Состояние каталога (активная категория, позиция скролла).

**Методы:**
- `setActiveCategoryId(categoryId)` - Установить активную категорию
- `setScrollY(scrollY)` - Сохранить позицию скролла

**Использование:**
- Для восстановления позиции скролла при возврате на страницу каталога
- Для сохранения выбранной категории

---

### 4.3 ordersStore

**Описание:** Состояние заказов (список заказов, загрузка, ошибки).

**Методы:**
- `loadOrders(force = false)` - Загрузить заказы пользователя
- `getOrderById(orderId)` - Получить заказ по ID из кеша
- `createOrder(payload)` - Создать новый заказ

**Использование:**
- В `useOrders` хуке для управления заказами

---

## 5. API клиенты

### 5.1 Структура API запросов

**Базовый URL:** `/api/v1`

**Заголовки:**
- `Content-Type: application/json`
- `Accept: application/json`
- `Authorization: Bearer {token}` (если есть)
- `X-Telegram-Init-Data: {initData}` (если в Telegram WebApp)

**Обработка ответов:**
- Поддерживает разные форматы ответов (массив, объект с `data`, пагинация)
- Автоматическая нормализация данных

---

### 5.2 Основные API модули

#### categoriesAPI
- `getAll()` - Получить все категории
- `updatePositions(categories)` - Обновить позиции категорий (admin)

#### productsAPI
- `getAll(categoryId?)` - Получить все товары (с фильтром по категории)
- `getById(id)` - Получить товар по ID
- `updatePositions(products)` - Обновить позиции товаров (admin)

#### ordersAPI
- `create(payload, telegramId)` - Создать заказ
- `getByTelegramId(telegramId)` - Получить заказы по Telegram ID
- `getByOrderId(orderId)` - Получить заказ по orderId
- `cancelOrder(orderId)` - Отменить заказ
- `getPaymentLink(orderId)` - Получить ссылку на оплату
- `updatePaymentStatus(orderId, paymentId, status)` - Обновить статус оплаты

#### paymentMethodsAPI
- `getAll()` - Получить все способы оплаты
- `getById(id, cartAmount?)` - Получить способ оплаты с расчетом скидки

#### paymentAPI
- `createYooKassaPayment(orderId, amount, returnUrl, description?, telegramId?, email?)` - Создать платеж ЮКассы

#### deliverySettingsAPI
- `getSettings()` - Получить настройки доставки
- `updateSettings(data)` - Обновить настройки доставки (admin)
- `calculateCost(address, cartTotal)` - Рассчитать стоимость доставки
- `getAddressSuggestions(query, city?)` - Получить подсказки адресов

#### aboutAPI
- `get()` - Получить данные страницы "О нас"

#### legalDocumentsAPI
- `getAll()` - Получить все юридические документы
- `getByType(type)` - Получить документ по типу
- `getAdmin()` - Получить документы для админки
- `update(documents)` - Обновить документы (admin)

---

## 6. Интеграция с Telegram WebApp

### 6.1 Инициализация

**Файл:** `src/lib/telegram.ts`

**Функции:**
- `initTelegramWebApp()` - Инициализация WebApp API
  - Вызывает `tg.ready()`
  - Вызывает `tg.expand()` для полного экрана
  - Устанавливает темную тему при необходимости
- `getTelegramUser()` - Получить данные пользователя из `initDataUnsafe.user`
- `getTelegramTheme()` - Получить тему из `tg.colorScheme`
- `hapticFeedback(type)` - Тактильная обратная связь
  - `light/medium/heavy` - Вибрация
  - `success/error/warning` - Уведомления
  - `selection` - Выбор
- `showTelegramPopup(message, title?, callback?)` - Показать popup
- `showTelegramConfirm(message, callback)` - Показать подтверждение
- `openTelegramLink(url)` - Открыть Telegram ссылку
- `closeTelegramApp()` - Закрыть приложение

### 6.2 Использование

**В компонентах:**
- Автоматическая инициализация в `main.tsx`
- Получение данных пользователя для создания заказов
- Использование haptic feedback для улучшения UX
- Открытие Telegram ссылок для поддержки

---

## 7. Используемые пакеты и плагины

### 7.1 UI библиотеки

**Radix UI** (через shadcn/ui):
- `@radix-ui/react-accordion` - Аккордеон
- `@radix-ui/react-alert-dialog` - Диалог с подтверждением
- `@radix-ui/react-dialog` - Модальное окно
- `@radix-ui/react-popover` - Всплывающее окно
- `@radix-ui/react-select` - Выпадающий список
- `@radix-ui/react-tabs` - Табы
- `@radix-ui/react-toast` - Уведомления
- И другие компоненты...

**shadcn/ui компоненты:**
- Все компоненты находятся в `src/components/ui/`
- Настроены через `components.json`
- Кастомизированы через Tailwind CSS

### 7.2 Утилиты

- `lucide-react` - Иконки
- `clsx` + `tailwind-merge` - Условные классы CSS
- `class-variance-authority` - Вариации компонентов
- `date-fns` - Работа с датами
- `zod` - Валидация схем
- `react-hook-form` - Управление формами
- `@hookform/resolvers` - Резолверы для react-hook-form

### 7.3 Специфичные плагины

- `embla-carousel-react` - Карусель для изображений
- `react-day-picker` - Календарь для выбора даты
- `sonner` - Toast уведомления (используется вместо shadcn/ui toast)
- `recharts` - Графики (для админки)
- `cmdk` - Command menu (для админки)

### 7.4 Dev зависимости

- `@vitejs/plugin-react-swc` - SWC компилятор для React
- `autoprefixer` - Автоматические префиксы CSS
- `postcss` - Обработка CSS
- `tailwindcss` - Utility-first CSS фреймворк
- `typescript` - Типизация
- `eslint` - Линтинг
- `lovable-tagger` - Тегирование компонентов (dev mode)

---

## 8. Типы данных (TypeScript)

### 8.1 Основные типы

**Product:**
```typescript
{
  id: string;
  name: string;
  description: string;
  price: number;
  categoryId: string;
  imageUrl: string;
  webpUrl?: string;
  imageVariants?: {...};
  isWeightProduct: boolean;
  sortOrder?: number;
  createdAt: Date;
  updatedAt: Date;
}
```

**Category:**
```typescript
{
  id: string;
  name: string;
  sortOrder?: number;
  isActive?: boolean;
  createdAt: Date;
  updatedAt: Date;
}
```

**Order:**
```typescript
{
  id: string;
  orderId: string; // ORD-20251220-1
  telegramId: number;
  status: OrderStatus;
  phone: string;
  name?: string;
  deliveryAddress: string;
  deliveryTime: string;
  comment?: string;
  totalAmount: number;
  items: OrderItem[];
  paymentId?: string;
  paymentStatus: PaymentStatus;
  createdAt: Date;
  updatedAt: Date;
}
```

**OrderStatus:** `'new' | 'accepted' | 'preparing' | 'ready_for_delivery' | 'in_transit' | 'delivered' | 'cancelled'`

**PaymentStatus:** `'pending' | 'succeeded' | 'failed' | 'cancelled'`

---

## 9. Стилизация

### 9.1 Tailwind CSS

**Конфигурация:** `tailwind.config.ts`

**Особенности:**
- Использует CSS переменные для темной/светлой темы
- Кастомные классы для анимаций
- Safe area insets для мобильных устройств

**Цветовая схема:**
- `background` - Фон
- `foreground` - Текст
- `primary` - Основной цвет
- `secondary` - Вторичный цвет
- `muted` - Приглушенный цвет
- `destructive` - Цвет ошибки
- `border` - Цвет границы

### 9.2 Темы

**Переключение темы:**
- Через `useTheme` хук
- Сохраняется в localStorage
- Синхронизируется с Telegram WebApp theme

---

## 10. Производительность

### 10.1 Оптимизации

- **Lazy loading изображений** - Компонент `OptimizedImage`
- **Debounce поиска** - 200ms задержка
- **Debounce расчета доставки** - 1000ms задержка
- **Мемоизация** - `useMemo` для фильтрации и сортировки
- **Персистентность** - Zustand stores с localStorage
- **Кеширование** - React Query для API запросов

### 10.2 Загрузка данных

- **Предзагрузка** - Данные загружаются через React Query
- **Фоновая загрузка** - Обновление заказов в фоне
- **Восстановление состояния** - Сохранение позиции скролла и категории

---

## 11. Безопасность

### 11.1 Telegram WebApp

- Валидация `initData` на сервере
- Передача `X-Telegram-Init-Data` заголовка в API запросах
- Проверка владельца заказа перед операциями

### 11.2 API запросы

- Авторизация через Bearer token (для админки)
- Проверка telegram_id для пользовательских операций
- Валидация данных на клиенте и сервере

---

## Заключение

Этот документ описывает полную структуру фронтенд приложения Express CMS, включая все страницы, компоненты, события, API вызовы и используемые технологии. Приложение построено на React + TypeScript с использованием современных практик разработки и интеграции с Telegram WebApp API.
