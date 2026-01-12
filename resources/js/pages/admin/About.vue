<template>
    <div class="about-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">О нас</h1>
            <p class="text-muted-foreground mt-1">Редактирование информации о компании</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !data" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка данных...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Форма -->
        <div v-else class="space-y-6">
            <div class="bg-card rounded-lg border border-border p-6">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Basic Info -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Основная информация</h2>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Название компании *
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="СВОЙ ХЛЕБ"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                required
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Телефон
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="+7 982 682-43-68"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Адрес
                            </label>
                            <input
                                v-model="form.address"
                                type="text"
                                placeholder="поселок Исток, ул. Главная, дом 15"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                        </div>
                    </div>

                    <!-- Cover Image -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Обложка</h2>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                URL обложки
                            </label>
                            <div class="flex gap-2">
                                <input
                                    v-model="form.cover_image_url"
                                    type="text"
                                    placeholder="/upload/..."
                                    class="flex-1 h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                                <button
                                    type="button"
                                    @click="showImageSelector = true"
                                    class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 whitespace-nowrap"
                                >
                                    Выбрать
                                </button>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Выберите изображение из медиа-библиотеки или введите URL вручную
                            </p>
                            <img
                                v-if="form.cover_image_url"
                                :src="form.cover_image_url"
                                alt="Preview"
                                class="mt-3 h-32 w-full rounded-lg object-cover border border-border"
                                @error="($event.target.style.display = 'none')"
                            />
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Описание</h2>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Описание
                            </label>
                            <textarea
                                v-model="form.description"
                                placeholder="Представляем вашему вниманию компанию..."
                                class="w-full min-h-[120px] px-3 py-2 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                rows="5"
                            ></textarea>
                            <p class="text-xs text-muted-foreground mt-1">
                                Поддерживается многострочный текст
                            </p>
                        </div>
                    </div>

                    <!-- Bullets -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Список пунктов</h2>
                        <p class="text-xs text-muted-foreground mb-2">
                            Важные моменты, которые будут отображаться в виде списка
                        </p>
                        
                        <div class="space-y-3">
                            <div
                                v-for="(bullet, index) in form.bullets"
                                :key="index"
                                class="flex items-center gap-2"
                            >
                                <textarea
                                    v-model="form.bullets[index]"
                                    placeholder="Введите пункт списка..."
                                    class="flex-1 min-h-[60px] px-3 py-2 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    rows="2"
                                ></textarea>
                                <button
                                    v-if="form.bullets.length > 0"
                                    type="button"
                                    @click="handleRemoveBullet(index)"
                                    class="p-2 text-destructive hover:text-destructive/80"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <button
                            type="button"
                            @click="handleAddBullet"
                            class="w-full h-10 px-4 rounded-lg border border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground inline-flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Добавить пункт
                        </button>
                    </div>

                    <!-- Yandex Maps -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Яндекс.Карты</h2>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                URL Яндекс.Карт
                            </label>
                            <input
                                v-model="form.yandex_maps_url"
                                type="url"
                                placeholder="https://yandex.ru/maps/..."
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-4 pt-4 border-t border-border">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2 disabled:opacity-50"
                        >
                            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MediaSelector Modal -->
        <MediaSelector
            :open="showImageSelector"
            :multiple="false"
            :allowedTypes="['photo']"
            :currentSelection="form.cover_image_url ? [{ url: form.cover_image_url }] : []"
            @close="showImageSelector = false"
            @select="handleImageSelected"
        />
    </div>
</template>

<script>
import { apiGet, apiPut } from '../../utils/api.js';
import { handleApiError, getErrorMessage } from '../../utils/errors.js';
import MediaSelector from '../../components/admin/MediaSelector.vue';
import swal from '../../utils/swal.js';

export default {
    name: 'About',
    components: {
        MediaSelector,
    },
    data() {
        return {
            data: null,
            form: {
                title: '',
                phone: '',
                address: '',
                description: '',
                bullets: [],
                yandex_maps_url: '',
                cover_image_url: '',
            },
            loading: false,
            saving: false,
            error: null,
            showImageSelector: false,
        };
    },
    mounted() {
        this.loadData();
    },
    methods: {
        async loadData() {
            this.loading = true;
            this.error = null;
            try {
                const response = await apiGet('/admin/about');
                const result = await response.json();
                
                if (response.ok && result.data) {
                    this.data = result.data;
                    this.form = {
                        title: result.data.title || '',
                        phone: result.data.phone || '',
                        address: result.data.address || '',
                        description: result.data.description || '',
                        bullets: result.data.bullets && Array.isArray(result.data.bullets) ? result.data.bullets : [],
                        yandex_maps_url: result.data.yandex_maps_url || '',
                        cover_image_url: result.data.cover_image_url || '',
                    };
                } else {
                    this.error = result.message || 'Ошибка при загрузке данных';
                }
            } catch (error) {
                console.error('Error loading about page data:', error);
                this.error = error.message || 'Ошибка при загрузке данных';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.saving = true;
            this.error = null;
            try {
                const response = await apiPut('/admin/about', this.form);
                const result = await response.json();
                
                if (response.ok) {
                    await swal.success('Страница "О нас" успешно сохранена');
                    this.data = result.data;
                } else {
                    const errorMsg = result.message || 'Ошибка при сохранении';
                    this.error = errorMsg;
                    await swal.error('Ошибка при сохранении: ' + errorMsg);
                }
            } catch (error) {
                console.error('Error saving about page:', error);
                this.error = error.message || 'Ошибка при сохранении данных';
                await swal.error('Ошибка при сохранении: ' + this.error);
            } finally {
                this.saving = false;
            }
        },
        handleAddBullet() {
            this.form.bullets.push('');
        },
        handleRemoveBullet(index) {
            this.form.bullets.splice(index, 1);
        },
        handleImageSelected(selectedFiles) {
            if (selectedFiles && selectedFiles.length > 0) {
                const selectedFile = selectedFiles[0];
                this.form.cover_image_url = selectedFile.url || selectedFile.path || '';
            }
            this.showImageSelector = false;
        },
    },
};
</script>

