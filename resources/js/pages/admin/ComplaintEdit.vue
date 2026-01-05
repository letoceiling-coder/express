<template>
    <div class="complaint-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать претензию</h1>
            <p class="text-muted-foreground mt-1">Изменение претензии #{{ complaint?.id }}</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !complaint" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка претензии...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
            <router-link
                to="/complaints"
                class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
                Вернуться к списку
            </router-link>
        </div>

        <!-- Форма -->
        <div v-else-if="complaint" class="space-y-6">
            <div class="bg-card rounded-lg border border-border p-6">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Информация о заказе -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Заказ</label>
                        <input
                            v-if="complaint.order_id"
                            :value="complaint.order?.order_id || complaint.order_id"
                            type="text"
                            disabled
                            class="w-full h-10 px-3 rounded-lg border border-input bg-muted text-muted-foreground"
                        />
                        <span v-else class="text-sm text-muted-foreground">Без привязки к заказу</span>
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

                    <!-- Вложения -->
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
                            <option value="closed">Закрыта</option>
                        </select>
                    </div>

                    <!-- Решение (если решена) -->
                    <div v-if="form.status === 'resolved'">
                        <label class="block text-sm font-medium text-foreground mb-2">Решение</label>
                        <textarea
                            v-model="form.resolution"
                            rows="4"
                            placeholder="Опишите решение проблемы..."
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
                            to="/complaints"
                            class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                        >
                            Отмена
                        </router-link>
                    </div>
                </form>
            </div>

            <!-- Комментарии -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4">Комментарии</h2>
                
                <!-- Список комментариев -->
                <div v-if="comments.length > 0" class="space-y-4 mb-4">
                    <div
                        v-for="comment in comments"
                        :key="comment.id"
                        class="p-4 bg-muted rounded-lg"
                    >
                        <div class="flex items-start justify-between mb-2">
                            <div class="text-sm font-medium text-foreground">{{ comment.user?.name || 'Администратор' }}</div>
                            <div class="text-xs text-muted-foreground">{{ formatDate(comment.created_at) }}</div>
                        </div>
                        <div class="text-sm text-foreground">{{ comment.comment }}</div>
                    </div>
                </div>

                <!-- Форма добавления комментария -->
                <div class="border-t border-border pt-4">
                    <div class="flex gap-4">
                        <textarea
                            v-model="newComment"
                            rows="3"
                            placeholder="Добавить комментарий..."
                            class="flex-1 px-3 py-2 rounded-lg border border-input bg-background"
                        ></textarea>
                        <button
                            @click="handleAddComment"
                            :disabled="!newComment.trim() || addingComment"
                            class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                        >
                            {{ addingComment ? 'Добавление...' : 'Добавить' }}
                        </button>
                    </div>
                </div>
            </div>
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
import { complaintsAPI } from '../../utils/api.js';
import MediaSelector from '../../components/admin/MediaSelector.vue';

export default {
    name: 'ComplaintEdit',
    components: {
        MediaSelector,
    },
    data() {
        return {
            complaint: null,
            comments: [],
            showMediaSelector: false,
            selectedAttachments: [],
            form: {
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
                resolution: '',
            },
            errors: {},
            loading: false,
            error: null,
            newComment: '',
            addingComment: false,
        };
    },
    mounted() {
        this.loadComplaint();
    },
    methods: {
        async loadComplaint() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                const response = await complaintsAPI.getById(id);
                this.complaint = response.data;
                
                // Заполняем форму
                this.form = {
                    type: this.complaint.type || '',
                    priority: this.complaint.priority || 'medium',
                    subject: this.complaint.subject || '',
                    description: this.complaint.description || '',
                    customer_name: this.complaint.customer_name || '',
                    customer_phone: this.complaint.customer_phone || '',
                    customer_email: this.complaint.customer_email || '',
                    attachments: [],
                    assigned_to: this.complaint.assigned_to || '',
                    status: this.complaint.status || 'new',
                    resolution: this.complaint.resolution || '',
                };

                // Загружаем вложения если есть
                if (this.complaint.attachments && Array.isArray(this.complaint.attachments)) {
                    this.selectedAttachments = this.complaint.attachments;
                }

                // Загружаем комментарии
                if (this.complaint.comments && Array.isArray(this.complaint.comments)) {
                    this.comments = this.complaint.comments;
                }
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки претензии';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                const id = this.$route.params.id;
                this.form.attachments = this.selectedAttachments.map(a => a.id);
                await complaintsAPI.update(id, this.form);
                await this.loadComplaint();
                alert('Претензия успешно обновлена');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка обновления претензии');
                }
            } finally {
                this.loading = false;
            }
        },
        async handleAddComment() {
            if (!this.newComment.trim()) return;

            this.addingComment = true;
            try {
                const id = this.$route.params.id;
                await complaintsAPI.addComment(id, this.newComment);
                this.newComment = '';
                await this.loadComplaint();
            } catch (error) {
                alert(error.message || 'Ошибка добавления комментария');
            } finally {
                this.addingComment = false;
            }
        },
        handleMediaSelect(files) {
            this.selectedAttachments = Array.isArray(files) ? files : [files];
        },
        removeAttachment(attachmentId) {
            this.selectedAttachments = this.selectedAttachments.filter(a => a.id !== attachmentId);
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU');
        },
    },
};
</script>



