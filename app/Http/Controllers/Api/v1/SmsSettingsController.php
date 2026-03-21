<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\SmsSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SmsSettingsController extends Controller
{
    /**
     * Получить настройки SMS (IQSMS)
     */
    public function getSettings(): JsonResponse
    {
        $settings = SmsSetting::forDriver('iqsms');

        if (!$settings) {
            return response()->json([
                'data' => null,
                'message' => 'Настройки SMS не найдены. Вы можете создать их ниже.',
            ]);
        }

        $data = $settings->toArray();
        unset($data['password']);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Обновить настройки SMS
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'sender' => ['nullable', 'string', 'max:20'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $settings = SmsSetting::firstOrCreate(
                ['driver' => 'iqsms'],
                ['driver' => 'iqsms']
            );

            $validated = $validator->validated();

            if (isset($validated['login'])) {
                $settings->login = $validated['login'] ?: null;
            }
            if (isset($validated['sender'])) {
                $settings->sender = $validated['sender'] ?: null;
            }
            if (array_key_exists('is_enabled', $validated)) {
                $settings->is_enabled = (bool) $validated['is_enabled'];
            }

            if (!empty(trim($validated['password'] ?? ''))) {
                $settings->password = $validated['password'];
            }

            $settings->save();

            DB::commit();

            $data = $settings->toArray();
            unset($data['password']);

            Log::info('SmsSettingsController: настройки SMS обновлены', [
                'user_id' => auth()->id(),
                'is_enabled' => $settings->is_enabled,
            ]);

            return response()->json([
                'data' => $data,
                'message' => 'Настройки SMS успешно обновлены',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SmsSettingsController: ошибка при сохранении', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка при сохранении настроек',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
