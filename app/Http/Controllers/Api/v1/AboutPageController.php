<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AboutPageController extends Controller
{
    /**
     * Получить страницу "О нас" (публичный эндпоинт)
     * 
     * GET /api/v1/about
     * 
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $page = AboutPage::getPage();
        
        return response()->json([
            'data' => [
                'id' => $page->id,
                'title' => $page->title,
                'phone' => $page->phone,
                'address' => $page->address,
                'description' => $page->description,
                'bullets' => $page->bullets ?? [],
                'yandex_maps_url' => $page->yandex_maps_url,
                'support_telegram_url' => $page->support_telegram_url,
                'cover_image_url' => $page->cover_image_url,
            ],
        ]);
    }

    /**
     * Получить страницу "О нас" (админ)
     * 
     * GET /api/v1/admin/about
     * 
     * @return JsonResponse
     */
    public function getAdmin(): JsonResponse
    {
        $page = AboutPage::getPage();
        
        return response()->json([
            'data' => [
                'id' => $page->id,
                'title' => $page->title,
                'phone' => $page->phone,
                'address' => $page->address,
                'description' => $page->description,
                'bullets' => $page->bullets ?? [],
                'yandex_maps_url' => $page->yandex_maps_url,
                'support_telegram_url' => $page->support_telegram_url,
                'cover_image_url' => $page->cover_image_url,
            ],
        ]);
    }

    /**
     * Обновить страницу "О нас" (админ)
     * 
     * PUT /api/v1/admin/about
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'bullets' => 'nullable|array',
            'bullets.*' => 'string|max:1000',
            'yandex_maps_url' => 'nullable|url|max:500',
            'support_telegram_url' => 'nullable|url|max:500',
            'cover_image_url' => 'nullable|string|max:500',
        ], [
            'title.required' => 'Название компании обязательно',
            'title.string' => 'Название компании должно быть строкой',
            'title.max' => 'Название компании не должно превышать 255 символов',
            'phone.string' => 'Телефон должен быть строкой',
            'address.string' => 'Адрес должен быть строкой',
            'yandex_maps_url.url' => 'URL Яндекс.Карт должен быть валидным URL',
            'yandex_maps_url.max' => 'URL Яндекс.Карт не должен превышать 500 символов',
            'support_telegram_url.url' => 'URL Telegram поддержки должен быть валидным URL',
            'support_telegram_url.max' => 'URL Telegram поддержки не должен превышать 500 символов',
            'cover_image_url.string' => 'URL обложки должен быть строкой',
            'cover_image_url.max' => 'URL обложки не должен превышать 500 символов',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $page = AboutPage::getPage();
            $validated = $validator->validated();
            
            // Обрабатываем bullets: если это массив, фильтруем пустые строки
            if (isset($validated['bullets']) && is_array($validated['bullets'])) {
                $validated['bullets'] = array_filter($validated['bullets'], function($bullet) {
                    return !empty(trim($bullet));
                });
                // Перенумеровываем массив после фильтрации
                $validated['bullets'] = array_values($validated['bullets']);
            }
            
            // Обновляем страницу
            $page->fill($validated);
            $page->save();
            
            DB::commit();
            
            $page->refresh();
            
            return response()->json([
                'data' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'phone' => $page->phone,
                    'address' => $page->address,
                    'description' => $page->description,
                    'bullets' => $page->bullets ?? [],
                    'yandex_maps_url' => $page->yandex_maps_url,
                    'support_telegram_url' => $page->support_telegram_url,
                    'cover_image_url' => $page->cover_image_url,
                ],
                'message' => 'Страница "О нас" успешно обновлена',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении страницы "О нас": ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Ошибка при обновлении страницы',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
