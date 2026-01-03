<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\Observers\ProductObserver;
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
    }
}
