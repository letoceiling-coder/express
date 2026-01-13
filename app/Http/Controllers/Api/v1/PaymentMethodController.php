<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    /**
     * Получить список способов оплаты
     * Для публичного доступа возвращает только активные
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaymentMethod::query();

        // Для публичных запросов показываем только активные
        if (!$request->user()) {
            $query->where('is_enabled', true);
        }

        $methods = $query->orderBy('is_default', 'desc') // Дефолтный способ оплаты первым
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $methods,
        ]);
    }

    /**
     * Получить способ оплаты с расчетом скидки
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $method = PaymentMethod::findOrFail($id);

        // Если публичный запрос, проверяем что способ оплаты активен
        if (!$request->user() && !$method->is_enabled) {
            return response()->json([
                'message' => 'Способ оплаты недоступен',
            ], 404);
        }

        $cartAmount = $request->get('cart_amount', 0);
        $discountInfo = $method->calculateDiscount((float)$cartAmount);
        $notification = $method->getNotificationMessage((float)$cartAmount);

        return response()->json([
            'data' => [
                'id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'description' => $method->description,
                'is_enabled' => $method->is_enabled,
                'is_default' => $method->is_default,
                'available_for_delivery' => $method->available_for_delivery,
                'available_for_pickup' => $method->available_for_pickup,
                'sort_order' => $method->sort_order,
                'discount_type' => $method->discount_type,
                'discount_value' => $method->discount_value,
                'min_cart_amount' => $method->min_cart_amount,
                'show_notification' => $method->show_notification,
                'notification_text' => $method->notification_text,
                'discount' => $discountInfo,
                'notification' => $notification,
            ],
        ]);
    }

    /**
     * Создать новый способ оплаты (только для админов)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'available_for_delivery' => 'boolean',
            'available_for_pickup' => 'boolean',
            'sort_order' => 'integer|min:0',
            'discount_type' => ['required', Rule::in(['none', 'percentage', 'fixed'])],
            'discount_value' => 'nullable|numeric|min:0',
            'min_cart_amount' => 'nullable|numeric|min:0',
            'show_notification' => 'boolean',
            'notification_text' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        // Если устанавливаем как дефолтный, снимаем флаг с остальных
        if (!empty($validated['is_default'])) {
            PaymentMethod::where('is_default', true)->update(['is_default' => false]);
        }

        $method = PaymentMethod::create($validated);

        Log::info('PaymentMethod created', [
            'id' => $method->id,
            'code' => $method->code,
            'name' => $method->name,
        ]);

        return response()->json([
            'data' => $method,
            'message' => 'Способ оплаты успешно создан',
        ], 201);
    }

    /**
     * Обновить способ оплаты (только для админов)
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $method = PaymentMethod::findOrFail($id);

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('payment_methods', 'code')->ignore($method->id)],
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'available_for_delivery' => 'boolean',
            'available_for_pickup' => 'boolean',
            'sort_order' => 'integer|min:0',
            'discount_type' => ['sometimes', Rule::in(['none', 'percentage', 'fixed'])],
            'discount_value' => 'nullable|numeric|min:0',
            'min_cart_amount' => 'nullable|numeric|min:0',
            'show_notification' => 'boolean',
            'notification_text' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        // Если устанавливаем как дефолтный, снимаем флаг с остальных
        if (isset($validated['is_default']) && $validated['is_default']) {
            PaymentMethod::where('id', '!=', $method->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $method->update($validated);

        Log::info('PaymentMethod updated', [
            'id' => $method->id,
            'code' => $method->code,
        ]);

        return response()->json([
            'data' => $method,
            'message' => 'Способ оплаты успешно обновлен',
        ]);
    }

    /**
     * Удалить способ оплаты (только для админов)
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        Log::info('PaymentMethod deleted', [
            'id' => $method->id,
            'code' => $method->code,
        ]);

        return response()->json([
            'message' => 'Способ оплаты успешно удален',
        ]);
    }
}
