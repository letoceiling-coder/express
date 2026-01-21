# Реализация системы управления правовыми документами

## Дата выполнения
Текущая сессия

## Краткое описание
Реализована система управления правовыми документами (Политика конфиденциальности, Оферта, Контакты) для админ-панели на Vue и добавлены ссылки на документы в Mini App.

---

## Выполненные задачи

### 1. Исправление архитектуры админ-панели
**Проблема:** Админ-панель должна работать только на Vue (Laravel Vue), а не на React.

**Выполнено:**
- ✅ Удален React компонент `frontend/src/pages/admin/AdminLegalDocuments.tsx`
- ✅ Удалены импорты и роуты React админки из `frontend/src/App.tsx`
- ✅ Удалена ссылка на документы из React админ-меню `frontend/src/components/admin/AdminLayout.tsx`

### 2. Создание Vue компонента для управления документами
**Файл:** `resources/js/pages/admin/LegalDocuments.vue`

**Функционал:**
- Управление тремя типами документов:
  - Политика конфиденциальности (`privacy_policy`)
  - Оферта / Соглашение (`offer`)
  - Контакты (`contacts`)
- Для каждого документа доступны поля:
  - Название (обязательное)
  - URL документа (опционально)
  - Текстовое содержимое (опционально)
  - Флаг активности (`is_active`)
- Форма с валидацией и обработкой ошибок
- Сохранение всех документов одним запросом

### 3. API интеграция
**Файл:** `resources/js/utils/api.js`

**Добавлено:**
```javascript
export const legalDocumentsAPI = {
    async getAdmin()      // Получить все документы (админ)
    async update(documents) // Обновить документы (админ)
}
```

**Эндпоинты:**
- `GET /api/v1/admin/legal-documents` - получение всех документов
- `PUT /api/v1/admin/legal-documents` - обновление документов

### 4. Роутинг и меню
**Файлы:**
- `resources/js/admin.js` - добавлен роут `admin.legal-documents`
- `app/Services/AdminMenu.php` - добавлен пункт меню "Документы"
- `resources/js/components/admin/Sidebar.vue` - добавлена иконка `file-text`

**Роут:**
```javascript
{
    path: 'legal-documents',
    name: 'admin.legal-documents',
    component: () => import('./pages/admin/LegalDocuments.vue'),
    meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Документы' },
}
```

**Меню:**
- Пункт "Документы" добавлен после "О нас"
- Доступен для ролей: `admin`, `manager`
- Иконка: `file-text`

### 5. Интеграция в Mini App (React)
**Страница документов:**
- `frontend/src/pages/miniapp/LegalDocumentsPage.tsx` - уже существовала, добавлен импорт в `App.tsx`

**Добавлены ссылки на документы:**

#### 5.1. AboutPage (`frontend/src/pages/miniapp/AboutPage.tsx`)
- Добавлена кнопка "Документы" в секцию быстрых действий
- Расположена рядом с кнопками: Телефон, Адрес, Поддержка
- Сетка изменена с `grid-cols-3` на `grid-cols-4`
- Импортированы: `FileText` из `lucide-react`, `useNavigate` из `react-router-dom`

#### 5.2. CheckoutPage (`frontend/src/pages/miniapp/CheckoutPage.tsx`)
- Добавлена ссылка на документы перед подтверждением заказа
- Отображается только на шаге 4 (подтверждение заказа)
- Текст: "Политика конфиденциальности и оферта"
- Импортирован: `FileText` из `lucide-react`

#### 5.3. OrdersPage (`frontend/src/pages/miniapp/OrdersPage.tsx`)
- Добавлена ссылка на документы внизу страницы
- Расположена перед `BottomNavigation`
- Текст: "Политика конфиденциальности и оферта"
- Импортирован: `FileText` из `lucide-react`

---

## Структура изменений

### Удаленные файлы
```
frontend/src/pages/admin/AdminLegalDocuments.tsx
```

### Созданные файлы
```
resources/js/pages/admin/LegalDocuments.vue
```

### Измененные файлы

#### Backend (Laravel)
- `app/Services/AdminMenu.php` - добавлен пункт меню

#### Frontend Admin (Vue)
- `resources/js/admin.js` - добавлен роут
- `resources/js/utils/api.js` - добавлены API функции
- `resources/js/components/admin/Sidebar.vue` - добавлена иконка

#### Frontend Mini App (React)
- `frontend/src/App.tsx` - добавлен импорт `LegalDocumentsPage`, удалены импорты React админки
- `frontend/src/components/admin/AdminLayout.tsx` - удалена ссылка на документы
- `frontend/src/pages/miniapp/AboutPage.tsx` - добавлена кнопка "Документы"
- `frontend/src/pages/miniapp/CheckoutPage.tsx` - добавлена ссылка на документы
- `frontend/src/pages/miniapp/OrdersPage.tsx` - добавлена ссылка на документы

---

## Технические детали

### Типы документов
1. **privacy_policy** - Политика конфиденциальности
2. **offer** - Публичная оферта / Соглашение
3. **contacts** - Контакты

### Структура данных документа
```typescript
{
    id: number | null,
    type: 'privacy_policy' | 'offer' | 'contacts',
    title: string,
    content: string | null,
    url: string | null,
    is_active: boolean,
    sort_order: number
}
```

### API запросы
- **GET** `/api/v1/admin/legal-documents` - получение всех документов
- **PUT** `/api/v1/admin/legal-documents` - обновление документов (массив документов)

### Права доступа
- Админ-панель: роли `admin`, `manager`
- Mini App: публичный доступ к просмотру активных документов

---

## Результат

✅ Админ-панель полностью работает на Vue (Laravel Vue)  
✅ Реализовано управление правовыми документами в админке  
✅ Добавлены ссылки на документы в ключевых местах Mini App:
   - Страница "О нас" (кнопка в быстрых действиях)
   - Страница оформления заказа (перед подтверждением)
   - Страница "Мои заказы" (внизу страницы)

---

## Примечания

- Все изменения соответствуют существующей архитектуре проекта
- Админ-панель строго на Vue, Mini App на React
- API эндпоинты уже были реализованы ранее в `LegalDocumentsController.php`
- Компонент `LegalDocumentsPage.tsx` для Mini App уже существовал, добавлены только ссылки на него
