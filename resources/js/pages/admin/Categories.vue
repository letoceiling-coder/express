<template>
    <div class="categories-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Категории</h1>
                <p class="text-muted-foreground mt-1">Управление категориями товаров</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="handleExportCsv"
                    class="h-10 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 inline-flex items-center gap-2"
                    :disabled="exporting"
                >
                    <span v-if="exporting">...</span>
                    <span v-else>📥 CSV</span>
                </button>
                <button
                    @click="handleExportExcel"
                    class="h-10 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2"
                    :disabled="exporting"
                >
                    <span v-if="exporting">...</span>
                    <span v-else>📥 Excel</span>
                </button>
                <button
                    @click="showImportDialog = true"
                    class="h-10 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 inline-flex items-center gap-2"
                >
                    <span>📤 Импорт</span>
                </button>
                <router-link
                    to="/categories/create"
                    class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
                >
                    <span>+</span>
                    <span>Создать категорию</span>
                </router-link>
            </div>
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
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground w-10"></th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Изображение</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Название</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Описание</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Порядок</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr 
                        v-for="(category, index) in filteredCategories" 
                        :key="category.id"
                        :draggable="true"
                        @dragstart="handleDragStart($event, index)"
                        @dragover.prevent="handleDragOver($event, index)"
                        @dragleave="handleDragLeave($event)"
                        @drop.prevent="handleDrop($event, index)"
                        @dragend="handleDragEnd"
                        :class="{
                            'opacity-50': draggedIndex === index,
                            'bg-blue-50 dark:bg-blue-900/20': draggedOverIndex === index
                        }"
                        class="cursor-move transition-colors"
                    >
                        <td class="px-6 py-4 w-10">
                            <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                            </svg>
                        </td>
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

        <!-- Диалог импорта -->
        <div
            v-if="showImportDialog"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showImportDialog = false"
        >
            <div class="bg-card rounded-lg border border-border p-6 max-w-md w-full mx-4">
                <h2 class="text-xl font-bold text-foreground mb-4">Импорт категорий</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-foreground mb-2 block">
                            Файл (CSV или Excel)
                        </label>
                        <input
                            ref="importFileInput"
                            type="file"
                            accept=".csv,.xlsx,.xls"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            @change="handleFileSelect"
                        />
                    </div>
                    <div v-if="importError" class="bg-destructive/10 border border-destructive/20 rounded-lg p-3">
                        <p class="text-destructive text-sm">{{ importError }}</p>
                    </div>
                    <div v-if="importSuccess" class="bg-green-100 border border-green-300 rounded-lg p-3">
                        <p class="text-green-800 text-sm">{{ importSuccess }}</p>
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button
                        @click="showImportDialog = false"
                        class="flex-1 h-10 px-4 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                    >
                        Отмена
                    </button>
                    <button
                        @click="handleImport"
                        class="flex-1 h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
                        :disabled="importing || !selectedFile"
                    >
                        <span v-if="importing">Импорт...</span>
                        <span v-else>Импортировать</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { categoriesAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

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
            draggedIndex: null,
            draggedOverIndex: null,
            exporting: false,
            showImportDialog: false,
            selectedFile: null,
            importing: false,
            importError: null,
            importSuccess: null,
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
                let categories = Array.isArray(response.data) ? response.data : [];
                
                // Сортируем по sort_order
                categories.sort((a, b) => {
                    const orderA = a.sort_order || 0;
                    const orderB = b.sort_order || 0;
                    if (orderA !== orderB) return orderA - orderB;
                    return a.name.localeCompare(b.name);
                });
                
                this.categories = categories;
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки категорий';
                this.categories = []; // В случае ошибки устанавливаем пустой массив
            } finally {
                this.loading = false;
            }
        },
        async handleDelete(category) {
            const result = await swal.confirm(
                `Вы уверены, что хотите удалить категорию "${category.name}"?`,
                'Удаление категории',
                'Удалить',
                'Отмена'
            );

            if (!result.isConfirmed) {
                return;
            }

            try {
                await categoriesAPI.delete(category.id);
                await this.loadCategories();
                await swal.success('Категория успешно удалена');
            } catch (error) {
                await swal.error(error.message || 'Ошибка удаления категории');
            }
        },
        
        handleDragStart(event, index) {
            this.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target);
        },
        
        handleDragOver(event, index) {
            event.preventDefault();
            this.draggedOverIndex = index;
        },
        
        handleDragLeave(event) {
            // Проверяем, что курсор действительно покинул элемент
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.draggedOverIndex = null;
            }
        },
        
        async handleDrop(event, dropIndex) {
            event.preventDefault();
            
            if (this.draggedIndex === null || this.draggedIndex === dropIndex) {
                this.draggedIndex = null;
                this.draggedOverIndex = null;
                return;
            }

            // Создаем копию массива категорий
            const categories = [...this.categories];
            const draggedCategory = categories[this.draggedIndex];
            
            // Удаляем элемент из старой позиции
            categories.splice(this.draggedIndex, 1);
            
            // Вставляем элемент в новую позицию
            categories.splice(dropIndex, 0, draggedCategory);
            
            // Обновляем sort_order для всех категорий
            const updatedCategories = categories.map((cat, index) => ({
                ...cat,
                sort_order: index,
            }));
            
            // Обновляем локальное состояние сразу для плавности UI
            this.categories = updatedCategories;
            this.draggedIndex = null;
            this.draggedOverIndex = null;

            try {
                // Отправляем обновленный порядок на сервер
                await categoriesAPI.updatePositions(
                    updatedCategories.map(cat => ({
                        id: cat.id,
                        sort_order: cat.sort_order || 0,
                    }))
                );
                // Порядок категорий обновлен успешно
                console.log('Порядок категорий обновлен');
            } catch (error) {
                console.error('Failed to update positions:', error);
                await swal.error('Ошибка при сохранении порядка категорий');
                // Откатываем изменения при ошибке
                await this.loadCategories();
            }
        },
        
        handleDragEnd() {
            this.draggedIndex = null;
            this.draggedOverIndex = null;
        },

        async handleExportCsv() {
            this.exporting = true;
            try {
                await categoriesAPI.exportCsv();
                await swal.success('Экспорт в CSV выполнен успешно');
            } catch (error) {
                await swal.error(error.message || 'Ошибка экспорта в CSV');
            } finally {
                this.exporting = false;
            }
        },

        async handleExportExcel() {
            this.exporting = true;
            try {
                await categoriesAPI.exportExcel();
                await swal.success('Экспорт в Excel выполнен успешно');
            } catch (error) {
                await swal.error(error.message || 'Ошибка экспорта в Excel');
            } finally {
                this.exporting = false;
            }
        },

        handleFileSelect(event) {
            this.selectedFile = event.target.files[0] || null;
            this.importError = null;
            this.importSuccess = null;
        },

        async handleImport() {
            if (!this.selectedFile) {
                this.importError = 'Выберите файл для импорта';
                return;
            }

            this.importing = true;
            this.importError = null;
            this.importSuccess = null;

            try {
                const result = await categoriesAPI.import(this.selectedFile);
                
                this.importSuccess = result.message || 'Категории успешно импортированы';
                
                // Перезагружаем список категорий
                await this.loadCategories();
                
                // Закрываем диалог через 2 секунды
                setTimeout(() => {
                    this.showImportDialog = false;
                    this.selectedFile = null;
                    if (this.$refs.importFileInput) {
                        this.$refs.importFileInput.value = '';
                    }
                    this.importSuccess = null;
                }, 2000);
            } catch (error) {
                this.importError = error.message || 'Ошибка импорта категорий';
            } finally {
                this.importing = false;
            }
        },
    },
};
</script>
