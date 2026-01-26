<template>
    <div class="payments-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Платежи</h1>
                <p class="text-muted-foreground mt-1">Управление платежами</p>
            </div>
            <router-link
                to="/payments/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать платеж</span>
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
                        placeholder="Поиск по transaction_id, номеру заказа..."
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
                        <option value="processing">Обрабатывается</option>
                        <option value="succeeded">Успешен</option>
                        <option value="failed">Ошибка</option>
                        <option value="refunded">Возвращен</option>
                        <option value="partially_refunded">Частично возвращен</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Метод</label>
                    <select
                        v-model="methodFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="card">Карта</option>
                        <option value="cash">Наличные</option>
                        <option value="online">Онлайн</option>
                        <option value="bank_transfer">Банковский перевод</option>
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
                        <option value="amount">По сумме</option>
                        <option value="status">По статусу</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка платежей...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица платежей -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Заказ</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Сумма</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Метод</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Провайдер</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Transaction ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Дата</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="payment in filteredPayments" :key="payment.id">
                        <td class="px-6 py-4">
                            <router-link
                                :to="`/orders/${payment.order_id}`"
                                class="text-sm font-medium text-accent hover:underline"
                            >
                                #{{ payment.order?.order_id || payment.order_id }}
                            </router-link>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-foreground">
                                {{ Number(payment.amount).toLocaleString('ru-RU') }} {{ payment.currency || '₽' }}
                            </div>
                            <div v-if="payment.refunded_amount > 0" class="text-xs text-muted-foreground">
                                Возвращено: {{ Number(payment.refunded_amount).toLocaleString('ru-RU') }} {{ payment.currency || '₽' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ getPaymentMethodLabel(payment.payment_method) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-muted-foreground">{{ payment.payment_provider || '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="isYooKassaPayment(payment)" class="flex items-center gap-2">
                                <span
                                    class="text-xs px-2 py-1 rounded"
                                    :class="getStatusClass(payment.status)"
                                >
                                    {{ getStatusLabel(payment.status) }}
                                </span>
                                <button
                                    @click="syncPaymentStatus(payment)"
                                    :disabled="syncingStatus === payment.id"
                                    class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 disabled:opacity-50"
                                    title="Синхронизировать статус с ЮKassa"
                                >
                                    {{ syncingStatus === payment.id ? '...' : '🔄' }}
                                </button>
                            </div>
                            <select
                                v-else
                                :value="payment.status"
                                @change="handleStatusChange(payment.id, $event.target.value)"
                                class="text-xs px-2 py-1 rounded border border-input bg-background"
                                :class="getStatusClass(payment.status)"
                            >
                                <option value="pending">Ожидает</option>
                                <option value="processing">Обрабатывается</option>
                                <option value="succeeded">Успешен</option>
                                <option value="failed">Ошибка</option>
                                <option value="refunded">Возвращен</option>
                                <option value="partially_refunded">Частично возвращен</option>
                                <option value="cancelled">Отменен</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-muted-foreground font-mono">{{ payment.transaction_id || '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatDate(payment.created_at) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="`/payments/${payment.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Редактировать
                                </router-link>
                                <button
                                    v-if="payment.status === 'succeeded' && payment.refunded_amount < payment.amount"
                                    @click="handleRefund(payment)"
                                    class="h-8 px-3 text-sm bg-orange-100 text-orange-800 rounded-lg hover:bg-orange-200"
                                >
                                    Возврат
                                </button>
                                <button
                                    @click="handleDelete(payment)"
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
            <div v-if="filteredPayments.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Платежи не найдены</p>
            </div>
        </div>

        <!-- Модальное окно возврата -->
        <div v-if="showRefundModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-card rounded-lg border border-border p-6 max-w-md w-full">
                <h2 class="text-xl font-bold text-foreground mb-4">Возврат платежа</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Сумма возврата
                            <span class="text-muted-foreground text-xs">(оставьте пустым для полного возврата)</span>
                        </label>
                        <input
                            v-model.number="refundAmount"
                            type="number"
                            step="0.01"
                            :min="0.01"
                            :max="selectedPaymentForRefund ? (selectedPaymentForRefund.amount - selectedPaymentForRefund.refunded_amount) : 0"
                            placeholder="Полный возврат"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                        <p v-if="selectedPaymentForRefund" class="mt-1 text-xs text-muted-foreground">
                            Доступно для возврата: {{ (selectedPaymentForRefund.amount - selectedPaymentForRefund.refunded_amount).toLocaleString('ru-RU') }} {{ selectedPaymentForRefund.currency || '₽' }}
                        </p>
                    </div>
                    <div class="flex gap-4">
                        <button
                            @click="confirmRefund"
                            :disabled="refunding"
                            class="flex-1 h-10 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50"
                        >
                            {{ refunding ? 'Возврат...' : 'Вернуть' }}
                        </button>
                        <button
                            @click="showRefundModal = false; selectedPaymentForRefund = null; refundAmount = null"
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
import { paymentsAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

export default {
    name: 'Payments',
    data() {
        return {
            payments: [],
            loading: false,
            error: null,
            searchQuery: '',
            statusFilter: '',
            methodFilter: '',
            sortBy: 'created_at',
            showRefundModal: false,
            selectedPaymentForRefund: null,
            refundAmount: null,
            refunding: false,
            syncingStatus: null,
        };
    },
    computed: {
        filteredPayments() {
            let filtered = [...this.payments];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(payment =>
                    (payment.transaction_id && payment.transaction_id.toLowerCase().includes(query)) ||
                    (payment.order?.order_id && payment.order.order_id.toLowerCase().includes(query))
                );
            }

            // Фильтр по статусу
            if (this.statusFilter) {
                filtered = filtered.filter(payment => payment.status === this.statusFilter);
            }

            // Фильтр по методу
            if (this.methodFilter) {
                filtered = filtered.filter(payment => payment.payment_method === this.methodFilter);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (this.sortBy === 'amount') {
                    return Number(b.amount) - Number(a.amount);
                } else if (this.sortBy === 'status') {
                    return a.status.localeCompare(b.status);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadPayments().then(() => {
            // Автоматически синхронизируем статусы платежей через ЮKassa при загрузке
            this.syncYooKassaPayments();
        });
    },
    methods: {
        async loadPayments() {
            this.loading = true;
            this.error = null;
            try {
                const response = await paymentsAPI.getAll();
                this.payments = response.data?.data || response.data || [];
                return this.payments;
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки платежей';
                return [];
            } finally {
                this.loading = false;
            }
        },
        async handleStatusChange(paymentId, newStatus) {
            try {
                await paymentsAPI.updateStatus(paymentId, newStatus);
                await this.loadPayments();
            } catch (error) {
                await swal.error(error.message || 'Ошибка изменения статуса');
                await this.loadPayments();
            }
        },
        isYooKassaPayment(payment) {
            return payment.payment_provider === 'yookassa' && payment.transaction_id;
        },
        async syncPaymentStatus(payment) {
            if (!this.isYooKassaPayment(payment)) {
                return;
            }
            
            this.syncingStatus = payment.id;
            try {
                await paymentsAPI.syncStatus(payment.id);
                await this.loadPayments();
                await swal.success('Статус платежа синхронизирован с ЮKassa');
            } catch (error) {
                await swal.error(error.message || 'Ошибка синхронизации статуса');
            } finally {
                this.syncingStatus = null;
            }
        },
        getStatusLabel(status) {
            const labels = {
                pending: 'Ожидает',
                processing: 'Обрабатывается',
                succeeded: 'Успешен',
                failed: 'Ошибка',
                refunded: 'Возвращен',
                partially_refunded: 'Частично возвращен',
                cancelled: 'Отменен',
            };
            return labels[status] || status;
        },
        handleRefund(payment) {
            this.selectedPaymentForRefund = payment;
            this.refundAmount = null;
            this.showRefundModal = true;
        },
        async confirmRefund() {
            if (!this.selectedPaymentForRefund) return;

            this.refunding = true;
            try {
                await paymentsAPI.refund(
                    this.selectedPaymentForRefund.id,
                    this.refundAmount || null
                );
                this.showRefundModal = false;
                this.selectedPaymentForRefund = null;
                this.refundAmount = null;
                await this.loadPayments();
                await swal.success('Возврат платежа выполнен');
            } catch (error) {
                await swal.error(error.message || 'Ошибка возврата платежа');
            } finally {
                this.refunding = false;
            }
        },
        async handleDelete(payment) {
            const result = await swal.confirm(
                `Вы уверены, что хотите удалить платеж для заказа #${payment.order?.order_id || payment.order_id}?`,
                'Удаление платежа',
                'Удалить',
                'Отмена'
            );

            if (!result.isConfirmed) {
                return;
            }

            try {
                await paymentsAPI.delete(payment.id);
                await this.loadPayments();
                await swal.success('Платеж успешно удален');
            } catch (error) {
                await swal.error(error.message || 'Ошибка удаления платежа');
            }
        },
        getPaymentMethodLabel(method) {
            const labels = {
                card: 'Карта',
                cash: 'Наличные',
                online: 'Онлайн',
                bank_transfer: 'Банковский перевод',
                other: 'Другое',
            };
            return labels[method] || method;
        },
        getStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                processing: 'bg-blue-100 text-blue-800',
                succeeded: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800',
                refunded: 'bg-gray-100 text-gray-800',
                partially_refunded: 'bg-orange-100 text-orange-800',
                cancelled: 'bg-red-100 text-red-800',
            };
            return classes[status] || '';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        },
        async syncYooKassaPayments() {
            try {
                // Синхронизируем только платежи со статусом pending или processing
                const yooKassaPayments = this.payments.filter(p => 
                    this.isYooKassaPayment(p) && 
                    (p.status === 'pending' || p.status === 'processing')
                );
                
                if (yooKassaPayments.length === 0) {
                    return;
                }
                
                // Синхронизируем все платежи через ЮKassa
                await paymentsAPI.syncAllStatuses();
                // Перезагружаем платежи после синхронизации
                await this.loadPayments();
            } catch (error) {
                // Не показываем ошибку пользователю, просто логируем
                console.error('Ошибка автоматической синхронизации статусов:', error);
            }
        },
    },
};
</script>




