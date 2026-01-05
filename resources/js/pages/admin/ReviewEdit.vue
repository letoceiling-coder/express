<template>
    <div class="review-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать отзыв</h1>
            <p class="text-muted-foreground mt-1">Изменение отзыва #{{ review?.id }}</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !review" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка отзыва...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-card rounded-lg border border-border p-6">
            <div class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
                <p class="text-destructive">{{ error }}</p>
                <router-link
                    to="/reviews"
                    class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
                >
                    Вернуться к списку
                </router-link>
            </div>
        </div>

        <!-- Форма -->
        <div v-else-if="review" class="space-y-6">
            <div class="bg-card rounded-lg border border-border p-6">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Информация о заказе -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Заказ</label>
                        <input
                            v-if="review.order_id"
                            :value="review.order?.order_id || review.order_id"
                            type="text"
                            disabled
                            class="w-full h-10 px-3 rounded-lg border border-input bg-muted text-muted-foreground"
                        />
                        <span v-else class="text-sm text-muted-foreground">Без привязки к заказу</span>
                    </div>

                    <!-- Товар -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Товар</label>
                        <input
                            :value="review.product?.name || 'Товар удален'"
                            type="text"
                            disabled
                            class="w-full h-10 px-3 rounded-lg border border-input bg-muted text-muted-foreground"
                        />
                    </div>

                    <!-- Рейтинг -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Рейтинг <span class="text-destructive">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                @click="form.rating = star"
                                class="text-3xl focus:outline-none"
                                :class="star <= form.rating ? 'text-yellow-500' : 'text-gray-300'"
                            >
                                ★
                            </button>
                            <span class="ml-2 text-sm text-muted-foreground">{{ form.rating }} из 5</span>
                        </div>
                        <input
                            v-model.number="form.rating"
                            type="number"
                            min="1"
                            max="5"
                            required
                            class="hidden"
                        />
                        <p v-if="errors.rating" class="mt-1 text-sm text-destructive">{{ errors.rating }}</p>
                    </div>

                    <!-- Комментарий -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Комментарий <span class="text-destructive">*</span>
                        </label>
                        <textarea
                            v-model="form.comment"
                            rows="6"
                            required
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.comment }"
                        ></textarea>
                        <p v-if="errors.comment" class="mt-1 text-sm text-destructive">{{ errors.comment }}</p>
                    </div>

                    <!-- Имя клиента -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Имя клиента</label>
                        <input
                            v-model="form.customer_name"
                            type="text"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <!-- Фотографии -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Фотографии</label>
                        <button
                            type="button"
                            @click="showMediaSelector = true"
                            class="h-10 px-4 bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                        >
                            Выбрать фото из медиа-библиотеки
                        </button>
                        <div v-if="selectedPhotos.length > 0" class="mt-2 grid grid-cols-4 gap-4">
                            <div
                                v-for="photo in selectedPhotos"
                                :key="photo.id"
                                class="relative group"
                            >
                                <img
                                    :src="photo.url || photo.path"
                                    :alt="photo.name || photo.filename"
                                    class="w-full h-24 object-cover rounded-lg"
                                />
                                <button
                                    type="button"
                                    @click="removePhoto(photo.id)"
                                    class="absolute top-1 right-1 bg-destructive text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Статус -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                        <select
                            v-model="form.status"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        >
                            <option value="pending">На модерации</option>
                            <option value="approved">Одобрен</option>
                            <option value="rejected">Отклонен</option>
                        </select>
                    </div>

                    <!-- Ответ компании -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Ответ компании</label>
                        <textarea
                            v-model="form.company_response"
                            rows="4"
                            placeholder="Ответ компании на отзыв..."
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
                            to="/reviews"
                            class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                        >
                            Отмена
                        </router-link>
                    </div>
                </form>
            </div>
        </div>

        <!-- MediaSelector Modal -->
        <MediaSelector
            v-if="showMediaSelector"
            :isOpen="showMediaSelector"
            :multiple="true"
            :allowedTypes="['photo']"
            :currentSelection="selectedPhotos"
            @close="showMediaSelector = false"
            @select="handleMediaSelect"
        />
    </div>
</template>

<script>
import { reviewsAPI } from '../../utils/api.js';
import MediaSelector from '../../components/admin/MediaSelector.vue';

export default {
    name: 'ReviewEdit',
    components: {
        MediaSelector,
    },
    data() {
        return {
            review: null,
            showMediaSelector: false,
            selectedPhotos: [],
            form: {
                rating: 5,
                comment: '',
                customer_name: '',
                photos: [],
                status: 'pending',
                company_response: '',
            },
            errors: {},
            loading: false,
            error: null,
        };
    },
    mounted() {
        this.loadReview();
    },
    methods: {
        async loadReview() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                const response = await reviewsAPI.getById(id);
                this.review = response.data;
                
                // Заполняем форму
                this.form = {
                    rating: this.review.rating || 5,
                    comment: this.review.comment || '',
                    customer_name: this.review.customer_name || '',
                    photos: [],
                    status: this.review.status || 'pending',
                    company_response: this.review.company_response || '',
                };

                // Загружаем фотографии если есть
                if (this.review.photos && Array.isArray(this.review.photos)) {
                    this.selectedPhotos = this.review.photos;
                }
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки отзыва';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                const id = this.$route.params.id;
                this.form.photos = this.selectedPhotos.map(p => p.id);
                await reviewsAPI.update(id, this.form);
                await this.loadReview();
                alert('Отзыв успешно обновлен');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка обновления отзыва');
                }
            } finally {
                this.loading = false;
            }
        },
        handleMediaSelect(files) {
            this.selectedPhotos = Array.isArray(files) ? files : [files];
        },
        removePhoto(photoId) {
            this.selectedPhotos = this.selectedPhotos.filter(p => p.id !== photoId);
        },
    },
};
</script>



