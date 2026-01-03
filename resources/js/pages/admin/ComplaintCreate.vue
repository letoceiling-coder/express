<template>
    <div class="complaint-create-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Создать претензию</h1>
            <p class="text-muted-foreground mt-1">Добавление новой претензии</p>
        </div>

        <div class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Заказ (опционально) -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Заказ</label>
                    <select
                        v-model="form.order_id"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        @change="handleOrderChange"
                    >
                        <option :value="null">Без привязки к заказу</option>
                        <option
                            v-for="order in orders"
                            :key="order.id"
                            :value="order.id"
                        >
                            #{{ order.order_id }} - {{ order.phone }} - {{ Number(order.total_amount).toLocaleString('ru-RU') }} ₽
                        </option>
                    </select>
                </div>

                <!-- Тип и приоритет -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Тип <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-model="form.type"
                            required
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.type }"
                        >
                            <option value="">Выберите тип</option>
                            <option value="quality">Качество</option>
                            <option value="delivery">Доставка</option>
                            <option value="service">Сервис</option>
                            <option value="payment">Оплата</option>
                            <option value="other">Другое</option>
                        </select>
                        <p v-if="errors.type" class="mt-1 text-sm text-destructive">{{ errors.type }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Приоритет <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-model="form.priority"
                            required
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.priority }"
                        >
                            <option value="">Выберите приоритет</option>
                            <option value="low">Низкий</option>
                            <option value="medium">Средний</option>
                            <option value="high">Высокий</option>
                            <option value="urgent">Срочный</option>
                        </select>
                        <p v-if="errors.priority" class="mt-1 text-sm text-destructive">{{ errors.priority }}</p>
                    </div>
                </div>

                <!-- Тема -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Тема <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.subject"
                        type="text"
                        required
                        placeholder="Краткое описание проблемы"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.subject }"
                    />
                    <p v-if="errors.subject" class="mt-1 text-sm text-destructive">{{ errors.subject }}</p>
                </div>

                <!-- Описание -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Описание <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="6"
                        required
                        placeholder="Подробное описание проблемы..."
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.description }"
                    ></textarea>
                    <p v-if="errors.description" class="mt-1 text-sm text-destructive">{{ errors.description }}</p>
                </div>

                <!-- Данные клиента -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Имя клиента</label>
                        <input
                            v-model="form.customer_name"
                            type="text"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Телефон</label>
                        <input
                            v-model="form.customer_phone"
                            type="text"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Email</label>
                        <input
                            v-model="form.customer_email"
                            type="email"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
                </div>

                <!-- Вложения (через MediaSelector) -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Вложения</label>
                    <button
                        type="button"
                        @click="showMediaSelector = true"
                        class="h-10 px-4 bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                    >
                        Выбрать файлы из медиа-библиотеки
                    </button>
                    <div v-if="selectedAttachments.length > 0" class="mt-2 space-y-2">
                        <div
                            v-for="attachment in selectedAttachments"
                            :key="attachment.id"
                            class="flex items-center justify-between p-2 bg-muted rounded-lg"
                        >
                            <span class="text-sm text-foreground">{{ attachment.name || attachment.filename }}</span>
                            <button
                                type="button"
                                @click="removeAttachment(attachment.id)"
                                class="text-destructive hover:text-destructive/80"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Назначено -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Назначено</label>
                    <input
                        v-model="form.assigned_to"
                        type="text"
                        placeholder="Имя сотрудника"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>

                <!-- Статус -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                    <select
                        v-model="form.status"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="new">Новая</option>
                        <option value="in_progress">В работе</option>
                        <option value="resolved">Решена</option>
                        <option value="rejected">Отклонена</option>
                    </select>
                </div>

                <!-- Кнопки -->
                <div class="flex items-center gap-4 pt-4 border-t border-border">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ loading ? 'Создание...' : 'Создать претензию' }}
                    </button>
                    <router-link
                        to="/complaints"
                        class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                    >
                        Отмена
                    </router-link>
                </div>
            </form>
        </div>

        <!-- MediaSelector Modal -->
        <MediaSelector
            v-if="showMediaSelector"
            :isOpen="showMediaSelector"
            :multiple="true"
            :allowedTypes="['document', 'photo', 'video']"
            :currentSelection="selectedAttachments"
            @close="showMediaSelector = false"
            @select="handleMediaSelect"
        />
    </div>
</template>

<script>
import { complaintsAPI, ordersAPI } from '../../utils/api.js';
import MediaSelector from '../../components/admin/MediaSelector.vue';

export default {
    name: 'ComplaintCreate',
    components: {
        MediaSelector,
    },
    data() {
        return {
            orders: [],
            showMediaSelector: false,
            selectedAttachments: [],
            form: {
                order_id: null,
                type: '',
                priority: 'medium',
                subject: '',
                description: '',
                customer_name: '',
                customer_phone: '',
                customer_email: '',
                attachments: [],
                assigned_to: '',
                status: 'new',
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
            if (this.form.order_id) {
                const order = this.orders.find(o => o.id === this.form.order_id);
                if (order) {
                    this.form.customer_name = order.name || '';
                    this.form.customer_phone = order.phone || '';
                    this.form.customer_email = order.email || '';
                }
            }
        },
        handleMediaSelect(files) {
            this.selectedAttachments = Array.isArray(files) ? files : [files];
        },
        removeAttachment(attachmentId) {
            this.selectedAttachments = this.selectedAttachments.filter(a => a.id !== attachmentId);
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                // Добавляем ID вложений в форму
                this.form.attachments = this.selectedAttachments.map(a => a.id);
                
                await complaintsAPI.create(this.form);
                this.$router.push('/complaints');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка создания претензии');
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

