<template>
    <div class="categories-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Категории</h1>
                <p class="text-muted-foreground mt-1">Управление категориями товаров</p>
            </div>
            <router-link
                to="/categories/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать категорию</span>
            </router-link>
        </div>

        <!-- Поиск и фильтры -->
        <div class="bg-card rounded-lg border border-border p-4 mb-6">
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="text-sm font-medium text-foreground mb-1 block">Поиск</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Поиск по названию..."
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус</label>
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="true">Активные</option>
                        <option value="false">Неактивные</option>
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
                        <option value="created_at">По дате</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка категорий...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица категорий -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Изображение</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Название</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Описание</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Порядок</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="category in filteredCategories" :key="category.id">
                        <td class="px-6 py-4">
                            <img
                                v-if="category.image?.url"
                                :src="category.image.url"
                                :alt="category.name"
                                class="w-16 h-16 object-cover rounded-lg"
                            />
                            <div v-else class="w-16 h-16 bg-muted rounded-lg flex items-center justify-center">
                                <span class="text-muted-foreground text-xs">Нет фото</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-foreground">{{ category.name }}</div>
                            <div v-if="category.slug" class="text-sm text-muted-foreground">{{ category.slug }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-muted-foreground line-clamp-2">
                                {{ category.description || '—' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ category.sort_order || 0 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="category.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                class="px-2 py-1 rounded-full text-xs font-medium"
                            >
                                {{ category.is_active ? 'Активна' : 'Неактивна' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="`/categories/${category.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Редактировать
                                </router-link>
                                <button
                                    @click="handleDelete(category)"
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
            <div v-if="filteredCategories.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Категории не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
import { categoriesAPI } from '../../utils/api.js';

export default {
    name: 'Categories',
    data() {
        return {
            categories: [],
            loading: false,
            error: null,
            searchQuery: '',
            statusFilter: '',
            sortBy: 'sort_order',
        };
    },
    computed: {
        filteredCategories() {
            // Гарантируем, что categories всегда массив
            const categories = Array.isArray(this.categories) ? this.categories : [];
            let filtered = [...categories];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(cat =>
                    cat.name.toLowerCase().includes(query) ||
                    (cat.slug && cat.slug.toLowerCase().includes(query))
                );
            }

            // Фильтр по статусу
            if (this.statusFilter !== '') {
                const isActive = this.statusFilter === 'true';
                filtered = filtered.filter(cat => cat.is_active === isActive);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'sort_order') {
                    return (a.sort_order || 0) - (b.sort_order || 0);
                } else if (this.sortBy === 'name') {
                    return a.name.localeCompare(b.name);
                } else if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadCategories();
    },
    methods: {
        async loadCategories() {
            this.loading = true;
            this.error = null;
            try {
                const response = await categoriesAPI.getAll();
                // Гарантируем, что categories всегда массив
                this.categories = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки категорий';
                this.categories = []; // В случае ошибки устанавливаем пустой массив
            } finally {
                this.loading = false;
            }
        },
        async handleDelete(category) {
            if (!confirm(`Вы уверены, что хотите удалить категорию "${category.name}"?`)) {
                return;
            }

            try {
                await categoriesAPI.delete(category.id);
                await this.loadCategories();
            } catch (error) {
                alert(error.message || 'Ошибка удаления категории');
            }
        },
    },
};
</script>
