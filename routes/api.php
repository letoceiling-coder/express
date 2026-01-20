<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminMenuController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\ComplaintController;
use App\Http\Controllers\Api\v1\DeliveryController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\v1\OrderController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\PaymentMethodController;
use App\Http\Controllers\Api\v1\PaymentSettingsController;
use App\Http\Controllers\Api\v1\DeliverySettingsController;
use App\Http\Controllers\Api\v1\OrderSettingsController;
use App\Http\Controllers\Api\v1\AboutPageController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ProductHistoryController;
use App\Http\Controllers\Api\v1\ReturnController;
use App\Http\Controllers\Api\v1\ReviewController;
use App\Http\Controllers\Api\v1\TelegramController;
use App\Http\Controllers\Api\v1\TelegramUserController;
use App\Http\Controllers\Api\v1\BroadcastController;
use App\Http\Controllers\Api\v1\TelegramUserRoleRequestController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Публичные роуты для miniApp (GET запросы к категориям и продуктам)
Route::prefix('v1')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index.public');
    Route::get('categories/{id}', [CategoryController::class, 'show'])->name('categories.show.public');
    Route::get('products', [ProductController::class, 'index'])->name('products.index.public');
    Route::get('products/{id}', [ProductController::class, 'show'])->name('products.show.public');
    
    // Публичные роуты для заказов из MiniApp
    // GET - только с фильтром telegram_id (пользователь может получить только свои заказы)
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index.public');
    // POST - создание заказа из MiniApp
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store.public');
    
    // Публичные роуты для способов оплаты (только активные)
    Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index.public');
    Route::get('payment-methods/{id}', [PaymentMethodController::class, 'show'])->name('payment-methods.show.public');
    
    // Публичный роут для создания платежа через ЮКасса (из MiniApp)
    Route::post('payments/yookassa/create', [PaymentController::class, 'createYooKassaPayment'])
        ->name('payments.yookassa.create.public');
    
    // Публичный роут для получения настроек доставки (из MiniApp)
    Route::get('delivery-settings', [DeliverySettingsController::class, 'getSettings'])
        ->name('delivery-settings.get.public');
    
    // Публичный роут для расчета стоимости доставки (из MiniApp)
    Route::post('delivery/calculate-cost', [DeliverySettingsController::class, 'calculateCost'])
        ->name('delivery.calculate-cost.public');
    
    // Публичный роут для получения подсказок адресов (из MiniApp)
    Route::post('delivery/address-suggestions', [DeliverySettingsController::class, 'getAddressSuggestions'])
        ->name('delivery.address-suggestions.public');
    
    // Публичный роут для страницы "О нас"
    Route::get('about', [AboutPageController::class, 'show'])->name('about.show.public');
    
    // Публичный роут для отмены заказа из MiniApp
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel'])
        ->name('orders.cancel.public');
    
    // Публичный вебхук от YooKassa (без авторизации, так как YooKassa отправляет уведомления напрямую)
    Route::post('webhooks/yookassa', [PaymentSettingsController::class, 'webhookYooKassa'])
        ->name('webhooks.yookassa.public');
});

// Защищённые роуты
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    
    // Меню
    Route::get('/admin/menu', [AdminMenuController::class, 'index']);
    
    // Уведомления
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/all', [NotificationController::class, 'all']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // Media API (v1)
    Route::prefix('v1')->group(function () {
        // Folders
        Route::get('folders/tree/all', [FolderController::class, 'tree'])->name('folders.tree');
        Route::post('folders/update-positions', [FolderController::class, 'updatePositions'])->name('folders.update-positions');
        Route::post('folders/{id}/restore', [FolderController::class, 'restore'])->name('folders.restore');
        Route::apiResource('folders', FolderController::class);
        
        // Media
        Route::post('media/{id}/restore', [MediaController::class, 'restore'])->name('media.restore');
        Route::delete('media/trash/empty', [MediaController::class, 'emptyTrash'])->name('media.trash.empty');
        Route::apiResource('media', MediaController::class);
        
        // Categories (POST, PUT, DELETE - GET обрабатывается публичными роутами)
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('categories/update-positions', [CategoryController::class, 'updatePositions'])->name('categories.update-positions');
        Route::get('categories/export/csv', [CategoryController::class, 'exportCsv'])->name('categories.export.csv');
        Route::get('categories/export/excel', [CategoryController::class, 'exportExcel'])->name('categories.export.excel');
        Route::post('categories/import', [CategoryController::class, 'import'])->name('categories.import');
        
        // Products (POST, PUT, DELETE - GET обрабатывается публичными роутами)
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('products/update-positions', [ProductController::class, 'updatePositions'])->name('products.update-positions');
        Route::get('products/export/csv', [ProductController::class, 'exportCsv'])->name('products.export.csv');
        Route::get('products/export/excel', [ProductController::class, 'exportExcel'])->name('products.export.excel');
        Route::get('products/export/zip', [ProductController::class, 'exportZip'])->name('products.export.zip');
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
        
        // Product History
        Route::get('products/{id}/history', [ProductHistoryController::class, 'index'])
            ->name('products.history');
        Route::get('products/{productId}/history/{historyId}', [ProductHistoryController::class, 'show'])
            ->name('products.history.show');
        
        // Orders (GET и POST обрабатываются публичными роутами, здесь только защищенные методы)
        Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{id}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::put('orders/{id}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.status');
        Route::get('orders/{id}/status-history', [OrderController::class, 'statusHistory'])
            ->name('orders.status-history');
        
        // Deliveries
        Route::apiResource('deliveries', DeliveryController::class);
        Route::put('deliveries/{id}/status', [DeliveryController::class, 'updateStatus'])
            ->name('deliveries.status');
        Route::get('orders/{orderId}/delivery', [DeliveryController::class, 'getByOrder'])
            ->name('orders.delivery');
        
        // Payments
        Route::apiResource('payments', PaymentController::class);
        // createYooKassaPayment вынесен в публичные роуты для MiniApp
        Route::put('payments/{id}/status', [PaymentController::class, 'updateStatus'])
            ->name('payments.status');
        Route::post('payments/{id}/refund', [PaymentController::class, 'refund'])
            ->name('payments.refund');
        Route::get('orders/{orderId}/payments', [PaymentController::class, 'getByOrder'])
            ->name('orders.payments');
        
        // Payment Methods (Admin only)
        Route::apiResource('payment-methods', PaymentMethodController::class)->except(['index', 'show']);
        
        // Returns
        Route::apiResource('returns', ReturnController::class);
        Route::put('returns/{id}/status', [ReturnController::class, 'updateStatus'])
            ->name('returns.status');
        Route::post('returns/{id}/approve', [ReturnController::class, 'approve'])
            ->name('returns.approve');
        Route::post('returns/{id}/reject', [ReturnController::class, 'reject'])
            ->name('returns.reject');
        Route::get('orders/{orderId}/returns', [ReturnController::class, 'getByOrder'])
            ->name('orders.returns');
        
        // Complaints
        Route::apiResource('complaints', ComplaintController::class);
        Route::put('complaints/{id}/status', [ComplaintController::class, 'updateStatus'])
            ->name('complaints.status');
        Route::get('orders/{orderId}/complaints', [ComplaintController::class, 'getByOrder'])
            ->name('orders.complaints');
        
        // Reviews
        Route::apiResource('reviews', ReviewController::class);
        Route::put('reviews/{id}/status', [ReviewController::class, 'updateStatus'])
            ->name('reviews.status');
        Route::post('reviews/{id}/response', [ReviewController::class, 'addResponse'])
            ->name('reviews.response');
        Route::get('products/{productId}/reviews', [ReviewController::class, 'getByProduct'])
            ->name('products.reviews');
        Route::get('orders/{orderId}/review', [ReviewController::class, 'getByOrder'])
            ->name('orders.review');
        
        // Payment Settings
        Route::get('payment-settings', [PaymentSettingsController::class, 'index'])
            ->name('payment-settings.index');
        Route::get('payment-settings/yookassa', [PaymentSettingsController::class, 'getYooKassa'])
            ->name('payment-settings.yookassa');
        Route::put('payment-settings/yookassa', [PaymentSettingsController::class, 'updateYooKassa'])
            ->name('payment-settings.yookassa.update');
        Route::post('payment-settings/yookassa/test', [PaymentSettingsController::class, 'testYooKassa'])
            ->name('payment-settings.yookassa.test');
        
        // Delivery Settings
        Route::get('delivery-settings', [DeliverySettingsController::class, 'getSettings'])
            ->name('delivery-settings.get');
        Route::put('delivery-settings', [DeliverySettingsController::class, 'updateSettings'])
            ->name('delivery-settings.update');
        
        // Order Settings
        Route::get('order-settings', [OrderSettingsController::class, 'getSettings'])
            ->name('order-settings.get');
        Route::put('order-settings', [OrderSettingsController::class, 'updateSettings'])
            ->name('order-settings.update');
        
        // About Page (Admin)
        Route::get('admin/about', [AboutPageController::class, 'getAdmin'])
            ->name('admin.about.get');
        Route::put('admin/about', [AboutPageController::class, 'update'])
            ->name('admin.about.update');
        
        // Telegram MiniApp
        Route::post('telegram/validate-init-data', [TelegramController::class, 'validateInitData'])
            ->name('telegram.validate-init-data');
        Route::get('telegram/user-info', [TelegramController::class, 'getUserInfo'])
            ->name('telegram.user-info');
        Route::post('telegram/notify-order', [TelegramController::class, 'notifyOrder'])
            ->name('telegram.notify-order');
        
        // Telegram Users
        Route::apiResource('telegram-users', TelegramUserController::class);
        Route::post('telegram-users/{id}/block', [TelegramUserController::class, 'block']);
        Route::post('telegram-users/{id}/unblock', [TelegramUserController::class, 'unblock']);
        Route::post('telegram-users/{id}/sync', [TelegramUserController::class, 'sync']);
        Route::get('telegram-users/{id}/statistics', [TelegramUserController::class, 'statistics']);
        
                // Broadcasts
                Route::post('broadcasts/send', [BroadcastController::class, 'send']);
                Route::post('broadcasts/preview', [BroadcastController::class, 'preview']);

                // Telegram User Role Requests
                Route::get('telegram-user-role-requests', [TelegramUserRoleRequestController::class, 'index']);
                Route::get('telegram-user-role-requests/statistics', [TelegramUserRoleRequestController::class, 'statistics']);
                Route::get('telegram-user-role-requests/{id}', [TelegramUserRoleRequestController::class, 'show']);
                Route::post('telegram-user-role-requests/{id}/approve', [TelegramUserRoleRequestController::class, 'approve']);
                Route::post('telegram-user-role-requests/{id}/reject', [TelegramUserRoleRequestController::class, 'reject']);
        
        // Admin only routes (Roles and Users management)
        Route::middleware('admin')->group(function () {
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('users', UserController::class);
            
            // Bots management
            Route::apiResource('bots', BotController::class);
            Route::get('bots/{id}/check-webhook', [BotController::class, 'checkWebhook']);
            Route::post('bots/{id}/register-webhook', [BotController::class, 'registerWebhook']);
            
            // Support tickets
            Route::get('support/tickets', [SupportController::class, 'index']);
            Route::get('support/tickets/{id}', [SupportController::class, 'show']);
            Route::post('support/ticket', [SupportController::class, 'store']);
            Route::post('support/message', [SupportController::class, 'sendMessage']);
        });
    });
});

// Integration API (protected by deploy.token middleware)
Route::middleware('deploy.token')->prefix('integration')->group(function () {
    Route::post('/messages', [\App\Http\Controllers\Api\IntegrationController::class, 'receiveMessage']);
    Route::post('/status', [\App\Http\Controllers\Api\IntegrationController::class, 'receiveStatusChange']);
});

// Legacy webhooks (deprecated, use /api/integration/*)
Route::middleware('deploy.token')->prefix('support/webhook')->group(function () {
    Route::post('/message', [SupportController::class, 'webhookMessage']);
    Route::post('/status', [SupportController::class, 'webhookStatus']);
});

// Маршрут для деплоя (защищен токеном)
Route::post('/deploy', [DeployController::class, 'deploy'])
    ->middleware('deploy.token');

// Маршрут для выполнения seeders (защищен токеном)
Route::post('/seed', [DeployController::class, 'seed'])
    ->middleware('deploy.token');

// Webhook от GitHub для автоматического деплоя (проверка подписи внутри контроллера)
Route::post('/webhook/github', [\App\Http\Controllers\Api\WebhookController::class, 'github']);

// Проверка подписки (публичный endpoint, используется фронтендом)
Route::get('/subscription/check', [\App\Http\Controllers\Api\SubscriptionCheckController::class, 'check']);

// Публичные роуты для просмотра логов
Route::get('/logs', [\App\Http\Controllers\LogController::class, 'getLogs']);
Route::get('/logs/files', [\App\Http\Controllers\LogController::class, 'getLogFilesList']);
Route::post('/logs/clear', [\App\Http\Controllers\LogController::class, 'clearLogs']);

// Telegram webhook (публичный роут, Telegram отправляет POST запросы)
Route::post('/telegram/webhook/{id}', [BotController::class, 'handleWebhook'])
    ->where('id', '[0-9]+')
    ->name('telegram.webhook');

