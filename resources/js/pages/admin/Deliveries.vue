<template>
    <div class="deliveries-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Доставки</h1>
                <p class="text-muted-foreground mt-1">Управление доставками</p>
            </div>
            <router-link
                to="/deliveries/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать доставку</span>
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
                        placeholder="Поиск по трек-номеру, адресу, курьеру..."
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
                        <option value="pending">Ожидает</option>
                        <option value="assigned">Назначена</option>
                        <option value="in_transit">В пути</option>
                        <option value="delivered">Доставлена</option>
                        <option value="failed">Ошибка</option>
                        <option value="returned">Возвращена</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Тип</label>
                    <select
                        v-model="typeFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="courier">Курьер</option>
                        <option value="pickup">Самовывоз</option>
                        <option value="self_delivery">Своя доставка</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Сортировка</label>
                    <select
                        v-model="sortBy"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="created_at">По дате</option>
                        <option value="delivery_date">По дате доставки</option>
                        <option value="status">По статусу</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка доставок...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица доставок -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Заказ</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Адрес</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Тип</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Курьер</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Дата доставки</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Стоимость</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="delivery in filteredDeliveries" :key="delivery.id">
                        <td class="px-6 py-4">
                            <router-link
                                :to="`/orders/${delivery.order_id}`"
                                class="text-sm font-medium text-accent hover:underline"
                            >
                                #{{ delivery.order?.order_id || delivery.order_id }}
                            </router-link>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-muted-foreground line-clamp-1">{{ delivery.delivery_address }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ getDeliveryTypeLabel(delivery.delivery_type) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <select
                                :value="delivery.status"
                                @change="handleStatusChange(delivery.id, $event.target.value)"
                                class="text-xs px-2 py-1 rounded border border-input bg-background"
                                :class="getStatusClass(delivery.status)"
                            >
                                <option value="pending">Ожидает</option>
                                <option value="assigned">Назначена</option>
                                <option value="in_transit">В пути</option>
                                <option value="delivered">Доставлена</option>
                                <option value="failed">Ошибка</option>
                                <option value="returned">Возвращена</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="delivery.courier_name" class="text-sm text-foreground">
                                {{ delivery.courier_name }}
                            </span>
                            <span v-else class="text-sm text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatDate(delivery.delivery_date) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">
                                {{ Number(delivery.delivery_cost).toLocaleString('ru-RU') }} ₽
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="`/deliveries/${delivery.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Редактировать
                                </router-link>
                                <button
                                    @click="handleDelete(delivery)"
                                    class="h-8 px-3 text-sm bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                                >
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Пусто -->
            <div v-if="filteredDeliveries.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Доставки не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
import { deliveriesAPI, ordersAPI } from '../../utils/api.js';

export default {
    name: 'Deliveries',
    data() {
        return {
            deliveries: [],
            loading: false,
            error: null,
            searchQuery: '',
            statusFilter: '',
            typeFilter: '',
            sortBy: 'created_at',
        };
    },
    computed: {
        filteredDeliveries() {
            let filtered = [...this.deliveries];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(delivery =>
                    (delivery.tracking_number && delivery.tracking_number.toLowerCase().includes(query)) ||
                    delivery.delivery_address.toLowerCase().includes(query) ||
                    (delivery.courier_name && delivery.courier_name.toLowerCase().includes(query)) ||
                    (delivery.courier_phone && delivery.courier_phone.includes(query))
                );
            }

            // Фильтр по статусу
            if (this.statusFilter) {
                filtered = filtered.filter(delivery => delivery.status === this.statusFilter);
            }

            // Фильтр по типу
            if (this.typeFilter) {
                filtered = filtered.filter(delivery => delivery.delivery_type === this.typeFilter);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (this.sortBy === 'delivery_date') {
                    const dateA = a.delivery_date ? new Date(a.delivery_date) : new Date(0);
                    const dateB = b.delivery_date ? new Date(b.delivery_date) : new Date(0);
                    return dateB - dateA;
                } else if (this.sortBy === 'status') {
                    return a.status.localeCompare(b.status);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadDeliveries();
    },
    methods: {
        async loadDeliveries() {
            this.loading = true;
            this.error = null;
            try {
                const response = await deliveriesAPI.getAll();
                this.deliveries = response.data?.data || response.data || [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки доставок';
            } finally {
                this.loading = false;
            }
        },
        async handleStatusChange(deliveryId, newStatus) {
            try {
                await deliveriesAPI.updateStatus(deliveryId, newStatus);
                await this.loadDeliveries();
            } catch (error) {
                alert(error.message || 'Ошибка изменения статуса');
                await this.loadDeliveries();
            }
        },
        async handleDelete(delivery) {
            if (!confirm(`Вы уверены, что хотите удалить доставку для заказа #${delivery.order?.order_id || delivery.order_id}?`)) {
                return;
            }

            try {
                await deliveriesAPI.delete(delivery.id);
                await this.loadDeliveries();
            } catch (error) {
                alert(error.message || 'Ошибка удаления доставки');
            }
        },
        getDeliveryTypeLabel(type) {
            const labels = {
                courier: 'Курьер',
                pickup: 'Самовывоз',
                self_delivery: 'Своя доставка',
            };
            return labels[type] || type;
        },
        getStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                assigned: 'bg-blue-100 text-blue-800',
                in_transit: 'bg-indigo-100 text-indigo-800',
                delivered: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800',
                returned: 'bg-orange-100 text-orange-800',
            };
            return classes[status] || '';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        },
    },
};
</script>




