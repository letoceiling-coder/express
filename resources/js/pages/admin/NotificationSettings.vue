<template>
    <div class="notification-settings-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                Настройки уведомлений заказов
            </h1>
            <p class="text-muted-foreground mt-1">
                Управление уведомлениями при создании и обработке заказов
            </p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !settings.length" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка настроек...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Настройки -->
        <div v-else class="space-y-6">
            <div v-for="setting in settings" :key="setting.event" class="bg-card rounded-lg border border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">{{ getEventLabel(setting.event).title }}</h2>
                        <p class="text-sm text-muted-foreground mt-1">{{ getEventLabel(setting.event).description }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm">Включено</label>
                        <input
                            type="checkbox"
                            v-model="setting.enabled"
                            @change="updateSetting(setting.event, { enabled: setting.enabled })"
                            class="w-4 h-4"
                        />
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Шаблон сообщения -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">
                            Шаблон сообщения
                        </label>
                        <textarea
                            v-model="setting.message_template"
                            @blur="updateSetting(setting.event, { message_template: setting.message_template || null })"
                            placeholder="Используйте {order_id} для подстановки номера заказа"
                            class="w-full min-h-[100px] px-3 py-2 rounded-lg border border-input bg-background text-foreground"
                        ></textarea>
                        <p class="text-xs text-muted-foreground mt-1">
                            Плейсхолдеры: {'{order_id}'}, {'{amount}'} и другие
                        </p>
                    </div>

                    <!-- Support Chat ID (только для order_accepted_client) -->
                    <div v-if="setting.event === 'order_accepted_client'">
                        <label class="block text-sm font-medium text-foreground mb-1">
                            ID чата поддержки (Telegram ID администратора или username)
                        </label>
                        <input
                            v-model="setting.support_chat_id"
                            @blur="updateSetting(setting.event, { support_chat_id: setting.support_chat_id || null })"
                            placeholder="Например: 123456789 или @username"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            Если не указан, будет использован первый администратор бота
                        </p>
                    </div>

                    <!-- Кнопки -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-foreground">Кнопки</label>
                            <button
                                type="button"
                                @click="addButtonRow(setting)"
                                class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                            >
                                + Добавить строку
                            </button>
                        </div>

                        <div v-if="setting.buttons && setting.buttons.length > 0" class="space-y-3">
                            <div v-for="(row, rowIndex) in setting.buttons" :key="rowIndex" class="border rounded-lg p-4 space-y-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium">Строка {{ rowIndex + 1 }}</span>
                                    <button
                                        type="button"
                                        @click="removeButtonRow(setting, rowIndex)"
                                        class="text-destructive hover:text-destructive/80"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="(button, buttonIndex) in row" :key="buttonIndex" class="flex gap-2 items-end">
                                        <div class="flex-1">
                                            <label class="text-xs">Текст кнопки</label>
                                            <input
                                                v-model="button.text"
                                                @blur="updateSetting(setting.event, { buttons: setting.buttons })"
                                                placeholder="Текст кнопки"
                                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground mt-1"
                                            />
                                        </div>
                                        <div class="w-32">
                                            <label class="text-xs">Тип</label>
                                            <select
                                                v-model="button.type"
                                                @change="updateSetting(setting.event, { buttons: setting.buttons })"
                                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground mt-1"
                                            >
                                                <option value="callback">Callback</option>
                                                <option value="open_chat">Открыть чат</option>
                                                <option value="open_url">Открыть URL</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <label class="text-xs">
                                                {{ button.type === 'callback' ? 'Callback Data' : 
                                                   button.type === 'open_chat' ? 'Значение (support)' : 
                                                   'URL' }}
                                            </label>
                                            <input
                                                v-model="button.value"
                                                @blur="updateSetting(setting.event, { buttons: setting.buttons })"
                                                :placeholder="button.type === 'callback' ? 'order_admin_action:{order_id}:accept' :
                                                              button.type === 'open_chat' ? 'support' :
                                                              'https://example.com'"
                                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground mt-1"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeButton(setting, rowIndex, buttonIndex)"
                                            class="text-destructive hover:text-destructive/80"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addButton(setting, rowIndex)"
                                        class="w-full h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                                    >
                                        + Добавить кнопку
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="border border-dashed rounded-lg p-4 text-center">
                            <p class="text-sm text-muted-foreground mb-2">Кнопки не настроены</p>
                            <button
                                type="button"
                                @click="addButtonRow(setting)"
                                class="h-8 px-3 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                            >
                                + Добавить кнопки
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

const EVENT_LABELS = {
    order_created_client: {
        title: 'Уведомление клиенту при создании заказа',
        description: 'Отправляется сразу после создания заказа (статус: new)',
    },
    order_created_admin: {
        title: 'Уведомление администратору при создании заказа',
        description: 'Отправляется администраторам при создании нового заказа',
    },
    order_accepted_client: {
        title: 'Уведомление клиенту при принятии заказа',
        description: 'Отправляется клиенту после того, как администратор принял заказ',
    },
};

export default {
    name: 'NotificationSettings',
    data() {
        return {
            settings: [],
            loading: false,
            error: null,
        };
    },
    mounted() {
        this.loadSettings();
    },
    methods: {
        async loadSettings() {
            try {
                this.loading = true;
                this.error = null;
                const response = await axios.get('/api/v1/notification-settings');
                this.settings = response.data.data || [];
            } catch (error) {
                console.error('Error loading notification settings:', error);
                this.error = 'Ошибка при загрузке настроек уведомлений';
            } finally {
                this.loading = false;
            }
        },
        async updateSetting(event, updates) {
            try {
                await axios.put(`/api/v1/notification-settings/${event}`, updates);
                // Обновление уже произошло через v-model, просто показываем уведомление
                this.$toast?.success('Настройки успешно сохранены');
            } catch (error) {
                console.error('Error updating notification setting:', error);
                this.$toast?.error(error.response?.data?.message || 'Ошибка при сохранении настроек');
            }
        },
        getEventLabel(event) {
            return EVENT_LABELS[event] || { title: event, description: '' };
        },
        addButtonRow(setting) {
            if (!setting.buttons) {
                this.$set(setting, 'buttons', []);
            }
            setting.buttons.push([]);
            this.updateSetting(setting.event, { buttons: setting.buttons });
        },
        removeButtonRow(setting, rowIndex) {
            setting.buttons.splice(rowIndex, 1);
            if (setting.buttons.length === 0) {
                setting.buttons = null;
            }
            this.updateSetting(setting.event, { buttons: setting.buttons });
        },
        addButton(setting, rowIndex) {
            if (!setting.buttons[rowIndex]) {
                this.$set(setting.buttons, rowIndex, []);
            }
            setting.buttons[rowIndex].push({
                text: '',
                type: 'callback',
                value: '',
            });
            this.updateSetting(setting.event, { buttons: setting.buttons });
        },
        removeButton(setting, rowIndex, buttonIndex) {
            setting.buttons[rowIndex].splice(buttonIndex, 1);
            if (setting.buttons[rowIndex].length === 0) {
                setting.buttons.splice(rowIndex, 1);
            }
            if (setting.buttons.length === 0) {
                setting.buttons = null;
            }
            this.updateSetting(setting.event, { buttons: setting.buttons });
        },
    },
};
</script>
