<template>
    <div class="delivery-settings-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Настройки доставки</h1>
            <p class="text-muted-foreground mt-1">Настройка расчета стоимости доставки по расстоянию</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !settings" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка настроек...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Форма настроек -->
        <div v-else class="space-y-6">
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold text-foreground mb-6">Параметры подключения</h2>
                
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- API Settings -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                API ключ Яндекс.Геокодер
                            </label>
                            <input
                                v-model="form.yandex_geocoder_api_key"
                                type="password"
                                placeholder="Введите API ключ Яндекс.Геокодера"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Оставьте пустым, чтобы не изменять существующий ключ
                            </p>
                        </div>
                    </div>

                    <!-- Default City -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Город по умолчанию для поиска адресов
                            </label>
                            <input
                                v-model="form.default_city"
                                type="text"
                                placeholder="Например: Екатеринбург"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Город, по которому будет происходить поиск адресов при оформлении заказа
                            </p>
                        </div>
                    </div>

                    <!-- Free Delivery Threshold -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Сумма корзины для бесплатной доставки (₽)
                            </label>
                            <input
                                v-model.number="form.free_delivery_threshold"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="10000"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                При сумме заказа равной или превышающей указанную, доставка будет бесплатной
                            </p>
                        </div>
                    </div>

                    <!-- Origin Point -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Точка начала доставки
                            </label>
                            <p class="text-xs text-muted-foreground mb-2">
                                Адрес и координаты точки, от которой рассчитывается расстояние доставки
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Адрес
                            </label>
                            <input
                                v-model="form.origin_address"
                                type="text"
                                placeholder="г. Екатеринбург, ул. Ленина, 1"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Адрес будет автоматически геокодирован при сохранении
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Широта
                                </label>
                                <input
                                    v-model="form.origin_latitude"
                                    type="number"
                                    step="any"
                                    placeholder="56.8431"
                                    class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Долгота
                                </label>
                                <input
                                    v-model="form.origin_longitude"
                                    type="number"
                                    step="any"
                                    placeholder="60.6454"
                                    class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Zones -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Зоны доставки
                            </label>
                            <p class="text-xs text-muted-foreground mb-2">
                                Настройте зоны доставки по расстоянию. Последняя зона с пустым расстоянием будет применяться для всех адресов дальше предыдущих зон.
                            </p>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="(zone, index) in form.delivery_zones"
                                :key="index"
                                class="flex items-end gap-4 p-4 border border-border rounded-lg"
                            >
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-foreground mb-1">
                                        Максимальное расстояние (км)
                                    </label>
                                    <input
                                        v-model.number="zone.max_distance"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        :placeholder="index === form.delivery_zones.length - 1 ? 'Свыше предыдущей зоны' : '3'"
                                        class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                        :disabled="index === form.delivery_zones.length - 1 && zone.max_distance === null"
                                    />
                                    <p v-if="index === form.delivery_zones.length - 1" class="text-xs text-muted-foreground mt-1">
                                        Оставьте пустым для последней зоны
                                    </p>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-foreground mb-1">
                                        Стоимость (₽)
                                    </label>
                                    <input
                                        v-model.number="zone.cost"
                                        type="number"
                                        step="1"
                                        min="0"
                                        placeholder="300"
                                        class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                                    />
                                </div>
                                <button
                                    v-if="form.delivery_zones.length > 1"
                                    type="button"
                                    @click="removeZone(index)"
                                    class="h-10 px-3 bg-destructive/10 text-destructive rounded-lg hover:bg-destructive/20"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="addZone"
                            class="w-full h-10 px-4 bg-secondary text-foreground rounded-lg hover:bg-secondary/80 border border-border inline-flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Добавить зону
                        </button>
                    </div>

                    <!-- Enable/Disable -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Включить систему расчета доставки
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Включите эту опцию, чтобы система автоматически рассчитывала стоимость доставки
                                </p>
                            </div>
                            <input
                                v-model="form.is_enabled"
                                type="checkbox"
                                id="is_enabled"
                                class="w-4 h-4 rounded border-input"
                            />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-4 pt-4 border-t border-border">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="h-10 px-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 inline-flex items-center gap-2 disabled:opacity-50"
                        >
                            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { deliverySettingsAPI } from '../../utils/api.js';

export default {
    name: 'DeliverySettings',
    data() {
        return {
            settings: null,
            form: {
                yandex_geocoder_api_key: '',
                origin_address: '',
                origin_latitude: '',
                origin_longitude: '',
                default_city: 'Екатеринбург',
                free_delivery_threshold: 10000,
                delivery_zones: [
                    { max_distance: 3, cost: 300 },
                    { max_distance: 7, cost: 500 },
                    { max_distance: 12, cost: 800 },
                    { max_distance: null, cost: 1000 },
                ],
                is_enabled: false,
            },
            // Сохраняем введенный API ключ, чтобы не терять его при перезагрузке
            savedApiKey: '',
            errors: {},
            loading: false,
            saving: false,
            error: null,
        };
    },
    mounted() {
        this.loadSettings();
    },
    methods: {
        async loadSettings() {
            this.loading = true;
            this.error = null;
            try {
                const response = await deliverySettingsAPI.getSettings();
                this.settings = response.data?.data || response.data;
                
                // Заполняем форму (если настройки уже есть)
                if (this.settings) {
                    // Сохраняем введенный ключ перед обновлением
                    const currentApiKey = this.form.yandex_geocoder_api_key || this.savedApiKey;
                    
                    this.form = {
                        yandex_geocoder_api_key: currentApiKey || '', // Сохраняем введенный ключ
                        origin_address: this.settings.origin_address || '',
                        origin_latitude: this.settings.origin_latitude ? String(this.settings.origin_latitude) : '',
                        origin_longitude: this.settings.origin_longitude ? String(this.settings.origin_longitude) : '',
                        default_city: this.settings.default_city || 'Екатеринбург',
                        free_delivery_threshold: this.settings.free_delivery_threshold !== undefined && this.settings.free_delivery_threshold !== null
                            ? Number(this.settings.free_delivery_threshold)
                            : 10000,
                        delivery_zones: this.settings.delivery_zones && Array.isArray(this.settings.delivery_zones) && this.settings.delivery_zones.length > 0
                            ? this.settings.delivery_zones
                            : this.form.delivery_zones,
                        is_enabled: this.settings.is_enabled !== undefined ? this.settings.is_enabled : false,
                    };
                    
                    // Обновляем сохраненный ключ
                    this.savedApiKey = currentApiKey;
                }
            } catch (error) {
                console.error('Error loading delivery settings:', error);
                if (error.response?.status !== 404) {
                    this.error = error.message || 'Ошибка при загрузке настроек';
                }
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.saving = true;
            this.errors = {};
            this.error = null;
            
            try {
                const submitData = {
                    ...this.form,
                    origin_latitude: this.form.origin_latitude ? parseFloat(this.form.origin_latitude) : null,
                    origin_longitude: this.form.origin_longitude ? parseFloat(this.form.origin_longitude) : null,
                    // Удаляем пустой API ключ из отправки (чтобы не перезаписывать существующий)
                    yandex_geocoder_api_key: this.form.yandex_geocoder_api_key || undefined,
                };
                
                const response = await deliverySettingsAPI.updateSettings(submitData);
                this.settings = response.data?.data || response.data;
                
                // Показываем успешное сообщение
                alert('Настройки доставки успешно сохранены');
                
                // Обновляем сохраненный ключ
                if (this.form.yandex_geocoder_api_key) {
                    this.savedApiKey = this.form.yandex_geocoder_api_key;
                }
            } catch (error) {
                console.error('Error saving delivery settings:', error);
                this.error = error.message || 'Ошибка при сохранении настроек';
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
            } finally {
                this.saving = false;
            }
        },
        addZone() {
            this.form.delivery_zones.push({ max_distance: null, cost: 0 });
        },
        removeZone(index) {
            if (this.form.delivery_zones.length > 1) {
                this.form.delivery_zones.splice(index, 1);
            }
        },
    },
};
</script>

