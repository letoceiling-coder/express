<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Получить список платежей
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Payment::query()->with('order');

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по методу оплаты
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
        }

        // Фильтрация по платежной системе
        if ($request->has('payment_provider')) {
            $query->where('payment_provider', $request->get('payment_provider'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('order_id', 'like', "%{$search}%");
                  });
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $payments = $query->paginate($perPage);
        } else {
            $payments = $query->get();
        }

        return response()->json([
            'data' => $payments,
        ]);
    }

    /**
     * Получить детали платежа
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $payment = Payment::with('order.items')->findOrFail($id);

        return response()->json([
            'data' => $payment,
        ]);
    }

    /**
     * Создать платеж
     * 
     * @param PaymentRequest $request
     * @return JsonResponse
     */
    public function store(PaymentRequest $request)
    {
        try {
            DB::beginTransaction();

            $payment = Payment::create($request->validated());

            DB::commit();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Платеж успешно создан',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить платеж
     * 
     * @param PaymentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(PaymentRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($id);
            $payment->update($request->validated());

            DB::commit();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Платеж успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус платежа
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                'in:pending,processing,succeeded,failed,refunded,partially_refunded,cancelled',
            ],
        ]);

        try {
            $payment = Payment::findOrFail($id);
            $payment->status = $request->get('status');
            $payment->save();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Статус платежа успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Вернуть платеж
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function refund(Request $request, $id)
    {
        $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        try {
            $payment = Payment::findOrFail($id);

            if (!$payment->canRefund()) {
                return response()->json([
                    'message' => 'Невозможно вернуть платеж. Статус должен быть "succeeded"',
                ], 422);
            }

            $refundAmount = $request->has('amount') 
                ? (float) $request->get('amount')
                : $payment->available_refund_amount;

            if ($refundAmount > $payment->available_refund_amount) {
                return response()->json([
                    'message' => 'Сумма возврата превышает доступную сумму',
                ], 422);
            }

            DB::beginTransaction();

            $newRefundedAmount = (float) $payment->refunded_amount + $refundAmount;
            $payment->refunded_amount = $newRefundedAmount;
            $payment->refunded_at = now();

            if ($newRefundedAmount >= $payment->amount) {
                $payment->status = Payment::STATUS_REFUNDED;
            } else {
                $payment->status = Payment::STATUS_PARTIALLY_REFUNDED;
            }

            $payment->save();

            DB::commit();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Платеж успешно возвращен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при возврате платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при возврате платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить платежи для заказа
     * 
     * @param int $orderId
     * @return JsonResponse
     */
    public function getByOrder($orderId)
    {
        $order = \App\Models\Order::findOrFail($orderId);
        $payments = Payment::where('order_id', $orderId)->with('order')->get();

        return response()->json([
            'data' => $payments,
            'order' => [
                'id' => $order->id,
                'order_id' => $order->order_id,
            ],
        ]);
    }

    /**
     * Удалить платеж
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment->delete();

            return response()->json([
                'message' => 'Платеж успешно удален',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
