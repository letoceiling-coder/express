<template>
    <div class="telegram-users-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Пользователи бота</h1>
                <p class="text-muted-foreground mt-1">Управление пользователями Telegram бота</p>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="bg-card rounded-lg border border-border p-4">
            <div class="flex gap-4 items-end flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-sm font-medium text-foreground mb-1 block">Поиск</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Поиск по имени, username, telegram_id..."
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        @input="debounceSearch"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Бот</label>
                    <select
                        v-model="botFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background min-w-[200px]"
                        @change="loadUsers"
                    >
                        <option value="">Все боты</option>
                        <option v-for="bot in bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус</label>
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                        @change="loadUsers"
                    >
                        <option value="">Все</option>
                        <option value="active">Активные</option>
                        <option value="blocked">Заблокированные</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Сортировка</label>
                    <select
                        v-model="sortBy"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                        @change="loadUsers"
                    >
                        <option value="last_interaction_at">По последнему взаимодействию</option>
                        <option value="created_at">По дате регистрации</option>
                        <option value="orders_count">По количеству заказов</option>
                        <option value="total_spent">По сумме покупок</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка пользователей...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица пользователей -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Telegram ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Имя</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Username</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Бот</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Заказов</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Сумма покупок</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Последнее взаимодействие</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="user in users" :key="user.id">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-foreground">{{ user.telegram_id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-foreground">{{ user.first_name }} {{ user.last_name || '' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="user.username" class="text-sm text-foreground">@{{ user.username }}</span>
                            <span v-else class="text-sm text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ user.bot?.name || '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ user.orders_count || 0 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatPrice(user.total_spent || 0) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'px-2 py-1 text-xs rounded-md',
                                    user.is_blocked
                                        ? 'bg-red-500/10 text-red-500'
                                        : 'bg-green-500/10 text-green-500'
                                ]"
                            >
                                {{ user.is_blocked ? 'Заблокирован' : 'Активен' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-muted-foreground">{{ formatDate(user.last_interaction_at) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="viewUser(user.id)"
                                    class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                >
                                    Просмотр
                                </button>
                                <button
                                    @click="toggleBlock(user)"
                                    :disabled="togglingBlock === user.id"
                                    :class="[
                                        'px-3 py-1 text-xs rounded transition-colors',
                                        user.is_blocked
                                            ? 'bg-green-500 hover:bg-green-600 text-white'
                                            : 'bg-red-500 hover:bg-red-600 text-white'
                                    ]"
                                >
                                    {{ togglingBlock === user.id ? '...' : (user.is_blocked ? 'Разблокировать' : 'Заблокировать') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Пагинация -->
        <div v-if="pagination && pagination.total > pagination.per_page" class="flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
                Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
            </div>
            <div class="flex gap-2">
                <button
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-4 py-2 border border-border rounded-lg hover:bg-accent/10 disabled:opacity-50"
                >
                    Назад
                </button>
                <button
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-4 py-2 border border-border rounded-lg hover:bg-accent/10 disabled:opacity-50"
                >
                    Вперед
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && users.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Пользователи не найдены</p>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TelegramUsers',
    data() {
        return {
            users: [],
            bots: [],
            loading: false,
            error: null,
            searchQuery: '',
            botFilter: '',
            statusFilter: '',
            sortBy: 'last_interaction_at',
            pagination: null,
            togglingBlock: null,
            searchTimeout: null,
        };
    },
    mounted() {
        this.loadBots();
        this.loadUsers();
    },
    methods: {
        async loadBots() {
            try {
                const response = await axios.get('/api/v1/bots');
                this.bots = response.data.data || [];
            } catch (error) {
                console.error('Error loading bots:', error);
            }
        },
        async loadUsers() {
            this.loading = true;
            this.error = null;

            try {
                const params = {
                    per_page: 15,
                    sort_by: this.sortBy,
                    sort_order: 'desc',
                };

                if (this.botFilter) {
                    params.bot_id = this.botFilter;
                }

                if (this.statusFilter === 'active') {
                    params.is_blocked = false;
                } else if (this.statusFilter === 'blocked') {
                    params.is_blocked = true;
                }

                if (this.searchQuery) {
                    params.search = this.searchQuery;
                }

                const response = await axios.get('/api/v1/telegram-users', { params });
                this.users = response.data.data?.data || [];
                this.pagination = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки пользователей';
                console.error('Error loading users:', error);
            } finally {
                this.loading = false;
            }
        },
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadUsers();
            }, 500);
        },
        changePage(page) {
            if (page >= 1) {
                this.loadUsers();
            }
        },
        viewUser(id) {
            this.$router.push({ name: 'admin.telegram-users.detail', params: { id } });
        },
        async toggleBlock(user) {
            if (!confirm(`Вы уверены, что хотите ${user.is_blocked ? 'разблокировать' : 'заблокировать'} этого пользователя?`)) {
                return;
            }

            this.togglingBlock = user.id;

            try {
                const endpoint = user.is_blocked ? 'unblock' : 'block';
                await axios.post(`/api/v1/telegram-users/${user.id}/${endpoint}`);
                await this.loadUsers();
            } catch (error) {
                alert(error.response?.data?.message || 'Ошибка при изменении статуса');
                console.error('Error toggling block:', error);
            } finally {
                this.togglingBlock = null;
            }
        },
        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleString('ru-RU');
        },
        formatPrice(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
            }).format(amount);
        },
    },
};
</script>



