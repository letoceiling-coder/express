<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Order;
use App\Models\TelegramUser;
use App\Services\Telegram\TelegramUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncTelegramUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:sync-users 
                            {--bot-id= : ID бота для синхронизации (если не указан, синхронизируются все боты)}
                            {--update-statistics : Обновить статистику пользователей}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизация пользователей Telegram из таблицы orders';

    protected TelegramUserService $telegramUserService;

    public function __construct(TelegramUserService $telegramUserService)
    {
        parent::__construct();
        $this->telegramUserService = $telegramUserService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Начало синхронизации пользователей Telegram...');
        $this->newLine();

        $botId = $this->option('bot-id');
        $updateStatistics = $this->option('update-statistics');

        try {
            // Получаем уникальных пользователей из заказов
            $query = Order::select('telegram_id', 'bot_id')
                ->whereNotNull('telegram_id')
                ->whereNotNull('bot_id')
                ->groupBy('telegram_id', 'bot_id');

            if ($botId) {
                $query->where('bot_id', $botId);
            }

            $users = $query->get();
            $total = $users->count();

            $this->info("📊 Найдено уникальных пользователей: {$total}");
            $this->newLine();

            if ($total === 0) {
                $this->warn('⚠️  Пользователи не найдены');
                return 0;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $created = 0;
            $updated = 0;
            $errors = 0;

            foreach ($users as $userData) {
                try {
                    // Проверяем, существует ли пользователь
                    $telegramUser = TelegramUser::where('bot_id', $userData->bot_id)
                        ->where('telegram_id', $userData->telegram_id)
                        ->first();

                    if ($telegramUser) {
                        // Пользователь уже существует, только обновляем статистику если нужно
                        if ($updateStatistics) {
                            $this->telegramUserService->updateStatistics($telegramUser);
                        }
                        $updated++;
                    } else {
                        // Создаем нового пользователя
                        // Получаем данные из первого заказа
                        $firstOrder = Order::where('bot_id', $userData->bot_id)
                            ->where('telegram_id', $userData->telegram_id)
                            ->orderBy('created_at', 'asc')
                            ->first();

                        // Создаем пользователя с минимальными данными
                        TelegramUser::create([
                            'bot_id' => $userData->bot_id,
                            'telegram_id' => $userData->telegram_id,
                            'first_name' => null, // Будет заполнено при следующем взаимодействии
                            'last_name' => null,
                            'username' => null,
                            'orders_count' => 0,
                            'total_spent' => 0,
                        ]);

                        $created++;

                        // Обновляем статистику для нового пользователя
                        if ($updateStatistics) {
                            $telegramUser = TelegramUser::where('bot_id', $userData->bot_id)
                                ->where('telegram_id', $userData->telegram_id)
                                ->first();
                            if ($telegramUser) {
                                $this->telegramUserService->updateStatistics($telegramUser);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Error syncing telegram user', [
                        'bot_id' => $userData->bot_id,
                        'telegram_id' => $userData->telegram_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Если нужно обновить статистику для всех пользователей
            if ($updateStatistics && ($created > 0 || $updated > 0)) {
                $this->info('📊 Обновление статистики для всех пользователей...');
                
                $usersQuery = TelegramUser::query();
                if ($botId) {
                    $usersQuery->where('bot_id', $botId);
                }
                $allUsers = $usersQuery->get();
                
                $statBar = $this->output->createProgressBar($allUsers->count());
                $statBar->start();

                foreach ($allUsers as $user) {
                    try {
                        $this->telegramUserService->updateStatistics($user);
                    } catch (\Exception $e) {
                        Log::error('Error updating statistics', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    $statBar->advance();
                }

                $statBar->finish();
                $this->newLine(2);
            }

            $this->info('✅ Синхронизация завершена!');
            $this->line("   Создано: {$created}");
            $this->line("   Обновлено: {$updated}");
            if ($errors > 0) {
                $this->warn("   Ошибок: {$errors}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Ошибка синхронизации: ' . $e->getMessage());
            Log::error('SyncTelegramUsers error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
