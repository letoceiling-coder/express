<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BroadcastRequest;
use App\Services\Telegram\BroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BroadcastController extends Controller
{
    protected BroadcastService $broadcastService;

    public function __construct(BroadcastService $broadcastService)
    {
        $this->broadcastService = $broadcastService;
    }

    /**
     * Отправка рассылки
     * 
     * POST /api/v1/broadcasts/send
     */
    public function send(BroadcastRequest $request): JsonResponse
    {

        try {
            $botId = $request->input('bot_id');
            $telegramUserIds = $request->input('telegram_user_ids');
            $type = $request->input('type');
            $content = $request->input('content');
            $options = $request->input('options', []);

            // Если telegram_user_ids пустой или null - рассылка всем
            if (empty($telegramUserIds)) {
                $result = $this->broadcastService->sendToAll($botId, $type, $content, $options);
            } else {
                $result = $this->broadcastService->sendToSelected($botId, $telegramUserIds, $type, $content, $options);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Broadcast send error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке рассылки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Предпросмотр рассылки (без отправки)
     * 
     * POST /api/v1/broadcasts/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bot_id' => 'required|exists:bots,id',
            'telegram_user_ids' => 'nullable|array',
            'type' => 'required|in:message,photo,video,document,media_group',
            'content' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $botId = $request->input('bot_id');
        $telegramUserIds = $request->input('telegram_user_ids');

        // Подсчитываем количество получателей
        if (empty($telegramUserIds)) {
            $count = \App\Models\TelegramUser::where('bot_id', $botId)->active()->count();
        } else {
            $count = \App\Models\TelegramUser::where('bot_id', $botId)
                ->whereIn('telegram_id', $telegramUserIds)
                ->active()
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recipients_count' => $count,
                'preview' => [
                    'type' => $request->input('type'),
                    'content' => $request->input('content'),
                    'options' => $request->input('options', []),
                ],
            ],
        ]);
    }
}
