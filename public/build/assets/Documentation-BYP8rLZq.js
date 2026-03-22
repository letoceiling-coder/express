import{_ as g,c as o,o as l,a as e,F as n,q as c,p,t as a}from"./admin-B0uRBwbT.js";const m={name:"Documentation",data(){return{activeSection:"overview",sections:[{id:"overview",title:"📖 Обзор системы"},{id:"getting-started",title:"🚀 Быстрый старт"},{id:"admin-panel",title:"👨‍💼 Админ-панель",children:[{id:"admin-dashboard",title:"Главная"},{id:"admin-catalog",title:"Каталог"},{id:"admin-orders",title:"Заказы"},{id:"admin-feedback",title:"Обратная связь"},{id:"admin-media",title:"Медиа-библиотека"},{id:"admin-users",title:"Пользователи и роли"},{id:"admin-bot-users",title:"Пользователи бота и роли"},{id:"admin-settings",title:"Настройки"},{id:"admin-other",title:"Прочее"}]},{id:"api",title:"🔌 API",children:[{id:"api-auth",title:"Авторизация"},{id:"api-catalog",title:"Каталог"},{id:"api-orders",title:"Заказы"},{id:"api-payments",title:"Платежи"},{id:"api-media",title:"Медиа"},{id:"api-other",title:"Прочее"}]},{id:"deployment",title:"📦 Деплой"},{id:"image-optimization",title:"🖼️ Оптимизация изображений"}],documentation:{overview:this.getOverviewContent(),"getting-started":this.getGettingStartedContent(),"admin-dashboard":this.getAdminDashboardContent(),"admin-catalog":this.getAdminCatalogContent(),"admin-orders":this.getAdminOrdersContent(),"admin-feedback":this.getAdminFeedbackContent(),"admin-media":this.getAdminMediaContent(),"admin-users":this.getAdminUsersContent(),"admin-bot-users":this.getAdminBotUsersContent(),"admin-settings":this.getAdminSettingsContent(),"admin-other":this.getAdminOtherContent(),"api-auth":this.getApiAuthContent(),"api-catalog":this.getApiCatalogContent(),"api-orders":this.getApiOrdersContent(),"api-payments":this.getApiPaymentsContent(),"api-media":this.getApiMediaContent(),"api-other":this.getApiOtherContent(),deployment:this.getDeploymentContent(),"image-optimization":this.getImageOptimizationContent()}}},computed:{currentContent(){return this.documentation[this.activeSection]||"<p>Раздел в разработке</p>"}},methods:{scrollToTop(){window.scrollTo({top:0,behavior:"smooth"})},getOverviewContent(){return`
                <h2>Обзор системы</h2>
                <p>Backend система для Telegram Mini App приложений на базе Laravel 11 с полнофункциональной админ-панелью на Vue 3.</p>

                <h3>Основные возможности</h3>
                <ul>
                    <li><strong>Система авторизации</strong> - Sanctum токены, регистрация, восстановление пароля</li>
                    <li><strong>Админ-панель</strong> - Современный интерфейс на Vue 3 + TypeScript с адаптивным дизайном</li>
                    <li><strong>Управление каталогом</strong> - Полный CRUD для категорий и товаров с историей изменений</li>
                    <li><strong>Управление заказами</strong> - Система заказов, доставок и платежей с интеграцией ЮКасса</li>
                    <li><strong>Обратная связь</strong> - Возвраты, претензии и отзывы</li>
                    <li><strong>Медиа-библиотека</strong> - Организация и управление файлами с поддержкой папок, автоматическая оптимизация изображений в WebP</li>
                    <li><strong>Система уведомлений</strong> - Встроенная система уведомлений для пользователей</li>
                    <li><strong>Система поддержки</strong> - Тикеты и чат с интеграцией внешних CRM</li>
                    <li><strong>Управление пользователями и ролями</strong> - Гибкая система прав доступа (admin, manager, user)</li>
                    <li><strong>Проверка подписки</strong> - Интеграция с внешними системами управления подписками</li>
                    <li><strong>Telegram интеграция</strong> - Пакет для работы с Telegram Bot API и Mini Apps</li>
                    <li><strong>Автоматический деплой</strong> - GitHub Actions, webhooks, Git hooks</li>
                </ul>

                <h3>Технологический стек</h3>
                <ul>
                    <li><strong>Backend:</strong> Laravel 11, PHP 8.2+, MySQL/PostgreSQL</li>
                    <li><strong>Frontend (Админ):</strong> Vue 3, TypeScript, Vite, TailwindCSS</li>
                    <li><strong>Frontend (MiniApp):</strong> React 18, TypeScript, Vite, shadcn/ui</li>
                    <li><strong>Авторизация:</strong> Laravel Sanctum</li>
                    <li><strong>Платежи:</strong> ЮКасса (YooKassa)</li>
                    <li><strong>Обработка изображений:</strong> Intervention Image</li>
                    <li><strong>Интеграции:</strong> Telegram Bot API</li>
                </ul>

                <h3>Система ролей</h3>
                <ul>
                    <li><strong>admin</strong> - Полный доступ ко всем разделам админ-панели</li>
                    <li><strong>manager</strong> - Доступ к Каталогу, Заказам, Обратной связи, Медиа, Уведомлениям, Поддержке, Подписке, Документации</li>
                    <li><strong>user</strong> - Доступ только к Документации</li>
                </ul>
            `},getGettingStartedContent(){return`
                <h2>Быстрый старт</h2>

                <h3>Требования</h3>
                <ul>
                    <li>PHP 8.2+</li>
                    <li>Composer 2.0+</li>
                    <li>Node.js 18.0+</li>
                    <li>MySQL 8.0+ или PostgreSQL 13+</li>
                    <li>SSL-сертификат (обязательно для Telegram Mini App)</li>
                </ul>

                <h3>Установка</h3>
                <ol>
                    <li><strong>Клонируйте репозиторий:</strong>
                        <pre><code>git clone https://github.com/letoceiling-coder/express.git
cd express</code></pre>
                    </li>
                    <li><strong>Установите зависимости:</strong>
                        <pre><code>composer install
npm install</code></pre>
                    </li>
                    <li><strong>Настройте окружение:</strong>
                        <pre><code>cp .env.example .env
php artisan key:generate</code></pre>
                    </li>
                    <li><strong>Настройте базу данных в <code>.env</code>:</strong>
                        <pre><code>DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password</code></pre>
                    </li>
                    <li><strong>Выполните миграции и seeders:</strong>
                        <pre><code>php artisan migrate
php artisan db:seed</code></pre>
                    </li>
                    <li><strong>Соберите фронтенд:</strong>
                        <pre><code>npm run build:admin</code></pre>
                    </li>
                </ol>

                <h3>Первоначальная настройка</h3>
                <p>После установки создайте первого администратора через команду:</p>
                <pre><code>php artisan tinker
User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password')]);</code></pre>
            `},getAdminDashboardContent(){return`
                <h2>Главная (Dashboard)</h2>
                <p>Главная страница админ-панели доступна по адресу <code>/admin</code>.</p>

                <h3>Функциональность</h3>
                <ul>
                    <li>Общая статистика системы</li>
                    <li>Быстрый доступ к основным разделам</li>
                    <li>Информация о последних действиях</li>
                </ul>

                <h3>Права доступа</h3>
                <p>Доступ имеют пользователи с ролями: <strong>admin</strong>, <strong>manager</strong></p>
            `},getAdminCatalogContent(){return`
                <h2>Каталог</h2>
                <p>Управление каталогом товаров включает работу с категориями и товарами.</p>

                <h3>Категории</h3>
                <p>Доступ: <code>/admin/categories</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Создание, редактирование и удаление категорий</li>
                    <li>Установка порядка сортировки</li>
                    <li>Активация/деактивация категорий</li>
                    <li>Генерация slug автоматически из названия</li>
                </ul>

                <p><strong>Поля категории:</strong></p>
                <ul>
                    <li><code>name</code> - Название категории (обязательное)</li>
                    <li><code>slug</code> - URL-путь (генерируется автоматически)</li>
                    <li><code>is_active</code> - Активна ли категория</li>
                    <li><code>sort_order</code> - Порядок сортировки</li>
                </ul>

                <p><strong>Пример создания категории:</strong></p>
                <pre><code>POST /api/v1/categories
{
    "name": "Мангал/гриль",
    "is_active": true,
    "sort_order": 1
}</code></pre>

                <h3>Товары</h3>
                <p>Доступ: <code>/admin/products</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Создание, редактирование и удаление товаров</li>
                    <li>Привязка товара к категории</li>
                    <li>Управление изображениями через медиа-библиотеку</li>
                    <li>Установка цены и описания</li>
                    <li>Метка "весовой товар" для товаров, продающихся на вес</li>
                    <li>Просмотр истории изменений товара</li>
                </ul>

                <p><strong>Поля товара:</strong></p>
                <ul>
                    <li><code>name</code> - Название товара (обязательное)</li>
                    <li><code>slug</code> - URL-путь (генерируется автоматически)</li>
                    <li><code>description</code> - Описание товара</li>
                    <li><code>price</code> - Цена (обязательное, decimal)</li>
                    <li><code>category_id</code> - ID категории (обязательное)</li>
                    <li><code>image_id</code> - ID изображения из медиа-библиотеки</li>
                    <li><code>is_available</code> - Доступен ли товар</li>
                    <li><code>is_weight_product</code> - Является ли весовым товаром</li>
                    <li><code>stock_quantity</code> - Количество на складе</li>
                    <li><code>sort_order</code> - Порядок сортировки</li>
                </ul>

                <p><strong>Пример создания товара:</strong></p>
                <pre><code>POST /api/v1/products
{
    "name": "Шашлык из свиной мякоти 500 г.",
    "description": "Шашлык из задней части свиного окорока, маринованный в специях",
    "price": 550.00,
    "category_id": 1,
    "image_id": 58,
    "is_available": true,
    "is_weight_product": true,
    "stock_quantity": 100,
    "sort_order": 1
}</code></pre>

                <h3>История изменений товаров</h3>
                <p>Каждое изменение товара сохраняется в истории с информацией:</p>
                <ul>
                    <li>Дата и время изменения</li>
                    <li>Пользователь, который внес изменения</li>
                    <li>Старые и новые значения полей</li>
                </ul>
                <p>Доступ к истории: <code>/admin/products/{id}/history</code></p>

                <h3>Права доступа</h3>
                <p>Доступ имеют пользователи с ролями: <strong>admin</strong>, <strong>manager</strong></p>
            `},getAdminOrdersContent(){return`
                <h2>Заказы</h2>
                <p>Система управления заказами включает заказы, доставки и платежи.</p>

                <h3>Заказы</h3>
                <p>Доступ: <code>/admin/orders</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Просмотр списка всех заказов</li>
                    <li>Просмотр детальной информации о заказе</li>
                    <li>Изменение статуса заказа</li>
                    <li>Фильтрация по статусу, дате, клиенту</li>
                </ul>

                <p><strong>Статусы заказа:</strong></p>
                <ul>
                    <li><code>new</code> - Новый</li>
                    <li><code>accepted</code> - Принят</li>
                    <li><code>preparing</code> - Готовится</li>
                    <li><code>ready_for_delivery</code> - Готов к отправке</li>
                    <li><code>in_transit</code> - В пути</li>
                    <li><code>delivered</code> - Доставлен</li>
                    <li><code>cancelled</code> - Отменён</li>
                </ul>

                <h3>Доставки</h3>
                <p>Доступ: <code>/admin/deliveries</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Создание записей о доставках</li>
                    <li>Управление информацией о доставке</li>
                    <li>Привязка доставки к заказу</li>
                    <li>Отслеживание статуса доставки</li>
                </ul>

                <p><strong>Поля доставки:</strong></p>
                <ul>
                    <li><code>order_id</code> - ID заказа (обязательное)</li>
                    <li><code>address</code> - Адрес доставки</li>
                    <li><code>delivery_date</code> - Дата доставки</li>
                    <li><code>status</code> - Статус доставки</li>
                </ul>

                <h3>Платежи</h3>
                <p>Доступ: <code>/admin/payments</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Просмотр всех платежей</li>
                    <li>Создание и редактирование платежей</li>
                    <li>Интеграция с платежной системой ЮКасса</li>
                    <li>Отслеживание статусов платежей</li>
                </ul>

                <p><strong>Статусы платежа:</strong></p>
                <ul>
                    <li><code>pending</code> - Ожидает оплаты</li>
                    <li><code>succeeded</code> - Оплачено</li>
                    <li><code>failed</code> - Ошибка оплаты</li>
                    <li><code>cancelled</code> - Отменено</li>
                </ul>

                <p><strong>Поля платежа:</strong></p>
                <ul>
                    <li><code>order_id</code> - ID заказа (обязательное)</li>
                    <li><code>amount</code> - Сумма платежа (обязательное)</li>
                    <li><code>status</code> - Статус платежа</li>
                    <li><code>payment_id</code> - ID платежа в платежной системе</li>
                    <li><code>payment_method</code> - Способ оплаты</li>
                </ul>

                <h3>Права доступа</h3>
                <p>Доступ имеют пользователи с ролями: <strong>admin</strong>, <strong>manager</strong></p>
            `},getAdminFeedbackContent(){return`
                <h2>Обратная связь</h2>
                <p>Система обратной связи включает возвраты, претензии и отзывы.</p>

                <h3>Возвраты</h3>
                <p>Доступ: <code>/admin/returns</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Создание записей о возвратах</li>
                    <li>Управление информацией о возврате</li>
                    <li>Привязка к заказу</li>
                    <li>Отслеживание статуса возврата</li>
                </ul>

                <p><strong>Поля возврата:</strong></p>
                <ul>
                    <li><code>order_id</code> - ID заказа (обязательное)</li>
                    <li><code>reason</code> - Причина возврата</li>
                    <li><code>status</code> - Статус возврата</li>
                    <li><code>refund_amount</code> - Сумма возврата</li>
                </ul>

                <h3>Претензии</h3>
                <p>Доступ: <code>/admin/complaints</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Создание и управление претензиями</li>
                    <li>Привязка к заказу или товару</li>
                    <li>Отслеживание статуса обработки</li>
                </ul>

                <p><strong>Поля претензии:</strong></p>
                <ul>
                    <li><code>order_id</code> - ID заказа (опционально)</li>
                    <li><code>product_id</code> - ID товара (опционально)</li>
                    <li><code>description</code> - Описание претензии</li>
                    <li><code>status</code> - Статус претензии</li>
                </ul>

                <h3>Отзывы</h3>
                <p>Доступ: <code>/admin/reviews</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Управление отзывами о товарах</li>
                    <li>Модерация отзывов</li>
                    <li>Оценка товаров (рейтинг)</li>
                </ul>

                <p><strong>Поля отзыва:</strong></p>
                <ul>
                    <li><code>product_id</code> - ID товара (обязательное)</li>
                    <li><code>rating</code> - Рейтинг (1-5)</li>
                    <li><code>comment</code> - Текст отзыва</li>
                    <li><code>is_approved</code> - Одобрен ли отзыв</li>
                </ul>

                <h3>Права доступа</h3>
                <p>Доступ имеют пользователи с ролями: <strong>admin</strong>, <strong>manager</strong></p>
            `},getAdminMediaContent(){return`
                <h2>Медиа-библиотека</h2>
                <p>Доступ: <code>/admin/media</code></p>
                <p>Централизованная система управления всеми медиа-файлами проекта.</p>

                <h3>Основные возможности</h3>
                <ul>
                    <li><strong>Организация файлов в папки</strong> - Древовидная структура папок</li>
                    <li><strong>Загрузка файлов</strong> - Drag & drop, множественная загрузка</li>
                    <li><strong>Типы файлов:</strong> Изображения (jpg, png, webp, gif), Видео (mp4, avi, mov), Документы (pdf, doc, docx)</li>
                    <li><strong>Корзина</strong> - Удаленные файлы можно восстановить</li>
                    <li><strong>Поиск и фильтрация</strong> - По имени, типу, расширению, папке</li>
                    <li><strong>Автоматическая оптимизация изображений</strong> - Конвертация в WebP и создание вариантов размеров</li>
                </ul>

                <h3>Управление папками</h3>
                <p><strong>Создание папки:</strong></p>
                <pre><code>POST /api/v1/folders
{
    "name": "Фотографии товаров",
    "parent_id": null,
    "position": 0
}</code></pre>

                <p><strong>Получение дерева папок:</strong></p>
                <pre><code>GET /api/v1/folders/tree/all</code></pre>

                <h3>Загрузка файлов</h3>
                <p><strong>Загрузка файла:</strong></p>
                <pre><code>POST /api/v1/media
Content-Type: multipart/form-data

file: [файл]
folder_id: 1 (опционально)</code></pre>

                <p><strong>При загрузке изображений автоматически:</strong></p>
                <ul>
                    <li>Создается WebP версия оригинала</li>
                    <li>Генерируются варианты размеров: thumbnail (300x300), medium (800x800), large (1200x1200)</li>
                    <li>Все варианты доступны в форматах WebP и JPEG</li>
                    <li>Метаданные сохраняются в поле <code>metadata</code></li>
                </ul>

                <h3>Корзина</h3>
                <p>Удаленные файлы перемещаются в корзину (папка с <code>is_trash = true</code>).</p>
                <ul>
                    <li>Файлы можно восстановить</li>
                    <li>Очистка корзины удаляет файлы безвозвратно</li>
                    <li>Загрузка файлов напрямую в корзину запрещена</li>
                </ul>

                <h3>API для медиа</h3>
                <ul>
                    <li><code>GET /api/v1/media</code> - Список файлов с фильтрацией</li>
                    <li><code>POST /api/v1/media</code> - Загрузить файл</li>
                    <li><code>PUT /api/v1/media/{id}</code> - Обновить (переместить в другую папку)</li>
                    <li><code>DELETE /api/v1/media/{id}</code> - Удалить в корзину</li>
                    <li><code>POST /api/v1/media/{id}/restore</code> - Восстановить из корзины</li>
                    <li><code>DELETE /api/v1/media/trash/empty</code> - Очистить корзину</li>
                </ul>

                <h3>Использование в формах</h3>
                <p>Для выбора медиа-файлов используется компонент <code>MediaSelector.vue</code>, который открывает модальное окно с медиа-библиотекой.</p>

                <h3>Права доступа</h3>
                <p>Доступ имеют пользователи с ролями: <strong>admin</strong>, <strong>manager</strong></p>
            `},getAdminUsersContent(){return`
                <h2>Пользователи и роли</h2>

                <h3>Пользователи</h3>
                <p>Доступ: <code>/admin/users</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Создание, редактирование и удаление пользователей</li>
                    <li>Назначение ролей пользователям</li>
                    <li>Управление данными пользователей</li>
                </ul>

                <p><strong>Поля пользователя:</strong></p>
                <ul>
                    <li><code>name</code> - Имя (обязательное)</li>
                    <li><code>email</code> - Email (обязательное, уникальное)</li>
                    <li><code>password</code> - Пароль (обязательное при создании)</li>
                </ul>

                <p><strong>Пример создания пользователя:</strong></p>
                <pre><code>POST /api/v1/users
{
    "name": "Менеджер",
    "email": "manager@example.com",
    "password": "secure_password",
    "roles": [2]  // ID ролей
}</code></pre>

                <h3>Роли</h3>
                <p>Доступ: <code>/admin/roles</code></p>
                <p><strong>Система ролей:</strong></p>
                <ul>
                    <li><strong>admin</strong> - Полный доступ ко всем разделам</li>
                    <li><strong>manager</strong> - Доступ к управлению контентом (каталог, заказы, медиа)</li>
                    <li><strong>user</strong> - Ограниченный доступ (только документация)</li>
                </ul>

                <p><strong>Создание роли:</strong></p>
                <pre><code>POST /api/v1/roles
{
    "name": "Менеджер",
    "slug": "manager",
    "description": "Менеджер контента"
}</code></pre>

                <h3>Назначение ролей</h3>
                <p>Роли назначаются пользователям через поле <code>roles</code> при создании или обновлении пользователя. Это массив ID ролей.</p>

                <h3>Фильтрация доступа</h3>
                <p>Доступ к разделам админ-панели фильтруется по ролям автоматически. Меню формируется динамически в зависимости от ролей пользователя.</p>

                <h3>Права доступа</h3>
                <p>Доступ имеют только пользователи с ролью: <strong>admin</strong></p>
            `},getAdminBotUsersContent(){return`
                <h2>Пользователи бота и система ролей</h2>
                <p>Система управления пользователями Telegram бота с поддержкой ролей и заявок на получение ролей.</p>

                <h3>Обзор системы</h3>
                <p>Пользователи Telegram бота могут иметь одну из следующих ролей:</p>
                <ul>
                    <li><strong>user</strong> - Обычный пользователь (роль по умолчанию)</li>
                    <li><strong>courier</strong> - Курьер</li>
                    <li><strong>kitchen</strong> - Кухня</li>
                    <li><strong>admin</strong> - Администратор бота</li>
                </ul>
                <p>При создании нового пользователя автоматически присваивается роль <strong>user</strong>.</p>

                <h3>Пользователи бота</h3>
                <p>Доступ: <code>/admin/telegram-users</code></p>
                <p><strong>Возможности:</strong></p>
                <ul>
                    <li>Просмотр списка всех пользователей бота</li>
                    <li>Фильтрация по боту, статусу блокировки, поиск по имени/username/ID</li>
                    <li>Просмотр детальной информации о пользователе</li>
                    <li>Просмотр заказов пользователя</li>
                    <li>Просмотр статистики (количество заказов, общая сумма покупок, средний чек)</li>
                    <li>Блокировка/разблокировка пользователей</li>
                    <li>Синхронизация данных пользователя из Telegram API</li>
                    <li>Обновление статистики пользователя</li>
                </ul>

                <h3>Заявки на роли</h3>
                <p>Доступ: <code>/admin/role-requests</code></p>
                <p>Пользователи бота могут подавать заявки на получение ролей <strong>courier</strong> (курьер), <strong>kitchen</strong> (кухня) или <strong>admin</strong> (администратор) через команды бота.</p>

                <h4>Команды бота</h4>
                <ul>
                    <li><code>/apply_courier</code> - Подать заявку на роль курьера</li>
                    <li><code>/apply_kitchen</code> - Подать заявку на роль кухни</li>
                    <li><code>/apply_admin</code> - Подать заявку на роль администратора</li>
                </ul>

                <h4>Процесс обработки заявки</h4>
                <ol>
                    <li>Пользователь выполняет команду в боте</li>
                    <li>Система проверяет, нет ли уже активной заявки или роли</li>
                    <li>Создается заявка со статусом pending</li>
                    <li>Администратор просматривает и обрабатывает заявку в админ-панели</li>
                    <li>При одобрении пользователю присваивается запрашиваемая роль</li>
                </ol>

                <h3>API Endpoints</h3>
                <p>Полное описание API доступно в разделе API документации.</p>
                <ul>
                    <li><code>GET /api/v1/telegram-users</code> - Список пользователей</li>
                    <li><code>GET /api/v1/telegram-user-role-requests</code> - Список заявок</li>
                    <li><code>POST /api/v1/telegram-user-role-requests/{id}/approve</code> - Одобрить заявку</li>
                    <li><code>POST /api/v1/telegram-user-role-requests/{id}/reject</code> - Отклонить заявку</li>
                </ul>

                <h3>Права доступа</h3>
                <p>Доступ к разделам "Пользователи бота" и "Заявки на роли" имеют пользователи с ролями: <strong>admin</strong>, <strong>manager</strong></p>
            `},getAdminSettingsContent(){return`
                <h2>Настройки</h2>

                <h3>Общие настройки</h3>
                <p>Доступ: <code>/admin/settings</code></p>
                <p>Раздел для общих настроек системы (в разработке).</p>

                <h3>Настройки платежей ЮКасса</h3>
                <p>Доступ: <code>/admin/settings/payments/yookassa</code></p>
                <p><strong>Настройка интеграции с платежной системой ЮКасса.</strong></p>

                <h4>Параметры подключения</h4>
                <ul>
                    <li><strong>Режим работы:</strong>
                        <ul>
                            <li><code>sandbox</code> - Тестовый режим (для разработки)</li>
                            <li><code>production</code> - Рабочий режим</li>
                        </ul>
                    </li>
                    <li><strong>Shop ID</strong> - Идентификатор магазина в ЮКасса (обязательное)</li>
                    <li><strong>Secret Key</strong> - Секретный ключ для API (обязательное)</li>
                    <li><strong>Webhook URL</strong> - URL для получения уведомлений о платежах</li>
                </ul>

                <h4>Получение ключей</h4>
                <ol>
                    <li>Зарегистрируйтесь в <a href="https://yookassa.ru" target="_blank">ЮКасса</a></li>
                    <li>Создайте магазин в личном кабинете</li>
                    <li>Получите Shop ID и Secret Key в разделе "Настройки" → "Ключи API"</li>
                    <li>Для тестового режима используйте тестовые ключи из раздела "Тестовые данные"</li>
                </ol>

                <h4>Настройка Webhook</h4>
                <ol>
                    <li>В личном кабинете ЮКасса перейдите в "Настройки" → "Уведомления"</li>
                    <li>Добавьте URL для уведомлений: <code>https://your-domain.com/api/v1/payments/yookassa/webhook</code></li>
                    <li>Выберите события для уведомлений: "payment.succeeded", "payment.cancelled", "payment.waiting_for_capture"</li>
                </ol>

                <h4>Пример сохранения настроек</h4>
                <pre><code>POST /api/v1/payment-settings/yookassa
{
    "mode": "production",
    "shop_id": "123456",
    "secret_key": "live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "webhook_url": "https://your-domain.com/api/v1/payments/yookassa/webhook"
}</code></pre>

                <h4>API для работы с ЮКасса</h4>
                <ul>
                    <li><code>POST /api/v1/payments/yookassa/create</code> - Создание платежа</li>
                    <li><code>POST /api/v1/payments/yookassa/webhook</code> - Webhook для уведомлений</li>
                    <li><code>GET /api/v1/payment-settings/yookassa</code> - Получить настройки</li>
                    <li><code>POST /api/v1/payment-settings/yookassa</code> - Сохранить настройки</li>
                </ul>

                <h4>Пример создания платежа</h4>
                <pre><code>POST /api/v1/payments/yookassa/create
{
    "order_id": 1,
    "amount": 1500.00,
    "description": "Оплата заказа №ORD-20250103-1",
    "return_url": "https://your-domain.com/order/1"
}</code></pre>

                <h3>Права доступа</h3>
                <p>Доступ имеют только пользователи с ролью: <strong>admin</strong></p>
            `},getAdminOtherContent(){return`
                <h2>Прочие разделы</h2>

                <h3>Уведомления</h3>
                <p>Доступ: <code>/admin/notifications</code></p>
                <p>Просмотр и управление системными уведомлениями для пользователей админ-панели.</p>
                <ul>
                    <li>Просмотр всех уведомлений</li>
                    <li>Отметка как прочитанные</li>
                    <li>Удаление уведомлений</li>
                    <li>Подсчет непрочитанных</li>
                </ul>
                <p>Доступ: <strong>admin</strong>, <strong>manager</strong>, <strong>user</strong></p>

                <h3>Боты</h3>
                <p>Доступ: <code>/admin/bots</code></p>
                <p>Управление Telegram ботами (в разработке).</p>
                <p>Доступ: <strong>admin</strong></p>

                <h3>Подписка</h3>
                <p>Доступ: <code>/admin/subscription</code></p>
                <p>Проверка статуса подписки на внешнем сервисе.</p>
                <p>Настройка в <code>.env</code>:</p>
                <pre><code>SUBSCRIPTION_API_URL=https://crm.example.com/api/v1/subscription/check
SUBSCRIPTION_API_TOKEN=your_token</code></pre>
                <p>Доступ: <strong>admin</strong>, <strong>manager</strong></p>

                <h3>Поддержка</h3>
                <p>Доступ: <code>/admin/support</code></p>
                <p>Система тикетов поддержки с интеграцией внешних CRM.</p>
                <ul>
                    <li>Создание тикетов</li>
                    <li>Обмен сообщениями</li>
                    <li>Прикрепление файлов</li>
                    <li>Интеграция с внешними системами через webhooks</li>
                </ul>
                <p>Доступ: <strong>admin</strong>, <strong>manager</strong></p>
            `},getApiAuthContent(){return`
                <h2>API: Авторизация</h2>
                <p>Все защищенные endpoints требуют Bearer токен в заголовке <code>Authorization</code>.</p>

                <h3>Получение токена</h3>
                <pre><code>POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password"
}

Ответ:
{
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
        "id": 1,
        "name": "Admin",
        "email": "admin@example.com"
    }
}</code></pre>

                <h3>Использование токена</h3>
                <pre><code>GET /api/auth/user
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</code></pre>

                <h3>Endpoints</h3>
                <ul>
                    <li><code>POST /api/auth/register</code> - Регистрация нового пользователя</li>
                    <li><code>POST /api/auth/login</code> - Вход в систему (возвращает токен)</li>
                    <li><code>POST /api/auth/logout</code> - Выход (требует авторизации)</li>
                    <li><code>GET /api/auth/user</code> - Получение данных текущего пользователя</li>
                    <li><code>POST /api/auth/forgot-password</code> - Запрос на восстановление пароля</li>
                    <li><code>POST /api/auth/reset-password</code> - Сброс пароля</li>
                </ul>
            `},getApiCatalogContent(){return`
                <h2>API: Каталог</h2>

                <h3>Категории</h3>
                <ul>
                    <li><code>GET /api/v1/categories</code> - Список категорий
                        <ul>
                            <li>Параметры: <code>per_page</code> (0 = без пагинации), <code>page</code></li>
                        </ul>
                    </li>
                    <li><code>GET /api/v1/categories/{id}</code> - Детали категории</li>
                    <li><code>POST /api/v1/categories</code> - Создать категорию</li>
                    <li><code>PUT /api/v1/categories/{id}</code> - Обновить категорию</li>
                    <li><code>DELETE /api/v1/categories/{id}</code> - Удалить категорию</li>
                </ul>

                <h3>Товары</h3>
                <ul>
                    <li><code>GET /api/v1/products</code> - Список товаров
                        <ul>
                            <li>Параметры: <code>category_id</code>, <code>is_available</code>, <code>per_page</code>, <code>page</code></li>
                        </ul>
                    </li>
                    <li><code>GET /api/v1/products/{id}</code> - Детали товара (включая изображение с WebP вариантами)</li>
                    <li><code>POST /api/v1/products</code> - Создать товар</li>
                    <li><code>PUT /api/v1/products/{id}</code> - Обновить товар</li>
                    <li><code>DELETE /api/v1/products/{id}</code> - Удалить товар</li>
                    <li><code>GET /api/v1/products/{id}/history</code> - История изменений товара</li>
                </ul>

                <h3>Пример ответа товара с изображением</h3>
                <pre><code>{
    "id": 1,
    "name": "Шашлык из свиной мякоти 500 г.",
    "price": "550.00",
    "category_id": 1,
    "image": {
        "id": 58,
        "url": "/media/photos/2026/01/...jpg",
        "webp_url": "/local/webp/....webp",
        "variants": {
            "thumbnail": {
                "webp": "/local/variants/..._thumbnail.webp",
                "jpeg": "/local/variants/..._thumbnail.jpg"
            },
            "medium": {
                "webp": "/local/variants/..._medium.webp",
                "jpeg": "/local/variants/..._medium.jpg"
            },
            "large": {
                "webp": "/local/variants/..._large.webp",
                "jpeg": "/local/variants/..._large.jpg"
            }
        }
    }
}</code></pre>
            `},getApiOrdersContent(){return`
                <h2>API: Заказы</h2>

                <h3>Заказы</h3>
                <ul>
                    <li><code>GET /api/v1/orders</code> - Список заказов</li>
                    <li><code>GET /api/v1/orders/{id}</code> - Детали заказа</li>
                    <li><code>POST /api/v1/orders</code> - Создать заказ</li>
                    <li><code>PUT /api/v1/orders/{id}</code> - Обновить заказ</li>
                    <li><code>GET /api/v1/orders/my</code> - Мои заказы (для авторизованного пользователя)</li>
                </ul>

                <h3>Доставки</h3>
                <ul>
                    <li><code>GET /api/v1/deliveries</code> - Список доставок</li>
                    <li><code>GET /api/v1/deliveries/{id}</code> - Детали доставки</li>
                    <li><code>POST /api/v1/deliveries</code> - Создать доставку</li>
                    <li><code>PUT /api/v1/deliveries/{id}</code> - Обновить доставку</li>
                    <li><code>DELETE /api/v1/deliveries/{id}</code> - Удалить доставку</li>
                </ul>

                <h3>Пример создания заказа</h3>
                <pre><code>POST /api/v1/orders
{
    "phone": "+79991234567",
    "delivery_address": "г. Москва, ул. Примерная, д. 1, кв. 1",
    "delivery_time": "15:00-16:00",
    "comment": "Позвонить за час",
    "items": [
        {
            "product_id": "1",
            "product_name": "Шашлык из свиной мякоти",
            "quantity": 2,
            "unit_price": 550.00
        }
    ],
    "total_amount": 1100.00
}</code></pre>
            `},getApiPaymentsContent(){return`
                <h2>API: Платежи</h2>

                <h3>Платежи</h3>
                <ul>
                    <li><code>GET /api/v1/payments</code> - Список платежей</li>
                    <li><code>GET /api/v1/payments/{id}</code> - Детали платежа</li>
                    <li><code>POST /api/v1/payments</code> - Создать платеж</li>
                    <li><code>PUT /api/v1/payments/{id}</code> - Обновить платеж</li>
                </ul>

                <h3>Интеграция с ЮКасса</h3>
                <ul>
                    <li><code>POST /api/v1/payments/yookassa/create</code> - Создать платеж через ЮКасса</li>
                    <li><code>POST /api/v1/payments/yookassa/webhook</code> - Webhook для уведомлений от ЮКасса</li>
                    <li><code>GET /api/v1/payment-settings/yookassa</code> - Получить настройки ЮКасса</li>
                    <li><code>POST /api/v1/payment-settings/yookassa</code> - Сохранить настройки ЮКасса</li>
                </ul>

                <h3>Пример создания платежа через ЮКасса</h3>
                <pre><code>POST /api/v1/payments/yookassa/create
{
    "order_id": 1,
    "amount": 1500.00,
    "description": "Оплата заказа №ORD-20250103-1",
    "return_url": "https://your-domain.com/order/1"
}

Ответ:
{
    "payment_id": "2d7c6f3b-000f-5000-9000-1b6b8e3f5e7a",
    "confirmation_url": "https://yoomoney.ru/checkout/payments/v2/contract?orderId=..."
}</code></pre>
            `},getApiMediaContent(){return`
                <h2>API: Медиа-библиотека</h2>
                <p>Полное описание API для работы с медиа-файлами см. в разделе "Админ-панель → Медиа-библиотека".</p>

                <h3>Основные endpoints</h3>
                <ul>
                    <li><code>GET /api/v1/folders</code> - Список папок</li>
                    <li><code>POST /api/v1/folders</code> - Создать папку</li>
                    <li><code>GET /api/v1/folders/tree/all</code> - Дерево всех папок</li>
                    <li><code>GET /api/v1/media</code> - Список файлов (с фильтрацией)</li>
                    <li><code>POST /api/v1/media</code> - Загрузить файл</li>
                    <li><code>DELETE /api/v1/media/{id}</code> - Удалить файл (в корзину)</li>
                    <li><code>POST /api/v1/media/{id}/restore</code> - Восстановить из корзины</li>
                </ul>

                <h3>Параметры фильтрации медиа</h3>
                <ul>
                    <li><code>folder_id</code> - Фильтр по папке (null для корневых)</li>
                    <li><code>type</code> - Тип файла (photo, video, document)</li>
                    <li><code>search</code> - Поиск по имени</li>
                    <li><code>per_page</code> - Количество на странице (0 = все)</li>
                </ul>
            `},getApiOtherContent(){return`
                <h2>API: Прочие endpoints</h2>

                <h3>Возвраты, претензии, отзывы</h3>
                <ul>
                    <li><code>GET /api/v1/returns</code> - Список возвратов</li>
                    <li><code>POST /api/v1/returns</code> - Создать возврат</li>
                    <li><code>GET /api/v1/complaints</code> - Список претензий</li>
                    <li><code>POST /api/v1/complaints</code> - Создать претензию</li>
                    <li><code>GET /api/v1/reviews</code> - Список отзывов</li>
                    <li><code>POST /api/v1/reviews</code> - Создать отзыв</li>
                </ul>

                <h3>Уведомления</h3>
                <ul>
                    <li><code>GET /api/notifications</code> - Непрочитанные уведомления</li>
                    <li><code>GET /api/notifications/all</code> - Все уведомления</li>
                    <li><code>POST /api/notifications/{id}/read</code> - Отметить как прочитанное</li>
                    <li><code>DELETE /api/notifications/{id}</code> - Удалить уведомление</li>
                </ul>

                <h3>Интеграции</h3>
                <ul>
                    <li><code>POST /api/integration/messages</code> - Получить сообщение от внешней системы</li>
                    <li><code>POST /api/integration/status</code> - Изменить статус от внешней системы</li>
                    <li><code>POST /api/webhook/github</code> - Webhook от GitHub для деплоя</li>
                </ul>
            `},getDeploymentContent(){return`
                <h2>Автоматический деплой</h2>
                <p>Система поддерживает автоматическое развертывание кода на сервере через несколько методов.</p>

                <h3>GitHub Actions</h3>
                <p>При каждом push в ветку <code>main</code> автоматически запускается деплой на сервер.</p>
                <ul>
                    <li>Workflow файл: <code>.github/workflows/deploy.yml</code></li>
                    <li>Игнорирует изменения в <code>*.md</code>, <code>.gitignore</code>, <code>.editorconfig</code></li>
                    <li>Использует Secrets: <code>DEPLOY_SERVER_URL</code> и <code>DEPLOY_TOKEN</code></li>
                </ul>

                <h3>GitHub Webhook</h3>
                <p>Endpoint <code>/api/webhook/github</code> обрабатывает push события от GitHub.</p>
                <ul>
                    <li>Проверяет подпись webhook (если настроен <code>GITHUB_WEBHOOK_SECRET</code>)</li>
                    <li>Или проверяет токен <code>DEPLOY_TOKEN</code></li>
                    <li>Деплоит только из ветки <code>main</code></li>
                </ul>

                <h3>Процесс деплоя</h3>
                <ol>
                    <li>Обновление кода из репозитория (<code>git pull origin main</code>)</li>
                    <li>Установка зависимостей (<code>composer install --no-dev</code>)</li>
                    <li>Выполнение миграций (<code>php artisan migrate --force</code>)</li>
                    <li>Очистка кешей (<code>php artisan optimize:clear</code>)</li>
                    <li>Оптимизация приложения (<code>php artisan optimize</code>)</li>
                    <li>Сборка фронтенда (<code>npm run build:admin</code>)</li>
                </ol>

                <h3>Настройка</h3>
                <p>В <code>.env</code> файле настройте:</p>
                <ul>
                    <li><code>DEPLOY_TOKEN</code> - Токен для авторизации деплоя (обязательно)</li>
                    <li><code>DEPLOY_SERVER_URL</code> - URL сервера (опционально, для локальных деплоев)</li>
                    <li><code>GITHUB_WEBHOOK_SECRET</code> - Секрет для проверки подписи webhook (опционально)</li>
                </ul>

                <h3>Настройка GitHub Actions</h3>
                <ol>
                    <li>Откройте Settings → Secrets → Actions в репозитории GitHub</li>
                    <li>Добавьте секреты:
                        <ul>
                            <li><code>DEPLOY_SERVER_URL</code> - URL вашего сервера</li>
                            <li><code>DEPLOY_TOKEN</code> - Токен (должен совпадать с токеном в <code>.env</code>)</li>
                        </ul>
                    </li>
                </ol>

                <h3>Ручной деплой</h3>
                <pre><code>POST /api/deploy
Authorization: Bearer {DEPLOY_TOKEN}
Content-Type: application/json</code></pre>
            `},getImageOptimizationContent(){return`
                <h2>Оптимизация изображений</h2>
                <p>Система автоматически оптимизирует все загружаемые изображения для ускорения загрузки сайта.</p>

                <h3>Автоматическая обработка</h3>
                <p>При загрузке изображения через медиа-библиотеку автоматически:</p>
                <ul>
                    <li>Создается WebP версия оригинала (качество 85%)</li>
                    <li>Генерируются варианты размеров:
                        <ul>
                            <li><strong>thumbnail</strong> - 300x300px (для списков и миниатюр)</li>
                            <li><strong>medium</strong> - 800x800px (для карточек товаров)</li>
                            <li><strong>large</strong> - 1200x1200px (для детальных страниц)</li>
                        </ul>
                    </li>
                    <li>Каждый вариант доступен в форматах WebP и JPEG</li>
                    <li>WebP версии на 25-35% меньше по размеру при том же качестве</li>
                </ul>

                <h3>Структура файлов</h3>
                <pre><code>public/
  local/
    {filename}.jpg          # Оригинал
    webp/
      {filename}.webp       # WebP версия оригинала
    variants/
      {filename}_thumbnail.webp
      {filename}_thumbnail.jpg
      {filename}_medium.webp
      {filename}_medium.jpg
      {filename}_large.webp
      {filename}_large.jpg</code></pre>

                <h3>Использование на фронтенде</h3>
                <p>На фронтенде используется компонент <code>OptimizedImage</code>, который:</p>
                <ul>
                    <li>Автоматически выбирает WebP формат для современных браузеров</li>
                    <li>Использует JPEG fallback для старых браузеров</li>
                    <li>Выбирает оптимальный размер изображения в зависимости от контекста</li>
                    <li>Поддерживает lazy loading</li>
                </ul>

                <h3>Пример использования</h3>
                <pre><code>&lt;OptimizedImage
    src={product.imageUrl}
    webpSrc={product.webpUrl}
    variants={product.imageVariants}
    alt={product.name}
    size="medium"
    loading="lazy"
/&gt;</code></pre>

                <h3>Обработка существующих изображений</h3>
                <p>Для обработки уже загруженных изображений используйте команду:</p>
                <pre><code>php artisan images:process-existing</code></pre>
                <p>Параметры:</p>
                <ul>
                    <li><code>--limit=N</code> - Обработать только N изображений</li>
                    <li><code>--force</code> - Переобработать даже если варианты уже существуют</li>
                </ul>

                <h3>Настройка качества</h3>
                <p>Качество сжатия настраивается в <code>app/Services/ImageService.php</code>:</p>
                <ul>
                    <li>WebP качество: 85%</li>
                    <li>JPEG качество: 80%</li>
                </ul>

                <h3>API ответ</h3>
                <p>При получении товара через API, изображение содержит:</p>
                <pre><code>{
    "image": {
        "url": "/media/photos/.../image.jpg",
        "webp_url": "/local/webp/image.webp",
        "variants": {
            "thumbnail": {
                "webp": "/local/variants/image_thumbnail.webp",
                "jpeg": "/local/variants/image_thumbnail.jpg"
            },
            "medium": {
                "webp": "/local/variants/image_medium.webp",
                "jpeg": "/local/variants/image_medium.jpg"
            },
            "large": {
                "webp": "/local/variants/image_large.webp",
                "jpeg": "/local/variants/image_large.jpg"
            }
        }
    }
}</code></pre>
            `}}},u={class:"documentation-page"},h={class:"flex gap-6"},v={class:"w-64 flex-shrink-0"},_={class:"bg-card rounded-lg border border-border p-4 sticky top-6 max-h-[calc(100vh-3rem)] overflow-y-auto"},x={class:"space-y-1"},T=["onClick"],b={key:1},y={class:"px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider"},P={class:"ml-2 mt-1 space-y-1"},S=["onClick"],E={class:"flex-1 min-w-0"},O={class:"bg-card rounded-lg border border-border p-8"},w=["innerHTML"];function A(k,s,f,C,t,r){return l(),o("div",u,[s[0]||(s[0]=e("div",{class:"mb-6"},[e("h1",{class:"text-2xl font-bold text-foreground"},"Документация"),e("p",{class:"text-muted-foreground mt-1"},"Полная техническая документация системы управления")],-1)),e("div",h,[e("aside",v,[e("div",_,[e("nav",x,[(l(!0),o(n,null,c(t.sections,i=>(l(),o(n,{key:i.id},[!i.children||i.children.length===0?(l(),o("button",{key:0,onClick:d=>{t.activeSection=i.id,r.scrollToTop()},class:p(["w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors",t.activeSection===i.id?"bg-primary text-primary-foreground":"text-muted-foreground hover:bg-muted hover:text-foreground"])},a(i.title),11,T)):(l(),o("div",b,[e("div",y,a(i.title.replace(/^[^\s]+\s/,"")),1),e("div",P,[(l(!0),o(n,null,c(i.children,d=>(l(),o("button",{key:d.id,onClick:I=>{t.activeSection=d.id,r.scrollToTop()},class:p(["w-full text-left px-3 py-1.5 rounded text-xs transition-colors",t.activeSection===d.id?"bg-primary/20 text-primary font-medium":"text-muted-foreground hover:bg-muted/50"])},a(d.title),11,S))),128))])]))],64))),128))])])]),e("main",E,[e("div",O,[e("div",{innerHTML:r.currentContent,class:"prose prose-sm max-w-none dark:prose-invert documentation-content"},null,8,w)])])])])}const G=g(m,[["render",A],["__scopeId","data-v-9205b328"]]);export{G as default};
