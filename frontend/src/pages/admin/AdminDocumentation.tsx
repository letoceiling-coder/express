import { useState } from 'react';
import { FileText, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Section {
  id: string;
  title: string;
  children?: { id: string; title: string }[];
}

const sections: Section[] = [
  { id: 'overview', title: 'Обзор системы' },
  { id: 'getting-started', title: 'Быстрый старт' },
  {
    id: 'admin-sections',
    title: 'Разделы админ-панели',
    children: [
      { id: 'dashboard', title: 'Главная' },
      { id: 'orders', title: 'Заказы' },
      { id: 'products', title: 'Товары' },
      { id: 'categories', title: 'Категории' },
      { id: 'about', title: 'О нас' },
      { id: 'banners', title: 'Баннеры' },
      { id: 'users', title: 'Пользователи' },
      { id: 'notifications', title: 'Уведомления' },
      { id: 'yookassa', title: 'ЮKassa (платежи)' },
      { id: 'delivery', title: 'Доставка' },
      { id: 'sms', title: 'SMS (IQSMS)' },
    ],
  },
];

function DocSection({ children }: { children: React.ReactNode }) {
  return <div className="space-y-4 text-slate-700 dark:text-slate-300 leading-relaxed">{children}</div>;
}

function DocH2({ children }: { children: React.ReactNode }) {
  return <h2 className="text-xl font-semibold text-foreground mt-8 mb-3 border-b pb-2">{children}</h2>;
}

function DocH3({ children }: { children: React.ReactNode }) {
  return <h3 className="text-lg font-medium text-foreground mt-6 mb-2">{children}</h3>;
}

function DocP({ children }: { children: React.ReactNode }) {
  return <p className="mb-3">{children}</p>;
}

function DocUl({ children }: { children: React.ReactNode }) {
  return <ul className="list-disc list-inside mb-4 space-y-1">{children}</ul>;
}

function DocOl({ children }: { children: React.ReactNode }) {
  return <ol className="list-decimal list-inside mb-4 space-y-1">{children}</ol>;
}

function DocCode({ children }: { children: React.ReactNode }) {
  return (
    <code className="px-1.5 py-0.5 rounded bg-muted text-sm font-mono">{children}</code>
  );
}

function DocPre({ children }: { children: React.ReactNode }) {
  return (
    <pre className="p-4 rounded-lg bg-muted overflow-x-auto text-sm mb-4 font-mono">
      {children}
    </pre>
  );
}

function DocExample({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="my-4 p-4 rounded-lg border border-border bg-muted/30">
      <p className="text-sm font-medium text-muted-foreground mb-2">{title}</p>
      {children}
    </div>
  );
}

const content: Record<string, React.ReactNode> = {
  overview: (
    <DocSection>
      <DocH2>Обзор системы</DocH2>
      <DocP>
        Админ-панель — веб-интерфейс для управления магазином: каталогом, заказами, настройками и контентом.
      </DocP>
      <DocH3>Основные возможности</DocH3>
      <DocUl>
        <li>Управление заказами и их статусами</li>
        <li>Каталог товаров и категорий</li>
        <li>Страница «О нас» и баннеры на главной</li>
        <li>Управление пользователями с доступом к админ-панели</li>
        <li>Настройка уведомлений</li>
        <li>Интеграция с ЮKassa для приёма платежей</li>
        <li>Настройка доставки и зон</li>
        <li>Настройка SMS для авторизации клиентов</li>
      </DocUl>
      <DocH3>Вход в админ-панель</DocH3>
      <DocP>Перейдите по адресу <DocCode>/admin</DocCode>. Для входа нужна авторизация (через веб-авторизацию по телефону или email/пароль, в зависимости от настройки).</DocP>
    </DocSection>
  ),

  'getting-started': (
    <DocSection>
      <DocH2>Быстрый старт</DocH2>
      <DocH3>Шаг 1. Войдите в админ-панель</DocH3>
      <DocP>Откройте сайт и перейдите в раздел админки. Авторизуйтесь.</DocP>
      <DocH3>Шаг 2. Настройте платёжную систему</DocH3>
      <DocP>Перейдите в <strong>Настройки → ЮKassa</strong>. Укажите Shop ID и секретный ключ из личного кабинета ЮKassa.</DocP>
      <DocH3>Шаг 3. Добавьте категории и товары</DocH3>
      <DocP>Создайте категории в разделе <strong>Категории</strong>, затем добавьте товары в <strong>Товары</strong>.</DocP>
      <DocH3>Шаг 4. Настройте доставку (если нужна)</DocH3>
      <DocP>В <strong>Настройки → Доставка</strong> укажите адрес склада и зоны доставки.</DocP>
      <DocH3>Шаг 5. Настройте SMS (для веб-заказов)</DocH3>
      <DocP>В <strong>Настройки → SMS (IQSMS)</strong> введите логин и пароль от IQSMS для отправки кодов подтверждения.</DocP>
    </DocSection>
  ),

  dashboard: (
    <DocSection>
      <DocH2>Главная (Dashboard)</DocH2>
      <DocP>Путь: <DocCode>/admin</DocCode></DocP>
      <DocP>На главной отображаются общая статистика: активные заказы, заказы за сегодня, выручка. Карточки ведут в соответствующие разделы.</DocP>
      <DocExample title="Что видно на главной">
        <ul className="list-disc list-inside space-y-1 text-sm">
          <li>Активные заказы (не доставленные и не отменённые)</li>
          <li>Заказы за сегодня</li>
          <li>Общая выручка</li>
          <li>Быстрые ссылки: Заказы, Товары, Категории</li>
        </ul>
      </DocExample>
    </DocSection>
  ),

  orders: (
    <DocSection>
      <DocH2>Заказы</DocH2>
      <DocP>Путь: <DocCode>/admin/orders</DocCode></DocP>
      <DocP>Просмотр и управление заказами клиентов.</DocP>
      <DocH3>Функции</DocH3>
      <DocUl>
        <li>Список заказов с фильтрацией по статусу и поиску</li>
        <li>Просмотр деталей заказа</li>
        <li>Изменение статуса заказа</li>
      </DocUl>
      <DocH3>Статусы заказа</DocH3>
      <DocUl>
        <li><DocCode>new</DocCode> — Новый</li>
        <li><DocCode>accepted</DocCode> — Принят</li>
        <li><DocCode>preparing</DocCode> — Готовится</li>
        <li><DocCode>ready_for_delivery</DocCode> — Готов к доставке</li>
        <li><DocCode>in_transit</DocCode> — В пути</li>
        <li><DocCode>delivered</DocCode> — Доставлен</li>
        <li><DocCode>cancelled</DocCode> — Отменён</li>
      </DocUl>
      <DocExample title="Как изменить статус">
        <ol className="list-decimal list-inside space-y-1 text-sm">
          <li>Откройте заказ кликом по строке</li>
          <li>Выберите новый статус в выпадающем списке</li>
          <li>Статус сохраняется автоматически</li>
        </ol>
      </DocExample>
    </DocSection>
  ),

  products: (
    <DocSection>
      <DocH2>Товары</DocH2>
      <DocP>Путь: <DocCode>/admin/products</DocCode></DocP>
      <DocP>Управление каталогом товаров.</DocP>
      <DocH3>Функции</DocH3>
      <DocUl>
        <li>Список товаров с поиском и фильтром по категории</li>
        <li>Добавление нового товара</li>
        <li>Редактирование товара (название, описание, цена, изображение, категория)</li>
        <li>Удаление товара</li>
      </DocUl>
      <DocH3>Поля товара</DocH3>
      <DocUl>
        <li><strong>Название</strong> — обязательно</li>
        <li><strong>Описание</strong> — текст о товаре</li>
        <li><strong>Цена</strong> — в рублях</li>
        <li><strong>Категория</strong> — выбор из списка</li>
        <li><strong>Изображение</strong> — URL или загрузка</li>
        <li><strong>Весовой товар</strong> — для товаров на развес (цена за единицу)</li>
      </DocUl>
      <DocExample title="Создание товара">
        <ol className="list-decimal list-inside space-y-1 text-sm">
          <li>Нажмите «Добавить товар»</li>
          <li>Заполните название, описание, цену, выберите категорию</li>
          <li>Добавьте изображение (по необходимости)</li>
          <li>Нажмите «Сохранить»</li>
        </ol>
      </DocExample>
    </DocSection>
  ),

  categories: (
    <DocSection>
      <DocH2>Категории</DocH2>
      <DocP>Путь: <DocCode>/admin/categories</DocCode></DocP>
      <DocP>Группировка товаров по категориям (например: «Мангал», «Напитки», «Десерты»).</DocP>
      <DocH3>Функции</DocH3>
      <DocUl>
        <li>Список категорий</li>
        <li>Создание новой категории</li>
        <li>Редактирование и удаление категории</li>
        <li>Порядок сортировки (влияет на отображение в каталоге)</li>
      </DocUl>
      <DocExample title="Создание категории">
        <ol className="list-decimal list-inside space-y-1 text-sm">
          <li>Нажмите «Добавить категорию»</li>
          <li>Введите название (например: «Шашлык»)</li>
          <li>При необходимости задайте порядок сортировки</li>
          <li>Сохраните</li>
        </ol>
      </DocExample>
    </DocSection>
  ),

  about: (
    <DocSection>
      <DocH2>О нас</DocH2>
      <DocP>Путь: <DocCode>/admin/about</DocCode></DocP>
      <DocP>Редактирование контента страницы «О нас»: текст, изображения, преимущества.</DocP>
      <DocH3>Функции</DocH3>
      <DocUl>
        <li>Заголовок и основной текст</li>
        <li>Обложка страницы (изображение)</li>
        <li>Блоки преимуществ (иконка, заголовок, описание)</li>
        <li>Галерея изображений</li>
      </DocUl>
    </DocSection>
  ),

  banners: (
    <DocSection>
      <DocH2>Баннеры</DocH2>
      <DocP>Путь: <DocCode>/admin/banners</DocCode></DocP>
      <DocP>Баннеры отображаются на главной странице в слайдере.</DocP>
      <DocH3>Функции</DocH3>
      <DocUl>
        <li>Список баннеров</li>
        <li>Создание баннера (изображение, заголовок, подзаголовок, ссылка)</li>
        <li>Редактирование и удаление</li>
        <li>Порядок отображения</li>
      </DocUl>
      <DocExample title="Создание баннера">
        <ol className="list-decimal list-inside space-y-1 text-sm">
          <li>Нажмите «Добавить баннер»</li>
          <li>Загрузите изображение (рекомендуется 1200×400 px)</li>
          <li>Введите заголовок и текст кнопки (например: «В каталог»)</li>
          <li>Укажите ссылку (например: /#products)</li>
          <li>Сохраните</li>
        </ol>
      </DocExample>
    </DocSection>
  ),

  users: (
    <DocSection>
      <DocH2>Пользователи</DocH2>
      <DocP>Путь: <DocCode>/admin/users</DocCode></DocP>
      <DocP>Управление пользователями с доступом к админ-панели.</DocP>
      <DocH3>Функции</DocH3>
      <DocUl>
        <li>Список пользователей с поиском и фильтром по роли</li>
        <li>Добавление пользователя (имя, email, пароль, роли)</li>
        <li>Редактирование пользователя</li>
        <li>Удаление пользователя (нельзя удалить себя)</li>
      </DocUl>
      <DocH3>Роли</DocH3>
      <DocUl>
        <li><strong>admin</strong> — полный доступ</li>
        <li><strong>manager</strong> — доступ к контенту и заказам</li>
        <li><strong>user</strong> — ограниченный доступ</li>
      </DocUl>
      <DocExample title="Добавление менеджера">
        <ol className="list-decimal list-inside space-y-1 text-sm">
          <li>Нажмите «Добавить пользователя»</li>
          <li>Введите имя, email и пароль</li>
          <li>Отметьте роли (например: manager)</li>
          <li>Сохраните</li>
        </ol>
      </DocExample>
    </DocSection>
  ),

  notifications: (
    <DocSection>
      <DocH2>Уведомления</DocH2>
      <DocP>Путь: <DocCode>/admin/notifications</DocCode></DocP>
      <DocP>Настройка уведомлений для клиентов и администраторов.</DocP>
      <DocH3>Типы уведомлений</DocH3>
      <DocUl>
        <li>Уведомление клиенту при создании заказа</li>
        <li>Уведомление администратору при новом заказе</li>
        <li>Уведомление клиенту при принятии заказа</li>
      </DocUl>
      <DocP>Для каждого типа можно включить/выключить отправку. История отправок — в разделе <DocCode>История уведомлений</DocCode>.</DocP>
    </DocSection>
  ),

  yookassa: (
    <DocSection>
      <DocH2>ЮKassa (платежи)</DocH2>
      <DocP>Путь: <DocCode>/admin/settings/payments/yookassa</DocCode></DocP>
      <DocP>Настройка приёма платежей через ЮKassa.</DocP>
      <DocH3>Параметры</DocH3>
      <DocUl>
        <li><strong>Shop ID</strong> — идентификатор магазина из личного кабинета ЮKassa</li>
        <li><strong>Секретный ключ</strong> — для подписи запросов</li>
        <li><strong>Тестовый режим</strong> — использовать тестовые Shop ID и ключ</li>
        <li><strong>Включить</strong> — активировать приём платежей</li>
      </DocUl>
      <DocExample title="Где взять данные">
        <ol className="list-decimal list-inside space-y-1 text-sm">
          <li>Войдите в личный кабинет ЮKassa (yookassa.ru)</li>
          <li>Раздел «Настройки» → «Ключи API»</li>
          <li>Скопируйте Shop ID и секретный ключ</li>
          <li>Вставьте в настройки админ-панели и сохраните</li>
        </ol>
      </DocExample>
    </DocSection>
  ),

  delivery: (
    <DocSection>
      <DocH2>Доставка</DocH2>
      <DocP>Путь: <DocCode>/admin/settings/delivery</DocCode></DocP>
      <DocP>Настройка зон доставки и стоимости.</DocP>
      <DocH3>Параметры</DocH3>
      <DocUl>
        <li><strong>API-ключ Yandex Geocoder</strong> — для расчёта расстояний и адресов</li>
        <li><strong>Адрес склада</strong> — точка отправления</li>
        <li><strong>Зоны доставки</strong> — расстояние (км) и стоимость (руб)</li>
        <li><strong>Бесплатная доставка</strong> — от какой суммы заказа</li>
      </DocUl>
      <DocExample title="Пример зон">
        <ul className="list-disc list-inside space-y-1 text-sm">
          <li>До 3 км — 300 ₽</li>
          <li>До 7 км — 500 ₽</li>
          <li>До 12 км — 800 ₽</li>
          <li>Более 12 км — 1000 ₽</li>
        </ul>
      </DocExample>
    </DocSection>
  ),

  sms: (
    <DocSection>
      <DocH2>SMS (IQSMS)</DocH2>
      <DocP>Путь: <DocCode>/admin/sms-settings</DocCode></DocP>
      <DocP>Настройка отправки SMS для веб-авторизации клиентов (код подтверждения по телефону).</DocP>
      <DocH3>Параметры</DocH3>
      <DocUl>
        <li><strong>Логин</strong> — логин от IQSMS (iqsms.ru)</li>
        <li><strong>Пароль</strong> — пароль от IQSMS</li>
        <li><strong>Отправитель</strong> — короткое имя (например: INFO)</li>
        <li><strong>Включить</strong> — активировать отправку реальных SMS</li>
      </DocUl>
      <DocExample title="Как настроить">
        <ol className="list-decimal list-inside space-y-1 text-sm">
          <li>Зарегистрируйтесь на iqsms.ru</li>
          <li>Пополните баланс</li>
          <li>Введите логин и пароль в настройках</li>
          <li>Включите отправку и сохраните</li>
        </ol>
      </DocExample>
    </DocSection>
  ),
};

export function AdminDocumentation() {
  const [activeSection, setActiveSection] = useState('overview');

  const currentContent = content[activeSection] ?? (
    <DocSection>
      <DocP>Раздел в разработке.</DocP>
    </DocSection>
  );

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-foreground flex items-center gap-2">
          <FileText className="h-7 w-7" />
          Документация
        </h1>
        <p className="text-muted-foreground mt-1">
          Инструкция по использованию админ-панели
        </p>
      </div>

      <div className="flex flex-col lg:flex-row gap-6">
        <aside className="w-full lg:w-64 flex-shrink-0">
          <nav className="bg-card rounded-lg border border-border p-4 sticky top-6 max-h-[calc(100vh-8rem)] overflow-y-auto">
            <div className="space-y-1">
              {sections.map((section) =>
                section.children ? (
                  <div key={section.id}>
                    <div className="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                      {section.title}
                    </div>
                    <div className="ml-2 mt-1 space-y-0.5">
                      {section.children.map((child) => (
                        <button
                          key={child.id}
                          onClick={() => setActiveSection(child.id)}
                          className={cn(
                            'w-full flex items-center gap-2 text-left px-3 py-2 rounded-lg text-sm transition-colors',
                            activeSection === child.id
                              ? 'bg-primary text-primary-foreground'
                              : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                          )}
                        >
                          <ChevronRight className={cn('h-4 w-4', activeSection === child.id && 'text-primary-foreground')} />
                          {child.title}
                        </button>
                      ))}
                    </div>
                  </div>
                ) : (
                  <button
                    key={section.id}
                    onClick={() => setActiveSection(section.id)}
                    className={cn(
                      'w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                      activeSection === section.id
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    )}
                  >
                    {section.title}
                  </button>
                )
              )}
            </div>
          </nav>
        </aside>

        <main className="flex-1 min-w-0">
          <div className="bg-card rounded-lg border border-border p-6 lg:p-8">
            {currentContent}
          </div>
        </main>
      </div>
    </div>
  );
}
