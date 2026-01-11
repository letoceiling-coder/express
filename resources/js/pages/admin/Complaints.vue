<template>
    <div class="complaints-page">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Претензии</h1>
                <p class="text-muted-foreground mt-1">Управление претензиями</p>
            </div>
            <router-link
                to="/complaints/create"
                class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2"
            >
                <span>+</span>
                <span>Создать претензию</span>
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
                        placeholder="Поиск по теме, номеру заказа..."
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Тип</label>
                    <select
                        v-model="typeFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="quality">Качество</option>
                        <option value="delivery">Доставка</option>
                        <option value="service">Сервис</option>
                        <option value="payment">Оплата</option>
                        <option value="other">Другое</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Приоритет</label>
                    <select
                        v-model="priorityFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="low">Низкий</option>
                        <option value="medium">Средний</option>
                        <option value="high">Высокий</option>
                        <option value="urgent">Срочный</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Статус</label>
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="">Все</option>
                        <option value="new">Новая</option>
                        <option value="in_progress">В работе</option>
                        <option value="resolved">Решена</option>
                        <option value="rejected">Отклонена</option>
                        <option value="closed">Закрыта</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Сортировка</label>
                    <select
                        v-model="sortBy"
                        class="h-10 px-3 rounded-lg border border-input bg-background"
                    >
                        <option value="created_at">По дате</option>
                        <option value="priority">По приоритету</option>
                        <option value="status">По статусу</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка претензий...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Таблица претензий -->
        <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Тема</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Тип</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Приоритет</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Заказ</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-foreground">Дата</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-foreground">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="complaint in filteredComplaints" :key="complaint.id">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-foreground">#{{ complaint.id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-foreground">{{ complaint.subject }}</div>
                            <div v-if="complaint.description" class="text-xs text-muted-foreground line-clamp-1 mt-1">
                                {{ complaint.description }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ getTypeLabel(complaint.type) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="text-xs px-2 py-1 rounded"
                                :class="getPriorityClass(complaint.priority)"
                            >
                                {{ getPriorityLabel(complaint.priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <router-link
                                v-if="complaint.order_id"
                                :to="`/orders/${complaint.order_id}`"
                                class="text-sm font-medium text-accent hover:underline"
                            >
                                #{{ complaint.order?.order_id || complaint.order_id }}
                            </router-link>
                            <span v-else class="text-sm text-muted-foreground">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <select
                                :value="complaint.status"
                                @change="handleStatusChange(complaint.id, $event.target.value)"
                                class="text-xs px-2 py-1 rounded border border-input bg-background"
                                :class="getStatusClass(complaint.status)"
                            >
                                <option value="new">Новая</option>
                                <option value="in_progress">В работе</option>
                                <option value="resolved">Решена</option>
                                <option value="rejected">Отклонена</option>
                                <option value="closed">Закрыта</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-foreground">{{ formatDate(complaint.created_at) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <router-link
                                    :to="`/complaints/${complaint.id}/edit`"
                                    class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                >
                                    Открыть
                                </router-link>
                                <button
                                    @click="handleDelete(complaint)"
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
            <div v-if="filteredComplaints.length === 0" class="p-12 text-center">
                <p class="text-muted-foreground">Претензии не найдены</p>
            </div>
        </div>
    </div>
</template>

<script>
import { complaintsAPI } from '../../utils/api.js';

export default {
    name: 'Complaints',
    data() {
        return {
            complaints: [],
            loading: false,
            error: null,
            searchQuery: '',
            typeFilter: '',
            priorityFilter: '',
            statusFilter: '',
            sortBy: 'created_at',
        };
    },
    computed: {
        filteredComplaints() {
            let filtered = [...this.complaints];

            // Поиск
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(complaint =>
                    complaint.subject.toLowerCase().includes(query) ||
                    (complaint.description && complaint.description.toLowerCase().includes(query)) ||
                    (complaint.order?.order_id && complaint.order.order_id.toLowerCase().includes(query))
                );
            }

            // Фильтр по типу
            if (this.typeFilter) {
                filtered = filtered.filter(complaint => complaint.type === this.typeFilter);
            }

            // Фильтр по приоритету
            if (this.priorityFilter) {
                filtered = filtered.filter(complaint => complaint.priority === this.priorityFilter);
            }

            // Фильтр по статусу
            if (this.statusFilter) {
                filtered = filtered.filter(complaint => complaint.status === this.statusFilter);
            }

            // Сортировка
            filtered.sort((a, b) => {
                if (this.sortBy === 'created_at') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (this.sortBy === 'priority') {
                    const priorityOrder = { urgent: 4, high: 3, medium: 2, low: 1 };
                    return (priorityOrder[b.priority] || 0) - (priorityOrder[a.priority] || 0);
                } else if (this.sortBy === 'status') {
                    return a.status.localeCompare(b.status);
                }
                return 0;
            });

            return filtered;
        },
    },
    mounted() {
        this.loadComplaints();
    },
    methods: {
        async loadComplaints() {
            this.loading = true;
            this.error = null;
            try {
                const response = await complaintsAPI.getAll();
                this.complaints = response.data?.data || response.data || [];
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки претензий';
            } finally {
                this.loading = false;
            }
        },
        async handleStatusChange(complaintId, newStatus) {
            try {
                await complaintsAPI.updateStatus(complaintId, newStatus);
                await this.loadComplaints();
            } catch (error) {
                alert(error.message || 'Ошибка изменения статуса');
                await this.loadComplaints();
            }
        },
        async handleDelete(complaint) {
            if (!confirm(`Вы уверены, что хотите удалить претензию #${complaint.id}?`)) {
                return;
            }

            try {
                await complaintsAPI.delete(complaint.id);
                await this.loadComplaints();
            } catch (error) {
                alert(error.message || 'Ошибка удаления претензии');
            }
        },
        getTypeLabel(type) {
            const labels = {
                quality: 'Качество',
                delivery: 'Доставка',
                service: 'Сервис',
                payment: 'Оплата',
                other: 'Другое',
            };
            return labels[type] || type;
        },
        getPriorityLabel(priority) {
            const labels = {
                low: 'Низкий',
                medium: 'Средний',
                high: 'Высокий',
                urgent: 'Срочный',
            };
            return labels[priority] || priority;
        },
        getPriorityClass(priority) {
            const classes = {
                low: 'bg-gray-100 text-gray-800',
                medium: 'bg-blue-100 text-blue-800',
                high: 'bg-orange-100 text-orange-800',
                urgent: 'bg-red-100 text-red-800',
            };
            return classes[priority] || '';
        },
        getStatusClass(status) {
            const classes = {
                new: 'bg-yellow-100 text-yellow-800',
                in_progress: 'bg-blue-100 text-blue-800',
                resolved: 'bg-green-100 text-green-800',
                rejected: 'bg-red-100 text-red-800',
                closed: 'bg-gray-100 text-gray-800',
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




