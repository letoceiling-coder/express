<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintRequest;
use App\Models\Complaint;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComplaintController extends Controller
{
    /**
     * Получить список претензий
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Complaint::query()->with(['order', 'assignedTo', 'resolvedBy']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по типу
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Фильтрация по приоритету
        if ($request->has('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Фильтрация по назначенному сотруднику
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('complaint_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $complaints = $query->paginate($perPage);
        } else {
            $complaints = $query->get();
        }

        return response()->json([
            'data' => $complaints,
        ]);
    }

    /**
     * Получить детали претензии
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $complaint = Complaint::with(['order.items', 'assignedTo', 'resolvedBy'])->findOrFail($id);

        return response()->json([
            'data' => $complaint,
        ]);
    }

    /**
     * Создать претензию
     * 
     * @param ComplaintRequest $request
     * @return JsonResponse
     */
    public function store(ComplaintRequest $request)
    {
        try {
            DB::beginTransaction();

            $complaint = Complaint::create($request->validated());

            DB::commit();

            $complaint->load(['order', 'assignedTo']);

            return response()->json([
                'data' => $complaint,
                'message' => 'Претензия успешно создана',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании претензии: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании претензии',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить претензию
     * 
     * @param ComplaintRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ComplaintRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $complaint = Complaint::findOrFail($id);
            $complaint->update($request->validated());

            DB::commit();

            $complaint->load(['order', 'assignedTo', 'resolvedBy']);

            return response()->json([
                'data' => $complaint,
                'message' => 'Претензия успешно обновлена',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении претензии: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении претензии',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус претензии
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
                'in:new,in_progress,resolved,rejected,closed',
            ],
            'resolution' => ['nullable', 'string', 'max:65535'],
        ]);

        try {
            $complaint = Complaint::findOrFail($id);
            $complaint->status = $request->get('status');

            if ($request->get('status') === Complaint::STATUS_RESOLVED) {
                $complaint->resolved_by = Auth::id();
                $complaint->resolved_at = now();
                if ($request->has('resolution')) {
                    $complaint->resolution = $request->get('resolution');
                }
            }

            if ($request->get('status') === Complaint::STATUS_CLOSED) {
                $complaint->closed_at = now();
            }

            $complaint->save();

            $complaint->load(['order', 'assignedTo', 'resolvedBy']);

            return response()->json([
                'data' => $complaint,
                'message' => 'Статус претензии успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса претензии: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса претензии',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить претензии для заказа
     * 
     * @param int $orderId
     * @return JsonResponse
     */
    public function getByOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $complaints = Complaint::where('order_id', $orderId)
            ->with(['assignedTo', 'resolvedBy'])
            ->get();

        return response()->json([
            'data' => $complaints,
            'order' => [
                'id' => $order->id,
                'order_id' => $order->order_id,
            ],
        ]);
    }

    /**
     * Удалить претензию
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $complaint = Complaint::findOrFail($id);
            $complaint->delete();

            return response()->json([
                'message' => 'Претензия успешно удалена',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении претензии: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении претензии',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
