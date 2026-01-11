<template>
    <div class="payment-settings-yookassa-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Настройки ЮКасса</h1>
            <p class="text-muted-foreground mt-1">Настройка интеграции с платежной системой ЮКасса</p>
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
                    <!-- Основные настройки -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Включить интеграцию
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Разрешить прием платежей через ЮКасса
                                </p>
                            </div>
                            <input
                                v-model="form.is_enabled"
                                type="checkbox"
                                id="is_enabled"
                                class="w-4 h-4 rounded border-input"
                            />
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Режим работы
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    {{ form.is_test_mode ? 'Тестовый режим — используются тестовые ключи' : 'Рабочий режим — используются реальные ключи' }}
                                </p>
                            </div>
                            <input
                                v-model="form.is_test_mode"
                                type="checkbox"
                                id="is_test_mode"
                                class="w-4 h-4 rounded border-input"
                                @change="handleModeChange"
                            />
                        </div>
                        <div v-if="form.is_test_mode" class="px-3 py-2 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                            <p class="text-xs font-medium text-yellow-700 dark:text-yellow-400">
                                ⚠️ Тестовый режим активен. Платежи будут тестовыми.
                            </p>
                        </div>
                        <div v-else class="px-3 py-2 bg-green-500/10 border border-green-500/20 rounded-lg">
                            <p class="text-xs font-medium text-green-700 dark:text-green-400">
                                ✓ Рабочий режим активен. Платежи будут реальными.
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Автоматическое подтверждение
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Автоматически подтверждать платежи
                                </p>
                            </div>
                            <input
                                v-model="form.auto_capture"
                                type="checkbox"
                                id="auto_capture"
                                class="w-4 h-4 rounded border-input"
                            />
                        </div>
                    </div>

                    <!-- Тестовые ключи -->
                    <div v-if="form.is_test_mode" class="space-y-4 p-4 bg-yellow-500/5 border border-yellow-500/20 rounded-lg">
                        <h3 class="text-sm font-semibold text-foreground mb-3">Тестовые ключи (Sandbox)</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Test Shop ID <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="form.test_shop_id"
                                type="text"
                                :required="form.is_test_mode"
                                placeholder="Идентификатор тестового магазина"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                                :class="{ 'border-destructive': errors.test_shop_id }"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Тестовый идентификатор магазина из личного кабинета ЮКасса
                            </p>
                            <p v-if="errors.test_shop_id" class="mt-1 text-sm text-destructive">{{ errors.test_shop_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Test Secret Key <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.test_secret_key"
                                    :type="showTestSecretKey ? 'text' : 'password'"
                                    :required="form.is_test_mode"
                                    placeholder="Введите новый тестовый секретный ключ (оставьте пустым, чтобы не менять)"
                                    class="w-full h-10 px-3 pr-10 rounded-lg border border-input bg-background"
                                    :class="{ 'border-destructive': errors.test_secret_key }"
                                />
                                <button
                                    type="button"
                                    @click="showTestSecretKey = !showTestSecretKey"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    {{ showTestSecretKey ? '👁️' : '👁️‍🗨️' }}
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Оставьте пустым, если не хотите менять существующий ключ
                            </p>
                            <p v-if="errors.test_secret_key" class="mt-1 text-sm text-destructive">{{ errors.test_secret_key }}</p>
                        </div>
                    </div>

                    <!-- Реальные ключи -->
                    <div v-else class="space-y-4 p-4 bg-green-500/5 border border-green-500/20 rounded-lg">
                        <h3 class="text-sm font-semibold text-foreground mb-3">Реальные ключи (Production)</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Shop ID <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="form.shop_id"
                                type="text"
                                :required="!form.is_test_mode"
                                placeholder="Идентификатор магазина"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                                :class="{ 'border-destructive': errors.shop_id }"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Реальный идентификатор магазина из личного кабинета ЮКасса
                            </p>
                            <p v-if="errors.shop_id" class="mt-1 text-sm text-destructive">{{ errors.shop_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Secret Key <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.secret_key"
                                    :type="showSecretKey ? 'text' : 'password'"
                                    :required="!form.is_test_mode"
                                    placeholder="Введите новый секретный ключ (оставьте пустым, чтобы не менять)"
                                    class="w-full h-10 px-3 pr-10 rounded-lg border border-input bg-background"
                                    :class="{ 'border-destructive': errors.secret_key }"
                                />
                                <button
                                    type="button"
                                    @click="showSecretKey = !showSecretKey"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    {{ showSecretKey ? '👁️' : '👁️‍🗨️' }}
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Оставьте пустым, если не хотите менять существующий ключ
                            </p>
                            <p v-if="errors.secret_key" class="mt-1 text-sm text-destructive">{{ errors.secret_key }}</p>
                        </div>
                    </div>

                    <!-- Дополнительные настройки -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Название магазина для страницы оплаты
                            </label>
                            <input
                                v-model="form.merchant_name"
                                type="text"
                                placeholder="ИП Ходжаян Артур Альбертович"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Название, которое будет отображаться на странице оплаты ЮКасса
                            </p>
                        </div>

                    <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Шаблон описания платежа
                            </label>
                            <input
                                v-model="form.description_template"
                                type="text"
                                placeholder="Оплата заказа {order_id}"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Используйте {'{order_id}'} для подстановки номера заказа
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Webhook URL</label>
                            <input
                                v-model="form.webhook_url"
                                type="url"
                                placeholder="https://yoursite.com/api/v1/webhooks/yookassa"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                                :value="webhookUrl"
                                readonly
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                URL для получения уведомлений от ЮКасса. Укажите этот URL в настройках вебхука в личном кабинете ЮКасса
                            </p>
                            <button
                                type="button"
                                @click="copyWebhookUrl"
                                class="mt-2 h-8 px-4 text-sm bg-accent/10 text-accent rounded-lg hover:bg-accent/20"
                            >
                                Копировать URL
                            </button>
                        </div>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex items-center gap-4 pt-4 border-t border-border">
                        <button
                            type="submit"
                            :disabled="loading"
                            class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
                        >
                            {{ loading ? 'Сохранение...' : 'Сохранить настройки' }}
                        </button>
                        <button
                            type="button"
                            @click="handleTestConnection"
                            :disabled="testing || loading"
                            class="h-10 px-6 bg-accent/10 text-accent rounded-lg hover:bg-accent/20 disabled:opacity-50"
                        >
                            {{ testing ? 'Проверка...' : 'Проверить подключение' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Информация -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">Инструкция по настройке</h3>
                <div class="space-y-3 text-sm text-blue-800">
                    <div>
                        <strong>1. Получение ключей:</strong>
                        <ul class="list-disc list-inside mt-1 ml-4">
                            <li>Войдите в личный кабинет ЮКасса</li>
                            <li>Перейдите в раздел "Настройки" → "API"</li>
                            <li>Создайте новый ключ или используйте существующий</li>
                            <li>Скопируйте Shop ID и Secret Key</li>
                        </ul>
                    </div>
                    <div>
                        <strong>2. Настройка вебхука:</strong>
                        <ul class="list-disc list-inside mt-1 ml-4">
                            <li>В личном кабинете ЮКасса перейдите в "Настройки" → "Webhook"</li>
                            <li>Укажите URL вебхука из формы выше</li>
                            <li>Выберите события: payment.succeeded, payment.canceled, refund.succeeded</li>
                        </ul>
                    </div>
                    <div>
                        <strong>3. Тестирование:</strong>
                        <ul class="list-disc list-inside mt-1 ml-4">
                            <li>Используйте тестовый режим для проверки</li>
                            <li>Тестовые карты: 5555 5555 5555 4444 (успешный), 5555 5555 5555 4477 (отклоненный)</li>
                            <li>После проверки переключитесь на рабочий режим</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Статус подключения -->
            <div v-if="testResult" class="bg-card rounded-lg border border-border p-6">
                <h3 class="text-lg font-semibold text-foreground mb-4">Результат проверки подключения</h3>
                <div
                    :class="testResult.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                    class="border rounded-lg p-4"
                >
                    <p class="font-medium">{{ testResult.success ? '✓ Подключение успешно' : '✗ Ошибка подключения' }}</p>
                    <p v-if="testResult.message" class="mt-2 text-sm">{{ testResult.message }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { paymentSettingsAPI } from '../../utils/api.js';

export default {
    name: 'PaymentSettingsYooKassa',
    data() {
        return {
            settings: null,
            form: {
                is_test_mode: true,
                is_enabled: false,
                shop_id: '',
                secret_key: '',
                test_shop_id: '',
                test_secret_key: '',
                webhook_url: '',
                description_template: '',
                merchant_name: '',
                auto_capture: true,
            },
            // Сохраняем введенные ключи, чтобы не терять их при перезагрузке
            savedSecretKeys: {
                secret_key: '',
                test_secret_key: '',
            },
            errors: {},
            loading: false,
            testing: false,
            error: null,
            showSecretKey: false,
            showTestSecretKey: false,
            testResult: null,
        };
    },
    computed: {
        webhookUrl() {
            const baseUrl = window.location.origin;
            return `${baseUrl}/api/v1/webhooks/yookassa`;
        },
    },
    mounted() {
        this.loadSettings();
    },
    methods: {
        async loadSettings() {
            this.loading = true;
            this.error = null;
            try {
                const response = await paymentSettingsAPI.getYooKassaSettings();
                this.settings = response.data?.data || response.data;
                
                // Заполняем форму (если настройки уже есть)
                if (this.settings) {
                    // Сохраняем введенные ключи перед обновлением
                    const currentSecretKey = this.form.secret_key || this.savedSecretKeys.secret_key;
                    const currentTestSecretKey = this.form.test_secret_key || this.savedSecretKeys.test_secret_key;
                    
                    this.form = {
                        is_test_mode: this.settings.is_test_mode !== undefined ? this.settings.is_test_mode : true,
                        is_enabled: this.settings.is_enabled !== undefined ? this.settings.is_enabled : false,
                        shop_id: this.settings.shop_id || '',
                        secret_key: currentSecretKey || '', // Сохраняем введенный ключ
                        test_shop_id: this.settings.test_shop_id || '',
                        test_secret_key: currentTestSecretKey || '', // Сохраняем введенный ключ
                        webhook_url: this.settings.webhook_url || this.webhookUrl,
                        description_template: this.settings.description_template || '',
                        merchant_name: this.settings.merchant_name || '',
                        auto_capture: this.settings.auto_capture !== undefined ? this.settings.auto_capture : true,
                    };
                    
                    // Обновляем сохраненные ключи
                    this.savedSecretKeys.secret_key = currentSecretKey;
                    this.savedSecretKeys.test_secret_key = currentTestSecretKey;
                } else {
                    this.form.webhook_url = this.webhookUrl;
                }
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки настроек';
            } finally {
                this.loading = false;
            }
        },
        
        handleModeChange() {
            // При переключении режима очищаем результаты теста
            this.testResult = null;
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;
            this.testResult = null;

            try {
                // Сохраняем введенные ключи перед отправкой
                const currentFormData = { ...this.form };
                const savedSecretKey = currentFormData.secret_key;
                const savedTestSecretKey = currentFormData.test_secret_key;
                
                // Подготавливаем данные для отправки
                const formData = {
                    provider: 'yookassa',
                    is_test_mode: this.form.is_test_mode,
                    is_enabled: this.form.is_enabled,
                    auto_capture: this.form.auto_capture,
                    webhook_url: this.form.webhook_url || this.webhookUrl,
                    description_template: this.form.description_template || null,
                    merchant_name: this.form.merchant_name || null,
                };
                
                // Добавляем соответствующие поля в зависимости от режима
                if (this.form.is_test_mode) {
                    if (this.form.test_shop_id) {
                        formData.test_shop_id = this.form.test_shop_id;
                    }
                    if (savedTestSecretKey && savedTestSecretKey.trim()) {
                        formData.test_secret_key = savedTestSecretKey;
                    }
                } else {
                    if (this.form.shop_id) {
                        formData.shop_id = this.form.shop_id;
                    }
                    if (savedSecretKey && savedSecretKey.trim()) {
                        formData.secret_key = savedSecretKey;
                    }
                }

                const response = await paymentSettingsAPI.updateYooKassaSettings(formData);
                const savedData = response.data?.data || response.data;
                
                // Сохраняем введенные ключи
                this.savedSecretKeys.secret_key = savedSecretKey;
                this.savedSecretKeys.test_secret_key = savedTestSecretKey;
                
                // Обновляем форму данными из ответа, сохраняя введенные ключи
                if (savedData) {
                    this.form = {
                        is_test_mode: savedData.is_test_mode !== undefined ? savedData.is_test_mode : this.form.is_test_mode,
                        is_enabled: savedData.is_enabled !== undefined ? savedData.is_enabled : this.form.is_enabled,
                        shop_id: savedData.shop_id || this.form.shop_id || '',
                        secret_key: savedSecretKey || '', // Сохраняем введенный ключ
                        test_shop_id: savedData.test_shop_id || this.form.test_shop_id || '',
                        test_secret_key: savedTestSecretKey || '', // Сохраняем введенный ключ
                        webhook_url: savedData.webhook_url || this.form.webhook_url || this.webhookUrl,
                        description_template: savedData.description_template || this.form.description_template || '',
                        merchant_name: savedData.merchant_name || this.form.merchant_name || '',
                        auto_capture: savedData.auto_capture !== undefined ? savedData.auto_capture : this.form.auto_capture,
                    };
                } else {
                    // Если ответ не содержит данных, перезагружаем настройки
                    await this.loadSettings();
                }
                
                window.showToast('success', 'Настройки успешно сохранены');
            } catch (error) {
                const errorData = error.response?.data || {};
                if (errorData.errors) {
                    this.errors = errorData.errors;
                    window.showToast('error', 'Ошибка валидации. Проверьте введенные данные.');
                } else {
                    window.showToast('error', error.message || 'Ошибка сохранения настроек');
                }
            } finally {
                this.loading = false;
            }
        },
        async handleTestConnection() {
            const shopId = this.form.is_test_mode ? this.form.test_shop_id : this.form.shop_id;
            const secretKey = this.form.is_test_mode ? this.form.test_secret_key : this.form.secret_key;
            
            if (!shopId || !secretKey) {
                window.showToast('error', `Заполните ${this.form.is_test_mode ? 'Test ' : ''}Shop ID и ${this.form.is_test_mode ? 'Test ' : ''}Secret Key перед проверкой`);
                return;
            }

            if (!this.form.is_enabled) {
                window.showToast('error', 'Включите интеграцию перед проверкой подключения');
                return;
            }

            this.testing = true;
            this.testResult = null;

            try {
                // Сначала сохраняем текущие настройки, если они были изменены
                // (тест использует сохраненные настройки из БД)
                const response = await paymentSettingsAPI.testYooKassaConnection();
                
                const result = response.data || response;
                this.testResult = {
                    success: result.success || false,
                    message: result.message || (result.success ? 'Подключение к API ЮКасса успешно установлено' : 'Не удалось подключиться к API ЮКасса'),
                };
                
                if (this.testResult.success) {
                    window.showToast('success', `Подключение успешно (${this.form.is_test_mode ? 'тестовый режим' : 'рабочий режим'})`);
                } else {
                    window.showToast('error', this.testResult.message);
                }
            } catch (error) {
                const errorData = error.response?.data || {};
                this.testResult = {
                    success: false,
                    message: errorData.message || error.message || 'Не удалось подключиться к API ЮКасса',
                };
                window.showToast('error', this.testResult.message);
            } finally {
                this.testing = false;
            }
        },
        copyWebhookUrl() {
            navigator.clipboard.writeText(this.webhookUrl).then(() => {
                alert('URL вебхука скопирован в буфер обмена');
            }).catch(() => {
                alert('Не удалось скопировать URL');
            });
        },
    },
};
</script>

