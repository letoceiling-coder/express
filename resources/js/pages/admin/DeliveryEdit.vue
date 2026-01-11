<template>
    <div class="delivery-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать доставку</h1>
            <p class="text-muted-foreground mt-1">Изменение доставки</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !delivery" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка доставки...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
            <router-link
                to="/deliveries"
                class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
                Вернуться к списку
            </router-link>
        </div>

        <!-- Форма -->
        <div v-else-if="delivery" class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Заказ -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Заказ <span class="text-destructive">*</span>
                    </label>
                    <input
                        :value="delivery.order?.order_id || delivery.order_id"
                        type="text"
                        disabled
                        class="w-full h-10 px-3 rounded-lg border border-input bg-muted text-muted-foreground"
                    />
                </div>

                <!-- Тип доставки -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Тип доставки</label>
                    <select
                        v-model="form.delivery_type"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="courier">Курьер</option>
                        <option value="pickup">Самовывоз</option>
                        <option value="self_delivery">Своя доставка</option>
                    </select>
                </div>

                <!-- Статус -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                    <select
                        v-model="form.status"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="pending">Ожидает</option>
                        <option value="assigned">Назначена</option>
                        <option value="in_transit">В пути</option>
                        <option value="delivered">Доставлена</option>
                        <option value="failed">Ошибка</option>
                        <option value="returned">Возвращена</option>
                    </select>
                </div>

                <!-- Адрес доставки -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Адрес доставки <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="form.delivery_address"
                        rows="3"
                        required
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.delivery_address }"
                    ></textarea>
                    <p v-if="errors.delivery_address" class="mt-1 text-sm text-destructive">{{ errors.delivery_address }}</p>
                </div>

                <!-- Курьер -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Имя курьера</label>
                        <input
                            v-model="form.courier_name"
                            type="text"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Телефон курьера</label>
                        <input
                            v-model="form.courier_phone"
                            type="text"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
                </div>

                <!-- Дата и время доставки -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Дата доставки</label>
                        <input
                            v-model="form.delivery_date"
                            type="date"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Время с</label>
                        <input
                            v-model="form.delivery_time_from"
                            type="time"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Время до</label>
                        <input
                            v-model="form.delivery_time_to"
                            type="time"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
                </div>

                <!-- Стоимость и трек-номер -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Стоимость доставки</label>
                        <input
                            v-model.number="form.delivery_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Трек-номер</label>
                        <input
                            v-model="form.tracking_number"
                            type="text"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
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
                        to="/deliveries"
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
import { deliveriesAPI } from '../../utils/api.js';

export default {
    name: 'DeliveryEdit',
    data() {
        return {
            delivery: null,
            form: {
                delivery_type: 'courier',
                status: 'pending',
                courier_name: '',
                courier_phone: '',
                delivery_address: '',
                delivery_date: '',
                delivery_time_from: '',
                delivery_time_to: '',
                delivery_cost: 0,
                tracking_number: '',
                notes: '',
            },
            errors: {},
            loading: false,
            error: null,
        };
    },
    mounted() {
        this.loadDelivery();
    },
    methods: {
        async loadDelivery() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                const response = await deliveriesAPI.getById(id);
                this.delivery = response.data;
                
                // Заполняем форму
                this.form = {
                    delivery_type: this.delivery.delivery_type || 'courier',
                    status: this.delivery.status || 'pending',
                    courier_name: this.delivery.courier_name || '',
                    courier_phone: this.delivery.courier_phone || '',
                    delivery_address: this.delivery.delivery_address || '',
                    delivery_date: this.delivery.delivery_date || '',
                    delivery_time_from: this.delivery.delivery_time_from || '',
                    delivery_time_to: this.delivery.delivery_time_to || '',
                    delivery_cost: Number(this.delivery.delivery_cost) || 0,
                    tracking_number: this.delivery.tracking_number || '',
                    notes: this.delivery.notes || '',
                };
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки доставки';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                const id = this.$route.params.id;
                await deliveriesAPI.update(id, this.form);
                this.$router.push('/deliveries');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка обновления доставки');
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>




