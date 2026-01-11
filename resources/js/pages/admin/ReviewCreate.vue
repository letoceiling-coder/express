<template>
    <div class="review-create-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Создать отзыв</h1>
            <p class="text-muted-foreground mt-1">Добавление нового отзыва</p>
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

                <!-- Товар -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Товар <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="form.product_id"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.product_id }"
                    >
                        <option :value="null">Выберите товар</option>
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }}
                        </option>
                    </select>
                    <p v-if="errors.product_id" class="mt-1 text-sm text-destructive">{{ errors.product_id }}</p>
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
                        placeholder="Напишите отзыв о товаре..."
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
                        placeholder="Имя клиента (если нет привязки к пользователю)"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>

                <!-- Фотографии (через MediaSelector) -->
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

                <!-- Кнопки -->
                <div class="flex items-center gap-4 pt-4 border-t border-border">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ loading ? 'Создание...' : 'Создать отзыв' }}
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
import { reviewsAPI, ordersAPI, productsAPI } from '../../utils/api.js';
import MediaSelector from '../../components/admin/MediaSelector.vue';

export default {
    name: 'ReviewCreate',
    components: {
        MediaSelector,
    },
    data() {
        return {
            orders: [],
            products: [],
            showMediaSelector: false,
            selectedPhotos: [],
            form: {
                order_id: null,
                product_id: null,
                rating: 5,
                comment: '',
                customer_name: '',
                photos: [],
                status: 'pending',
            },
            errors: {},
            loading: false,
        };
    },
    mounted() {
        this.loadOrders();
        this.loadProducts();
        
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
        async loadProducts() {
            try {
                const response = await productsAPI.getAll();
                this.products = response.data?.data || response.data || [];
            } catch (error) {
                console.error('Ошибка загрузки товаров:', error);
            }
        },
        handleOrderChange() {
            if (this.form.order_id) {
                const order = this.orders.find(o => o.id === this.form.order_id);
                if (order && order.items && order.items.length > 0) {
                    // Автоматически выбираем первый товар из заказа
                    this.form.product_id = order.items[0].product_id;
                }
            }
        },
        handleMediaSelect(files) {
            this.selectedPhotos = Array.isArray(files) ? files : [files];
        },
        removePhoto(photoId) {
            this.selectedPhotos = this.selectedPhotos.filter(p => p.id !== photoId);
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                // Добавляем ID фотографий в форму
                this.form.photos = this.selectedPhotos.map(p => p.id);
                
                await reviewsAPI.create(this.form);
                this.$router.push('/reviews');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка создания отзыва');
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>




