<template>
    <div class="returns-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Возвраты</h1>
                <p class="text-muted-foreground mt-1">Управление возвратами</p>
            </div>
            <router-link
                to="/returns/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать возврат</span>
            </router-link>
        </div>

        <!-- Поиск и фильтры -->
        <div class="bg-card rounded-lg border border-border p-4 mb-6">
            <div class="flex gap-4 items-end flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-sm font-medium text-foreground mb-1 block">Поиск</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Поиск по номеру возврата, заказу..."
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус</label>
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="pending">Ожидает</option>
                        <option value="approved">Одобрен</option>
                        <option value="rejected">Отклонен</option>
                        <option value="processing">Обрабатывается</option>
                        <option value="completed">Завершен</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Причина</label>
                    <select
                        v-model="reasonFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="defective">Бракованный товар</option>
                        <option value="wrong_item">Не тот товар</option>
                        <option value="not_as_described">Не соответствует описанию</option>
                        <option value="changed_mind">Передумал</option>
                        <option value="damaged">Поврежден при доставке</option>
                        <option value="other">Другое</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Сортировка</label>
                    <select
                        v-model="sortBy"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="created_at">По дате</option>
                        <option value="return_amount">По сумме</option>
                        <option value="status">По статусу</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка возвратов...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица возвратов -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Заказ</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Товар</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Причина</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Сумма</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Дата</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="returnItem in filteredReturns" :key="returnItem.id">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-foreground">#{{ returnItem.id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <router-link
                                :to="`/orders/${returnItem.order_id}`"
                                class="text-sm font-medium text-accent hover:underline"
                            >
                                #{{ returnItem.order?.order_id || returnItem.order_id }}
                            </router-link>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ returnItem.product?.name || 'Товар удален' }}</span>
                            <span v-if="returnItem.quantity > 1" class="text-xs text-muted-foreground ml-1">
                                ({{ returnItem.quantity }} шт.)
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-muted-foreground">{{ getReasonLabel(returnItem.reason) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-foreground">
                                {{ Number(returnItem.return_amount).toLocaleString('ru-RU') }} ₽
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <select
                                :value="returnItem.status"
                                @change="handleStatusChange(returnItem.id, $event.target.value)"
                                class="text-xs px-2 py-1 rounded border border-input bg-background"
                                :class="getStatusClass(returnItem.status)"
                            >
                                <option value="pending">Ожидает</option>
                                <option value="approved">Одобрен</option>
                                <option value="rejected">Отклонен</option>
                                <option value="processing">Обрабатывается</option>
                                <option value="completed">Завершен</option>
                                <option value="cancelled">Отменен</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatDate(returnItem.created_at) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    v-if="returnItem.status === 'pending'"
                                    @click="handleApprove(returnItem)"
                                    class="h-8 px-3 text-sm bg-green-100 text-green-800 rounded-lg hover:bg-green-200"
                                >
                                    Одобрить
                                </button>
                                <button
                                    v-if="returnItem.status === 'pending'"
                                    @click="handleReject(returnItem)"
                                    class="h-8 px-3 text-sm bg-red-100 text-red-800 rounded-lg hover:bg-red-200"
                                >
                                    Отклонить
                                </button>
                                <router-link
                                    :to="`/returns/${returnItem.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Редактировать
                                </router-link>
                                <button
                                    @click="handleDelete(returnItem)"
                                    class="h-8 px-3 text-sm bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                                >
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Пусто -->
            <div v-if="filteredReturns.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Возвраты не найдены</p>
            </div>
        </div>

        <!-- Модальное окно отклонения -->
        <div v-if="showRejectModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-card rounded-lg border border-border p-6 max-w-md w-full">
                <h2 class="text-xl font-bold text-foreground mb-4">Отклонить возврат</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Причина отклонения <span class="text-destructive">*</span>
                        </label>
                        <textarea
                            v-model="rejectReason"
                            rows="4"
                            required
                            placeholder="Укажите причину отклонения..."
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        ></textarea>
                    </div>
                    <div class="flex gap-4">
                        <button
                            @click="confirmReject"
                            :disabled="rejecting || !rejectReason"
                            class="flex-1 h-10 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ rejecting ? 'Отклонение...' : 'Отклонить' }}
                        </button>
                        <button
                            @click="showRejectModal = false; selectedReturnForReject = null; rejectReason = ''"
                            class="flex-1 h-10 px-4 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                        >
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { returnsAPI } from '../../utils/api.js';

export default {
    name: 'Returns',
    data() {
        return {
            returns: [],
            loading: false,
            error: null,
            searchQuery: '',
            statusFilter: '',
            reasonFilter: '',
            sortBy: 'created_at',
            showRejectModal: false,
            selectedReturnForReject: null,
            rejectReason: '',
            rejecting: false,
        };
    },
    computed: {
        filteredReturns() {
            let filtered = [...this.returns];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(returnItem =>
                    returnItem.id.toString().includes(query) ||
                    (returnItem.order?.order_id && returnItem.order.order_id.toLowerCase().includes(query))
                );
            }

            // Фильтр по статусу
            if (this.statusFilter) {
                filtered = filtered.filter(returnItem => returnItem.status === this.statusFilter);
            }

            // Фильтр по причине
            if (this.reasonFilter) {
                filtered = filtered.filter(returnItem => returnItem.reason === this.reasonFilter);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (this.sortBy === 'return_amount') {
                    return Number(b.return_amount) - Number(a.return_amount);
                } else if (this.sortBy === 'status') {
                    return a.status.localeCompare(b.status);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadReturns();
    },
    methods: {
        async loadReturns() {
            this.loading = true;
            this.error = null;
            try {
                const response = await returnsAPI.getAll();
                this.returns = response.data?.data || response.data || [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки возвратов';
            } finally {
                this.loading = false;
            }
        },
        async handleStatusChange(returnId, newStatus) {
            try {
                await returnsAPI.updateStatus(returnId, newStatus);
                await this.loadReturns();
            } catch (error) {
                alert(error.message || 'Ошибка изменения статуса');
                await this.loadReturns();
            }
        },
        async handleApprove(returnItem) {
            if (!confirm(`Вы уверены, что хотите одобрить возврат #${returnItem.id}?`)) {
                return;
            }

            try {
                await returnsAPI.approve(returnItem.id);
                await this.loadReturns();
            } catch (error) {
                alert(error.message || 'Ошибка одобрения возврата');
            }
        },
        handleReject(returnItem) {
            this.selectedReturnForReject = returnItem;
            this.rejectReason = '';
            this.showRejectModal = true;
        },
        async confirmReject() {
            if (!this.selectedReturnForReject || !this.rejectReason) return;

            this.rejecting = true;
            try {
                await returnsAPI.reject(this.selectedReturnForReject.id, this.rejectReason);
                this.showRejectModal = false;
                this.selectedReturnForReject = null;
                this.rejectReason = '';
                await this.loadReturns();
            } catch (error) {
                alert(error.message || 'Ошибка отклонения возврата');
            } finally {
                this.rejecting = false;
            }
        },
        async handleDelete(returnItem) {
            if (!confirm(`Вы уверены, что хотите удалить возврат #${returnItem.id}?`)) {
                return;
            }

            try {
                await returnsAPI.delete(returnItem.id);
                await this.loadReturns();
            } catch (error) {
                alert(error.message || 'Ошибка удаления возврата');
            }
        },
        getReasonLabel(reason) {
            const labels = {
                defective: 'Бракованный товар',
                wrong_item: 'Не тот товар',
                not_as_described: 'Не соответствует описанию',
                changed_mind: 'Передумал',
                damaged: 'Поврежден при доставке',
                other: 'Другое',
            };
            return labels[reason] || reason;
        },
        getStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                approved: 'bg-green-100 text-green-800',
                rejected: 'bg-red-100 text-red-800',
                processing: 'bg-blue-100 text-blue-800',
                completed: 'bg-green-100 text-green-800',
                cancelled: 'bg-gray-100 text-gray-800',
            };
            return classes[status] || '';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        },
    },
};
</script>



