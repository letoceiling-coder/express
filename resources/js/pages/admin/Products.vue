<template>
    <div class="products-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Товары</h1>
                <p class="text-muted-foreground mt-1">Управление товарами</p>
            </div>
            <router-link
                to="/products/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать товар</span>
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
                        placeholder="Поиск по названию, артикулу, штрих-коду..."
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Категория</label>
                    <select
                        v-model="categoryFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все категории</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус</label>
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="true">Доступны</option>
                        <option value="false">Недоступны</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Сортировка</label>
                    <select
                        v-model="sortBy"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="sort_order">По порядку</option>
                        <option value="name">По названию</option>
                        <option value="price">По цене</option>
                        <option value="created_at">По дате</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка товаров...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица товаров -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Изображение</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Название</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Категория</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Артикул</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Цена</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Остаток</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="product in filteredProducts" :key="product.id">
                        <td class="px-6 py-4">
                            <img
                                v-if="product.image?.url"
                                :src="product.image.url"
                                :alt="product.name"
                                class="w-16 h-16 object-cover rounded-lg"
                            />
                            <div v-else class="w-16 h-16 bg-muted rounded-lg flex items-center justify-center">
                                <span class="text-muted-foreground text-xs">Нет фото</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-foreground">{{ product.name }}</div>
                            <div v-if="product.slug" class="text-sm text-muted-foreground">{{ product.slug }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="product.category" class="text-sm text-foreground">
                                {{ product.category.name }}
                            </span>
                            <span v-else class="text-sm text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ product.sku || '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-foreground">
                                {{ Number(product.price).toLocaleString('ru-RU') }} ₽
                            </div>
                            <div v-if="product.compare_price" class="text-xs text-muted-foreground line-through">
                                {{ Number(product.compare_price).toLocaleString('ru-RU') }} ₽
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ product.stock_quantity || 0 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="product.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                class="px-2 py-1 rounded-full text-xs font-medium"
                            >
                                {{ product.is_available ? 'Доступен' : 'Недоступен' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="`/products/${product.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Редактировать
                                </router-link>
                                <router-link
                                    :to="`/products/${product.id}/history`"
                                    class="h-8 px-3 text-sm bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                                >
                                    История
                                </router-link>
                                <button
                                    @click="handleDelete(product)"
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
            <div v-if="filteredProducts.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Товары не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
import { productsAPI, categoriesAPI } from '../../utils/api.js';

export default {
    name: 'Products',
    data() {
        return {
            products: [],
            categories: [],
            loading: false,
            error: null,
            searchQuery: '',
            categoryFilter: '',
            statusFilter: '',
            sortBy: 'sort_order',
        };
    },
    computed: {
        filteredProducts() {
            let filtered = [...this.products];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(product =>
                    product.name.toLowerCase().includes(query) ||
                    (product.sku && product.sku.toLowerCase().includes(query)) ||
                    (product.barcode && product.barcode.toLowerCase().includes(query))
                );
            }

            // Фильтр по категории
            if (this.categoryFilter) {
                filtered = filtered.filter(product =>
                    product.category_id && Number(product.category_id) === Number(this.categoryFilter)
                );
            }

            // Фильтр по статусу
            if (this.statusFilter !== '') {
                const isAvailable = this.statusFilter === 'true';
                filtered = filtered.filter(product => product.is_available === isAvailable);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'sort_order') {
                    return (a.sort_order || 0) - (b.sort_order || 0);
                } else if (this.sortBy === 'name') {
                    return a.name.localeCompare(b.name);
                } else if (this.sortBy === 'price') {
                    return Number(a.price) - Number(b.price);
                } else if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadProducts();
        this.loadCategories();
    },
    methods: {
        async loadProducts() {
            this.loading = true;
            this.error = null;
            try {
                const response = await productsAPI.getAll();
                this.products = response.data || [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки товаров';
            } finally {
                this.loading = false;
            }
        },
        async loadCategories() {
            try {
                const response = await categoriesAPI.getAll();
                this.categories = response.data || [];
            } catch (error) {
                console.error('Ошибка загрузки категорий:', error);
            }
        },
        async handleDelete(product) {
            if (!confirm(`Вы уверены, что хотите удалить товар "${product.name}"?`)) {
                return;
            }

            try {
                await productsAPI.delete(product.id);
                await this.loadProducts();
            } catch (error) {
                alert(error.message || 'Ошибка удаления товара');
            }
        },
    },
};
</script>
