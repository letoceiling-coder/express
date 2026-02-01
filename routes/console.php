<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Расписание команд для WOW Рулетки
Schedule::command('wow:restore-tickets')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->runInBackground();

// Автоматическое начисление билетов через заданный интервал
// Запускаем каждый час для проверки всех пользователей
Schedule::command('wow:accrue-tickets')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Автоматическая рассылка сообщений пользователям
// Запускаем каждый час для проверки условий отправки
Schedule::command('wow:send-broadcast-messages')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Персональные уведомления о доступности бесплатной прокрутки
// Запускаем каждые 5 минут для проверки индивидуальных 24-часовых таймеров
Schedule::command('wow:send-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Синхронизация статусов платежей ЮKassa с API (на случай если webhook не пришёл)
Schedule::command('payments:sync-statuses')
    ->everyTenMinutes()
    ->withoutOverlapping(5)
    ->runInBackground();
