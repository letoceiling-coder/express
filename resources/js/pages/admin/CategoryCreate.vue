<template>
    <div class="category-create-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Создать категорию</h1>
            <p class="text-muted-foreground mt-1">Добавление новой категории товаров</p>
        </div>

        <div class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Название -->
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

                <!-- Описание -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Описание</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background"
                    ></textarea>
                </div>

                <!-- Изображение -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Изображение</label>
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
                                @click="showMediaSelector = true"
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
                    <p class="mt-2 text-sm text-muted-foreground">Выберите изображение из медиа-библиотеки</p>
                </div>

                <!-- Порядок сортировки -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Порядок сортировки</label>
                    <input
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>

                <!-- Статус -->
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 rounded border-input"
                        />
                        <span class="text-sm font-medium text-foreground">Категория активна</span>
                    </label>
                </div>

                <!-- Кнопки -->
                <div class="flex items-center gap-4 pt-4 border-t border-border">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ loading ? 'Создание...' : 'Создать категорию' }}
                    </button>
                    <router-link
                        to="/categories"
                        class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                    >
                        Отмена
                    </router-link>
                </div>
            </form>
        </div>

        <!-- Медиа селектор (временная реализация - открывается в новом окне) -->
        <div v-if="showMediaSelector" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-card rounded-lg border border-border p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-foreground">Выбор изображения</h2>
                    <button
                        @click="showMediaSelector = false"
                        class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-muted"
                    >
                        ✕
                    </button>
                </div>
                <p class="text-sm text-muted-foreground mb-4">
                    Откройте медиа-библиотеку в отдельной вкладке и скопируйте ID выбранного изображения:
                </p>
                <div class="space-y-4">
                    <a
                        href="/admin/media"
                        target="_blank"
                        class="block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 text-center"
                    >
                        Открыть медиа-библиотеку
                    </a>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">ID изображения</label>
                        <input
                            v-model="mediaIdInput"
                            type="number"
                            placeholder="Введите ID изображения"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
                    <button
                        @click="handleSelectMediaById"
                        class="w-full h-10 px-4 bg-accent text-accent-foreground rounded-lg hover:bg-accent/90"
                    >
                        Выбрать
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { categoriesAPI, apiGet } from '../../utils/api.js';

export default {
    name: 'CategoryCreate',
    data() {
        return {
            form: {
                name: '',
                description: '',
                image_id: null,
                sort_order: 0,
                is_active: true,
            },
            errors: {},
            loading: false,
            showMediaSelector: false,
            selectedImage: null,
            mediaIdInput: '',
        };
    },
    methods: {
        async handleSubmit() {
            this.errors = {};
            this.loading = true;

            try {
                // Валидация
                if (!this.form.name || this.form.name.length < 2) {
                    this.errors.name = 'Название должно содержать минимум 2 символа';
                    this.loading = false;
                    return;
                }

                await categoriesAPI.create(this.form);
                this.$router.push('/categories');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    alert(error.message || 'Ошибка создания категории');
                }
            } finally {
                this.loading = false;
            }
        },
        async handleSelectMediaById() {
            if (!this.mediaIdInput) {
                alert('Введите ID изображения');
                return;
            }

            try {
                const response = await apiGet(`/media/${this.mediaIdInput}`);
                if (!response.ok) {
                    throw new Error('Изображение не найдено');
                }
                const media = await response.json();
                this.selectedImage = media.data;
                this.form.image_id = media.data.id;
                this.showMediaSelector = false;
                this.mediaIdInput = '';
            } catch (error) {
                alert(error.message || 'Ошибка загрузки изображения');
            }
        },
    },
};
</script>

