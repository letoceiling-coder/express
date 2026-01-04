<template>
    <div class="broadcasts-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Рассылки</h1>
                <p class="text-muted-foreground mt-1">Отправка сообщений пользователям бота</p>
            </div>
        </div>

        <!-- Форма создания рассылки -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-xl font-semibold text-foreground mb-4">Создать рассылку</h2>

            <form @submit.prevent="sendBroadcast" class="space-y-4">
                <!-- Выбор бота -->
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Бот *</label>
                    <select
                        v-model="form.bot_id"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        @change="handleBotChange"
                    >
                        <option value="">Выберите бота</option>
                        <option v-for="bot in bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                    </select>
                </div>

                <!-- Тип контента -->
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Тип контента *</label>
                    <select
                        v-model="form.type"
                        required
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                        @change="handleTypeChange"
                    >
                        <option value="message">Сообщение (текст)</option>
                        <option value="photo">Фото + текст</option>
                        <option value="video">Видео + текст</option>
                        <option value="document">Документ</option>
                        <option value="media_group">Галерея (фото/видео)</option>
                    </select>
                </div>

                <!-- Контент -->
                <div v-if="form.type === 'message'">
                    <label class="text-sm font-medium text-foreground mb-1 block">Текст сообщения *</label>
                    <textarea
                        v-model="form.content.text"
                        required
                        rows="6"
                        placeholder="Введите текст сообщения..."
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background resize-none font-mono text-sm"
                    ></textarea>
                    <p class="text-xs text-muted-foreground mt-1">
                        Поддерживается HTML форматирование, если выбран parse_mode: HTML
                    </p>
                </div>

                <div v-if="form.type === 'photo'">
                    <label class="text-sm font-medium text-foreground mb-1 block">Фото *</label>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="showMediaSelector = true; mediaSelectorType = 'photo'"
                            class="px-4 py-2 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            Выбрать из медиа-библиотеки
                        </button>
                        <input
                            v-model="form.content.photo"
                            type="text"
                            placeholder="Или введите URL/file_id"
                            class="flex-1 h-10 px-3 rounded-lg border border-input bg-background font-mono text-sm"
                        />
                    </div>
                    <div v-if="selectedPhoto" class="mt-2">
                        <div class="relative inline-block">
                            <img :src="selectedPhoto.url" :alt="selectedPhoto.name" class="w-32 h-32 object-cover rounded border border-border" />
                            <button
                                type="button"
                                @click="removePhoto"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Выберите фото из медиа-библиотеки или введите URL/file_id
                    </p>
                    <label class="text-sm font-medium text-foreground mb-1 block mt-3">Подпись к фото (опционально)</label>
                    <textarea
                        v-model="form.content.caption"
                        rows="3"
                        placeholder="Подпись к фото..."
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background resize-none"
                    ></textarea>
                </div>

                <div v-if="form.type === 'video'">
                    <label class="text-sm font-medium text-foreground mb-1 block">Видео *</label>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="showMediaSelector = true; mediaSelectorType = 'video'"
                            class="px-4 py-2 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            Выбрать из медиа-библиотеки
                        </button>
                        <input
                            v-model="form.content.video"
                            type="text"
                            placeholder="Или введите URL/file_id"
                            class="flex-1 h-10 px-3 rounded-lg border border-input bg-background font-mono text-sm"
                        />
                    </div>
                    <div v-if="selectedVideo" class="mt-2">
                        <div class="relative inline-block">
                            <div class="w-32 h-32 bg-muted rounded border border-border flex items-center justify-center">
                                <span class="text-2xl">🎥</span>
                            </div>
                            <button
                                type="button"
                                @click="removeVideo"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Выберите видео из медиа-библиотеки или введите URL/file_id
                    </p>
                    <label class="text-sm font-medium text-foreground mb-1 block mt-3">Подпись к видео (опционально)</label>
                    <textarea
                        v-model="form.content.caption"
                        rows="3"
                        placeholder="Подпись к видео..."
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background resize-none"
                    ></textarea>
                </div>

                <div v-if="form.type === 'document'">
                    <label class="text-sm font-medium text-foreground mb-1 block">URL документа или file_id *</label>
                    <input
                        v-model="form.content.document"
                        type="text"
                        required
                        placeholder="https://example.com/document.pdf или BQACAgIAAxkBAAI..."
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background font-mono text-sm"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Можно использовать URL документа или file_id из Telegram
                    </p>
                    <label class="text-sm font-medium text-foreground mb-1 block mt-3">Подпись к документу (опционально)</label>
                    <textarea
                        v-model="form.content.caption"
                        rows="3"
                        placeholder="Подпись к документу..."
                        class="w-full px-3 py-2 rounded-lg border border-input bg-background resize-none"
                    ></textarea>
                </div>

                <div v-if="form.type === 'media_group'">
                    <label class="text-sm font-medium text-foreground mb-1 block">Медиа-файлы *</label>
                    <button
                        type="button"
                        @click="showMediaSelector = true; mediaSelectorType = 'media_group'"
                        class="w-full px-4 py-2 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors mb-2"
                    >
                        {{ selectedMedia.length > 0 ? `Выбрано файлов: ${selectedMedia.length}` : 'Выбрать медиа-файлы' }}
                    </button>
                    <p class="text-xs text-muted-foreground mb-2">
                        Можно выбрать до 10 фото или видео (в одной галерее могут быть только фото или только видео)
                    </p>
                    
                    <div v-if="selectedMedia.length > 0" class="grid grid-cols-4 gap-2 mt-2">
                        <div
                            v-for="(media, index) in selectedMedia"
                            :key="media.id || index"
                            class="relative group"
                        >
                            <img
                                v-if="media.type === 'photo'"
                                :src="media.url"
                                :alt="media.name"
                                class="w-full h-24 object-cover rounded border border-border"
                            />
                            <div
                                v-else
                                class="w-full h-24 bg-muted rounded border border-border flex items-center justify-center"
                            >
                                <span class="text-xl">🎥</span>
                            </div>
                            <button
                                type="button"
                                @click="removeMedia(index)"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                ×
                            </button>
                            <input
                                v-model="media.caption"
                                type="text"
                                placeholder="Подпись (опционально)"
                                class="w-full mt-1 px-2 py-1 text-xs rounded border border-border bg-background"
                                @input="updateMediaCaption(index, $event.target.value)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Получатели -->
                <div>
                    <label class="text-sm font-medium text-foreground mb-1 block">Получатели *</label>
                    <select
                        v-model="recipientType"
                        class="w-full h-10 px-3 rounded-lg border border-input bg-background mb-2"
                        @change="handleRecipientTypeChange"
                    >
                        <option value="all">Все пользователи бота</option>
                        <option value="selected">Выбранные пользователи</option>
                    </select>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ recipientType === 'all' 
                            ? 'Рассылка будет отправлена всем активным пользователям выбранного бота' 
                            : 'Выберите конкретных пользователей из списка' }}
                    </p>

                    <!-- Выбор пользователей -->
                    <div v-if="recipientType === 'selected'" class="mt-4">
                        <button
                            type="button"
                            @click="showUserSelector = true"
                            class="w-full px-4 py-2 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            {{ selectedUsers.length > 0 
                                ? `Выбрано пользователей: ${selectedUsers.length}` 
                                : 'Выбрать пользователей' }}
                        </button>
                        <div v-if="selectedUsers.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="user in selectedUsers"
                                :key="user.id"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500/10 text-blue-500 rounded text-sm"
                            >
                                {{ user.first_name || user.username || `ID: ${user.telegram_id}` }}
                                <button
                                    type="button"
                                    @click="removeUser(user.id)"
                                    class="hover:text-blue-700"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Опции -->
                <div class="bg-muted/30 rounded-lg p-4">
                    <label class="text-sm font-medium text-foreground mb-3 block">Дополнительные опции</label>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-foreground mb-1 block">Формат текста (parse_mode)</label>
                            <select
                                v-model="form.options.parse_mode"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            >
                <option value="HTML">HTML</option>
                <option value="MarkdownV2">MarkdownV2</option>
                <option :value="null">Без форматирования</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.options.disable_notification"
                                type="checkbox"
                                class="w-4 h-4"
                            />
                            <span class="text-sm">Отключить уведомления (тихая отправка)</span>
                        </label>
                    </div>
                </div>

                <!-- Кнопки -->
                <div class="flex gap-2 pt-4 border-t border-border">
                    <button
                        type="button"
                        @click="previewBroadcast"
                        :disabled="sending || previewing || !canPreview"
                        class="flex-1 px-4 py-2 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ previewing ? 'Предпросмотр...' : 'Предпросмотр' }}
                    </button>
                    <button
                        type="submit"
                        :disabled="sending || previewing || !canSend"
                        class="flex-1 px-4 py-2 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ sending ? 'Отправка...' : 'Отправить' }}
                    </button>
                </div>
            </form>

            <!-- Предпросмотр -->
            <div v-if="previewResult" class="mt-6 p-4 bg-muted/50 rounded-lg border border-border">
                <h3 class="text-lg font-semibold text-foreground mb-3">Предпросмотр рассылки</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-muted-foreground">Количество получателей:</span>
                        <span class="text-sm font-medium text-foreground">{{ previewResult.recipients_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-muted-foreground">Тип контента:</span>
                        <span class="text-sm font-medium text-foreground">{{ getTypeLabel(form.type) }}</span>
                    </div>
                </div>
            </div>

            <!-- Результат отправки -->
            <div v-if="sendResult" class="mt-6 p-4 rounded-lg border" :class="sendResult.success ? 'bg-green-500/10 border-green-500/20' : 'bg-red-500/10 border-red-500/20'">
                <h3 class="text-lg font-semibold mb-3" :class="sendResult.success ? 'text-green-500' : 'text-red-500'">
                    {{ sendResult.success ? 'Рассылка отправлена' : 'Ошибка отправки' }}
                </h3>
                <div v-if="sendResult.success && sendResult.data" class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-muted-foreground">Всего получателей:</span>
                        <span class="text-sm font-medium text-foreground">{{ sendResult.data.total }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-muted-foreground">Успешно отправлено:</span>
                        <span class="text-sm font-medium text-green-500">{{ sendResult.data.sent }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-muted-foreground">Ошибок:</span>
                        <span class="text-sm font-medium text-red-500">{{ sendResult.data.failed }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-red-500">{{ sendResult.message }}</p>
            </div>
        </div>

        <!-- MediaSelector Modal -->
        <MediaSelector
            :open="showMediaSelector"
            :multiple="mediaSelectorType === 'media_group'"
            :allowedTypes="mediaSelectorType === 'photo' ? ['photo'] : mediaSelectorType === 'video' ? ['video'] : ['photo', 'video']"
            :currentSelection="mediaSelectorType === 'photo' ? (selectedPhoto ? [selectedPhoto] : []) : mediaSelectorType === 'video' ? (selectedVideo ? [selectedVideo] : []) : selectedMedia"
            @close="showMediaSelector = false"
            @select="handleMediaSelect"
        />

        <!-- Модальное окно выбора пользователей -->
        <div v-if="showUserSelector" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
                <div class="sticky top-0 bg-background border-b border-border p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Выбор пользователей</h3>
                        <button
                            @click="showUserSelector = false"
                            class="text-muted-foreground hover:text-foreground"
                        >
                            ✕
                        </button>
                    </div>
                    <div class="mt-3">
                        <input
                            v-model="userSearchQuery"
                            type="text"
                            placeholder="Поиск по имени, username, telegram_id..."
                            class="w-full h-10 px-3 rounded-lg border border-input bg-background"
                            @input="debounceUserSearch"
                        />
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <div v-if="usersLoading" class="text-center py-8">
                        <p class="text-muted-foreground">Загрузка пользователей...</p>
                    </div>
                    <div v-else-if="availableUsers.length === 0" class="text-center py-8">
                        <p class="text-muted-foreground">Пользователи не найдены</p>
                    </div>
                    <div v-else class="space-y-2">
                        <label
                            v-for="user in availableUsers"
                            :key="user.id"
                            class="flex items-center gap-3 p-3 hover:bg-muted/50 rounded-lg cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="user.id"
                                :checked="isUserSelected(user.id)"
                                @change="toggleUserSelection(user)"
                                class="w-4 h-4"
                            />
                            <div class="flex-1">
                                <div class="font-medium text-foreground">
                                    {{ user.first_name || '' }} {{ user.last_name || '' }}
                                    <span v-if="user.username" class="text-muted-foreground">@{{ user.username }}</span>
                                </div>
                                <div class="text-sm text-muted-foreground font-mono">ID: {{ user.telegram_id }}</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="sticky bottom-0 bg-background border-t border-border p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">
                            Выбрано: {{ selectedUsers.length }}
                        </span>
                        <button
                            @click="showUserSelector = false"
                            class="px-4 py-2 bg-accent/10 text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors"
                        >
                            Готово
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import MediaSelector from '../../components/admin/MediaSelector.vue';

export default {
    name: 'Broadcasts',
    components: {
        MediaSelector,
    },
    data() {
        return {
            bots: [],
            users: [],
            availableUsers: [],
            selectedUsers: [],
            loading: false,
            usersLoading: false,
            sending: false,
            previewing: false,
            recipientType: 'all',
            showUserSelector: false,
            userSearchQuery: '',
            userSearchTimeout: null,
            previewResult: null,
            sendResult: null,
            showMediaSelector: false,
            mediaSelectorType: 'photo', // 'photo', 'video', 'media_group'
            selectedPhoto: null,
            selectedVideo: null,
            selectedMedia: [],
            form: {
                bot_id: '',
                type: 'message',
                telegram_user_ids: null,
                content: {
                    text: '',
                    photo: '',
                    video: '',
                    document: '',
                    caption: '',
                    media: [],
                },
                options: {
                    parse_mode: 'HTML',
                    disable_notification: false,
                },
            },
        };
    },
    computed: {
        canPreview() {
            return this.form.bot_id && this.form.type && this.hasContent();
        },
        canSend() {
            return this.canPreview && (this.recipientType === 'all' || this.selectedUsers.length > 0);
        },
    },
    mounted() {
        this.loadBots();
    },
    methods: {
        async loadBots() {
            try {
                const response = await axios.get('/api/v1/bots');
                this.bots = response.data.data || [];
            } catch (error) {
                console.error('Error loading bots:', error);
            }
        },
        async loadUsers() {
            if (!this.form.bot_id) return;

            this.usersLoading = true;
            try {
                const params = {
                    bot_id: this.form.bot_id,
                    per_page: 100,
                    is_blocked: false,
                };

                if (this.userSearchQuery) {
                    params.search = this.userSearchQuery;
                }

                const response = await axios.get('/api/v1/telegram-users', { params });
                this.availableUsers = response.data.data?.data || [];
            } catch (error) {
                console.error('Error loading users:', error);
            } finally {
                this.usersLoading = false;
            }
        },
        debounceUserSearch() {
            clearTimeout(this.userSearchTimeout);
            this.userSearchTimeout = setTimeout(() => {
                this.loadUsers();
            }, 500);
        },
        handleBotChange() {
            if (this.recipientType === 'selected') {
                this.selectedUsers = [];
                this.loadUsers();
            }
        },
        handleTypeChange() {
            // Очищаем контент при смене типа
            this.form.content = {
                text: '',
                photo: '',
                video: '',
                document: '',
                caption: '',
                media: [],
            };
            this.selectedPhoto = null;
            this.selectedVideo = null;
            this.selectedMedia = [];
        },
        handleMediaSelect(files) {
            const selected = Array.isArray(files) ? files : [files];
            
            if (this.mediaSelectorType === 'photo') {
                this.selectedPhoto = selected[0] || null;
                if (this.selectedPhoto) {
                    this.form.content.photo = this.selectedPhoto.url || '';
                }
            } else if (this.mediaSelectorType === 'video') {
                this.selectedVideo = selected[0] || null;
                if (this.selectedVideo) {
                    this.form.content.video = this.selectedVideo.url || '';
                }
            } else if (this.mediaSelectorType === 'media_group') {
                this.selectedMedia = selected.map(media => ({
                    ...media,
                    caption: media.caption || '',
                }));
                this.updateMediaGroup();
            }
            
            this.showMediaSelector = false;
        },
        removePhoto() {
            this.selectedPhoto = null;
            this.form.content.photo = '';
        },
        removeVideo() {
            this.selectedVideo = null;
            this.form.content.video = '';
        },
        removeMedia(index) {
            this.selectedMedia.splice(index, 1);
            this.updateMediaGroup();
        },
        updateMediaCaption(index, caption) {
            if (this.selectedMedia[index]) {
                this.selectedMedia[index].caption = caption;
                this.updateMediaGroup();
            }
        },
        updateMediaGroup() {
            // Формируем массив media для Telegram API
            this.form.content.media = this.selectedMedia.map((media, index) => ({
                type: media.type === 'photo' ? 'photo' : 'video',
                media: media.url || '',
                caption: media.caption || undefined,
            }));
        },
        handleRecipientTypeChange() {
            if (this.recipientType === 'all') {
                this.form.telegram_user_ids = null;
                this.selectedUsers = [];
            } else {
                this.form.telegram_user_ids = [];
                if (this.form.bot_id) {
                    this.loadUsers();
                }
            }
        },
        toggleUserSelection(user) {
            const index = this.selectedUsers.findIndex(u => u.id === user.id);
            if (index >= 0) {
                this.selectedUsers.splice(index, 1);
            } else {
                this.selectedUsers.push(user);
            }
            this.updateFormUserIds();
        },
        removeUser(userId) {
            this.selectedUsers = this.selectedUsers.filter(u => u.id !== userId);
            this.updateFormUserIds();
        },
        isUserSelected(userId) {
            return this.selectedUsers.some(u => u.id === userId);
        },
        updateFormUserIds() {
            this.form.telegram_user_ids = this.selectedUsers.map(u => u.telegram_id);
        },
        hasContent() {
            if (this.form.type === 'message') {
                return !!this.form.content.text;
            } else if (this.form.type === 'photo') {
                return !!this.form.content.photo;
            } else if (this.form.type === 'video') {
                return !!this.form.content.video;
            } else if (this.form.type === 'document') {
                return !!this.form.content.document;
            } else if (this.form.type === 'media_group') {
                return this.selectedMedia.length > 0 && this.selectedMedia.length <= 10;
            }
            return false;
        },
        getTypeLabel(type) {
            const labels = {
                message: 'Сообщение',
                photo: 'Фото + текст',
                video: 'Видео + текст',
                document: 'Документ',
                media_group: 'Галерея',
            };
            return labels[type] || type;
        },
        async previewBroadcast() {
            if (!this.form.bot_id) {
                alert('Выберите бота');
                return;
            }

            this.previewing = true;
            this.previewResult = null;
            this.sendResult = null;

            try {
                const content = this.buildContent();
                
                const payload = {
                    bot_id: this.form.bot_id,
                    type: this.form.type,
                    content: content,
                    telegram_user_ids: this.recipientType === 'all' ? null : this.form.telegram_user_ids,
                };

                const response = await axios.post('/api/v1/broadcasts/preview', payload);
                this.previewResult = response.data.data;
            } catch (error) {
                alert(error.response?.data?.message || 'Ошибка предпросмотра');
                console.error('Error previewing broadcast:', error);
            } finally {
                this.previewing = false;
            }
        },
        async sendBroadcast() {
            if (!confirm('Вы уверены, что хотите отправить рассылку?')) {
                return;
            }

            this.sending = true;
            this.sendResult = null;

            try {
                // Формируем content только с нужными полями для выбранного типа
                const content = this.buildContent();
                
                const payload = {
                    bot_id: this.form.bot_id,
                    type: this.form.type,
                    content: content,
                    options: this.form.options,
                    telegram_user_ids: this.recipientType === 'all' ? null : this.form.telegram_user_ids,
                };

                const response = await axios.post('/api/v1/broadcasts/send', payload);
                
                if (response.data.success) {
                    this.sendResult = {
                        success: true,
                        data: response.data.data,
                    };
                    
                    // Сброс формы через 5 секунд
                    setTimeout(() => {
                        this.resetForm();
                    }, 5000);
                } else {
                    this.sendResult = {
                        success: false,
                        message: response.data.message || 'Ошибка отправки рассылки',
                    };
                }
            } catch (error) {
                this.sendResult = {
                    success: false,
                    message: error.response?.data?.message || 'Ошибка отправки рассылки',
                };
                console.error('Error sending broadcast:', error);
            } finally {
                this.sending = false;
            }
        },
        buildContent() {
            // Формируем объект content только с нужными полями для выбранного типа
            const content = {};
            
            if (this.form.type === 'message') {
                content.text = this.form.content.text || '';
            } else if (this.form.type === 'photo') {
                content.photo = this.form.content.photo || '';
                if (this.form.content.caption) {
                    content.caption = this.form.content.caption;
                }
            } else if (this.form.type === 'video') {
                content.video = this.form.content.video || '';
                if (this.form.content.caption) {
                    content.caption = this.form.content.caption;
                }
            } else if (this.form.type === 'document') {
                content.document = this.form.content.document || '';
                if (this.form.content.caption) {
                    content.caption = this.form.content.caption;
                }
            } else if (this.form.type === 'media_group') {
                content.media = this.form.content.media || [];
            }
            
            return content;
        },
        resetForm() {
            this.form = {
                bot_id: '',
                type: 'message',
                telegram_user_ids: null,
                content: {
                    text: '',
                    photo: '',
                    video: '',
                    document: '',
                    caption: '',
                    media: [],
                },
                options: {
                    parse_mode: 'HTML',
                    disable_notification: false,
                },
            };
            this.recipientType = 'all';
            this.selectedUsers = [];
            this.selectedMedia = [];
            this.selectedPhoto = null;
            this.selectedVideo = null;
            this.previewResult = null;
            this.sendResult = null;
        },
    },
    watch: {
        'showUserSelector'(newVal) {
            if (newVal && this.form.bot_id) {
                this.loadUsers();
            }
        },
    },
};
</script>
