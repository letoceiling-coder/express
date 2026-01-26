<?php

namespace App\Helpers;

use App\Models\Bot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TelegramHelper
{
    /**
     * Получить URL Mini App с единого источника
     * 
     * @param Bot|null $bot Бот (опционально, для получения из настроек бота)
     * @param bool $withVersion Добавить версию для сброса кеша
     * @return string
     * @throws \Exception Если URL невалиден
     */
    public static function getMiniAppUrl(?Bot $bot = null, bool $withVersion = true): string
    {
        // Приоритет 1: настройки бота
        $url = $bot?->settings['mini_app_url'] ?? null;
        
        // Приоритет 2: конфиг
        if (!$url) {
            $url = config('telegram.mini_app_url');
        }
        
        // Приоритет 3: env
        if (!$url) {
            $url = env('TELEGRAM_MINI_APP_URL', env('APP_URL'));
        }
        
        // Валидация URL
        $url = trim($url);
        if (empty($url)) {
            throw new \Exception('Mini App URL не настроен. Установите TELEGRAM_MINI_APP_URL в .env или в настройках бота.');
        }
        
        // Удаляем пробелы
        $url = str_replace(' ', '', $url);
        
        // Проверка на https
        if (!str_starts_with($url, 'https://')) {
            Log::warning('⚠️ Mini App URL должен использовать HTTPS', [
                'url' => $url,
                'bot_id' => $bot?->id,
            ]);
            throw new \Exception('Mini App URL должен использовать HTTPS протокол.');
        }
        
        // Валидация формата URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Mini App URL имеет неверный формат: ' . $url);
        }
        
        // Добавляем версию для сброса кеша Telegram
        if ($withVersion) {
            $appVersion = self::getAppVersion();
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url = $url . $separator . 'v=' . $appVersion;
        }
        
        return $url;
    }
    
    /**
     * Получить версию приложения для сброса кеша
     * 
     * @return string
     */
    protected static function getAppVersion(): string
    {
        // Пытаемся получить из кеша
        $cacheKey = 'app_version';
        $version = Cache::get($cacheKey);
        
        if ($version) {
            return $version;
        }
        
        // Пытаемся получить git hash
        $gitHash = null;
        if (function_exists('exec') && is_dir(base_path('.git'))) {
            $gitHash = @exec('git rev-parse --short HEAD 2>/dev/null');
            if (!empty($gitHash)) {
                $version = $gitHash;
            }
        }
        
        // Fallback: config или timestamp
        if (empty($version)) {
            $version = config('app.version');
            if (empty($version) || $version === date('YmdHis')) {
                $version = (string)(int)(microtime(true) * 1000); // миллисекунды
            }
        }
        
        // Кешируем на 1 час
        Cache::put($cacheKey, $version, 3600);
        
        return $version;
    }
    
    /**
     * Получить текст кнопки меню
     * 
     * @param Bot|null $bot
     * @return string
     */
    public static function getMenuButtonLabel(?Bot $bot = null): string
    {
        // Приоритет 1: настройки бота
        $label = $bot?->settings['menu_button_label'] ?? null;
        
        // Приоритет 2: конфиг
        if (!$label) {
            $label = config('telegram.menu_button_label');
        }
        
        // Дефолт
        return $label ?: 'Открыть приложение';
    }
    
    /**
     * Получить текст приветственного сообщения
     * 
     * @param Bot|null $bot
     * @return string
     */
    public static function getWelcomeMessage(?Bot $bot = null): string
    {
        // Приоритет 1: welcome_message из БД
        if ($bot?->welcome_message) {
            return $bot->welcome_message;
        }
        
        // Приоритет 2: настройки бота
        $message = $bot?->settings['welcome_bot_text'] ?? null;
        
        // Приоритет 3: конфиг
        if (!$message) {
            $message = config('telegram.welcome_bot_text');
        }
        
        // Дефолт
        return $message ?: '👋 Добро пожаловать! Нажмите на кнопку ниже, чтобы открыть приложение.';
    }
    
    /**
     * Получить текст inline кнопки
     * 
     * @param Bot|null $bot
     * @return string
     */
    public static function getInlineButtonLabel(?Bot $bot = null): string
    {
        // Приоритет 1: button_text из БД
        if ($bot?->button_text) {
            return $bot->button_text;
        }
        
        // Приоритет 2: настройки бота
        $label = $bot?->settings['inline_button_label'] ?? null;
        
        // Приоритет 3: конфиг
        if (!$label) {
            $label = config('telegram.inline_button_label');
        }
        
        // Дефолт
        return $label ?: 'Сделать заказ';
    }
}
