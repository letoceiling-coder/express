<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTelegramUserRequest;
use App\Models\TelegramUser;
use App\Services\Telegram\TelegramUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramUserController extends Controller
{
    protected TelegramUserService $telegramUserService;

    public function __construct(TelegramUserService $telegramUserService)
    {
        $this->telegramUserService = $telegramUserService;
    }

    /**
     * Список пользователей бота
     * 
     * GET /api/v1/telegram-users?bot_id={id}&search={text}&is_blocked={bool}&sort={field}&per_page={num}
     */
    public function index(Request $request): JsonResponse
    {
        $query = TelegramUser::query()->with('bot');

        // Фильтрация по боту
        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->get('bot_id'));
        }

        // Фильтрация по статусу блокировки
        if ($request->has('is_blocked')) {
            $query->where('is_blocked', $request->boolean('is_blocked'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('telegram_id', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'last_interaction_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = (int) $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users,
        ]);
    }

    /**
     * Детали пользователя
     * 
     * GET /api/v1/telegram-users/{id}
     */
    public function show($id): JsonResponse
    {
        $user = TelegramUser::with(['bot', 'orders'])->findOrFail($id);

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Обновление пользователя
     * 
     * PUT /api/v1/telegram-users/{id}
     */
    public function update(UpdateTelegramUserRequest $request, $id): JsonResponse
    {
        $user = TelegramUser::findOrFail($id);

        $user->update($request->validated());

        return response()->json([
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Удаление пользователя
     * 
     * DELETE /api/v1/telegram-users/{id}
     */
    public function destroy($id): JsonResponse
    {
        $user = TelegramUser::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Пользователь удален',
        ], 200);
    }

    /**
     * Заблокировать пользователя
     * 
     * POST /api/v1/telegram-users/{id}/block
     */
    public function block($id): JsonResponse
    {
        $user = TelegramUser::findOrFail($id);
        $user->block();

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'Пользователь заблокирован',
        ]);
    }

    /**
     * Разблокировать пользователя
     * 
     * POST /api/v1/telegram-users/{id}/unblock
     */
    public function unblock($id): JsonResponse
    {
        $user = TelegramUser::findOrFail($id);
        $user->unblock();

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'Пользователь разблокирован',
        ]);
    }

    /**
     * Синхронизировать данные из Telegram
     * 
     * POST /api/v1/telegram-users/{id}/sync
     */
    public function sync($id): JsonResponse
    {
        $user = TelegramUser::findOrFail($id);

        try {
            $user = $this->telegramUserService->updateUserFromTelegram($user);
            
            return response()->json([
                'data' => $user,
                'message' => 'Данные пользователя синхронизированы',
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing telegram user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка синхронизации: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Статистика пользователя
     * 
     * GET /api/v1/telegram-users/{id}/statistics
     */
    public function statistics($id): JsonResponse
    {
        $user = TelegramUser::with(['orders'])->findOrFail($id);

        // Обновляем статистику
        $this->telegramUserService->updateStatistics($user);
        $user->refresh();

        return response()->json([
            'data' => [
                'user' => $user,
                'orders_count' => $user->orders_count,
                'total_spent' => $user->total_spent,
                'last_order' => $user->orders()->latest()->first(),
            ],
        ]);
    }
}
