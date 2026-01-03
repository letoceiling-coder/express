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
use App\Http\Controllers\Api\v1\DeliveryController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\v1\OrderController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ProductHistoryController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
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
        
        // Categories
        Route::apiResource('categories', CategoryController::class);
        
        // Products
        Route::apiResource('products', ProductController::class);
        
        // Product History
        Route::get('products/{id}/history', [ProductHistoryController::class, 'index'])
            ->name('products.history');
        Route::get('products/{productId}/history/{historyId}', [ProductHistoryController::class, 'show'])
            ->name('products.history.show');
        
        // Orders
        Route::apiResource('orders', OrderController::class);
        Route::put('orders/{id}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.status');
        
        // Deliveries
        Route::apiResource('deliveries', DeliveryController::class);
        Route::put('deliveries/{id}/status', [DeliveryController::class, 'updateStatus'])
            ->name('deliveries.status');
        Route::get('orders/{orderId}/delivery', [DeliveryController::class, 'getByOrder'])
            ->name('orders.delivery');
        
        // Payments
        Route::apiResource('payments', PaymentController::class);
        Route::put('payments/{id}/status', [PaymentController::class, 'updateStatus'])
            ->name('payments.status');
        Route::post('payments/{id}/refund', [PaymentController::class, 'refund'])
            ->name('payments.refund');
        Route::get('orders/{orderId}/payments', [PaymentController::class, 'getByOrder'])
            ->name('orders.payments');
        
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

