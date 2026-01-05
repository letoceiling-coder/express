<template>
    <div class="reviews-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Отзывы</h1>
                <p class="text-muted-foreground mt-1">Управление отзывами</p>
            </div>
            <router-link
                to="/reviews/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать отзыв</span>
            </router-link>
        </div>

        <!-- Поиск и фильтры -->
        <div class="bg-card rounded-lg border border-border p-4 mb-6">
            <div class="flex gap-4 items-end flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-sm font-medium text-foreground mb-1 block">Поиск</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Поиск по товару, имени клиента..."
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Товар</label>
                    <select
                        v-model="productFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option :value="null">Все товары</option>
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Рейтинг</label>
                    <select
                        v-model="ratingFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="5">5 звезд</option>
                        <option value="4">4 звезды</option>
                        <option value="3">3 звезды</option>
                        <option value="2">2 звезды</option>
                        <option value="1">1 звезда</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус</label>
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="pending">На модерации</option>
                        <option value="approved">Одобрен</option>
                        <option value="rejected">Отклонен</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Сортировка</label>
                    <select
                        v-model="sortBy"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="created_at">По дате</option>
                        <option value="rating">По рейтингу</option>
                        <option value="status">По статусу</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка отзывов...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица отзывов -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Товар</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Клиент</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Рейтинг</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Отзыв</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Дата</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="review in filteredReviews" :key="review.id">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-foreground">#{{ review.id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <router-link
                                v-if="review.product_id"
                                :to="`/products/${review.product_id}/edit`"
                                class="text-sm font-medium text-accent hover:underline"
                            >
                                {{ review.product?.name || 'Товар удален' }}
                            </router-link>
                            <span v-else class="text-sm text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-foreground">{{ review.user?.name || review.customer_name || 'Аноним' }}</div>
                            <div v-if="review.order_id" class="text-xs text-muted-foreground">
                                Заказ #{{ review.order?.order_id || review.order_id }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1">
                                <span class="text-yellow-500">★</span>
                                <span class="text-sm font-medium text-foreground">{{ review.rating }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-foreground line-clamp-2">{{ review.comment || '—' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <select
                                :value="review.status"
                                @change="handleStatusChange(review.id, $event.target.value)"
                                class="text-xs px-2 py-1 rounded border border-input bg-background"
                                :class="getStatusClass(review.status)"
                            >
                                <option value="pending">На модерации</option>
                                <option value="approved">Одобрен</option>
                                <option value="rejected">Отклонен</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatDate(review.created_at) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="`/reviews/${review.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Открыть
                                </router-link>
                                <button
                                    @click="handleDelete(review)"
                                    class="h-8 px-3 text-sm bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                                >
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Пусто -->
            <div v-if="filteredReviews.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Отзывы не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
import { reviewsAPI, productsAPI } from '../../utils/api.js';

export default {
    name: 'Reviews',
    data() {
        return {
            reviews: [],
            products: [],
            loading: false,
            error: null,
            searchQuery: '',
            productFilter: null,
            ratingFilter: '',
            statusFilter: '',
            sortBy: 'created_at',
        };
    },
    computed: {
        filteredReviews() {
            let filtered = [...this.reviews];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(review =>
                    (review.product?.name && review.product.name.toLowerCase().includes(query)) ||
                    (review.user?.name && review.user.name.toLowerCase().includes(query)) ||
                    (review.customer_name && review.customer_name.toLowerCase().includes(query)) ||
                    (review.comment && review.comment.toLowerCase().includes(query))
                );
            }

            // Фильтр по товару
            if (this.productFilter) {
                filtered = filtered.filter(review => review.product_id === this.productFilter);
            }

            // Фильтр по рейтингу
            if (this.ratingFilter) {
                filtered = filtered.filter(review => review.rating === Number(this.ratingFilter));
            }

            // Фильтр по статусу
            if (this.statusFilter) {
                filtered = filtered.filter(review => review.status === this.statusFilter);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (this.sortBy === 'rating') {
                    return Number(b.rating) - Number(a.rating);
                } else if (this.sortBy === 'status') {
                    return a.status.localeCompare(b.status);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadReviews();
        this.loadProducts();
    },
    methods: {
        async loadReviews() {
            this.loading = true;
            this.error = null;
            try {
                const response = await reviewsAPI.getAll();
                this.reviews = response.data?.data || response.data || [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки отзывов';
            } finally {
                this.loading = false;
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
        async handleStatusChange(reviewId, newStatus) {
            try {
                await reviewsAPI.updateStatus(reviewId, newStatus);
                await this.loadReviews();
            } catch (error) {
                alert(error.message || 'Ошибка изменения статуса');
                await this.loadReviews();
            }
        },
        async handleDelete(review) {
            if (!confirm(`Вы уверены, что хотите удалить отзыв #${review.id}?`)) {
                return;
            }

            try {
                await reviewsAPI.delete(review.id);
                await this.loadReviews();
            } catch (error) {
                alert(error.message || 'Ошибка удаления отзыва');
            }
        },
        getStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                approved: 'bg-green-100 text-green-800',
                rejected: 'bg-red-100 text-red-800',
            };
            return classes[status] || '';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        },
    },
};
</script>



