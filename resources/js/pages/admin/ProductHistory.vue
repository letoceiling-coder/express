<template>
    <div class="product-history-page">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">История изменений товара</h1>
                    <p v-if="product" class="text-muted-foreground mt-1">
                        {{ product.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <router-link
                        v-if="product"
                        :to="`/products/${product.id}/edit`"
                        class="h-10 px-4 bg-accent/10 text-accent rounded-lg hover:bg-accent/20 inline-flex items-center"
                    >
                        Редактировать товар
                    </router-link>
                    <router-link
                        to="/products"
                        class="h-10 px-4 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 inline-flex items-center"
                    >
                        К списку товаров
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !history.length" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка истории...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
            <router-link
                to="/products"
                class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
                Вернуться к списку
            </router-link>
        </div>

        <!-- История -->
        <div v-else class="space-y-4">
            <!-- Фильтры -->
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="flex gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Тип изменения</label>
                        <select
                            v-model="actionFilter"
                            class="h-10 px-3 rounded-lg border border-input bg-background"
                        >
                            <option value="">Все</option>
                            <option value="created">Создание</option>
                            <option value="updated">Изменение</option>
                            <option value="deleted">Удаление</option>
                            <option value="restored">Восстановление</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Список изменений -->
            <div v-if="filteredHistory.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
                <p class="text-muted-foreground">История изменений пуста</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="item in filteredHistory"
                    :key="item.id"
                    class="bg-card rounded-lg border border-border p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800': item.action === 'created',
                                        'bg-blue-100 text-blue-800': item.action === 'updated',
                                        'bg-red-100 text-red-800': item.action === 'deleted',
                                        'bg-yellow-100 text-yellow-800': item.action === 'restored',
                                    }"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ getActionLabel(item.action) }}
                                </span>
                                <span class="text-sm text-muted-foreground">
                                    {{ formatDate(item.created_at) }}
                                </span>
                            </div>
                            <p v-if="item.user" class="text-sm text-foreground">
                                Изменено пользователем: {{ item.user.name || item.user.email || 'Неизвестно' }}
                            </p>
                        </div>
                    </div>

                    <!-- Детали изменения -->
                    <div v-if="item.action === 'updated' && item.changes" class="space-y-2">
                        <div
                            v-for="(change, field) in item.changes"
                            :key="field"
                            class="border-l-2 border-accent pl-4 py-2"
                        >
                            <div class="font-medium text-foreground mb-1">{{ getFieldLabel(field) }}</div>
                            <div class="text-sm space-y-1">
                                <div class="text-muted-foreground">
                                    <span class="font-medium">Было:</span>
                                    <span class="ml-2">{{ formatValue(change.old) }}</span>
                                </div>
                                <div class="text-foreground">
                                    <span class="font-medium">Стало:</span>
                                    <span class="ml-2">{{ formatValue(change.new) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Полные данные для создания -->
                    <div v-else-if="item.action === 'created' && item.changes" class="text-sm text-muted-foreground">
                        Товар создан
                    </div>

                    <!-- Полные данные для удаления -->
                    <div v-else-if="item.action === 'deleted' && item.changes" class="text-sm text-muted-foreground">
                        Товар удален
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { productsAPI } from '../../utils/api.js';

export default {
    name: 'ProductHistory',
    data() {
        return {
            product: null,
            history: [],
            loading: false,
            error: null,
            actionFilter: '',
        };
    },
    computed: {
        filteredHistory() {
            if (!this.actionFilter) {
                return this.history;
            }
            return this.history.filter(item => item.action === this.actionFilter);
        },
    },
    mounted() {
        this.loadHistory();
    },
    methods: {
        async loadHistory() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                
                // Загружаем товар для отображения названия
                try {
                    const productResponse = await productsAPI.getById(id);
                    this.product = productResponse.data;
                } catch (e) {
                    // Товар может быть удален, это нормально
                }

                // Загружаем историю
                const response = await productsAPI.getHistory(id);
                this.history = response.data || [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки истории';
            } finally {
                this.loading = false;
            }
        },
        getActionLabel(action) {
            const labels = {
                created: 'Создан',
                updated: 'Изменен',
                deleted: 'Удален',
                restored: 'Восстановлен',
            };
            return labels[action] || action;
        },
        getFieldLabel(field) {
            const labels = {
                name: 'Название',
                description: 'Описание',
                short_description: 'Краткое описание',
                price: 'Цена',
                compare_price: 'Цена для сравнения',
                category_id: 'Категория',
                sku: 'Артикул',
                barcode: 'Штрих-код',
                stock_quantity: 'Количество на складе',
                is_available: 'Доступность',
                is_weight_product: 'Товар на вес',
                weight: 'Вес',
                image_id: 'Изображение',
                gallery_ids: 'Галерея',
                video_id: 'Видео',
                sort_order: 'Порядок сортировки',
                meta_title: 'Meta Title',
                meta_description: 'Meta Description',
            };
            return labels[field] || field;
        },
        formatValue(value) {
            if (value === null || value === undefined) {
                return '—';
            }
            if (typeof value === 'boolean') {
                return value ? 'Да' : 'Нет';
            }
            if (typeof value === 'number') {
                return value.toLocaleString('ru-RU');
            }
            if (Array.isArray(value)) {
                return value.length > 0 ? `[${value.length} элементов]` : 'Пусто';
            }
            return String(value);
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    },
};
</script>



