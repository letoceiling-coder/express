<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Sms\SmsMessenger;
use App\Services\Sms\SmsSender;
use App\Services\Sms\SmsSettingsResolver;
use InvalidArgumentException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsSettingsResolver::class);

        $this->app->bind(SmsSender::class, function ($app): SmsSender {
            $driver = (string) (config('sms.driver') ?: 'iqsms');
            $registry = (array) config('sms.drivers', []);

            if (! isset($registry[$driver])) {
                throw new InvalidArgumentException(
                    'Неизвестный SMS driver «'.$driver.'». Проверьте SMS_DRIVER в .env.'
                );
            }

            $class = $registry[$driver];
            if (! is_string($class) || ! is_a($class, SmsSender::class, true)) {
                throw new InvalidArgumentException(
                    'Класс SMS driver «'.$class.'» не реализует SmsSender.'
                );
            }

            return $app->make($class);
        });

        $this->app->singleton(SmsMessenger::class, fn ($app) => new SmsMessenger($app->make(SmsSender::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($proxy = config('telegram.http_proxy')) {
            Http::globalOptions(['proxy' => $proxy]);
        }

        // Используем кастомную модель PersonalAccessToken
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Регистрируем Observer для автоматического логирования изменений товаров
        Product::observe(ProductObserver::class);

        // Rate limit for send-code: 3 per minute per IP
        RateLimiter::for('sms-send-code-ip', function (Request $request) {
            $limit = config('sms.rate_limit_per_ip', 3);
            return Limit::perMinute($limit)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Слишком много запросов с вашего IP. Попробуйте через минуту.',
                    ], 429);
                });
        });
    }
}
