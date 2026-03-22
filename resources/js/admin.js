import './bootstrap';
import { createApp } from 'vue';
import Toast from './components/admin/Toast.vue';
import { createStore } from 'vuex';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';

// Store
const store = createStore({
    state: {
        user: null,
        token: localStorage.getItem('token') || null,
        menu: [],
        notifications: [],
        theme: localStorage.getItem('theme') || 'light',
    },
    mutations: {
        SET_USER(state, user) {
            console.log('🔍 SET_USER mutation - Setting user:', {
                user,
                roles: user?.roles,
                rolesCount: user?.roles?.length || 0,
            });
            state.user = user;
            console.log('✅ SET_USER mutation - User set:', {
                user: state.user,
                roles: state.user?.roles,
                rolesCount: state.user?.roles?.length || 0,
            });
        },
        SET_TOKEN(state, token) {
            state.token = token;
            if (token) {
                localStorage.setItem('token', token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            } else {
                localStorage.removeItem('token');
                delete axios.defaults.headers.common['Authorization'];
            }
        },
        SET_MENU(state, menu) {
            state.menu = menu;
        },
        SET_NOTIFICATIONS(state, notifications) {
            state.notifications = notifications;
        },
        LOGOUT(state) {
            state.user = null;
            state.token = null;
            state.menu = [];
            state.notifications = [];
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        },
        SET_THEME(state, theme) {
            state.theme = theme;
            localStorage.setItem('theme', theme);
            // Применяем тему к документу
            const html = document.documentElement;
            const body = document.body;
            if (theme === 'dark') {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                if (body) body.classList.add('dark');
                html.style.colorScheme = 'dark';
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
                if (body) body.classList.remove('dark');
                html.style.colorScheme = 'light';
            }
        },
    },
    actions: {
        async login({ commit, dispatch }, credentials) {
            try {
                const response = await axios.post('/api/auth/login', credentials);
                commit('SET_TOKEN', response.data.token);
                commit('SET_USER', response.data.user);
                // Загружаем меню после успешной авторизации
                await dispatch('fetchMenu');
                await dispatch('fetchNotifications');
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message || 'Ошибка авторизации' };
            }
        },
        async register({ commit, dispatch }, userData) {
            try {
                const response = await axios.post('/api/auth/register', userData);
                commit('SET_TOKEN', response.data.token);
                commit('SET_USER', response.data.user);
                // Загружаем меню после успешной регистрации
                await dispatch('fetchMenu');
                await dispatch('fetchNotifications');
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message || 'Ошибка регистрации' };
            }
        },
        async logout({ commit }) {
            try {
                await axios.post('/api/auth/logout');
            } catch (error) {
                console.error('Logout error:', error);
            }
            commit('LOGOUT');
        },
        async fetchUser({ commit, state }) {
            if (!state.token) return;
            try {
                const response = await axios.get('/api/auth/user');
                console.log('🔍 fetchUser - Response:', {
                    user: response.data.user,
                    roles: response.data.user?.roles,
                    rolesCount: response.data.user?.roles?.length || 0,
                });
                commit('SET_USER', response.data.user);
                console.log('✅ fetchUser - User set in store:', {
                    user: state.user,
                    roles: state.user?.roles,
                });
            } catch (error) {
                console.error('❌ fetchUser - Error:', error);
                commit('LOGOUT');
            }
        },
        async fetchMenu({ commit, state }) {
            if (!state.token) return;
            try {
                const response = await axios.get('/api/admin/menu');
                // Используем JSON для правильного логирования реактивных объектов
                console.log('Menu loaded:', JSON.parse(JSON.stringify(response.data.menu)));
                commit('SET_MENU', response.data.menu);
            } catch (error) {
                console.error('Menu fetch error:', error);
            }
        },
        async fetchNotifications({ commit, state }) {
            if (!state.token) return;
            try {
                const response = await axios.get('/api/notifications');
                commit('SET_NOTIFICATIONS', response.data.notifications);
            } catch (error) {
                console.error('Notifications fetch error:', error);
            }
        },
        toggleTheme({ commit, state }) {
            const newTheme = state.theme === 'dark' ? 'light' : 'dark';
            commit('SET_THEME', newTheme);
        },
    },
    getters: {
        isAuthenticated: (state) => !!state.token,
        user: (state) => state.user,
        menu: (state) => state.menu,
        notifications: (state) => state.notifications,
        theme: (state) => state.theme,
        isDarkMode: (state) => state.theme === 'dark',
        unreadNotificationsCount: (state) => {
            return state.notifications.filter(n => !n.read).length;
        },
        hasRole: (state) => (roleSlug) => {
            if (!state.user || !state.user.roles) return false;
            return state.user.roles.some(role => role.slug === roleSlug);
        },
        hasAnyRole: (state) => (roleSlugs) => {
            if (!state.user || !state.user.roles) return false;
            return state.user.roles.some(role => roleSlugs.includes(role.slug));
        },
        isAdmin: (state) => {
            if (!state.user || !state.user.roles) return false;
            return state.user.roles.some(role => role.slug === 'admin');
        },
    },
});

// Router - используем базовый путь /admin
// Все маршруты определены относительно /admin, поэтому в router они без префикса /admin
const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('./pages/auth/Login.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('./pages/auth/Register.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('./pages/auth/ForgotPassword.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('./pages/auth/ResetPassword.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/403',
        name: 'forbidden',
        component: () => import('./pages/auth/Forbidden403.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/',
        component: () => import('./layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, requiresRole: ['admin'] },
        children: [
            {
                path: '',
                name: 'admin.dashboard',
                component: () => import('./pages/admin/Dashboard.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Главная' },
            },
            {
                path: 'media',
                name: 'admin.media',
                component: () => import('./pages/admin/Media.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Медиа' },
            },
            {
                path: 'about',
                name: 'admin.about',
                component: () => import('./pages/admin/About.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'О нас' },
            },
            {
                path: 'notifications',
                name: 'admin.notifications',
                component: () => import('./pages/admin/Notifications.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Уведомления' },
            },
            {
                path: 'users',
                name: 'admin.users',
                component: () => import('./pages/admin/Users.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Пользователи' },
            },
            {
                path: 'roles',
                name: 'admin.roles',
                component: () => import('./pages/admin/Roles.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Роли' },
            },
            {
                path: 'subscription',
                name: 'admin.subscription',
                component: () => import('./pages/admin/Subscription.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Подписка' },
            },
            // Документация
            {
                path: 'documentation',
                name: 'admin.documentation',
                component: () => import('./pages/admin/Documentation.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Документация' },
            },
            {
                path: 'support',
                name: 'admin.support',
                component: () => import('./pages/admin/Support.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'Поддержка' },
            },
            {
                path: 'support/:id',
                name: 'admin.support.ticket',
                component: () => import('./pages/admin/SupportTicket.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'Тикет поддержки', parent: 'admin.support' },
            },
            {
                path: 'bots',
                name: 'admin.bots',
                component: () => import('./pages/admin/Bots.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Боты' },
            },
            // Telegram Users
            {
                path: 'telegram-users',
                name: 'admin.telegram-users',
                component: () => import('./pages/admin/TelegramUsers.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'Пользователи бота' },
            },
            {
                path: 'telegram-users/:id',
                name: 'admin.telegram-users.detail',
                component: () => import('./pages/admin/TelegramUserDetail.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'Детали пользователя' },
            },
            // Broadcasts
            {
                path: 'broadcasts',
                name: 'admin.broadcasts',
                component: () => import('./pages/admin/Broadcasts.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'Рассылки' },
            },
            // Role Requests
            {
                path: 'role-requests',
                name: 'admin.role-requests',
                component: () => import('./pages/admin/RoleRequests.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'Заявки на роли' },
            },
            // Categories
            {
                path: 'categories',
                name: 'admin.categories',
                component: () => import('./pages/admin/Categories.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Категории' },
            },
            {
                path: 'categories/create',
                name: 'admin.categories.create',
                component: () => import('./pages/admin/CategoryCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать категорию' },
            },
            {
                path: 'categories/:id/edit',
                name: 'admin.categories.edit',
                component: () => import('./pages/admin/CategoryEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать категорию' },
            },
            // Products
            {
                path: 'products',
                name: 'admin.products',
                component: () => import('./pages/admin/Products.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Товары' },
            },
            {
                path: 'products/create',
                name: 'admin.products.create',
                component: () => import('./pages/admin/ProductCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать товар' },
            },
            {
                path: 'products/:id/edit',
                name: 'admin.products.edit',
                component: () => import('./pages/admin/ProductEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать товар' },
            },
            {
                path: 'products/:id/history',
                name: 'admin.products.history',
                component: () => import('./pages/admin/ProductHistory.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'История товара' },
            },
            // Orders
            {
                path: 'orders',
                name: 'admin.orders',
                component: () => import('./pages/admin/Orders.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Заказы' },
            },
            {
                path: 'orders/:id',
                name: 'admin.orders.detail',
                component: () => import('./pages/admin/OrderDetail.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Детали заказа' },
            },
            // Deliveries
            {
                path: 'deliveries',
                name: 'admin.deliveries',
                component: () => import('./pages/admin/Deliveries.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Доставки' },
            },
            {
                path: 'deliveries/create',
                name: 'admin.deliveries.create',
                component: () => import('./pages/admin/DeliveryCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать доставку' },
            },
            {
                path: 'deliveries/:id/edit',
                name: 'admin.deliveries.edit',
                component: () => import('./pages/admin/DeliveryEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать доставку' },
            },
            // Payments
            {
                path: 'payments',
                name: 'admin.payments',
                component: () => import('./pages/admin/Payments.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Платежи' },
            },
            {
                path: 'payments/create',
                name: 'admin.payments.create',
                component: () => import('./pages/admin/PaymentCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать платеж' },
            },
            {
                path: 'payments/:id/edit',
                name: 'admin.payments.edit',
                component: () => import('./pages/admin/PaymentEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать платеж' },
            },
            // Payment Methods
            {
                path: 'payment-methods',
                name: 'admin.payment-methods',
                component: () => import('./pages/admin/PaymentMethods.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Способы оплаты' },
            },
            {
                path: 'payment-methods/create',
                name: 'admin.payment-methods.create',
                component: () => import('./pages/admin/PaymentMethodCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать способ оплаты' },
            },
            {
                path: 'payment-methods/:id/edit',
                name: 'admin.payment-methods.edit',
                component: () => import('./pages/admin/PaymentMethodEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать способ оплаты' },
            },
            // Returns
            {
                path: 'returns',
                name: 'admin.returns',
                component: () => import('./pages/admin/Returns.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Возвраты' },
            },
            {
                path: 'returns/create',
                name: 'admin.returns.create',
                component: () => import('./pages/admin/ReturnCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать возврат' },
            },
            {
                path: 'returns/:id/edit',
                name: 'admin.returns.edit',
                component: () => import('./pages/admin/ReturnEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать возврат' },
            },
            // Complaints
            {
                path: 'complaints',
                name: 'admin.complaints',
                component: () => import('./pages/admin/Complaints.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Претензии' },
            },
            {
                path: 'complaints/create',
                name: 'admin.complaints.create',
                component: () => import('./pages/admin/ComplaintCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать претензию' },
            },
            {
                path: 'complaints/:id/edit',
                name: 'admin.complaints.edit',
                component: () => import('./pages/admin/ComplaintEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать претензию' },
            },
            // Reviews
            {
                path: 'reviews',
                name: 'admin.reviews',
                component: () => import('./pages/admin/Reviews.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Отзывы' },
            },
            {
                path: 'reviews/create',
                name: 'admin.reviews.create',
                component: () => import('./pages/admin/ReviewCreate.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Создать отзыв' },
            },
            {
                path: 'reviews/:id/edit',
                name: 'admin.reviews.edit',
                component: () => import('./pages/admin/ReviewEdit.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Редактировать отзыв' },
            },
            // Settings
            {
                path: 'settings',
                name: 'admin.settings',
                component: () => import('./pages/admin/Settings.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Настройки' },
            },
            // Payment Settings
            {
                path: 'settings/payments/yookassa',
                name: 'admin.settings.payments.yookassa',
                component: () => import('./pages/admin/PaymentSettingsYooKassa.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Настройки ЮКасса' },
            },
            // Delivery Settings
            {
                path: 'settings/delivery',
                name: 'admin.settings.delivery',
                component: () => import('./pages/admin/DeliverySettings.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Настройки доставки' },
            },
            // Notification Settings
            {
                path: 'settings/notifications',
                name: 'admin.settings.notifications',
                component: () => import('./pages/admin/NotificationSettings.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Настройки уведомлений' },
            },
            // Legal Documents
            {
                path: 'legal-documents',
                name: 'admin.legal-documents',
                component: () => import('./pages/admin/LegalDocuments.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin'], title: 'Документы' },
            },
            // Banners
            {
                path: 'banners',
                name: 'admin.banners',
                component: () => import('./pages/admin/Banners.vue'),
                meta: { requiresAuth: true, requiresRole: ['admin', 'manager'], title: 'Баннеры' },
            },
        ],
    },
];

// КРИТИЧНО: Исправляем текущий путь ДО инициализации Vue Router
// Это нужно сделать как можно раньше, чтобы Vue Router не использовал неправильный путь
const currentPath = window.location.pathname;
const currentHref = window.location.href;

console.log('🔍 Initial path check:', {
    pathname: currentPath,
    href: currentHref,
    documentBaseURI: document.baseURI,
});

// Исправляем путь, если он содержит /public/
if (currentPath.includes('/public/')) {
    const fixedPath = currentPath.replace(/\/public\/?/g, '/');
    const fixedHref = currentHref.replace(/\/public\/?/g, '/');
    console.log('🔧 Fixing current path with /public/:', { 
        originalPath: currentPath, 
        fixedPath,
        originalHref: currentHref,
        fixedHref,
    });
    // Заменяем текущий URL на исправленный БЕЗ перезагрузки страницы
    window.history.replaceState({}, '', fixedPath);
    console.log('✅ Replaced history state with fixed path');
}

// Исправляем base для Vue Router
// Всегда используем '/admin' как base, независимо от document.baseURI
let routerBase = '/admin';
console.log('🔧 Vue Router - Base:', { 
    routerBase, 
    documentBaseURI: document.baseURI,
    currentPath: window.location.pathname,
    fixedPath: window.location.pathname.replace(/\/public\/?/g, '/'),
});

const router = createRouter({
    history: createWebHistory(routerBase),
    routes,
});

// Обработка ошибок при загрузке компонентов
router.onError((error) => {
    console.error('❌ Router error:', error);
    console.error('Error details:', {
        message: error.message,
        stack: error.stack,
        name: error.name
    });
    // Не прерываем навигацию, просто логируем ошибку
});

// Navigation guard
router.beforeEach(async (to, from, next) => {
    // КРИТИЧНО: Исправляем путь, если он содержит /public/
    if (to.path.includes('/public/')) {
        const fixedPath = to.path.replace(/\/public\/?/g, '/');
        console.log('🔧 Router Guard - Fixing path with /public/:', { original: to.path, fixed: fixedPath });
        // Редиректим на исправленный путь
        next(fixedPath);
        return;
    }
    
    // Исправляем fullPath, если он содержит /public/
    if (to.fullPath.includes('/public/')) {
        const fixedFullPath = to.fullPath.replace(/\/public\/?/g, '/');
        console.log('🔧 Router Guard - Fixing fullPath with /public/:', { original: to.fullPath, fixed: fixedFullPath });
        // Редиректим на исправленный путь
        next(fixedFullPath);
        return;
    }
    
    const isAuthenticated = store.getters.isAuthenticated;
    
    // КРИТИЧНО: Если требуется авторизация или роль, но пользователь еще не загружен, загружаем его
    if ((to.meta.requiresAuth || to.meta.requiresRole) && isAuthenticated && !store.state.user) {
        console.log('⏳ Router Guard - User not loaded, fetching user...');
        try {
            await store.dispatch('fetchUser');
            console.log('✅ Router Guard - User loaded:', {
                user: store.state.user,
                roles: store.state.user?.roles?.map(r => r.slug) || [],
            });
        } catch (error) {
            console.error('❌ Router Guard - Failed to fetch user:', error);
            next('/login');
            return;
        }
    }
    
    console.log('🔍 Router Guard - Navigation:', {
        to: to.path,
        fullPath: to.fullPath,
        from: from.path,
        requiresAuth: to.meta.requiresAuth,
        requiresRole: to.meta.requiresRole,
        isAuthenticated,
        user: store.state.user,
        userRoles: store.state.user?.roles?.map(r => r.slug) || [],
    });
    
    // 1. Проверка авторизации - ПЕРВЫЙ ПРИОРИТЕТ
    if (to.meta.requiresAuth && !isAuthenticated) {
        console.log('❌ Router Guard - Not authenticated, redirecting to /login');
        next('/login');
        return;
    }
    
    // 2. Если пользователь авторизован и пытается зайти на страницы авторизации, редиректим на главную (или /403 при отсутствии роли)
    if ((to.path === '/login' || to.path === '/register') && isAuthenticated) {
        const hasAdminRole = store.getters.hasAnyRole(['admin']);
        console.log('✅ Router Guard - Already authenticated, redirecting to', hasAdminRole ? '/' : '/403');
        next(hasAdminRole ? '/' : '/403');
        return;
    }
    
    // 3. Проверка подписки для админ-панели
    // Пропускаем проверку для локальной среды разработки
    const isLocalDevelopment = window.location.hostname === 'localhost' || 
                                window.location.hostname === '127.0.0.1' || 
                                window.location.hostname.endsWith('.loc');
    
    if (to.meta.requiresAuth && isAuthenticated && to.path !== '/subscription-expired' && !isLocalDevelopment) {
        try {
            const subscriptionResponse = await axios.get('/api/subscription/check');
            if (!subscriptionResponse.data.success || !subscriptionResponse.data.is_active) {
                console.log('❌ Router Guard - Subscription expired or inactive, redirecting to expired page');
                window.location.href = '/subscription-expired';
                return;
            }
        } catch (error) {
            // Если получили 403, значит подписка истекла
            if (error.response && error.response.status === 403) {
                console.log('❌ Router Guard - Subscription check failed (403), redirecting to expired page');
                window.location.href = '/subscription-expired';
                return;
            }
            // Для других ошибок продолжаем (может быть временная проблема с API)
            console.warn('⚠️ Router Guard - Subscription check error, continuing:', error.message);
        }
    } else if (isLocalDevelopment && to.meta.requiresAuth && isAuthenticated) {
        console.log('✅ Router Guard - Local development mode, skipping subscription check');
    }
    
    // 4. Проверка ролей - ВАЖНО: проверяем ПОСЛЕ загрузки пользователя
    if (to.meta.requiresRole) {
        const requiredRoles = Array.isArray(to.meta.requiresRole) 
            ? to.meta.requiresRole 
            : [to.meta.requiresRole];
        
        const userRoles = store.state.user?.roles?.map(r => r.slug) || [];
        const hasRole = store.getters.hasAnyRole(requiredRoles);
        
        console.log('🔍 Router Guard - Role check:', {
            route: to.path,
            routeName: to.name,
            requiredRoles,
            hasRole,
            userRoles,
            user: store.state.user,
            userRolesFull: store.state.user?.roles,
        });
        
        if (!hasRole) {
            const userRoles = store.state.user?.roles || [];
            const userHasRoles = userRoles.length > 0;
            console.log('❌ Router Guard - No required role, redirecting to /403', {
                route: to.path,
                requiredRoles,
                userRoles: userRoles.map(r => r.slug),
                userHasRoles,
                userRolesCount: userRoles.length,
            });
            next('/403');
            return;
        } else {
            console.log('✅ Router Guard - Role check passed', {
                route: to.path,
                requiredRoles,
                userRoles,
            });
        }
    }
    
    console.log('✅ Router Guard - All checks passed, allowing navigation');
    next();
});

// Initialize app
import App from './App.vue';
const app = createApp(App);

// Set up axios defaults
if (store.state.token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${store.state.token}`;
}

// Добавляем interceptor для автоматического добавления токена во все запросы
axios.interceptors.request.use(
    (config) => {
        // Получаем токен из localStorage (на случай, если он обновился)
        const token = localStorage.getItem('token');
        if (token) {
            config.headers = config.headers || {};
            config.headers['Authorization'] = `Bearer ${token}`;
            
            // Логируем для отладки (только для API запросов)
            if (config.url && config.url.includes('/api/')) {
                console.log('🔐 Axios Interceptor - Adding Authorization header', {
                    url: config.url,
                    hasToken: !!token,
                    tokenLength: token ? token.length : 0,
                });
            }
        } else {
            // Логируем предупреждение, если токена нет для API запросов
            if (config.url && config.url.includes('/api/') && !config.url.includes('/api/auth/')) {
                console.warn('⚠️ Axios Interceptor - No token found for API request', {
                    url: config.url,
                });
            }
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Инициализация пользователя при загрузке приложения
if (store.state.token) {
    console.log('🔍 App initialization - Token found, fetching user...');
    store.dispatch('fetchUser').then(() => {
        console.log('✅ App initialization - User fetched:', {
            user: store.state.user,
            roles: store.state.user?.roles,
            rolesCount: store.state.user?.roles?.length || 0,
        });
        // Загружаем меню после загрузки пользователя
        store.dispatch('fetchMenu');
        store.dispatch('fetchNotifications');
    }).catch((error) => {
        console.error('❌ App initialization - Error fetching user:', error);
    });
} else {
    console.log('⚠️ App initialization - No token found');
}

// Инициализация темы при загрузке приложения
// Применяем тему сразу, до монтирования приложения
const savedTheme = localStorage.getItem('theme') || 'light';
const html = document.documentElement;
if (savedTheme === 'dark') {
    html.classList.add('dark');
    html.setAttribute('data-theme', 'dark');
    html.style.colorScheme = 'dark';
} else {
    html.classList.remove('dark');
    html.setAttribute('data-theme', 'light');
    html.style.colorScheme = 'light';
}
// Устанавливаем начальное состояние в store
store.state.theme = savedTheme;

// Initialize user and menu on app start
if (store.state.token) {
    console.log('🔍 App initialization - Token found, fetching user...');
    store.dispatch('fetchUser').then(() => {
        console.log('✅ App initialization - User fetched:', {
            user: store.state.user,
            roles: store.state.user?.roles,
            rolesCount: store.state.user?.roles?.length || 0,
        });
        // Загружаем меню после загрузки пользователя
        store.dispatch('fetchMenu');
        store.dispatch('fetchNotifications');
    }).catch((error) => {
        console.error('❌ App initialization - Error fetching user:', error);
    });
} else {
    console.log('⚠️ App initialization - No token found');
}

app.use(store);
app.use(router);
app.component('Toast', Toast);

// Mount app
// Монтируем приложение в контейнер #admin-app
const appContainer = document.getElementById('admin-app');
if (appContainer) {
    app.mount('#admin-app');
}

