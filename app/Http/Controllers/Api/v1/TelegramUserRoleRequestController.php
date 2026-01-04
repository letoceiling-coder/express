<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\TelegramUserRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramUserRoleRequestController extends Controller
{
    /**
     * Список заявок на роли
     * GET /api/v1/telegram-user-role-requests?status={pending|approved|rejected}&requested_role={courier|admin}
     */
    public function index(Request $request): JsonResponse
    {
        $query = TelegramUserRoleRequest::with(['telegramUser.bot', 'processedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('requested_role')) {
            $query->where('requested_role', $request->get('requested_role'));
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->get('per_page', 15);
        $requests = $perPage > 0 ? $query->paginate($perPage) : $query->get();

        return response()->json(['data' => $requests]);
    }

    /**
     * Детали заявки
     * GET /api/v1/telegram-user-role-requests/{id}
     */
    public function show(string $id): JsonResponse
    {
        $request = TelegramUserRoleRequest::with(['telegramUser.bot', 'processedBy'])->findOrFail($id);
        return response()->json(['data' => $request]);
    }

    /**
     * Одобрить заявку
     * POST /api/v1/telegram-user-role-requests/{id}/approve
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $roleRequest = TelegramUserRoleRequest::with('telegramUser')->findOrFail($id);

            if ($roleRequest->status !== TelegramUserRoleRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Заявка уже обработана',
                ], 400);
            }

            // Обновляем роль пользователя
            $roleRequest->telegramUser->update([
                'role' => $roleRequest->requested_role,
            ]);

            // Обновляем заявку
            $roleRequest->update([
                'status' => TelegramUserRoleRequest::STATUS_APPROVED,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            DB::commit();

            Log::info('Role request approved', [
                'request_id' => $roleRequest->id,
                'telegram_user_id' => $roleRequest->telegram_user_id,
                'new_role' => $roleRequest->requested_role,
                'processed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заявка одобрена',
                'data' => $roleRequest->fresh(['telegramUser', 'processedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving role request: ' . $e->getMessage(), [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при одобрении заявки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отклонить заявку
     * POST /api/v1/telegram-user-role-requests/{id}/reject
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $roleRequest = TelegramUserRoleRequest::findOrFail($id);

            if ($roleRequest->status !== TelegramUserRoleRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Заявка уже обработана',
                ], 400);
            }

            $rejectionReason = $request->input('rejection_reason');

            $roleRequest->update([
                'status' => TelegramUserRoleRequest::STATUS_REJECTED,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            Log::info('Role request rejected', [
                'request_id' => $roleRequest->id,
                'telegram_user_id' => $roleRequest->telegram_user_id,
                'processed_by' => auth()->id(),
                'rejection_reason' => $rejectionReason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заявка отклонена',
                'data' => $roleRequest->fresh(['telegramUser', 'processedBy']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting role request: ' . $e->getMessage(), [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отклонении заявки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Статистика заявок
     * GET /api/v1/telegram-user-role-requests/statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => TelegramUserRoleRequest::count(),
            'pending' => TelegramUserRoleRequest::where('status', TelegramUserRoleRequest::STATUS_PENDING)->count(),
            'approved' => TelegramUserRoleRequest::where('status', TelegramUserRoleRequest::STATUS_APPROVED)->count(),
            'rejected' => TelegramUserRoleRequest::where('status', TelegramUserRoleRequest::STATUS_REJECTED)->count(),
            'courier_requests' => TelegramUserRoleRequest::where('requested_role', TelegramUserRoleRequest::ROLE_COURIER)->count(),
            'admin_requests' => TelegramUserRoleRequest::where('requested_role', TelegramUserRoleRequest::ROLE_ADMIN)->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}
