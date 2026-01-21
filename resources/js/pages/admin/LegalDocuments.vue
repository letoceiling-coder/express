<template>
    <div class="legal-documents-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Документы</h1>
            <p class="text-muted-foreground mt-1">Управление правовыми документами: Политика конфиденциальности, Оферта, Контакты</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading && !documents.length" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Загрузка документов...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Форма -->
        <div v-else class="space-y-6">
            <div class="bg-card rounded-lg border border-border p-6">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Privacy Policy -->
                    <div class="space-y-4 pt-4 border-t border-border first:border-t-0 first:pt-0">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Политика конфиденциальности</h2>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Название *
                            </label>
                            <input
                                v-model="form.privacy_policy.title"
                                type="text"
                                placeholder="Политика конфиденциальности"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                URL документа
                            </label>
                            <input
                                v-model="form.privacy_policy.url"
                                type="url"
                                placeholder="https://example.com/privacy-policy"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Если указан URL, он будет использоваться вместо текстового содержимого
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Текстовое содержимое
                            </label>
                            <textarea
                                v-model="form.privacy_policy.content"
                                placeholder="Текст политики конфиденциальности..."
                                class="w-full min-h-[200px] px-3 py-2 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                rows="10"
                            ></textarea>
                            <p class="text-xs text-muted-foreground mt-1">
                                Поддерживается многострочный текст и HTML разметка
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Активен
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Документ будет отображаться в Mini App
                                </p>
                            </div>
                            <input
                                v-model="form.privacy_policy.is_active"
                                type="checkbox"
                                id="privacy_policy_active"
                                class="w-4 h-4 rounded border-input"
                            />
                        </div>
                    </div>

                    <!-- Offer -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Оферта / Соглашение</h2>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Название *
                            </label>
                            <input
                                v-model="form.offer.title"
                                type="text"
                                placeholder="Публичная оферта"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                URL документа
                            </label>
                            <input
                                v-model="form.offer.url"
                                type="url"
                                placeholder="https://example.com/offer"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Если указан URL, он будет использоваться вместо текстового содержимого
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Текстовое содержимое
                            </label>
                            <textarea
                                v-model="form.offer.content"
                                placeholder="Текст оферты..."
                                class="w-full min-h-[200px] px-3 py-2 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                rows="10"
                            ></textarea>
                            <p class="text-xs text-muted-foreground mt-1">
                                Поддерживается многострочный текст и HTML разметка
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Активен
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Документ будет отображаться в Mini App
                                </p>
                            </div>
                            <input
                                v-model="form.offer.is_active"
                                type="checkbox"
                                id="offer_active"
                                class="w-4 h-4 rounded border-input"
                            />
                        </div>
                    </div>

                    <!-- Contacts -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Контакты</h2>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Название *
                            </label>
                            <input
                                v-model="form.contacts.title"
                                type="text"
                                placeholder="Контакты"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                URL документа
                            </label>
                            <input
                                v-model="form.contacts.url"
                                type="url"
                                placeholder="https://example.com/contacts"
                                class="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Если указан URL, он будет использоваться вместо текстового содержимого
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                Текстовое содержимое
                            </label>
                            <textarea
                                v-model="form.contacts.content"
                                placeholder="Контактная информация..."
                                class="w-full min-h-[200px] px-3 py-2 rounded-lg border border-input bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                rows="10"
                            ></textarea>
                            <p class="text-xs text-muted-foreground mt-1">
                                Поддерживается многострочный текст и HTML разметка
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    Активен
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Документ будет отображаться в Mini App
                                </p>
                            </div>
                            <input
                                v-model="form.contacts.is_active"
                                type="checkbox"
                                id="contacts_active"
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
                            {{ saving ? 'Сохранение...' : 'Сохранить документы' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { legalDocumentsAPI } from '../../utils/api.js';
import swal from '../../utils/swal.js';

export default {
    name: 'LegalDocuments',
    data() {
        return {
            documents: [],
            form: {
                privacy_policy: {
                    id: null,
                    type: 'privacy_policy',
                    title: '',
                    content: '',
                    url: '',
                    is_active: true,
                    sort_order: 0,
                },
                offer: {
                    id: null,
                    type: 'offer',
                    title: '',
                    content: '',
                    url: '',
                    is_active: true,
                    sort_order: 1,
                },
                contacts: {
                    id: null,
                    type: 'contacts',
                    title: '',
                    content: '',
                    url: '',
                    is_active: true,
                    sort_order: 2,
                },
            },
            loading: false,
            saving: false,
            error: null,
        };
    },
    mounted() {
        this.loadData();
    },
    methods: {
        async loadData() {
            this.loading = true;
            this.error = null;
            try {
                const documents = await legalDocumentsAPI.getAdmin();
                this.documents = documents;

                // Заполняем форму данными из API
                documents.forEach(doc => {
                    if (doc.type === 'privacy_policy') {
                        this.form.privacy_policy = {
                            id: doc.id,
                            type: doc.type,
                            title: doc.title || '',
                            content: doc.content || '',
                            url: doc.url || '',
                            is_active: doc.is_active !== undefined ? doc.is_active : true,
                            sort_order: doc.sort_order || 0,
                        };
                    } else if (doc.type === 'offer') {
                        this.form.offer = {
                            id: doc.id,
                            type: doc.type,
                            title: doc.title || '',
                            content: doc.content || '',
                            url: doc.url || '',
                            is_active: doc.is_active !== undefined ? doc.is_active : true,
                            sort_order: doc.sort_order || 1,
                        };
                    } else if (doc.type === 'contacts') {
                        this.form.contacts = {
                            id: doc.id,
                            type: doc.type,
                            title: doc.title || '',
                            content: doc.content || '',
                            url: doc.url || '',
                            is_active: doc.is_active !== undefined ? doc.is_active : true,
                            sort_order: doc.sort_order || 2,
                        };
                    }
                });
            } catch (error) {
                console.error('Error loading legal documents:', error);
                this.error = error.message || 'Ошибка при загрузке документов';
            } finally {
                this.loading = false;
            }
        },
        async handleSubmit() {
            this.saving = true;
            this.error = null;
            try {
                const documents = [
                    this.form.privacy_policy,
                    this.form.offer,
                    this.form.contacts,
                ];

                const updatedDocuments = await legalDocumentsAPI.update(documents);
                this.documents = updatedDocuments;

                await swal.success('Документы успешно сохранены');
            } catch (error) {
                console.error('Error saving legal documents:', error);
                this.error = error.message || 'Ошибка при сохранении документов';
                await swal.error('Ошибка при сохранении: ' + this.error);
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
