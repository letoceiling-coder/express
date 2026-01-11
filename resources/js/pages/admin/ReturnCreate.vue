<template>
    <div class="return-create-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Создать возврат</h1>
            <p class="text-muted-foreground mt-1">Добавление нового возврата</p>
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

                <!-- Товар -->
                <div v-if="orderItems.length > 0">
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Товар <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="form.product_id"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.product_id }"
                        @change="handleProductChange"
                    >
                        <option :value="null">Выберите товар</option>
                        <option
                            v-for="item in orderItems"
                            :key="item.product_id"
                            :value="item.product_id"
                        >
                            {{ item.product?.name || 'Товар' }} - {{ item.quantity }} шт. × {{ Number(item.price).toLocaleString('ru-RU') }} ₽
                        </option>
                    </select>
                    <p v-if="errors.product_id" class="mt-1 text-sm text-destructive">{{ errors.product_id }}</p>
                </div>

                <!-- Количество -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Количество <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        :max="selectedOrderItem?.quantity || 1"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.quantity }"
                    />
                    <p v-if="selectedOrderItem" class="mt-1 text-xs text-muted-foreground">
                        Максимум: {{ selectedOrderItem.quantity }} шт.
                    </p>
                    <p v-if="errors.quantity" class="mt-1 text-sm text-destructive">{{ errors.quantity }}</p>
                </div>

                <!-- Причина -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Причина возврата <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="form.reason"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.reason }"
                    >
                        <option value="">Выберите причину</option>
                        <option value="defective">Бракованный товар</option>
                        <option value="wrong_item">Не тот товар</option>
                        <option value="not_as_described">Не соответствует описанию</option>
                        <option value="changed_mind">Передумал</option>
                        <option value="damaged">Поврежден при доставке</option>
                        <option value="other">Другое</option>
                    </select>
                    <p v-if="errors.reason" class="mt-1 text-sm text-destructive">{{ errors.reason }}</p>
                </div>

                <!-- Сумма возврата -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Сумма возврата <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model.number="form.return_amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.return_amount }"
                    />
                    <p v-if="errors.return_amount" class="mt-1 text-sm text-destructive">{{ errors.return_amount }}</p>
                </div>

                <!-- Метод возврата -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Метод возврата</label>
                    <select
                        v-model="form.refund_method"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="original_payment">На способ оплаты</option>
                        <option value="bank_transfer">Банковский перевод</option>
                        <option value="cash">Наличными</option>
                        <option value="store_credit">Бонусы/Кредит магазина</option>
                    </select>
                </div>

                <!-- Описание -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Описание</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        placeholder="Дополнительная информация о возврате..."
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                    ></textarea>
                </div>

                <!-- Статус -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                    <select
                        v-model="form.status"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="pending">Ожидает</option>
                        <option value="approved">Одобрен</option>
                        <option value="processing">Обрабатывается</option>
                    </select>
                </div>

                <!-- Кнопки -->
                <div class="flex items-center gap-4 pt-4 border-t border-border">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ loading ? 'Создание...' : 'Создать возврат' }}
                    </button>
                    <router-link
                        to="/returns"
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
import { returnsAPI, ordersAPI } from '../../utils/api.js';

export default {
    name: 'ReturnCreate',
    data() {
        return {
            orders: [],
            orderItems: [],
            selectedOrderItem: null,
            form: {
                order_id: null,
                product_id: null,
                quantity: 1,
                reason: '',
                return_amount: 0,
                refund_method: 'original_payment',
                description: '',
                status: 'pending',
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
        async handleOrderChange() {
            this.orderItems = [];
            this.selectedOrderItem = null;
            this.form.product_id = null;
            this.form.quantity = 1;
            this.form.return_amount = 0;

            if (this.form.order_id) {
                try {
                    const order = this.orders.find(o => o.id === this.form.order_id);
                    if (order && order.items) {
                        this.orderItems = order.items;
                    } else {
                        // Загрузить детали заказа
                        const response = await ordersAPI.getById(this.form.order_id);
                        if (response.data && response.data.items) {
                            this.orderItems = response.data.items;
                        }
                    }
                } catch (error) {
                    console.error('Ошибка загрузки товаров заказа:', error);
                }
            }
        },
        handleProductChange() {
            if (this.form.product_id) {
                this.selectedOrderItem = this.orderItems.find(item => item.product_id === this.form.product_id);
                if (this.selectedOrderItem) {
                    this.form.quantity = 1;
                    this.calculateReturnAmount();
                }
            } else {
                this.selectedOrderItem = null;
                this.form.return_amount = 0;
            }
        },
        calculateReturnAmount() {
            if (this.selectedOrderItem && this.form.quantity > 0) {
                this.form.return_amount = Number(this.selectedOrderItem.price) * this.form.quantity;
            }
        },
        watch: {
            'form.quantity'() {
                this.calculateReturnAmount();
            },
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                await returnsAPI.create(this.form);
                this.$router.push('/returns');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка создания возврата');
                }
            } finally {
                this.loading = false;
            }
        },
    },
    watch: {
        'form.quantity'() {
            this.calculateReturnAmount();
        },
    },
};
</script>




