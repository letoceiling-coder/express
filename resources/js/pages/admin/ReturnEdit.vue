<template>
    <div class="return-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать возврат</h1>
            <p class="text-muted-foreground mt-1">Изменение возврата</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !returnItem" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка возврата...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
            <router-link
                to="/returns"
                class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
                Вернуться к списку
            </router-link>
        </div>

        <!-- Форма -->
        <div v-else-if="returnItem" class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Информация о заказе -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Заказ</label>
                    <input
                        :value="returnItem.order?.order_id || returnItem.order_id"
                        type="text"
                        disabled
                        class="w-full h-10 px-3 rounded-lg border border-input bg-muted text-muted-foreground"
                    />
                </div>

                <!-- Товар -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Товар</label>
                    <input
                        :value="returnItem.product?.name || 'Товар удален'"
                        type="text"
                        disabled
                        class="w-full h-10 px-3 rounded-lg border border-input bg-muted text-muted-foreground"
                    />
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
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.quantity }"
                    />
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
                        <option value="rejected">Отклонен</option>
                        <option value="processing">Обрабатывается</option>
                        <option value="completed">Завершен</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>

                <!-- Информация об отклонении -->
                <div v-if="returnItem.status === 'rejected' && returnItem.rejection_reason" class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="text-sm">
                        <div class="font-medium text-red-900 mb-2">Причина отклонения</div>
                        <div class="text-red-800">{{ returnItem.rejection_reason }}</div>
                        <div v-if="returnItem.rejected_at" class="text-red-700 text-xs mt-1">
                            Дата: {{ formatDate(returnItem.rejected_at) }}
                        </div>
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
                        to="/returns"
                        class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                    >
                        Отмена
                    </router-link>
                    <button
                        v-if="returnItem.status === 'pending'"
                        @click="handleApprove"
                        class="h-10 px-6 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        Одобрить
                    </button>
                    <button
                        v-if="returnItem.status === 'pending'"
                        @click="handleReject"
                        class="h-10 px-6 bg-red-600 text-white rounded-lg hover:bg-red-700"
                    >
                        Отклонить
                    </button>
                </div>
            </form>
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
                            @click="showRejectModal = false; rejectReason = ''"
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
import swal from '../../utils/swal.js';

export default {
    name: 'ReturnEdit',
    data() {
        return {
            returnItem: null,
            form: {
                quantity: 1,
                reason: '',
                return_amount: 0,
                refund_method: 'original_payment',
                description: '',
                status: 'pending',
            },
            errors: {},
            loading: false,
            error: null,
            showRejectModal: false,
            rejectReason: '',
            rejecting: false,
        };
    },
    mounted() {
        this.loadReturn();
    },
    methods: {
        async loadReturn() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                const response = await returnsAPI.getById(id);
                this.returnItem = response.data;
                
                // Заполняем форму
                this.form = {
                    quantity: this.returnItem.quantity || 1,
                    reason: this.returnItem.reason || '',
                    return_amount: Number(this.returnItem.return_amount) || 0,
                    refund_method: this.returnItem.refund_method || 'original_payment',
                    description: this.returnItem.description || '',
                    status: this.returnItem.status || 'pending',
                };
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки возврата';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                const id = this.$route.params.id;
                await returnsAPI.update(id, this.form);
                await this.loadReturn();
                await swal.success('Возврат успешно обновлен');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    await swal.error(error.message || 'Ошибка обновления возврата');
                }
            } finally {
                this.loading = false;
            }
        },
        async handleApprove() {
            if (!confirm(`Вы уверены, что хотите одобрить возврат #${this.returnItem.id}?`)) {
                return;
            }

            try {
                await returnsAPI.approve(this.returnItem.id);
                await this.loadReturn();
                await swal.success('Возврат одобрен');
            } catch (error) {
                await swal.error(error.message || 'Ошибка одобрения возврата');
            }
        },
        handleReject() {
            this.rejectReason = '';
            this.showRejectModal = true;
        },
        async confirmReject() {
            if (!this.rejectReason) return;

            this.rejecting = true;
            try {
                await returnsAPI.reject(this.returnItem.id, this.rejectReason);
                this.showRejectModal = false;
                this.rejectReason = '';
                await this.loadReturn();
                await swal.success('Возврат отклонен');
            } catch (error) {
                await swal.error(error.message || 'Ошибка отклонения возврата');
            } finally {
                this.rejecting = false;
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




