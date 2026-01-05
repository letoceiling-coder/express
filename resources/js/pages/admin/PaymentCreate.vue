<template>
    <div class="payment-create-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Создать платеж</h1>
            <p class="text-muted-foreground mt-1">Добавление нового платежа</p>
        </div>

        <div class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Заказ -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Заказ <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="form.order_id"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.order_id }"
                        @change="handleOrderChange"
                    >
                        <option :value="null">Выберите заказ</option>
                        <option
                            v-for="order in orders"
                            :key="order.id"
                            :value="order.id"
                        >
                            #{{ order.order_id }} - {{ order.phone }} - {{ Number(order.total_amount).toLocaleString('ru-RU') }} ₽
                        </option>
                    </select>
                    <p v-if="errors.order_id" class="mt-1 text-sm text-destructive">{{ errors.order_id }}</p>
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
                            placeholder="yookassa, stripe, sberbank..."
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
                            placeholder="RUB"
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
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>

                <!-- Transaction ID -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Transaction ID</label>
                    <input
                        v-model="form.transaction_id"
                        type="text"
                        placeholder="ID транзакции в платежной системе"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
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
                        {{ loading ? 'Создание...' : 'Создать платеж' }}
                    </button>
                    <router-link
                        to="/payments"
                        class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                    >
                        Отмена
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { paymentsAPI, ordersAPI } from '../../utils/api.js';

export default {
    name: 'PaymentCreate',
    data() {
        return {
            orders: [],
            form: {
                order_id: null,
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
        };
    },
    mounted() {
        this.loadOrders();
        
        // Если передан orderId через query параметр
        const orderId = this.$route.query.orderId;
        if (orderId) {
            this.form.order_id = Number(orderId);
            this.handleOrderChange();
        }
    },
    methods: {
        async loadOrders() {
            try {
                const response = await ordersAPI.getAll();
                this.orders = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Ошибка загрузки заказов:', error);
            }
        },
        handleOrderChange() {
            // Автозаполнение суммы из заказа
            if (this.form.order_id) {
                const order = this.orders.find(o => o.id === this.form.order_id);
                if (order) {
                    this.form.amount = Number(order.total_amount) || 0;
                }
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                await paymentsAPI.create(this.form);
                this.$router.push('/payments');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка создания платежа');
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>



