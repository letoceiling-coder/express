<template>
    <div class="order-status-history">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-foreground">История статусов</h3>
            <button
                @click="loadHistory"
                :disabled="loading"
                class="px-3 py-1.5 text-sm bg-muted hover:bg-muted/80 text-muted-foreground rounded-lg transition-colors disabled:opacity-50"
            >
                {{ loading ? 'Загрузка...' : 'Обновить' }}
            </button>
        </div>

        <!-- Фильтры -->
        <div v-if="history.length > 0" class="mb-4 flex gap-2 flex-wrap">
            <select
                v-model="filters.role"
                class="h-9 px-3 text-sm rounded-lg border border-input bg-background text-foreground"
            >
                <option value="">Все роли</option>
                <option value="admin">Администратор</option>
                <option value="kitchen">Кухня</option>
                <option value="courier">Курьер</option>
                <option value="user">Клиент</option>
            </select>
            <select
                v-model="filters.status"
                class="h-9 px-3 text-sm rounded-lg border border-input bg-background text-foreground"
            >
                <option value="">Все статусы</option>
                <option value="new">Новый</option>
                <option value="accepted">Принят</option>
                <option value="sent_to_kitchen">Отправлен на кухню</option>
                <option value="kitchen_accepted">Принят кухней</option>
                <option value="preparing">Готовится</option>
                <option value="ready_for_delivery">Готов к доставке</option>
                <option value="courier_assigned">Курьер назначен</option>
                <option value="in_transit">В пути</option>
                <option value="delivered">Доставлен</option>
                <option value="cancelled">Отменен</option>
            </select>
            <input
                v-model="filters.search"
                type="text"
                placeholder="Поиск по комментариям..."
                class="h-9 px-3 text-sm rounded-lg border border-input bg-background text-foreground flex-1 min-w-[200px]"
            />
        </div>

        <!-- Загрузка -->
        <div v-if="loading && history.length === 0" class="text-center py-8 text-muted-foreground">
            Загрузка истории статусов...
        </div>

        <!-- Пусто -->
        <div v-else-if="filteredHistory.length === 0" class="text-center py-8 text-muted-foreground">
            {{ history.length === 0 ? 'История статусов пуста' : 'Нет записей, соответствующих фильтрам' }}
        </div>

        <!-- Timeline -->
        <div v-else class="relative">
            <!-- Вертикальная линия -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-border"></div>

            <!-- Элементы истории -->
            <div class="space-y-4">
                <div
                    v-for="(item, index) in filteredHistory"
                    :key="item.id"
                    class="relative flex gap-4"
                >
                    <!-- Точка на линии -->
                    <div
                        :class="[
                            'absolute left-4 w-3 h-3 rounded-full border-2 border-background -translate-x-1/2',
                            getStatusColor(item.status)
                        ]"
                        :style="{ top: '0.5rem' }"
                    ></div>

                    <!-- Содержимое -->
                    <div class="flex-1 ml-8 pb-4">
                        <div class="bg-card rounded-lg border border-border p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-md',
                                            getStatusBadgeClass(item.status)
                                        ]"
                                    >
                                        {{ translateStatus(item.status) }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-md',
                                            getRoleBadgeClass(item.role)
                                        ]"
                                    >
                                        {{ translateRole(item.role) }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground">
                                    {{ formatDate(item.created_at) }}
                                </span>
                            </div>

                            <div v-if="item.previous_status" class="text-sm text-muted-foreground mb-2">
                                <span class="font-medium">Предыдущий статус:</span>
                                {{ translateStatus(item.previous_status) }}
                            </div>

                            <div v-if="getUserName(item)" class="text-sm text-muted-foreground mb-2">
                                <span class="font-medium">Изменил:</span>
                                {{ getUserName(item) }}
                            </div>

                            <div v-if="item.comment" class="text-sm text-foreground mt-2 p-2 bg-muted/30 rounded">
                                <span class="font-medium text-muted-foreground">Комментарий:</span>
                                <p class="mt-1">{{ item.comment }}</p>
                            </div>

                            <div v-if="item.metadata && Object.keys(item.metadata).length > 0" class="text-xs text-muted-foreground mt-2">
                                <details class="cursor-pointer">
                                    <summary class="font-medium">Дополнительная информация</summary>
                                    <pre class="mt-2 p-2 bg-muted/20 rounded text-xs overflow-x-auto">{{ JSON.stringify(item.metadata, null, 2) }}</pre>
                                </details>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'OrderStatusHistory',
    props: {
        orderId: {
            type: [Number, String],
            required: true,
        },
    },
    data() {
        return {
            history: [],
            loading: false,
            error: null,
            filters: {
                role: '',
                status: '',
                search: '',
            },
        };
    },
    computed: {
        filteredHistory() {
            let result = [...this.history];

            if (this.filters.role) {
                result = result.filter(item => item.role === this.filters.role);
            }

            if (this.filters.status) {
                result = result.filter(item => item.status === this.filters.status);
            }

            if (this.filters.search) {
                const search = this.filters.search.toLowerCase();
                result = result.filter(item => 
                    (item.comment || '').toLowerCase().includes(search)
                );
            }

            return result;
        },
    },
    mounted() {
        this.loadHistory();
    },
    methods: {
        async loadHistory() {
            this.loading = true;
            this.error = null;
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get(`/api/v1/orders/${this.orderId}/status-history`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                });
                this.history = response.data.data || [];
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки истории статусов';
                console.error('Error loading status history:', error);
            } finally {
                this.loading = false;
            }
        },
        translateStatus(status) {
            const translations = {
                'new': 'Новый',
                'accepted': 'Принят',
                'sent_to_kitchen': 'Отправлен на кухню',
                'kitchen_accepted': 'Принят кухней',
                'preparing': 'Готовится',
                'ready_for_delivery': 'Готов к доставке',
                'courier_assigned': 'Курьер назначен',
                'in_transit': 'В пути',
                'delivered': 'Доставлен',
                'cancelled': 'Отменен',
            };
            return translations[status] || status;
        },
        translateRole(role) {
            const translations = {
                'admin': 'Администратор',
                'kitchen': 'Кухня',
                'courier': 'Курьер',
                'user': 'Клиент',
            };
            return translations[role] || role;
        },
        getStatusColor(status) {
            const colors = {
                'new': 'bg-blue-500',
                'accepted': 'bg-green-500',
                'sent_to_kitchen': 'bg-yellow-500',
                'kitchen_accepted': 'bg-yellow-600',
                'preparing': 'bg-orange-500',
                'ready_for_delivery': 'bg-purple-500',
                'courier_assigned': 'bg-indigo-500',
                'in_transit': 'bg-blue-600',
                'delivered': 'bg-green-600',
                'cancelled': 'bg-red-500',
            };
            return colors[status] || 'bg-gray-500';
        },
        getStatusBadgeClass(status) {
            const classes = {
                'new': 'bg-blue-500/10 text-blue-500',
                'accepted': 'bg-green-500/10 text-green-500',
                'sent_to_kitchen': 'bg-yellow-500/10 text-yellow-500',
                'kitchen_accepted': 'bg-yellow-600/10 text-yellow-600',
                'preparing': 'bg-orange-500/10 text-orange-500',
                'ready_for_delivery': 'bg-purple-500/10 text-purple-500',
                'courier_assigned': 'bg-indigo-500/10 text-indigo-500',
                'in_transit': 'bg-blue-600/10 text-blue-600',
                'delivered': 'bg-green-600/10 text-green-600',
                'cancelled': 'bg-red-500/10 text-red-500',
            };
            return classes[status] || 'bg-gray-500/10 text-gray-500';
        },
        getRoleBadgeClass(role) {
            const classes = {
                'admin': 'bg-purple-500/10 text-purple-500',
                'kitchen': 'bg-orange-500/10 text-orange-500',
                'courier': 'bg-blue-500/10 text-blue-500',
                'user': 'bg-gray-500/10 text-gray-500',
            };
            return classes[role] || 'bg-gray-500/10 text-gray-500';
        },
        getUserName(item) {
            if (item.changed_by_user) {
                return item.changed_by_user.name || item.changed_by_user.email || 'Администратор';
            }
            if (item.changed_by_telegram_user) {
                const user = item.changed_by_telegram_user;
                const name = [user.first_name, user.last_name].filter(Boolean).join(' ');
                return name || user.username || `User #${user.telegram_id}`;
            }
            return null;
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    },
};
</script>

<style scoped>
.order-status-history {
    @apply w-full;
}
</style>




