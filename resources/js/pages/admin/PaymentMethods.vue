<template>
    <div class="payment-methods-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Способы оплаты</h1>
                <p class="text-muted-foreground mt-1">Управление способами оплаты и настройками скидок</p>
            </div>
            <router-link
                to="/payment-methods/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать способ оплаты</span>
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
                        placeholder="Поиск по названию, коду..."
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
            <p class="text-muted-foreground">Загрузка способов оплаты...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица способов оплаты -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Код</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Название</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Описание</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Скидка</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Порядок</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">По умолчанию</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="method in filteredMethods" :key="method.id">
                        <td class="px-6 py-4">
                            <span class="text-sm font-mono text-foreground">{{ method.code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-foreground">{{ method.name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-muted-foreground line-clamp-2">
                                {{ method.description || '—' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="method.discount_type !== 'none'" class="text-sm">
                                <span class="text-foreground">
                                    {{ method.discount_type === 'percentage' 
                                        ? `${method.discount_value}%` 
                                        : `${Number(method.discount_value).toLocaleString('ru-RU')} ₽` }}
                                </span>
                                <div v-if="method.min_cart_amount" class="text-xs text-muted-foreground mt-1">
                                    от {{ Number(method.min_cart_amount).toLocaleString('ru-RU') }} ₽
                                </div>
                            </div>
                            <span v-else class="text-sm text-muted-foreground">Нет скидки</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ method.sort_order || 0 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                v-if="method.is_default"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                            >
                                Да
                            </span>
                            <span v-else class="text-sm text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                :class="method.is_enabled 
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'"
                            >
                                {{ method.is_enabled ? 'Активен' : 'Неактивен' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="`/payment-methods/${method.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Редактировать
                                </router-link>
                                <button
                                    @click="handleDelete(method.id)"
                                    class="h-8 px-3 text-sm bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                                >
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Пустое состояние -->
            <div v-if="filteredMethods.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Способы оплаты не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
import { apiGet, apiDelete } from '@/utils/api';
import swal from '../../utils/swal.js';

export default {
    name: 'PaymentMethods',
    data() {
        return {
            methods: [],
            loading: false,
            error: null,
            searchQuery: '',
            statusFilter: '',
            sortBy: 'sort_order',
        };
    },
    computed: {
        filteredMethods() {
            let filtered = [...this.methods];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(method =>
                    method.name.toLowerCase().includes(query) ||
                    method.code.toLowerCase().includes(query) ||
                    (method.description && method.description.toLowerCase().includes(query))
                );
            }

            // Фильтр по статусу
            if (this.statusFilter !== '') {
                const isEnabled = this.statusFilter === 'true';
                filtered = filtered.filter(method => method.is_enabled === isEnabled);
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
        this.loadMethods();
    },
    methods: {
        async loadMethods() {
            this.loading = true;
            this.error = null;
            try {
                const response = await apiGet('/payment-methods');
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Ошибка загрузки способов оплаты');
                }
                const data = await response.json();
                // Обрабатываем разные форматы ответа
                if (Array.isArray(data.data)) {
                    this.methods = data.data;
                } else if (Array.isArray(data)) {
                    this.methods = data;
                } else {
                    this.methods = [];
                }
            } catch (err) {
                this.error = err.message || 'Ошибка загрузки способов оплаты';
                console.error('Error loading payment methods:', err);
            } finally {
                this.loading = false;
            }
        },
        async handleDelete(id) {
            const result = await swal.confirm(
                'Вы уверены, что хотите удалить этот способ оплаты?',
                'Удаление способа оплаты',
                'Удалить',
                'Отмена'
            );

            if (!result.isConfirmed) {
                return;
            }

            try {
                await apiDelete(`/payment-methods/${id}`);
                this.methods = this.methods.filter(m => m.id !== id);
                await swal.success('Способ оплаты успешно удален');
            } catch (err) {
                await swal.error(err.response?.data?.message || 'Ошибка удаления способа оплаты');
            }
        },
    },
};
</script>

