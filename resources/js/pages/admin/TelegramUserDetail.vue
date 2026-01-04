<template>
    <div class="telegram-user-detail-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <router-link
                    to="/telegram-users"
                    class="text-sm text-muted-foreground hover:text-foreground mb-2 inline-block"
                >
                    ← Назад к списку пользователей
                </router-link>
                <h1 class="text-3xl font-semibold text-foreground">Детали пользователя</h1>
                <p v-if="user" class="text-muted-foreground mt-1">
                    Telegram ID: <span class="font-mono">{{ user.telegram_id }}</span>
                </p>
            </div>
            <div v-if="user" class="flex gap-2">
                <button
                    @click="refreshStatistics"
                    :disabled="refreshingStats"
                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors disabled:opacity-50"
                >
                    {{ refreshingStats ? 'Обновление...' : 'Обновить статистику' }}
                </button>
                <button
                    @click="syncUser"
                    :disabled="syncing"
                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors disabled:opacity-50"
                >
                    {{ syncing ? 'Синхронизация...' : 'Синхронизировать' }}
                </button>
                <button
                    @click="toggleBlock"
                    :disabled="togglingBlock"
                    :class="[
                        'px-4 py-2 rounded-lg transition-colors',
                        user.is_blocked
                            ? 'bg-green-500 hover:bg-green-600 text-white'
                            : 'bg-red-500 hover:bg-red-600 text-white'
                    ]"
                >
                    {{ togglingBlock ? '...' : (user.is_blocked ? 'Разблокировать' : 'Заблокировать') }}
                </button>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка данных пользователя...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Данные пользователя -->
        <div v-else-if="user" class="space-y-6">
            <!-- Основная информация -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4 border-b border-border pb-2">Основная информация</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Telegram ID</label>
                        <p class="text-lg font-mono text-foreground">{{ user.telegram_id }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Полное имя</label>
                        <p class="text-lg text-foreground">
                            {{ user.first_name || '' }} {{ user.last_name || '' }}
                            <span v-if="!user.first_name && !user.last_name" class="text-muted-foreground">—</span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Username</label>
                        <p class="text-lg text-foreground">{{ user.username ? '@' + user.username : '—' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Бот</label>
                        <p class="text-lg text-foreground">{{ user.bot?.name || '—' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Язык</label>
                        <p class="text-lg text-foreground">{{ user.language_code || '—' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Premium статус</label>
                        <p class="text-lg text-foreground">
                            <span
                                :class="[
                                    'px-2 py-1 text-xs rounded-md',
                                    user.is_premium
                                        ? 'bg-yellow-500/10 text-yellow-500'
                                        : 'bg-gray-500/10 text-gray-500'
                                ]"
                            >
                                {{ user.is_premium ? 'Premium' : 'Обычный' }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Статус</label>
                        <p class="text-lg text-foreground">
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
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Последнее взаимодействие</label>
                        <p class="text-lg text-foreground">{{ formatDate(user.last_interaction_at) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Дата регистрации</label>
                        <p class="text-lg text-foreground">{{ formatDate(user.created_at) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Последнее обновление</label>
                        <p class="text-lg text-foreground">{{ formatDate(user.updated_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2">Статистика</h2>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="p-4 bg-muted/30 rounded-lg">
                        <label class="text-sm font-medium text-muted-foreground block mb-2">Количество заказов</label>
                        <p class="text-3xl font-bold text-foreground">{{ user.orders_count || 0 }}</p>
                    </div>
                    <div class="p-4 bg-muted/30 rounded-lg">
                        <label class="text-sm font-medium text-muted-foreground block mb-2">Общая сумма покупок</label>
                        <p class="text-3xl font-bold text-foreground">{{ formatPrice(user.total_spent || 0) }}</p>
                    </div>
                    <div class="p-4 bg-muted/30 rounded-lg">
                        <label class="text-sm font-medium text-muted-foreground block mb-2">Средний чек</label>
                        <p class="text-3xl font-bold text-foreground">
                            {{ user.orders_count > 0 ? formatPrice((user.total_spent || 0) / user.orders_count) : formatPrice(0) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Заказы пользователя -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2">Заказы пользователя</h2>
                </div>

                <div v-if="ordersLoading" class="p-8 text-center">
                    <p class="text-muted-foreground">Загрузка заказов...</p>
                </div>

                <div v-else-if="orders.length === 0" class="p-8 text-center">
                    <p class="text-muted-foreground">Заказы не найдены</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-foreground">Номер заказа</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-foreground">Дата</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-foreground">Сумма</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="order in orders" :key="order.id">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-foreground">{{ order.order_id }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-foreground">{{ formatDate(order.created_at) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs rounded-md',
                                            getStatusClass(order.status)
                                        ]"
                                    >
                                        {{ getStatusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-foreground">{{ formatPrice(order.total_amount) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end">
                                        <router-link
                                            :to="{ name: 'admin.orders.detail', params: { id: order.id } }"
                                            class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                        >
                                            Просмотр
                                        </router-link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TelegramUserDetail',
    data() {
        return {
            user: null,
            orders: [],
            loading: false,
            ordersLoading: false,
            error: null,
            togglingBlock: false,
            syncing: false,
            refreshingStats: false,
        };
    },
    mounted() {
        this.loadUser();
    },
    methods: {
        async loadUser() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(`/api/v1/telegram-users/${this.$route.params.id}`);
                this.user = response.data.data;
                
                // Загружаем заказы пользователя
                if (this.user && this.user.telegram_id) {
                    await this.loadOrders();
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки данных пользователя';
                console.error('Error loading user:', error);
            } finally {
                this.loading = false;
            }
        },
        async loadOrders() {
            this.ordersLoading = true;
            try {
                const response = await axios.get('/api/v1/orders', {
                    params: {
                        telegram_id: this.user.telegram_id,
                        per_page: 50,
                        sort_by: 'created_at',
                        sort_order: 'desc',
                    },
                });
                this.orders = response.data.data?.data || response.data.data || [];
            } catch (error) {
                console.error('Error loading orders:', error);
            } finally {
                this.ordersLoading = false;
            }
        },
        async toggleBlock() {
            if (!confirm(`Вы уверены, что хотите ${this.user.is_blocked ? 'разблокировать' : 'заблокировать'} этого пользователя?`)) {
                return;
            }

            this.togglingBlock = true;

            try {
                const endpoint = this.user.is_blocked ? 'unblock' : 'block';
                await axios.post(`/api/v1/telegram-users/${this.user.id}/${endpoint}`);
                await this.loadUser();
            } catch (error) {
                alert(error.response?.data?.message || 'Ошибка при изменении статуса');
                console.error('Error toggling block:', error);
            } finally {
                this.togglingBlock = false;
            }
        },
        async syncUser() {
            this.syncing = true;

            try {
                await axios.post(`/api/v1/telegram-users/${this.user.id}/sync`);
                await this.loadUser();
                alert('Пользователь успешно синхронизирован');
            } catch (error) {
                alert(error.response?.data?.message || 'Ошибка синхронизации');
                console.error('Error syncing user:', error);
            } finally {
                this.syncing = false;
            }
        },
        async refreshStatistics() {
            this.refreshingStats = true;

            try {
                await axios.get(`/api/v1/telegram-users/${this.user.id}/statistics`);
                await this.loadUser();
                alert('Статистика обновлена');
            } catch (error) {
                alert(error.response?.data?.message || 'Ошибка обновления статистики');
                console.error('Error refreshing statistics:', error);
            } finally {
                this.refreshingStats = false;
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
        getStatusLabel(status) {
            const labels = {
                new: 'Новый',
                accepted: 'Принят',
                preparing: 'Готовится',
                ready_for_delivery: 'Готов к доставке',
                in_transit: 'В пути',
                delivered: 'Доставлен',
                cancelled: 'Отменен',
            };
            return labels[status] || status;
        },
        getStatusClass(status) {
            const classes = {
                new: 'bg-blue-500/10 text-blue-500',
                accepted: 'bg-yellow-500/10 text-yellow-500',
                preparing: 'bg-orange-500/10 text-orange-500',
                ready_for_delivery: 'bg-purple-500/10 text-purple-500',
                in_transit: 'bg-indigo-500/10 text-indigo-500',
                delivered: 'bg-green-500/10 text-green-500',
                cancelled: 'bg-red-500/10 text-red-500',
            };
            return classes[status] || 'bg-gray-500/10 text-gray-500';
        },
    },
};
</script>
