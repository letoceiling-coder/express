<template>
    <div class="orders-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Заказы</h1>
            <p class="text-muted-foreground mt-1">Управление заказами</p>
        </div>

        <!-- Поиск и фильтры -->
        <div class="bg-card rounded-lg border border-border p-4 mb-6">
            <div class="flex gap-4 items-end flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-sm font-medium text-foreground mb-1 block">Поиск</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Поиск по номеру, телефону, адресу..."
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
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус оплаты</label>
                    <select
                        v-model="paymentStatusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="pending">Ожидает</option>
                        <option value="succeeded">Оплачен</option>
                        <option value="failed">Ошибка</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Сортировка</label>
                    <select
                        v-model="sortBy"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="created_at">По дате</option>
                        <option value="total_amount">По сумме</option>
                        <option value="status">По статусу</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка заказов...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица заказов -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Номер</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Дата</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Телефон</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Адрес</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Сумма</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Оплата</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="order in filteredOrders" :key="order.id">
                        <td class="px-6 py-4">
                            <div class="font-medium text-foreground">{{ order.order_id }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatDate(order.created_at) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ order.phone }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-muted-foreground line-clamp-1">{{ order.delivery_address }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-foreground">
                                {{ Number(order.total_amount).toLocaleString('ru-RU') }} ₽
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <select
                                :value="order.status"
                                @change="handleStatusChange(order.id, $event.target.value)"
                                class="text-xs px-2 py-1 rounded border border-input bg-background"
                                :class="getStatusClass(order.status)"
                            >
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
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="getPaymentStatusClass(order.payment_status)"
                                class="px-2 py-1 rounded-full text-xs font-medium"
                            >
                                {{ getPaymentStatusLabel(order.payment_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <router-link
                                :to="`/orders/${order.id}`"
                                class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                            >
                                Просмотр
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Пусто -->
            <div v-if="filteredOrders.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Заказы не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
import { ordersAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

export default {
    name: 'Orders',
    data() {
        return {
            orders: [],
            loading: false,
            error: null,
            searchQuery: '',
            statusFilter: '',
            paymentStatusFilter: '',
            sortBy: 'created_at',
        };
    },
    computed: {
        filteredOrders() {
            let filtered = [...this.orders];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(order =>
                    order.order_id.toLowerCase().includes(query) ||
                    order.phone.toLowerCase().includes(query) ||
                    order.delivery_address.toLowerCase().includes(query)
                );
            }

            // Фильтр по статусу
            if (this.statusFilter) {
                filtered = filtered.filter(order => order.status === this.statusFilter);
            }

            // Фильтр по статусу оплаты
            if (this.paymentStatusFilter) {
                filtered = filtered.filter(order => order.payment_status === this.paymentStatusFilter);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (this.sortBy === 'total_amount') {
                    return Number(b.total_amount) - Number(a.total_amount);
                } else if (this.sortBy === 'status') {
                    return a.status.localeCompare(b.status);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadOrders();
    },
    methods: {
        async loadOrders() {
            this.loading = true;
            this.error = null;
            try {
                const response = await ordersAPI.getAll();
                this.orders = response.data?.data || response.data || [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки заказов';
            } finally {
                this.loading = false;
            }
        },
        async handleStatusChange(orderId, newStatus) {
            try {
                await ordersAPI.updateStatus(orderId, newStatus);
                await this.loadOrders();
            } catch (error) {
                await swal.error(error.message || 'Ошибка изменения статуса');
                await this.loadOrders(); // Перезагружаем для отката изменений
            }
        },
        getStatusClass(status) {
            const classes = {
                new: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                accepted: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                sent_to_kitchen: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
                kitchen_accepted: 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
                preparing: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                ready_for_delivery: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                courier_assigned: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                in_transit: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                delivered: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            };
            return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        },
        getPaymentStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                succeeded: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800',
                cancelled: 'bg-gray-100 text-gray-800',
            };
            return classes[status] || '';
        },
        getPaymentStatusLabel(status) {
            const labels = {
                pending: 'Ожидает',
                succeeded: 'Оплачен',
                failed: 'Ошибка',
                cancelled: 'Отменен',
            };
            return labels[status] || status;
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    },
};
</script>

