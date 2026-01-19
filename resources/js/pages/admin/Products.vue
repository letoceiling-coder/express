<template>
    <div class="products-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Товары</h1>
                <p class="text-muted-foreground mt-1">Управление товарами • Перетащите товары для изменения порядка</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="hasPositionChanges"
                    @click="handleSavePositions"
                    class="h-10 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2"
                    :disabled="savingPositions"
                >
                    <span v-if="savingPositions">Сохранение...</span>
                    <span v-else>💾 Сохранить порядок</span>
                </button>
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
                    @click="handleExportZip"
                    class="h-10 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 inline-flex items-center gap-2"
                    :disabled="exporting"
                >
                    <span v-if="exporting">...</span>
                    <span v-else>📦 ZIP + фото</span>
                </button>
                <button
                    @click="openImportDialog"
                    class="h-10 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 inline-flex items-center gap-2"
                >
                    <span>📤 Импорт</span>
                </button>
                <router-link
                    to="/products/create"
                    class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
                >
                    <span>+</span>
                    <span>Создать товар</span>
                </router-link>
            </div>
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
                        <option value="position">По позиции</option>
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
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground w-12"></th>
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
                <tbody class="divide-y divide-border" id="products-tbody">
                    <tr 
                        v-for="(product, index) in filteredProducts" 
                        :key="product.id"
                        :data-id="product.id"
                        :class="[
                            'cursor-move hover:bg-muted/50 transition-colors',
                            draggedIndex === index ? 'opacity-50 bg-blue-100' : '',
                            draggedOverIndex === index ? 'border-t-2 border-blue-500' : ''
                        ]"
                        draggable="true"
                        @dragstart="handleDragStart($event, index)"
                        @dragover.prevent="handleDragOver($event, index)"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop($event, index)"
                        @dragend="handleDragEnd"
                    >
                        <td class="px-6 py-4">
                            <div class="cursor-grab active:cursor-grabbing text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="9" cy="12" r="1"></circle>
                                    <circle cx="9" cy="5" r="1"></circle>
                                    <circle cx="9" cy="19" r="1"></circle>
                                    <circle cx="15" cy="12" r="1"></circle>
                                    <circle cx="15" cy="5" r="1"></circle>
                                    <circle cx="15" cy="19" r="1"></circle>
                                </svg>
                            </div>
                        </td>
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

        <!-- Диалог импорта -->
        <div
            v-if="showImportDialog"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showImportDialog = false"
        >
            <div class="bg-card rounded-lg border border-border p-6 max-w-md w-full mx-4">
                <h2 class="text-xl font-bold text-foreground mb-4">Импорт товаров</h2>
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
                    <div>
                        <label class="text-sm font-medium text-foreground mb-2 block">
                            Архив с изображениями (ZIP, опционально)
                        </label>
                        <input
                            ref="imagesArchiveInput"
                            type="file"
                            accept=".zip"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
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
import { productsAPI, categoriesAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

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
            sortBy: 'position',
            exporting: false,
            showImportDialog: false,
            selectedFile: null,
            importing: false,
            importError: null,
            importSuccess: null,
            hasPositionChanges: false,
            savingPositions: false,
            draggedIndex: null,
            draggedOverIndex: null,
        };
    },
    computed: {
        filteredProducts() {
            // Гарантируем, что products всегда массив
            const products = Array.isArray(this.products) ? this.products : [];
            let filtered = [...products];

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
                if (this.sortBy === 'position') {
                    const posA = a.position !== undefined ? a.position : (a.sort_order || 0);
                    const posB = b.position !== undefined ? b.position : (b.sort_order || 0);
                    if (posA !== posB) return posA - posB;
                    return (a.id || 0) - (b.id || 0);
                } else if (this.sortBy === 'sort_order') {
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
                // Гарантируем, что products всегда массив
                this.products = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки товаров';
                this.products = []; // В случае ошибки устанавливаем пустой массив
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
            const result = await swal.confirm(
                `Вы уверены, что хотите удалить товар "${product.name}"?`,
                'Удаление товара',
                'Удалить',
                'Отмена'
            );

            if (!result.isConfirmed) {
                return;
            }

            try {
                await productsAPI.delete(product.id);
                await this.loadProducts();
                await swal.success('Товар успешно удален');
            } catch (error) {
                await swal.error(error.message || 'Ошибка удаления товара');
            }
        },

        async handleExportCsv() {
            this.exporting = true;
            try {
                await productsAPI.exportCsv();
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
                await productsAPI.exportExcel();
                await swal.success('Экспорт в Excel выполнен успешно');
            } catch (error) {
                await swal.error(error.message || 'Ошибка экспорта в Excel');
            } finally {
                this.exporting = false;
            }
        },

        async handleExportZip() {
            this.exporting = true;
            try {
                await productsAPI.exportZip();
                await swal.success('Экспорт в ZIP с фото выполнен успешно');
            } catch (error) {
                await swal.error(error.message || 'Ошибка экспорта в ZIP');
            } finally {
                this.exporting = false;
            }
        },

        openImportDialog() {
            // Сбрасываем состояние при открытии диалога
            this.selectedFile = null;
            this.importError = null;
            this.importSuccess = null;
            this.showImportDialog = true;
            
            // Очищаем поля ввода файлов
            this.$nextTick(() => {
                if (this.$refs.importFileInput) {
                    this.$refs.importFileInput.value = '';
                }
                if (this.$refs.imagesArchiveInput) {
                    this.$refs.imagesArchiveInput.value = '';
                }
            });
        },

        handleFileSelect(event) {
            const file = event.target.files?.[0] || null;
            this.selectedFile = file;
            this.importError = null;
            this.importSuccess = null;
            
            if (file) {
                console.log('Файл выбран:', file.name, file.size);
            } else {
                console.log('Файл не выбран');
            }
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
                const imagesArchive = this.$refs.imagesArchiveInput?.files[0] || null;
                const result = await productsAPI.import(this.selectedFile, imagesArchive);
                
                this.importSuccess = result.message || 'Товары успешно импортированы';
                
                // Перезагружаем список товаров
                await this.loadProducts();
                
                // Закрываем диалог через 2 секунды
                setTimeout(() => {
                    this.showImportDialog = false;
                    this.selectedFile = null;
                    if (this.$refs.importFileInput) {
                        this.$refs.importFileInput.value = '';
                    }
                    if (this.$refs.imagesArchiveInput) {
                        this.$refs.imagesArchiveInput.value = '';
                    }
                    this.importSuccess = null;
                }, 2000);
            } catch (error) {
                this.importError = error.message || 'Ошибка импорта товаров';
            } finally {
                this.importing = false;
            }
        },

        handleDragStart(event, index) {
            this.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target);
            event.target.style.opacity = '0.5';
        },

        handleDragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            
            const tbody = document.getElementById('products-tbody');
            if (!tbody) return;
            
            const afterElement = this.getDragAfterElement(tbody, event.clientY);
            
            if (afterElement == null) {
                tbody.appendChild(event.currentTarget);
            } else {
                tbody.insertBefore(event.currentTarget, afterElement);
            }
        },

        getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('tr:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        },

        handleDrop(event, dropIndex) {
            event.preventDefault();
            event.stopPropagation();
            
            if (this.draggedIndex === null || this.draggedIndex === dropIndex) {
                this.draggedIndex = null;
                this.draggedOverIndex = null;
                return;
            }

            // Работаем с отфильтрованными продуктами для визуального порядка
            const filtered = [...this.filteredProducts];
            const draggedProduct = filtered[this.draggedIndex];
            
            // Удаляем из старой позиции и вставляем в новую
            filtered.splice(this.draggedIndex, 1);
            filtered.splice(dropIndex, 0, draggedProduct);
            
            // Обновляем позиции в исходном массиве products
            // Находим все продукты из filtered в исходном массиве и обновляем их порядок
            const reorderedProducts = [...this.products];
            const filteredIds = filtered.map(p => p.id);
            
            // Сортируем исходный массив по порядку filtered
            reorderedProducts.sort((a, b) => {
                const indexA = filteredIds.indexOf(a.id);
                const indexB = filteredIds.indexOf(b.id);
                
                // Если продукт не в filtered, оставляем его на месте
                if (indexA === -1) return -1;
                if (indexB === -1) return 1;
                
                return indexA - indexB;
            });
            
            this.products = reorderedProducts;
            this.hasPositionChanges = true;
            this.draggedIndex = null;
            this.draggedOverIndex = null;
        },

        handleDragEnd(event) {
            event.target.style.opacity = '';
            this.draggedIndex = null;
            this.draggedOverIndex = null;
        },

        async handleSavePositions() {
            this.savingPositions = true;
            try {
                // Используем исходный массив products, а не filteredProducts
                // чтобы сохранить позиции для всех товаров
                const positions = this.products.map((product, index) => ({
                    id: parseInt(product.id),
                    position: index,
                }));
                
                await productsAPI.updatePositions(positions);
                
                // Обновляем позиции в локальном массиве
                positions.forEach(({ id, position }) => {
                    const product = this.products.find(p => parseInt(p.id) === id);
                    if (product) {
                        product.position = position;
                    }
                });
                
                await swal.success('Порядок товаров успешно сохранён');
                this.hasPositionChanges = false;
                
                // Перезагружаем товары для синхронизации с сервером
                await this.loadProducts();
            } catch (error) {
                console.error('Error saving positions:', error);
                await swal.error(error.message || 'Ошибка при сохранении порядка');
            } finally {
                this.savingPositions = false;
            }
        },
    },
};
</script>

<style scoped>
.products-page tbody tr[draggable="true"] {
    cursor: move;
}

.products-page tbody tr[draggable="true"]:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.products-page tbody tr.dragging {
    opacity: 0.5;
}

.products-page tbody tr.drag-over {
    border-top: 2px solid #3b82f6;
}
</style>
