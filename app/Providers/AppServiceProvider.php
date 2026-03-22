<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
