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
                    <!-- Режим работы -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Режим работы <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-model="form.mode"
                            required
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.mode }"
                        >
                            <option value="sandbox">Тестовый (Sandbox)</option>
                            <option value="production">Рабочий (Production)</option>
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">
                            В тестовом режиме используются тестовые ключи, платежи не обрабатываются реально
                        </p>
                        <p v-if="errors.mode" class="mt-1 text-sm text-destructive">{{ errors.mode }}</p>
                    </div>

                    <!-- Shop ID -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Shop ID <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.shop_id"
                            type="text"
                            required
                            placeholder="Идентификатор магазина в ЮКасса"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            :class="{ 'border-destructive': errors.shop_id }"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Идентификатор магазина, полученный в личном кабинете ЮКасса
                        </p>
                        <p v-if="errors.shop_id" class="mt-1 text-sm text-destructive">{{ errors.shop_id }}</p>
                    </div>

                    <!-- Secret Key -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Secret Key <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.secret_key"
                                :type="showSecretKey ? 'text' : 'password'"
                                required
                                placeholder="Секретный ключ для API"
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
                            Секретный ключ для авторизации в API ЮКасса. Хранится в зашифрованном виде
                        </p>
                        <p v-if="errors.secret_key" class="mt-1 text-sm text-destructive">{{ errors.secret_key }}</p>
                    </div>

                    <!-- Return URL -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Return URL</label>
                        <input
                            v-model="form.return_url"
                            type="url"
                            placeholder="https://yoursite.com/payment/return"
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            URL для возврата после успешной оплаты (опционально)
                        </p>
                    </div>

                    <!-- Webhook URL -->
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

                    <!-- Автоматическое подтверждение платежа -->
                    <div class="flex items-center gap-3">
                        <input
                            v-model="form.auto_capture"
                            type="checkbox"
                            id="auto_capture"
                            class="w-4 h-4 rounded border-input"
                        />
                        <label for="auto_capture" class="text-sm font-medium text-foreground cursor-pointer">
                            Автоматическое подтверждение платежа (auto_capture)
                        </label>
                    </div>
                    <p class="text-xs text-muted-foreground -mt-4">
                        Если включено, платеж будет автоматически подтвержден после успешной оплаты
                    </p>

                    <!-- Включено -->
                    <div class="flex items-center gap-3">
                        <input
                            v-model="form.is_enabled"
                            type="checkbox"
                            id="is_enabled"
                            class="w-4 h-4 rounded border-input"
                        />
                        <label for="is_enabled" class="text-sm font-medium text-foreground cursor-pointer">
                            Включить платежную систему ЮКасса
                        </label>
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
                mode: 'sandbox',
                shop_id: '',
                secret_key: '',
                return_url: '',
                webhook_url: '',
                auto_capture: false,
                is_enabled: false,
            },
            errors: {},
            loading: false,
            testing: false,
            error: null,
            showSecretKey: false,
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
                this.settings = response.data;
                
                // Заполняем форму (если настройки уже есть)
                if (this.settings) {
                    this.form = {
                        mode: this.settings.is_test_mode ? 'sandbox' : 'production',
                        shop_id: this.settings.is_test_mode ? (this.settings.test_shop_id || '') : (this.settings.shop_id || ''),
                        secret_key: '', // Не показываем реальный ключ
                        return_url: this.settings.return_url || '',
                        webhook_url: this.settings.webhook_url || this.webhookUrl,
                        auto_capture: this.settings.auto_capture || false,
                        is_enabled: this.settings.is_enabled || false,
                    };
                } else {
                    this.form.webhook_url = this.webhookUrl;
                }
            } catch (error) {
                this.error = error.message || 'Ошибка загрузки настроек';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.errors = {};
            this.loading = true;
            this.testResult = null;

            try {
                // Преобразуем mode в is_test_mode для отправки на сервер
                const formData = {
                    ...this.form,
                    is_test_mode: this.form.mode === 'sandbox',
                    provider: 'yookassa',
                };
                
                // В зависимости от режима используем соответствующие поля
                if (this.form.mode === 'sandbox') {
                    formData.test_shop_id = this.form.shop_id;
                    formData.test_secret_key = this.form.secret_key;
                    // Очищаем production поля, если они были
                    delete formData.shop_id;
                    delete formData.secret_key;
                } else {
                    formData.shop_id = this.form.shop_id;
                    formData.secret_key = this.form.secret_key;
                    // Очищаем тестовые поля
                    delete formData.test_shop_id;
                    delete formData.test_secret_key;
                }
                
                // Удаляем mode, так как он не нужен на сервере
                delete formData.mode;

                await paymentSettingsAPI.updateYooKassaSettings(formData);
                window.showToast('success', 'Настройки успешно сохранены');
                await this.loadSettings();
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
            if (!this.form.shop_id || !this.form.secret_key) {
                window.showToast('error', 'Заполните Shop ID и Secret Key перед проверкой');
                return;
            }

            this.testing = true;
            this.testResult = null;

            try {
                const response = await paymentSettingsAPI.testYooKassaConnection({
                    shop_id: this.form.shop_id,
                    secret_key: this.form.secret_key,
                    is_test_mode: this.form.mode === 'sandbox',
                });
                
                this.testResult = {
                    success: true,
                    message: response.data?.message || 'Подключение к API ЮКасса успешно установлено',
                };
                window.showToast('success', 'Подключение успешно установлено');
            } catch (error) {
                this.testResult = {
                    success: false,
                    message: error.message || 'Не удалось подключиться к API ЮКасса',
                };
                window.showToast('error', error.message || 'Не удалось подключиться к API ЮКасса');
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

