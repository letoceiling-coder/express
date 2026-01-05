<template>
    <div class="role-requests-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Заявки на роли</h1>
                <p class="text-muted-foreground mt-1">Обработка заявок на роли курьера и администратора</p>
            </div>
        </div>

        <!-- Статистика -->
        <div v-if="statistics" class="grid grid-cols-4 gap-4">
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Всего заявок</div>
                <div class="text-2xl font-bold text-foreground mt-1">{{ statistics.total || 0 }}</div>
            </div>
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Ожидают</div>
                <div class="text-2xl font-bold text-yellow-500 mt-1">{{ statistics.pending || 0 }}</div>
            </div>
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Одобрено</div>
                <div class="text-2xl font-bold text-green-500 mt-1">{{ statistics.approved || 0 }}</div>
            </div>
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Отклонено</div>
                <div class="text-2xl font-bold text-red-500 mt-1">{{ statistics.rejected || 0 }}</div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="bg-card rounded-lg border border-border p-4">
            <div class="flex gap-4 items-end flex-wrap">
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус</label>
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                        @change="loadRequests"
                    >
                        <option value="">Все</option>
                        <option value="pending">Ожидают</option>
                        <option value="approved">Одобрены</option>
                        <option value="rejected">Отклонены</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Роль</label>
                    <select
                        v-model="roleFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                        @change="loadRequests"
                    >
                        <option value="">Все</option>
                        <option value="courier">Курьер</option>
                        <option value="admin">Администратор</option>
                        <option value="kitchen">Кухня</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка заявок...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица заявок -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Пользователь</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Запрашиваемая роль</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Дата подачи</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Обработано</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="request in requests" :key="request.id">
                        <td class="px-6 py-4">
                            <div>
                                <div class="font-medium text-foreground">
                                    {{ request.telegram_user?.first_name }} {{ request.telegram_user?.last_name || '' }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    @{{ request.telegram_user?.username || '—' }} (ID: {{ request.telegram_user?.telegram_id }})
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'px-2 py-1 text-xs rounded-md font-medium',
                                    request.requested_role === 'courier'
                                        ? 'bg-blue-500/10 text-blue-500'
                                        : request.requested_role === 'admin'
                                        ? 'bg-purple-500/10 text-purple-500'
                                        : 'bg-orange-500/10 text-orange-500'
                                ]"
                            >
                                {{ getRoleLabel(request.requested_role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'px-2 py-1 text-xs rounded-md',
                                    request.status === 'pending'
                                        ? 'bg-yellow-500/10 text-yellow-500'
                                        : request.status === 'approved'
                                        ? 'bg-green-500/10 text-green-500'
                                        : 'bg-red-500/10 text-red-500'
                                ]"
                            >
                                {{ getStatusLabel(request.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatDate(request.created_at) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="request.processed_at">
                                <div class="text-sm text-foreground">{{ formatDate(request.processed_at) }}</div>
                                <div class="text-xs text-muted-foreground">{{ request.processed_by?.name || '—' }}</div>
                            </div>
                            <span v-else class="text-sm text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="request.status === 'pending'" class="flex items-center justify-end gap-2">
                                <button
                                    @click="approveRequest(request)"
                                    :disabled="processing === request.id"
                                    class="px-3 py-1 text-xs bg-green-500 hover:bg-green-600 text-white rounded transition-colors disabled:opacity-50"
                                >
                                    {{ processing === request.id ? '...' : 'Одобрить' }}
                                </button>
                                <button
                                    @click="showRejectModal(request)"
                                    :disabled="processing === request.id"
                                    class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors disabled:opacity-50"
                                >
                                    Отклонить
                                </button>
                            </div>
                            <div v-else class="text-sm text-muted-foreground text-right">
                                Обработано
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Пагинация -->
        <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
            <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="!pagination.prev_page_url"
                class="px-4 py-2 border border-border bg-background rounded-lg disabled:opacity-50"
            >
                Назад
            </button>
            <span class="px-4 py-2 text-sm text-foreground">
                Страница {{ pagination.current_page }} из {{ pagination.last_page }}
            </span>
            <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="!pagination.next_page_url"
                class="px-4 py-2 border border-border bg-background rounded-lg disabled:opacity-50"
            >
                Вперед
            </button>
        </div>

        <!-- Модальное окно отклонения -->
        <div v-if="showRejectDialog" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-card rounded-lg border border-border w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-foreground mb-4">Отклонить заявку</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-foreground mb-1 block">Причина отклонения (опционально)</label>
                        <textarea
                            v-model="rejectionReason"
                            rows="3"
                            placeholder="Укажите причину отклонения..."
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background resize-none"
                        ></textarea>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button
                            @click="showRejectDialog = false; selectedRequest = null; rejectionReason = ''"
                            class="px-4 py-2 border border-border bg-background rounded-lg"
                        >
                            Отмена
                        </button>
                        <button
                            @click="rejectRequest"
                            :disabled="rejecting"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg disabled:opacity-50"
                        >
                            {{ rejecting ? 'Отклонение...' : 'Отклонить' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { ref, onMounted } from 'vue';
import Swal from 'sweetalert2';

export default {
    name: 'RoleRequests',
    setup() {
        const requests = ref([]);
        const statistics = ref(null);
        const loading = ref(false);
        const error = ref(null);
        const statusFilter = ref('');
        const roleFilter = ref('');
        const processing = ref(null);
        const rejecting = ref(false);
        const showRejectDialog = ref(false);
        const selectedRequest = ref(null);
        const rejectionReason = ref('');
        const pagination = ref(null);

        const loadStatistics = async () => {
            try {
                const response = await axios.get('/api/v1/telegram-user-role-requests/statistics');
                statistics.value = response.data.data;
            } catch (err) {
                console.error('Error loading statistics:', err);
            }
        };

        const loadRequests = async (page = 1) => {
            loading.value = true;
            error.value = null;
            try {
                const params = {
                    page,
                    per_page: 15,
                };
                if (statusFilter.value) {
                    params.status = statusFilter.value;
                }
                if (roleFilter.value) {
                    params.requested_role = roleFilter.value;
                }

                const response = await axios.get('/api/v1/telegram-user-role-requests', { params });
                requests.value = response.data.data.data || [];
                pagination.value = {
                    current_page: response.data.data.current_page,
                    last_page: response.data.data.last_page,
                    prev_page_url: response.data.data.prev_page_url,
                    next_page_url: response.data.data.next_page_url,
                };
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка загрузки заявок';
                console.error('Error loading requests:', err);
            } finally {
                loading.value = false;
            }
        };

        const approveRequest = async (request) => {
            const result = await Swal.fire({
                title: 'Одобрить заявку?',
                text: `Вы уверены, что хотите одобрить заявку пользователя на роль ${request.requested_role === 'courier' ? 'курьера' : 'администратора'}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Да, одобрить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#22c55e',
            });

            if (!result.isConfirmed) return;

            processing.value = request.id;
            try {
                await axios.post(`/api/v1/telegram-user-role-requests/${request.id}/approve`);
                Swal.fire('Успешно!', 'Заявка одобрена.', 'success');
                await loadRequests(pagination.value?.current_page || 1);
                await loadStatistics();
            } catch (err) {
                Swal.fire('Ошибка!', err.response?.data?.message || 'Ошибка при одобрении заявки.', 'error');
                console.error('Error approving request:', err);
            } finally {
                processing.value = null;
            }
        };

        const showRejectModal = (request) => {
            selectedRequest.value = request;
            rejectionReason.value = '';
            showRejectDialog.value = true;
        };

        const rejectRequest = async () => {
            if (!selectedRequest.value) return;

            rejecting.value = true;
            try {
                await axios.post(`/api/v1/telegram-user-role-requests/${selectedRequest.value.id}/reject`, {
                    rejection_reason: rejectionReason.value || null,
                });
                Swal.fire('Успешно!', 'Заявка отклонена.', 'success');
                showRejectDialog.value = false;
                selectedRequest.value = null;
                rejectionReason.value = '';
                await loadRequests(pagination.value?.current_page || 1);
                await loadStatistics();
            } catch (err) {
                Swal.fire('Ошибка!', err.response?.data?.message || 'Ошибка при отклонении заявки.', 'error');
                console.error('Error rejecting request:', err);
            } finally {
                rejecting.value = false;
            }
        };

        const changePage = (page) => {
            loadRequests(page);
        };

        const getRoleLabel = (role) => {
            const labels = {
                courier: 'Курьер',
                admin: 'Администратор',
                kitchen: 'Кухня',
            };
            return labels[role] || role;
        };

        const getStatusLabel = (status) => {
            const labels = {
                pending: 'Ожидает',
                approved: 'Одобрена',
                rejected: 'Отклонена',
            };
            return labels[status] || status;
        };

        const formatDate = (date) => {
            if (!date) return '—';
            return new Date(date).toLocaleString('ru-RU');
        };

        onMounted(() => {
            loadStatistics();
            loadRequests();
        });

        return {
            requests,
            statistics,
            loading,
            error,
            statusFilter,
            roleFilter,
            processing,
            rejecting,
            showRejectDialog,
            selectedRequest,
            rejectionReason,
            pagination,
            loadRequests,
            approveRequest,
            showRejectModal,
            rejectRequest,
            changePage,
            getRoleLabel,
            getStatusLabel,
            formatDate,
        };
    },
};
</script>

