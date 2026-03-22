<template>
    <div class="banners-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Баннеры</h1>
                <p class="text-muted-foreground mt-1">Управление слайдами главной страницы (HeroSlider)</p>
            </div>
            <button
                @click="openCreate"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Добавить</span>
            </button>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка баннеров...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица баннеров -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <div v-if="banners.length === 0" class="p-12 text-center text-muted-foreground">
                Нет баннеров. Добавьте первый — он появится в HeroSlider на главной.
            </div>
            <table v-else class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Изображение</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Заголовок</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Подзаголовок</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Порядок</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Вкл.</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="b in sortedBanners" :key="b.id">
                        <td class="px-6 py-4">
                            <img
                                v-if="b.image"
                                :src="b.image"
                                alt=""
                                class="h-12 w-20 object-cover rounded-lg"
                                @error="$event.target.style.display='none'"
                            />
                            <span v-else class="text-muted-foreground text-xs">—</span>
                        </td>
                        <td class="px-6 py-4 font-medium text-foreground">{{ b.title }}</td>
                        <td class="px-6 py-4 text-sm text-muted-foreground max-w-[200px] truncate">
                            {{ b.subtitle || '—' }}
                        </td>
                        <td class="px-6 py-4 text-foreground">{{ b.sort_order ?? 0 }}</td>
                        <td class="px-6 py-4">
                            <span
                                :class="b.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                class="px-2 py-1 rounded-full text-xs font-medium"
                            >
                                {{ b.is_active ? 'Да' : 'Нет' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button
                                @click="openEdit(b)"
                                class="inline-flex p-2 text-muted-foreground hover:text-foreground hover:bg-muted rounded"
                                title="Редактировать"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                            <button
                                @click="handleDelete(b)"
                                class="inline-flex p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded"
                                title="Удалить"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <div
            v-if="showDialog"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showDialog = false"
        >
            <div class="bg-card rounded-lg border border-border shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-foreground mb-4">
                        {{ editing ? 'Редактировать баннер' : 'Новый баннер' }}
                    </h2>
                    <form @submit.prevent="handleSave" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Заголовок *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="Свежая выпечка каждый день"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Подзаголовок</label>
                            <input
                                v-model="form.subtitle"
                                type="text"
                                placeholder="Печём с душой"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Изображение</label>
                            <div class="flex flex-col gap-2">
                                <button
                                    type="button"
                                    @click="openMediaSelector"
                                    class="h-11 px-4 rounded-lg bg-primary/10 text-primary border border-primary/30 hover:bg-primary/20 inline-flex items-center justify-center gap-2 font-medium"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Выбрать из медиа
                                </button>
                                <input
                                    v-model="form.image"
                                    type="text"
                                    placeholder="URL (или выберите из медиа выше)"
                                    class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                />
                            </div>
                            <img
                                v-if="form.image"
                                :src="form.image"
                                alt=""
                                class="mt-2 h-24 object-cover rounded-lg border border-border"
                                @error="$event.target.style.display='none'"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">Текст кнопки</label>
                                <input
                                    v-model="form.cta_text"
                                    type="text"
                                    placeholder="В каталог"
                                    class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">Ссылка кнопки</label>
                                <input
                                    v-model="form.cta_href"
                                    type="text"
                                    placeholder="/#products"
                                    class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                />
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">Порядок</label>
                                <input
                                    v-model.number="form.sort_order"
                                    type="number"
                                    min="0"
                                    class="w-20 h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-foreground">Включён</label>
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-input"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4 border-t border-border">
                            <button
                                type="button"
                                @click="showDialog = false"
                                class="h-10 px-4 rounded-lg border border-input bg-background text-foreground hover:bg-muted"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                :disabled="saving"
                                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                            >
                                {{ saving ? 'Сохранение...' : 'Сохранить' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { bannersAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

export default {
    name: 'Banners',
    data() {
        const mediaCallback = 'banner-image-' + Math.random().toString(36).slice(2);
        return {
            banners: [],
            loading: false,
            error: null,
            showDialog: false,
            editing: null,
            saving: false,
            mediaCallback,
            form: {
                title: '',
                subtitle: '',
                image: '',
                cta_text: 'В каталог',
                cta_href: '/#products',
                is_active: true,
                sort_order: 0,
            },
            mediaCheckInterval: null,
        };
    },
    computed: {
        sortedBanners() {
            return [...this.banners].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
        },
    },
    mounted() {
        this.loadBanners();
        this.setupMediaListener();
    },
    beforeUnmount() {
        if (this.mediaCheckInterval) clearInterval(this.mediaCheckInterval);
        window.removeEventListener('message', this.handleMediaMessage);
        window.removeEventListener('storage', this.checkMediaStorage);
        window.removeEventListener('focus', this.checkMediaStorage);
    },
    methods: {
        setupMediaListener() {
            window.addEventListener('message', this.handleMediaMessage);
            window.addEventListener('storage', this.checkMediaStorage);
            window.addEventListener('focus', this.checkMediaStorage);
            this.mediaCheckInterval = setInterval(this.checkMediaStorage, 500);
        },
        handleMediaMessage(e) {
            if (e.data?.type === 'media-selected' && e.data?.callback?.startsWith('banner-image-') && e.data?.url) {
                this.form.image = e.data.url;
            }
        },
        checkMediaStorage() {
            const key = Object.keys(localStorage).find((k) => k.startsWith('media-selected-banner-image-'));
            if (key) {
                try {
                    const data = JSON.parse(localStorage.getItem(key) || '{}');
                    if (data?.url) {
                        this.form.image = data.url;
                        localStorage.removeItem(key);
                    }
                } catch (_) {}
            }
        },
        async loadBanners() {
            this.loading = true;
            this.error = null;
            try {
                this.banners = await bannersAPI.getAdmin();
                if (!Array.isArray(this.banners)) this.banners = [];
            } catch (err) {
                this.error = err.message || 'Ошибка загрузки баннеров';
                this.banners = [];
            } finally {
                this.loading = false;
            }
        },
        resetForm() {
            this.form = {
                title: '',
                subtitle: '',
                image: '',
                cta_text: 'В каталог',
                cta_href: '/#products',
                is_active: true,
                sort_order: this.banners.length,
            };
            this.editing = null;
        },
        openCreate() {
            this.resetForm();
            this.form.sort_order = this.banners.length;
            this.showDialog = true;
        },
        openEdit(b) {
            this.editing = b;
            this.form = {
                title: b.title,
                subtitle: b.subtitle || '',
                image: b.image || '',
                cta_text: b.cta_text || 'В каталог',
                cta_href: b.cta_href || '/#products',
                is_active: b.is_active ?? true,
                sort_order: b.sort_order ?? 0,
            };
            this.showDialog = true;
        },
        openMediaSelector() {
            window.open(`/admin/media?select=true&callback=${this.mediaCallback}`, '_blank');
        },
        async handleSave() {
            if (!this.form.title?.trim()) {
                await swal.error('Введите заголовок');
                return;
            }
            this.saving = true;
            try {
                if (this.editing) {
                    await bannersAPI.update(this.editing.id, this.form);
                    await swal.success('Баннер обновлён');
                } else {
                    await bannersAPI.create(this.form);
                    await swal.success('Баннер создан');
                }
                this.showDialog = false;
                await this.loadBanners();
            } catch (err) {
                await swal.error(err.message || 'Ошибка сохранения');
            } finally {
                this.saving = false;
            }
        },
        async handleDelete(b) {
            const result = await swal.confirm('Удалить баннер?', 'Удаление баннера', 'Удалить', 'Отмена');
            if (!result.isConfirmed) return;
            try {
                await bannersAPI.delete(b.id);
                await swal.success('Баннер удалён');
                await this.loadBanners();
            } catch (err) {
                await swal.error(err.message || 'Ошибка удаления');
            }
        },
    },
};
</script>
