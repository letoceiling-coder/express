<template>
    <div class="sms-settings-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Настройки SMS (IQSMS)</h1>
            <p class="text-muted-foreground mt-1">
                Управление отправкой SMS для авторизации по номеру телефона
            </p>
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
        <div v-else class="bg-card rounded-lg border border-border p-6">
            <p class="text-sm text-muted-foreground mb-6">
                Если настройки не заданы в админке, используются переменные окружения (IQSMS_LOGIN, IQSMS_PASSWORD).
                Включите «Использовать настройки из БД», чтобы приоритетно использовать данные ниже.
            </p>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="flex items-center justify-between rounded-lg border border-border p-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground">Использовать настройки из БД</label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Приоритет над переменными окружения
                        </p>
                    </div>
                    <input
                        v-model="form.is_enabled"
                        type="checkbox"
                        class="w-4 h-4 rounded border-input"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Логин</label>
                    <input
                        v-model="form.login"
                        type="text"
                        placeholder="IQSMS login"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Пароль</label>
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="Оставьте пустым, чтобы не менять"
                        autocomplete="new-password"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Хранится в зашифрованном виде. Заполните только при смене пароля.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Подпись отправителя</label>
                    <input
                        v-model="form.sender"
                        type="text"
                        placeholder="INFO"
                        maxlength="20"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Максимум 20 символов (по умолчанию INFO)
                    </p>
                </div>

                <div class="flex justify-end pt-4 border-t border-border">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50 inline-flex items-center gap-2"
                    >
                        <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ saving ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { smsSettingsAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

export default {
    name: 'SmsSettings',
    data() {
        return {
            settings: null,
            loading: false,
            error: null,
            saving: false,
            form: {
                login: '',
                password: '',
                sender: 'INFO',
                is_enabled: false,
            },
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
                const data = await smsSettingsAPI.getSettings();
                this.settings = data;
                if (data) {
                    this.form = {
                        login: data.login ?? '',
                        password: '',
                        sender: data.sender ?? 'INFO',
                        is_enabled: !!data.is_enabled,
                    };
                } else {
                    this.form = {
                        login: '',
                        password: '',
                        sender: 'INFO',
                        is_enabled: false,
                    };
                }
            } catch (err) {
                this.error = err.message || 'Ошибка загрузки настроек';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.saving = true;
            try {
                const payload = {
                    login: this.form.login || undefined,
                    sender: this.form.sender || undefined,
                    is_enabled: this.form.is_enabled,
                };
                if (this.form.password) {
                    payload.password = this.form.password;
                }

                const result = await smsSettingsAPI.updateSettings(payload);
                if (result) {
                    this.form.login = result.login ?? this.form.login;
                    this.form.sender = result.sender ?? this.form.sender;
                    this.form.is_enabled = result.is_enabled ?? this.form.is_enabled;
                    this.form.password = '';
                }
                await swal.success('Настройки SMS успешно сохранены');
            } catch (err) {
                await swal.error(err.message || 'Ошибка сохранения настроек');
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
