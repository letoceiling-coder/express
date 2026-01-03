<template>
    <div v-if="open" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-card rounded-lg border border-border w-full max-w-6xl max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-border">
                <div>
                    <h2 class="text-xl font-bold text-foreground">
                        {{ multiple ? 'Выберите файлы' : 'Выберите файл' }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ allowedTypes ? `Разрешенные типы: ${allowedTypes.join(', ')}` : 'Все типы файлов' }}
                    </p>
                </div>
                <button
                    @click="handleClose"
                    class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-muted"
                >
                    ✕
                </button>
            </div>

            <!-- Media Library -->
            <div class="flex-1 overflow-y-auto min-h-0">
                <Media
                    :selectionMode="true"
                    @file-selected="handleFileSelected"
                />
            </div>

            <!-- Footer with selected items -->
            <div v-if="selectedItems.length > 0" class="p-4 border-t border-border bg-muted/30">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-foreground">
                        Выбрано: {{ selectedItems.length }}
                    </span>
                    <button
                        @click="selectedItems = []"
                        class="text-sm text-muted-foreground hover:text-foreground"
                    >
                        Очистить
                    </button>
                </div>
                <div class="flex gap-2 flex-wrap max-h-24 overflow-y-auto">
                    <div
                        v-for="item in selectedItems"
                        :key="item.id"
                        class="relative group"
                    >
                        <img
                            v-if="item.type === 'photo'"
                            :src="item.url"
                            :alt="item.name"
                            class="w-16 h-16 object-cover rounded border border-border"
                        />
                        <div
                            v-else
                            class="w-16 h-16 bg-muted rounded border border-border flex items-center justify-center"
                        >
                            <span class="text-xs text-muted-foreground">{{ item.type === 'video' ? '🎥' : '📄' }}</span>
                        </div>
                        <button
                            @click="removeSelectedItem(item.id)"
                            class="absolute -top-1 -right-1 w-5 h-5 bg-destructive text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4 p-4 border-t border-border">
                <button
                    @click="handleClose"
                    class="h-10 px-4 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
                >
                    Отмена
                </button>
                <button
                    @click="handleConfirm"
                    :disabled="selectedItems.length === 0"
                    class="h-10 px-6 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Выбрать{{ multiple && selectedItems.length > 1 ? ` (${selectedItems.length})` : '' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import Media from '../../pages/admin/Media.vue';

export default {
    name: 'MediaSelector',
    components: {
        Media,
    },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        multiple: {
            type: Boolean,
            default: false,
        },
        allowedTypes: {
            type: Array,
            default: null, // null = все типы, ['photo'], ['video'], ['photo', 'video'], ['document']
        },
        currentSelection: {
            type: Array,
            default: () => [],
        },
    },
    emits: ['close', 'select'],
    data() {
        return {
            selectedItems: [],
        };
    },
    watch: {
        open(newVal) {
            if (newVal) {
                // Инициализируем выбранные элементы
                this.selectedItems = [...this.currentSelection];
            } else {
                this.selectedItems = [];
            }
        },
        currentSelection: {
            immediate: true,
            handler(newVal) {
                if (this.open) {
                    this.selectedItems = [...(newVal || [])];
                }
            },
        },
    },
    methods: {
        handleFileSelected(file) {
            // Проверяем тип файла
            if (this.allowedTypes && !this.allowedTypes.includes(file.type)) {
                alert(`Тип файла "${file.type}" не разрешен. Разрешенные типы: ${this.allowedTypes.join(', ')}`);
                return;
            }

            // Если single mode, заменяем выбранный элемент
            if (!this.multiple) {
                this.selectedItems = [file];
                this.handleConfirm();
                return;
            }

            // Если multiple mode, добавляем если еще нет
            if (!this.selectedItems.find(item => item.id === file.id)) {
                this.selectedItems.push(file);
            }
        },
        removeSelectedItem(id) {
            this.selectedItems = this.selectedItems.filter(item => item.id !== id);
        },
        handleConfirm() {
            if (this.selectedItems.length === 0) {
                return;
            }

            const selected = this.multiple ? this.selectedItems : this.selectedItems[0];
            this.$emit('select', selected);
            this.handleClose();
        },
        handleClose() {
            this.selectedItems = [];
            this.$emit('close');
        },
    },
};
</script>

