<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ВАЖНО: Роут для storage должен быть ПЕРВЫМ, до всех остальных
// Это нужно, чтобы Laravel обрабатывал запросы к /storage, даже если символическая ссылка не работает
Route::get('/storage/{path}', function ($path) {
    // Защита от path traversal
    $path = str_replace('..', '', $path);
    $path = ltrim($path, '/');
    
    $filePath = storage_path('app/public/' . $path);
    $basePath = storage_path('app/public');
    
    // Проверяем, что файл находится внутри базовой директории
    $realFilePath = realpath($filePath);
    $realBasePath = realpath($basePath);
    
    if (!$realFilePath || !$realBasePath || !str_starts_with($realFilePath, $realBasePath)) {
        \Illuminate\Support\Facades\Log::warning('Storage file access denied - path traversal attempt', [
            'path' => $path,
            'file_path' => $filePath,
        ]);
        abort(404);
    }
    
    if (!file_exists($realFilePath) || !is_file($realFilePath)) {
        \Illuminate\Support\Facades\Log::warning('Storage file not found', [
            'path' => $path,
            'real_file_path' => $realFilePath,
        ]);
        abort(404);
    }
    
    $mimeType = mime_content_type($realFilePath);
    $fileName = basename($realFilePath);
    
    \Illuminate\Support\Facades\Log::debug('Storage file served', [
        'path' => $path,
        'file' => $fileName,
        'mime_type' => $mimeType,
    ]);
    
    return response()->file($realFilePath, [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*')->name('storage.serve');

// API маршруты должны быть обработаны до SPA маршрутов
// Они определены в routes/api.php

// Проксирование assets для React приложения - должно быть ПЕРВЫМ
// Обработка запросов к /assets/* (перенаправление на /frontend/assets/*)
Route::get('/assets/{path}', function ($path) {
    // Защита от path traversal
    $path = str_replace('..', '', $path);
    $path = ltrim($path, '/');
    
    $filePath = public_path('frontend/assets/' . $path);
    $basePath = public_path('frontend/assets');
    
    // Проверяем, что файл находится внутри базовой директории
    $realFilePath = realpath($filePath);
    $realBasePath = realpath($basePath);
    
    if (!$realFilePath || !$realBasePath || !str_starts_with($realFilePath, $realBasePath)) {
        abort(404, "File not found: {$path}");
    }
    
    // Проверяем существование файла
    if (!file_exists($realFilePath) || !is_file($realFilePath)) {
        abort(404, "File not found: {$path}");
    }
    
    // Определяем MIME тип по расширению
    $extension = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    $mimeType = $mimeTypes[$extension] ?? mime_content_type($realFilePath);
    
    return response()->file($realFilePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.+')->name('react.assets.alias');

// Проксирование assets для React приложения - должно быть ПЕРВЫМ
// Если запрашивается /frontend/assets/*, отдаем из /public/frontend/assets/*
Route::get('/frontend/assets/{path}', function ($path) {
    // Защита от path traversal
    $path = str_replace('..', '', $path);
    $path = ltrim($path, '/');
    
    $filePath = public_path('frontend/assets/' . $path);
    $basePath = public_path('frontend/assets');
    
    // Проверяем, что файл находится внутри базовой директории
    $realFilePath = realpath($filePath);
    $realBasePath = realpath($basePath);
    
    if (!$realFilePath || !$realBasePath || !str_starts_with($realFilePath, $realBasePath)) {
        abort(404, "File not found: {$path}");
    }
    
    // Проверяем существование файла
    if (!file_exists($realFilePath) || !is_file($realFilePath)) {
        abort(404, "File not found: {$path}");
    }
    
    // Определяем MIME тип по расширению
    $extension = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    $mimeType = $mimeTypes[$extension] ?? mime_content_type($realFilePath);
    
    return response()->file($realFilePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.+')->name('react.assets');

// Проксирование других файлов из frontend (например, vite.svg)
Route::get('/frontend/{path}', function ($path) {
    // Безопасно получаем имя файла (защита от path traversal)
    $fileName = basename($path);
    $filePath = public_path('frontend/' . $fileName);
    
    // Проверяем существование файла
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404, "File not found: {$fileName}");
    }
    
    // Определяем MIME тип по расширению
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
    ];
    
    $mimeType = $mimeTypes[$extension] ?? mime_content_type($filePath);
    
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '^(?!assets/).+')->name('react.files');

// Проксирование assets для Vue админки (build/assets/*)
Route::get('/build/assets/{path}', function ($path) {
    // Безопасно получаем имя файла (защита от path traversal)
    $fileName = basename($path);
    $filePath = public_path('build/assets/' . $fileName);
    
    // Проверяем существование файла
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404, "File not found: {$fileName}");
    }
    
    // Определяем MIME тип по расширению
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    $mimeType = $mimeTypes[$extension] ?? mime_content_type($filePath);
    
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.+')->name('build.assets');

// Проксирование assets для Vue админки (build/assets/*)
// Должно быть ДО catch-all роутов
Route::get('/build/assets/{path}', function ($path) {
    // Безопасно получаем имя файла (защита от path traversal)
    $fileName = basename($path);
    $filePath = public_path('build/assets/' . $fileName);
    
    // Проверяем существование файла
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404, "File not found: {$fileName}");
    }
    
    // Определяем MIME тип по расширению
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    $mimeType = $mimeTypes[$extension] ?? mime_content_type($filePath);
    
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.+')->name('build.assets');

// Версия для проверки деплоя (curl https://dev.svoihlebekb.ru/version)
Route::get('/version', function () {
    $commit = 'unknown';
    $adminBuild = 'unknown';
    try {
        $proc = \Illuminate\Support\Facades\Process::path(base_path())->run('git rev-parse --short HEAD');
        if ($proc->successful()) {
            $commit = trim($proc->output()) ?: 'unknown';
        }
    } catch (\Throwable $e) {
        // ignore
    }
    $manifestPath = public_path('build/manifest.json');
    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $adminBuild = $manifest['resources/js/admin.js']['file'] ?? 'not-found';
    }
    $sms = app(\App\Services\Sms\IqSmsService::class);
    return response()->json([
        'commit' => $commit,
        'admin_build' => $adminBuild,
        'expected' => 'assets/admin-B0uRBwbT.js',
        'ok' => str_contains($adminBuild, 'admin-B0uRBwbT'),
        'sms_dev_mode' => $sms->isDevMode(),
        'host' => request()->getHost(),
        'app_env' => config('app.env'),
    ]);
});

// Страница истечения подписки (должна быть до админ-панели)
Route::get('/subscription-expired', [\App\Http\Controllers\SubscriptionExpiredController::class, 'index'])
    ->name('subscription.expired');

// Маршруты для админ-панели (Vue) - с проверкой подписки
Route::middleware('subscription.check')->group(function () {
Route::get('/admin/{any?}', function () {
    return response()
        ->view('admin')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->where('any', '.*')->name('admin');
});

// Публичный роут для просмотра логов
Route::get('/logs', [\App\Http\Controllers\LogController::class, 'index'])->name('logs.index');

// Страница для звонка (для iOS в Telegram Mini App)
Route::get('/call', function () {
    $phone = request()->query('phone', '');
    // Очищаем номер от лишних символов, оставляем только цифры и +
    $phone = preg_replace('/[^\d+]/', '', $phone);
    
    return view('call', ['phone' => $phone]);
})->name('call');

// Маршруты для основного приложения (React)
// No-cache для HTML — чтобы при обновлении сборки браузер получал свежие скрипты
$reactHeaders = [
    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    'Pragma' => 'no-cache',
    'Expires' => '0',
];

// Корневой роут для React приложения
Route::get('/', function () use ($reactHeaders) {
    return response()->view('react')->withHeaders($reactHeaders);
})->name('react.root');

// Все остальные маршруты (кроме admin, api, storage, build, frontend, assets, logs, call) отдаются React приложению
Route::get('/{any?}', function ($any = null) use ($reactHeaders) {
    // Перед отдачей React view проверяем, не запрашивается ли статический файл
    if ($any && preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|webp|woff|woff2|ttf|eot)$/i', $any)) {
        $filePath = public_path($any);
        if (file_exists($filePath) && is_file($filePath)) {
            return response()->file($filePath);
        }
    }
    
    return response()->view('react')->withHeaders($reactHeaders);
})->where('any', '^(?!admin|api|storage|build|frontend|assets|logs|call).*')->name('react');
