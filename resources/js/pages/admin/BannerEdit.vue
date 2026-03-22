<template>
    <div class="banner-edit-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Редактировать баннер</h1>
            <p class="text-muted-foreground mt-1">Изменение слайда главной страницы</p>
        </div>

        <div v-if="loading && !banner" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка баннера...</p>
        </div>

        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
            <router-link
                to="/banners"
                class="mt-4 inline-block h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90"
            >
                Вернуться к списку
            </router-link>
        </div>

        <div v-else-if="banner" class="bg-card rounded-lg border border-border p-6">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Заголовок <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        required
                        placeholder="Свежая выпечка каждый день"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        :class="{ 'border-destructive': errors.title }"
                    />
                    <p v-if="errors.title" class="mt-1 text-sm text-destructive">{{ errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Подзаголовок</label>
                    <input
                        v-model="form.subtitle"
                        type="text"
                        placeholder="Печём с душой из отборной муки"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Изображение</label>
                    <div class="flex items-center gap-4">
                        <img
                            v-if="form.image"
                            :src="form.image"
                            alt="Preview"
                            class="w-32 h-32 object-cover rounded-lg border border-border"
                            @error="$event.target.style.display='none'"
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
                                v-if="form.image"
                                type="button"
                                @click="form.image = ''"
                                class="ml-2 h-10 px-4 bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                            >
                                Удалить
                            </button>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-muted-foreground">Выберите изображение из медиа-библиотеки</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Текст кнопки</label>
                        <input
                            v-model="form.cta_text"
                            type="text"
                            placeholder="В каталог"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Ссылка кнопки</label>
                        <input
                            v-model="form.cta_href"
                            type="text"
                            placeholder="/#products"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-8">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Порядок</label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="w-24 h-10 px-3 rounded-lg border border-input bg-background"
                        />
                    </div>
                    <div class="flex items-center gap-2 pt-7">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 rounded border-input"
                        />
                        <span class="text-sm font-medium text-foreground">Включён</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-border">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ loading ? 'Сохранение...' : 'Сохранить изменения' }}
                    </button>
                    <router-link
                        to="/banners"
                        class="h-10 px-6 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                    >
                        Отмена
                    </router-link>
                </div>
            </form>
        </div>

        <MediaSelector
            :open="showMediaSelector"
            :multiple="false"
            :allowedTypes="['photo']"
            :currentSelection="form.image ? [{ id: 0, url: form.image, type: 'photo', name: '' }] : []"
            @close="showMediaSelector = false"
            @select="handleMediaSelected"
        />
    </div>
</template>

<script>
import { bannersAPI } from '../../utils/api.js';
import MediaSelector from '../../components/admin/MediaSelector.vue';
import swal from '../../utils/swal.js';

export default {
    name: 'BannerEdit',
    components: {
        MediaSelector,
    },
    data() {
        return {
            banner: null,
            form: {
                title: '',
                subtitle: '',
                image: '',
                cta_text: 'В каталог',
                cta_href: '/#products',
                sort_order: 0,
                is_active: true,
            },
            errors: {},
            loading: false,
            error: null,
            showMediaSelector: false,
        };
    },
    mounted() {
        this.loadBanner();
    },
    methods: {
        async loadBanner() {
            this.loading = true;
            this.error = null;
            try {
                const id = this.$route.params.id;
                this.banner = await bannersAPI.getById(id);
                if (this.banner) {
                    this.form = {
                        title: this.banner.title || '',
                        subtitle: this.banner.subtitle || '',
                        image: this.banner.image || '',
                        cta_text: this.banner.cta_text || 'В каталог',
                        cta_href: this.banner.cta_href || '/#products',
                        sort_order: this.banner.sort_order ?? 0,
                        is_active: this.banner.is_active !== undefined ? this.banner.is_active : true,
                    };
                } else {
                    this.error = 'Баннер не найден';
                }
            } catch (err) {
                this.error = err.message || 'Ошибка загрузки баннера';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;
            try {
                if (!this.form.title?.trim()) {
                    this.errors.title = 'Введите заголовок';
                    this.loading = false;
                    return;
                }
                await bannersAPI.update(this.banner.id, this.form);
                await swal.success('Баннер обновлён');
                this.$router.push('/banners');
            } catch (err) {
                const errorData = err.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                } else {
                    await swal.error(err.message || 'Ошибка обновления баннера');
                }
            } finally {
                this.loading = false;
            }
        },
        handleMediaSelected(media) {
            this.form.image = media?.url || '';
            this.showMediaSelector = false;
        },
    },
};
</script>
