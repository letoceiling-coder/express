<template>
    <div class="banners-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Баннеры</h1>
                <p class="text-muted-foreground mt-1">Управление слайдами главной страницы (HeroSlider)</p>
            </div>
            <router-link
                to="/banners/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Добавить</span>
            </router-link>
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
                            <router-link
                                :to="`/banners/${b.id}/edit`"
                                class="inline-flex p-2 text-muted-foreground hover:text-foreground hover:bg-muted rounded"
                                title="Редактировать"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </router-link>
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
    </div>
</template>

<script>
import { bannersAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

export default {
    name: 'Banners',
    data() {
        return {
            banners: [],
            loading: false,
            error: null,
        };
    },
    computed: {
        sortedBanners() {
            return [...this.banners].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
        },
    },
    mounted() {
        this.loadBanners();
    },
    methods: {
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
