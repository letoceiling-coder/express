<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    /**
     * Публичный список баннеров (только активные, для HeroSlider)
     * GET /api/v1/banners
     */
    public function index(): JsonResponse
    {
        try {
            $banners = Banner::active()->ordered()->get();
        } catch (\Throwable $e) {
            Log::warning('BannerController::index: ' . $e->getMessage());
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $banners->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'subtitle' => $b->subtitle,
                'image' => $b->image,
                'cta_text' => $b->cta_text,
                'cta_href' => $b->cta_href,
            ]),
        ]);
    }

    /**
     * Админ: список баннеров
     */
    public function adminIndex(): JsonResponse
    {
        $banners = Banner::ordered()->get();

        return response()->json(['data' => $banners]);
    }

    /**
     * Админ: один баннер по ID
     */
    public function show(Banner $banner): JsonResponse
    {
        return response()->json(['data' => $banner]);
    }

    /**
     * Админ: создать баннер
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'cta_href' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['is_active'] = $data['is_active'] ?? true;
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $banner = Banner::create($data);

            return response()->json([
                'data' => $banner,
                'message' => 'Баннер создан',
            ], 201);
        } catch (\Exception $e) {
            Log::error('BannerController::store: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка при создании'], 500);
        }
    }

    /**
     * Админ: обновить баннер
     */
    public function update(Request $request, Banner $banner): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'cta_href' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $banner->update($validator->validated());
            return response()->json([
                'data' => $banner->fresh(),
                'message' => 'Баннер обновлён',
            ]);
        } catch (\Exception $e) {
            Log::error('BannerController::update: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка при обновлении'], 500);
        }
    }

    /**
     * Админ: удалить баннер
     */
    public function destroy(Banner $banner): JsonResponse
    {
        try {
            $banner->delete();
            return response()->json(['message' => 'Баннер удалён']);
        } catch (\Exception $e) {
            Log::error('BannerController::destroy: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка при удалении'], 500);
        }
    }
}
