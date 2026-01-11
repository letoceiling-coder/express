<template>
    <div class="payment-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать платеж</h1>
            <p class="text-muted-foreground mt-1">Изменение платежа</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !payment" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка платежа...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
            <router-link
                to="/payments"
                class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
                Вернуться к списку
            </router-link>
        </div>

        <!-- Форма -->
        <div v-else-if="payment" class="space-y-6">
            <div class="bg-card rounded-lg border border-border p-6">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Заказ -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Заказ</label>
                        <input
                            :value="payment.order?.order_id || payment.order_id"
                            type="text"
                            disabled
                            class="w-full h-10 px-3 rounded-lg border border-input bg-muted text-muted-foreground"
                        />
                    </div>

                    <!-- Метод и провайдер -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Метод оплаты</label>
                            <select
                                v-model="form.payment_method"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            >
                                <option value="card">Карта</option>
                                <option value="cash">Наличные</option>
                                <option value="online">Онлайн</option>
                                <option value="bank_transfer">Банковский перевод</option>
                                <option value="other">Другое</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Провайдер</label>
                            <input
                                v-model="form.payment_provider"
                                type="text"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>
                    </div>

                    <!-- Сумма и валюта -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Сумма <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model.number="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                                :class="{ 'border-destructive': errors.amount }"
                            />
                            <p v-if="errors.amount" class="mt-1 text-sm text-destructive">{{ errors.amount }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Валюта</label>
                            <input
                                v-model="form.currency"
                                type="text"
                                maxlength="3"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>
                    </div>

                    <!-- Статус -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                        <select
                            v-model="form.status"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        >
                            <option value="pending">Ожидает</option>
                            <option value="processing">Обрабатывается</option>
                            <option value="succeeded">Успешен</option>
                            <option value="failed">Ошибка</option>
                            <option value="refunded">Возвращен</option>
                            <option value="partially_refunded">Частично возвращен</option>
                            <option value="cancelled">Отменен</option>
                        </select>
                    </div>

                    <!-- Transaction ID -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Transaction ID</label>
                        <input
                            v-model="form.transaction_id"
                            type="text"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <!-- Информация о возврате -->
                    <div v-if="payment.refunded_amount > 0" class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="text-sm">
                            <div class="font-medium text-orange-900 mb-2">Информация о возврате</div>
                            <div class="text-orange-800">
                                Возвращено: {{ Number(payment.refunded_amount).toLocaleString('ru-RU') }} {{ payment.currency || '₽' }}
                            </div>
                            <div v-if="payment.refunded_at" class="text-orange-700 text-xs mt-1">
                                Дата возврата: {{ formatDate(payment.refunded_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Заметки -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Заметки</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        ></textarea>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex items-center gap-4 pt-4 border-t border-border">
                        <button
                            type="submit"
                            :disabled="loading"
                            class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                        >
                            {{ loading ? 'Сохранение...' : 'Сохранить изменения' }}
                        </button>
                        <router-link
                            to="/payments"
                            class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                        >
                            Отмена
                        </router-link>
                        <button
                            v-if="payment.status === 'succeeded' && payment.refunded_amount < payment.amount"
                            @click="handleRefund"
                            class="h-10 px-6 bg-orange-600 text-white rounded-lg hover:bg-orange-700"
                        >
                            Возврат
                        </button>
                    </div>
                </form>
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
                            :max="payment ? (payment.amount - payment.refunded_amount) : 0"
                            placeholder="Полный возврат"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                        <p v-if="payment" class="mt-1 text-xs text-muted-foreground">
                            Доступно для возврата: {{ (payment.amount - payment.refunded_amount).toLocaleString('ru-RU') }} {{ payment.currency || '₽' }}
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
                            @click="showRefundModal = false; refundAmount = null"
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

export default {
    name: 'PaymentEdit',
    data() {
        return {
            payment: null,
            form: {
                payment_method: 'card',
                payment_provider: '',
                amount: 0,
                currency: 'RUB',
                status: 'pending',
                transaction_id: '',
                notes: '',
            },
            errors: {},
            loading: false,
            error: null,
            showRefundModal: false,
            refundAmount: null,
            refunding: false,
        };
    },
    mounted() {
        this.loadPayment();
    },
    methods: {
        async loadPayment() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                const response = await paymentsAPI.getById(id);
                this.payment = response.data;
                
                // Заполняем форму
                this.form = {
                    payment_method: this.payment.payment_method || 'card',
                    payment_provider: this.payment.payment_provider || '',
                    amount: Number(this.payment.amount) || 0,
                    currency: this.payment.currency || 'RUB',
                    status: this.payment.status || 'pending',
                    transaction_id: this.payment.transaction_id || '',
                    notes: this.payment.notes || '',
                };
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки платежа';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                const id = this.$route.params.id;
                await paymentsAPI.update(id, this.form);
                await this.loadPayment();
                alert('Платеж успешно обновлен');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка обновления платежа');
                }
            } finally {
                this.loading = false;
            }
        },
        handleRefund() {
            this.refundAmount = null;
            this.showRefundModal = true;
        },
        async confirmRefund() {
            if (!this.payment) return;

            this.refunding = true;
            try {
                await paymentsAPI.refund(this.payment.id, this.refundAmount || null);
                this.showRefundModal = false;
                this.refundAmount = null;
                await this.loadPayment();
                alert('Возврат платежа выполнен');
            } catch (error) {
                alert(error.message || 'Ошибка возврата платежа');
            } finally {
                this.refunding = false;
            }
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU');
        },
    },
};
</script>




