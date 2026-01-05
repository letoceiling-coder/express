<template>
    <div class="order-detail-page">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Заказ #{{ order?.order_id }}</h1>
                    <p class="text-muted-foreground mt-1">Детальная информация о заказе</p>
                </div>
                <router-link
                    to="/orders"
                    class="h-10 px-4 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 inline-flex items-center"
                >
                    К списку заказов
                </router-link>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !order" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка заказа...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Детали заказа -->
        <div v-else-if="order" class="space-y-6">
            <!-- Основная информация -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4 border-b border-border pb-2">Основная информация</h2>
                
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Статус заказа</label>
                            <select
                                v-model="form.status"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            >
                                <option value="new">Новый</option>
                                <option value="accepted">Принят</option>
                                <option value="sent_to_kitchen">Отправлен на кухню</option>
                                <option value="kitchen_accepted">Принят кухней</option>
                                <option value="preparing">Готовится</option>
                                <option value="ready_for_delivery">Готов к доставке</option>
                                <option value="courier_assigned">Курьер назначен</option>
                                <option value="in_transit">В пути</option>
                                <option value="delivered">Доставлен</option>
                                <option value="cancelled">Отменен</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Статус оплаты</label>
                            <select
                                v-model="form.payment_status"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            >
                                <option value="pending">Ожидает</option>
                                <option value="succeeded">Оплачен</option>
                                <option value="failed">Ошибка</option>
                                <option value="cancelled">Отменен</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Телефон</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                required
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Общая сумма</label>
                            <input
                                v-model.number="form.total_amount"
                                type="number"
                                step="0.01"
                                required
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-foreground mb-2">Адрес доставки</label>
                            <textarea
                                v-model="form.delivery_address"
                                rows="2"
                                required
                                class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Время доставки</label>
                            <input
                                v-model="form.delivery_time"
                                type="text"
                                required
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Комментарий</label>
                            <input
                                v-model="form.comment"
                                type="text"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-foreground mb-2">Внутренние заметки</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-border">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                        >
                            {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Товары в заказе -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4 border-b border-border pb-2">Товары</h2>
                
                <div v-if="order.items && order.items.length > 0" class="space-y-2">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="flex items-center justify-between p-4 bg-muted/30 rounded-lg"
                    >
                        <div class="flex items-center gap-4">
                            <img
                                v-if="item.product_image"
                                :src="item.product_image"
                                :alt="item.product_name"
                                class="w-16 h-16 object-cover rounded-lg"
                            />
                            <div>
                                <div class="font-medium text-foreground">{{ item.product_name }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ item.quantity }} шт. × {{ Number(item.unit_price).toLocaleString('ru-RU') }} ₽
                                </div>
                            </div>
                        </div>
                        <div class="font-medium text-foreground">
                            {{ Number(item.total).toLocaleString('ru-RU') }} ₽
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    Товары не найдены
                </div>
            </div>

            <!-- Связанная информация -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Доставка -->
                <div class="bg-card rounded-lg border border-border p-6">
                    <h2 class="text-lg font-semibold text-foreground mb-4 border-b border-border pb-2">Доставка</h2>
                    
                    <div v-if="delivery" class="space-y-2">
                        <div class="text-sm">
                            <span class="text-muted-foreground">Статус:</span>
                            <span class="ml-2 font-medium text-foreground">{{ delivery.status }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-muted-foreground">Тип:</span>
                            <span class="ml-2 font-medium text-foreground">{{ delivery.delivery_type }}</span>
                        </div>
                        <div v-if="delivery.courier_name" class="text-sm">
                            <span class="text-muted-foreground">Курьер:</span>
                            <span class="ml-2 font-medium text-foreground">{{ delivery.courier_name }}</span>
                        </div>
                        <div v-if="delivery.tracking_number" class="text-sm">
                            <span class="text-muted-foreground">Трек-номер:</span>
                            <span class="ml-2 font-medium text-foreground">{{ delivery.tracking_number }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-muted-foreground">
                        Доставка не создана
                    </div>
                </div>

                <!-- Платежи -->
                <div class="bg-card rounded-lg border border-border p-6">
                    <h2 class="text-lg font-semibold text-foreground mb-4 border-b border-border pb-2">Платежи</h2>
                    
                    <div v-if="payments && payments.length > 0" class="space-y-2">
                        <div
                            v-for="payment in payments"
                            :key="payment.id"
                            class="p-3 bg-muted/30 rounded-lg"
                        >
                            <div class="text-sm">
                                <span class="text-muted-foreground">Сумма:</span>
                                <span class="ml-2 font-medium text-foreground">
                                    {{ Number(payment.amount).toLocaleString('ru-RU') }} ₽
                                </span>
                            </div>
                            <div class="text-sm">
                                <span class="text-muted-foreground">Статус:</span>
                                <span class="ml-2 font-medium text-foreground">{{ payment.status }}</span>
                            </div>
                            <div class="text-sm">
                                <span class="text-muted-foreground">Метод:</span>
                                <span class="ml-2 font-medium text-foreground">{{ payment.payment_method }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-muted-foreground">
                        Платежей нет
                    </div>
                </div>

                <!-- История статусов -->
                <div class="bg-card rounded-lg border border-border p-6">
                    <OrderStatusHistory :order-id="order.id" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ordersAPI, deliveriesAPI, paymentsAPI } from '../../utils/api.js';
import OrderStatusHistory from '../../components/admin/OrderStatusHistory.vue';

export default {
    name: 'OrderDetail',
    components: {
        OrderStatusHistory,
    },
    data() {
        return {
            order: null,
            delivery: null,
            payments: [],
            form: {},
            loading: false,
            saving: false,
            error: null,
        };
    },
    mounted() {
        this.loadOrder();
        this.loadDelivery();
        this.loadPayments();
    },
    methods: {
        async loadOrder() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                const response = await ordersAPI.getById(id);
                this.order = response.data;
                
                // Заполняем форму
                this.form = {
                    status: this.order.status || 'new',
                    payment_status: this.order.payment_status || 'pending',
                    phone: this.order.phone || '',
                    delivery_address: this.order.delivery_address || '',
                    delivery_time: this.order.delivery_time || '',
                    comment: this.order.comment || '',
                    total_amount: Number(this.order.total_amount) || 0,
                    notes: this.order.notes || '',
                };
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки заказа';
            } finally {
                this.loading = false;
            }
        },
        async loadDelivery() {
            try {
                const orderId = this.$route.params.id;
                const response = await deliveriesAPI.getByOrder(orderId);
                this.delivery = response.data;
            } catch (error) {
                // Доставка может отсутствовать, это нормально
                console.log('Доставка не найдена:', error);
            }
        },
        async loadPayments() {
            try {
                const orderId = this.$route.params.id;
                const response = await paymentsAPI.getByOrder(orderId);
                this.payments = response.data || [];
            } catch (error) {
                console.log('Ошибка загрузки платежей:', error);
            }
        },
        async handleSubmit() {
            this.saving = true;
            try {
                const id = this.$route.params.id;
                await ordersAPI.update(id, this.form);
                await this.loadOrder();
                alert('Заказ успешно обновлен');
            } catch (error) {
                alert(error.message || 'Ошибка обновления заказа');
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

