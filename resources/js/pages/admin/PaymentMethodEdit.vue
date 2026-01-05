<template>
    <div class="payment-method-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать способ оплаты</h1>
            <p class="text-muted-foreground mt-1">Изменение способа оплаты</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !method" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка способа оплаты...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
            <router-link
                to="/payment-methods"
                class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
                Вернуться к списку
            </router-link>
        </div>

        <!-- Форма -->
        <div v-else-if="method" class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Основная информация -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground">Основная информация</h2>
                    
                    <!-- Код -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Код <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            required
                            pattern="[a-z0-9_]+"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.code }"
                        />
                        <p v-if="errors.code" class="mt-1 text-sm text-destructive">{{ errors.code }}</p>
                    </div>

                    <!-- Название -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Название <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.name }"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-destructive">{{ errors.name }}</p>
                    </div>

                    <!-- Описание -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        ></textarea>
                    </div>

                    <!-- Порядок сортировки -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Порядок сортировки</label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <!-- Статус -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_enabled"
                                type="checkbox"
                                class="w-4 h-4 rounded border-input"
                            />
                            <span class="text-sm font-medium text-foreground">Способ оплаты активен</span>
                        </label>
                    </div>

                    <!-- По умолчанию -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_default"
                                type="checkbox"
                                class="w-4 h-4 rounded border-input"
                            />
                            <span class="text-sm font-medium text-foreground">По умолчанию (будет выбран автоматически)</span>
                        </label>
                    </div>
                </div>

                <!-- Настройки скидки -->
                <div class="space-y-4 pt-6 border-t border-border">
                    <h2 class="text-lg font-semibold text-foreground">Настройки скидки</h2>
                    
                    <!-- Тип скидки -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Тип скидки</label>
                        <select
                            v-model="form.discount_type"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        >
                            <option value="none">Нет скидки</option>
                            <option value="percentage">Процент от суммы корзины</option>
                            <option value="fixed">Фиксированная сумма</option>
                        </select>
                    </div>

                    <!-- Значение скидки -->
                    <div v-if="form.discount_type !== 'none'">
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Значение скидки <span class="text-destructive">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="form.discount_value"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="flex-1 h-10 px-3 rounded-lg border border-input bg-background"
                            />
                            <span class="text-sm text-muted-foreground">
                                {{ form.discount_type === 'percentage' ? '%' : '₽' }}
                            </span>
                        </div>
                    </div>

                    <!-- Минимальная сумма корзины -->
                    <div v-if="form.discount_type !== 'none'">
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Минимальная сумма корзины (₽)
                        </label>
                        <input
                            v-model.number="form.min_cart_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Скидка будет применяться только если сумма корзины превышает указанное значение
                        </p>
                    </div>
                </div>

                <!-- Уведомление -->
                <div class="space-y-4 pt-6 border-t border-border">
                    <h2 class="text-lg font-semibold text-foreground">Уведомление пользователя</h2>
                    
                    <!-- Показывать уведомление -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.show_notification"
                                type="checkbox"
                                class="w-4 h-4 rounded border-input"
                            />
                            <span class="text-sm font-medium text-foreground">Показывать уведомление при выборе</span>
                        </label>
                    </div>

                    <!-- Текст уведомления -->
                    <div v-if="form.show_notification">
                        <label class="block text-sm font-medium text-foreground mb-2">Текст уведомления</label>
                        <textarea
                            v-model="form.notification_text"
                            rows="3"
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        ></textarea>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Доступные плейсхолдеры: {name}, {discount}, {discount_percent}, {final_amount}, {cart_amount}
                        </p>
                    </div>
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
                        to="/payment-methods"
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
import { apiGet, apiPut } from '@/utils/api';

export default {
    name: 'PaymentMethodEdit',
    data() {
        return {
            method: null,
            form: {
                code: '',
                name: '',
                description: '',
                is_enabled: true,
                is_default: false,
                sort_order: 0,
                discount_type: 'none',
                discount_value: null,
                min_cart_amount: null,
                show_notification: false,
                notification_text: '',
            },
            errors: {},
            loading: false,
            error: null,
        };
    },
    mounted() {
        this.loadMethod();
    },
    methods: {
        async loadMethod() {
            this.loading = true;
            this.error = null;
            try {
                const response = await apiGet(`/payment-methods/${this.$route.params.id}`);
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Ошибка загрузки способа оплаты');
                }
                const data = await response.json();
                this.method = data.data || data;
                this.form = {
                    code: this.method.code || '',
                    name: this.method.name || '',
                    description: this.method.description || '',
                    is_enabled: this.method.is_enabled !== false,
                    is_default: this.method.is_default || false,
                    sort_order: this.method.sort_order || 0,
                    discount_type: this.method.discount_type || 'none',
                    discount_value: this.method.discount_value || null,
                    min_cart_amount: this.method.min_cart_amount || null,
                    show_notification: this.method.show_notification || false,
                    notification_text: this.method.notification_text || '',
                };
            } catch (err) {
                this.error = err.response?.data?.message || 'Ошибка загрузки способа оплаты';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                // Очистка полей в зависимости от типа скидки
                const formData = { ...this.form };
                if (formData.discount_type === 'none') {
                    formData.discount_value = null;
                    formData.min_cart_amount = null;
                }
                if (!formData.show_notification) {
                    formData.notification_text = null;
                }

                await apiPut(`/payment-methods/${this.$route.params.id}`, formData);
                this.$router.push('/payment-methods');
            } catch (err) {
                if (err.response?.data?.errors) {
                    this.errors = err.response.data.errors;
                } else {
                    alert(err.response?.data?.message || 'Ошибка сохранения способа оплаты');
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

