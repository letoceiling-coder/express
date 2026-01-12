<template>
    <div class="product-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать товар</h1>
            <p class="text-muted-foreground mt-1">Изменение товара</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !product" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка товара...</p>
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

        <!-- Форма -->
        <div v-else-if="product" class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-8">
                <!-- Основная информация -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">Основная информация</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Название <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            minlength="2"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.name }"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-destructive">{{ errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Краткое описание</label>
                        <input
                            v-model="form.short_description"
                            type="text"
                            maxlength="255"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Категория</label>
                        <select
                            v-model="form.category_id"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        >
                            <option :value="null">Без категории</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Цены -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">Цены</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Цена <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model.number="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                                :class="{ 'border-destructive': errors.price }"
                            />
                            <p v-if="errors.price" class="mt-1 text-sm text-destructive">{{ errors.price }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Цена для сравнения</label>
                            <input
                                v-model.number="form.compare_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>
                    </div>
                </div>

                <!-- Склад -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">Склад</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Артикул (SKU)</label>
                            <input
                                v-model="form.sku"
                                type="text"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Штрих-код</label>
                            <input
                                v-model="form.barcode"
                                type="text"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Количество на складе</label>
                            <input
                                v-model.number="form.stock_quantity"
                                type="number"
                                min="0"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="form.is_available"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-input"
                                />
                                <span class="text-sm font-medium text-foreground">Товар доступен</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Характеристики -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">Характеристики</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="form.is_weight_product"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-input"
                                />
                                <span class="text-sm font-medium text-foreground">Товар на вес</span>
                            </label>
                        </div>

                        <div v-if="form.is_weight_product">
                            <label class="block text-sm font-medium text-foreground mb-2">Вес (кг)</label>
                            <input
                                v-model.number="form.weight"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                        </div>
                    </div>
                </div>

                <!-- Медиа -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">Медиа</h2>
                    
                    <!-- Главное изображение -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Главное изображение</label>
                        <div class="flex items-center gap-4">
                            <img
                                v-if="selectedImage"
                                :src="selectedImage.url"
                                alt="Preview"
                                class="w-32 h-32 object-cover rounded-lg border border-border"
                            />
                            <div v-else class="w-32 h-32 bg-muted rounded-lg flex items-center justify-center">
                                <span class="text-muted-foreground text-sm">Нет изображения</span>
                            </div>
                            <div>
                                <button
                                    type="button"
                                    @click="showImageSelector = true"
                                    class="h-10 px-4 bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Выбрать из медиа
                                </button>
                                <button
                                    v-if="selectedImage"
                                    type="button"
                                    @click="selectedImage = null; form.image_id = null"
                                    class="ml-2 h-10 px-4 bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                                >
                                    Удалить
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Галерея -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Галерея изображений</label>
                        <div class="flex gap-2 flex-wrap mb-2">
                            <div
                                v-for="img in selectedGallery"
                                :key="img.id"
                                class="relative group"
                            >
                                <img
                                    :src="img.url"
                                    :alt="img.name"
                                    class="w-24 h-24 object-cover rounded-lg border border-border"
                                />
                                <button
                                    type="button"
                                    @click="removeFromGallery(img.id)"
                                    class="absolute -top-1 -right-1 w-5 h-5 bg-destructive text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showGallerySelector = true"
                            class="h-10 px-4 bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                        >
                            + Добавить изображения
                        </button>
                    </div>

                    <!-- Видео -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Видео</label>
                        <div class="flex items-center gap-4">
                            <video
                                v-if="selectedVideo"
                                :src="selectedVideo.url"
                                class="w-32 h-32 object-cover rounded-lg border border-border"
                                controls
                            ></video>
                            <div v-else class="w-32 h-32 bg-muted rounded-lg flex items-center justify-center">
                                <span class="text-muted-foreground text-sm">Нет видео</span>
                            </div>
                            <div>
                                <button
                                    type="button"
                                    @click="showVideoSelector = true"
                                    class="h-10 px-4 bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Выбрать видео
                                </button>
                                <button
                                    v-if="selectedVideo"
                                    type="button"
                                    @click="selectedVideo = null; form.video_id = null"
                                    class="ml-2 h-10 px-4 bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                                >
                                    Удалить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">SEO</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Meta Title</label>
                        <input
                            v-model="form.meta_title"
                            type="text"
                            maxlength="255"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Meta Description</label>
                        <textarea
                            v-model="form.meta_description"
                            rows="3"
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                        ></textarea>
                    </div>
                </div>

                <!-- Прочее -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">Прочее</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Порядок сортировки</label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
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
                        to="/products"
                        class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                    >
                        Отмена
                    </router-link>
                    <router-link
                        :to="`/products/${product.id}/history`"
                        class="h-10 px-6 bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                    >
                        История изменений
                    </router-link>
                </div>
            </form>
        </div>

        <!-- Медиа селекторы -->
        <MediaSelector
            :open="showImageSelector"
            :multiple="false"
            :allowedTypes="['photo']"
            :currentSelection="selectedImage ? [selectedImage] : []"
            @close="showImageSelector = false"
            @select="handleImageSelected"
        />

        <MediaSelector
            :open="showGallerySelector"
            :multiple="true"
            :allowedTypes="['photo']"
            :currentSelection="selectedGallery"
            @close="showGallerySelector = false"
            @select="handleGallerySelected"
        />

        <MediaSelector
            :open="showVideoSelector"
            :multiple="false"
            :allowedTypes="['video']"
            :currentSelection="selectedVideo ? [selectedVideo] : []"
            @close="showVideoSelector = false"
            @select="handleVideoSelected"
        />
    </div>
</template>

<script>
import { productsAPI, categoriesAPI } from '../../utils/api.js';
import MediaSelector from '../../components/admin/MediaSelector.vue';
import swal from '../../utils/swal.js';

export default {
    name: 'ProductEdit',
    components: {
        MediaSelector,
    },
    data() {
        return {
            product: null,
            categories: [],
            form: {
                name: '',
                description: '',
                short_description: '',
                category_id: null,
                price: 0,
                compare_price: null,
                sku: '',
                barcode: '',
                stock_quantity: 0,
                is_available: true,
                is_weight_product: false,
                weight: null,
                image_id: null,
                gallery_ids: [],
                video_id: null,
                meta_title: '',
                meta_description: '',
                sort_order: 0,
            },
            errors: {},
            loading: false,
            error: null,
            showImageSelector: false,
            showGallerySelector: false,
            showVideoSelector: false,
            selectedImage: null,
            selectedGallery: [],
            selectedVideo: null,
        };
    },
    mounted() {
        this.loadProduct();
        this.loadCategories();
    },
    methods: {
        async loadProduct() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                const response = await productsAPI.getById(id);
                this.product = response.data;
                
                // Заполняем форму
                this.form = {
                    name: this.product.name || '',
                    description: this.product.description || '',
                    short_description: this.product.short_description || '',
                    category_id: this.product.category_id || null,
                    price: Number(this.product.price) || 0,
                    compare_price: this.product.compare_price ? Number(this.product.compare_price) : null,
                    sku: this.product.sku || '',
                    barcode: this.product.barcode || '',
                    stock_quantity: this.product.stock_quantity || 0,
                    is_available: this.product.is_available !== undefined ? this.product.is_available : true,
                    is_weight_product: this.product.is_weight_product || false,
                    weight: this.product.weight ? Number(this.product.weight) : null,
                    image_id: this.product.image_id || null,
                    gallery_ids: this.product.gallery_ids || [],
                    video_id: this.product.video_id || null,
                    meta_title: this.product.meta_title || '',
                    meta_description: this.product.meta_description || '',
                    sort_order: this.product.sort_order || 0,
                };

                // Загружаем медиа
                if (this.product.image) {
                    this.selectedImage = this.product.image;
                }
                
                // Загружаем галерею (нужно получить медиа по ID)
                if (this.product.gallery_ids && this.product.gallery_ids.length > 0) {
                    // Временное решение - загружаем медиа по ID
                    // В будущем можно улучшить, если API будет возвращать полные объекты галереи
                    this.selectedGallery = [];
                }

                if (this.product.video) {
                    this.selectedVideo = this.product.video;
                }
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки товара';
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
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                const id = this.$route.params.id;
                const data = {
                    ...this.form,
                    gallery_ids: this.selectedGallery.map(img => img.id),
                };

                await productsAPI.update(id, data);
                this.$router.push('/products');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    await swal.error(error.message || 'Ошибка обновления товара');
                }
            } finally {
                this.loading = false;
            }
        },
        handleImageSelected(media) {
            this.selectedImage = media;
            this.form.image_id = media.id;
            this.showImageSelector = false;
        },
        handleGallerySelected(media) {
            const items = Array.isArray(media) ? media : [media];
            items.forEach(item => {
                if (!this.selectedGallery.find(img => img.id === item.id)) {
                    this.selectedGallery.push(item);
                }
            });
            this.showGallerySelector = false;
        },
        removeFromGallery(id) {
            this.selectedGallery = this.selectedGallery.filter(img => img.id !== id);
        },
        handleVideoSelected(media) {
            this.selectedVideo = media;
            this.form.video_id = media.id;
            this.showVideoSelector = false;
        },
    },
};
</script>




