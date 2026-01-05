<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\Telegram\TelegramMiniAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected TelegramMiniAppService $telegramMiniAppService;

    public function __construct(TelegramMiniAppService $telegramMiniAppService)
    {
        $this->telegramMiniAppService = $telegramMiniAppService;
    }

    /**
     * Валидация initData от Telegram
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateInitData(Request $request)
    {
        $request->validate([
            'init_data' => ['required', 'string'],
            'bot_token' => ['nullable', 'string'],
            'bot_id' => ['nullable', 'integer', 'exists:bots,id'],
        ]);

        $initData = $request->input('init_data');
        $botToken = $request->input('bot_token');
        $botId = $request->input('bot_id');

        // Получаем токен бота
        if (!$botToken && $botId) {
            $bot = Bot::find($botId);
            if ($bot) {
                $botToken = $bot->token;
            }
        }

        if (!$botToken) {
            $botToken = config('telegram.bot_token');
        }

        if (!$botToken) {
            return response()->json([
                'valid' => false,
                'message' => 'Токен бота не найден',
            ], 400);
        }

        $validation = $this->telegramMiniAppService->validateInitData($initData, $botToken);

        return response()->json($validation);
    }

    /**
     * Получить информацию о пользователе из initData
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserInfo(Request $request)
    {
        $request->validate([
            'init_data' => ['required', 'string'],
            'bot_token' => ['nullable', 'string'],
            'bot_id' => ['nullable', 'integer', 'exists:bots,id'],
        ]);

        $initData = $request->input('init_data');
        $botToken = $request->input('bot_token');
        $botId = $request->input('bot_id');

        // Получаем токен бота
        if (!$botToken && $botId) {
            $bot = Bot::find($botId);
            if ($bot) {
                $botToken = $bot->token;
            }
        }

        if (!$botToken) {
            $botToken = config('telegram.bot_token');
        }

        if (!$botToken) {
            return response()->json([
                'message' => 'Токен бота не найден',
            ], 400);
        }

        $user = $this->telegramMiniAppService->getUserFromInitData($initData, $botToken);

        if (!$user) {
            return response()->json([
                'message' => 'Не удалось получить данные пользователя',
            ], 400);
        }

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Отправить уведомление о заказе (используется администраторами)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function notifyOrder(Request $request)
    {
        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = \App\Models\Order::findOrFail($request->input('order_id'));
        
        $sent = $this->telegramMiniAppService->notifyNewOrder($order);

        return response()->json([
            'success' => $sent,
            'message' => $sent ? 'Уведомление отправлено' : 'Не удалось отправить уведомление',
        ]);
    }
}



