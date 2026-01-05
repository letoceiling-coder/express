<template>
    <TransitionGroup
        name="toast"
        tag="div"
        class="fixed top-4 right-4 z-50 space-y-2 pointer-events-none"
    >
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="pointer-events-auto max-w-sm w-full bg-card shadow-lg rounded-lg border border-border overflow-hidden"
            :class="getToastClass(toast.type)"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <component :is="getIcon(toast.type)" class="h-5 w-5" />
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium" :class="getTextClass(toast.type)">
                            {{ toast.message }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button
                            @click="removeToast(toast.id)"
                            class="inline-flex text-muted-foreground hover:text-foreground focus:outline-none"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TransitionGroup>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';

const toasts = ref([]);
let toastId = 0;

// Иконки для разных типов уведомлений
const SuccessIcon = {
    template: `
        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
    `,
};

const ErrorIcon = {
    template: `
        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
    `,
};

const WarningIcon = {
    template: `
        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
    `,
};

const InfoIcon = {
    template: `
        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
    `,
};

export default {
    name: 'Toast',
    setup() {
        const getIcon = (type) => {
            const icons = {
                success: SuccessIcon,
                error: ErrorIcon,
                warning: WarningIcon,
                info: InfoIcon,
            };
            return icons[type] || InfoIcon;
        };

        const getToastClass = (type) => {
            const classes = {
                success: 'border-green-200 bg-green-50',
                error: 'border-red-200 bg-red-50',
                warning: 'border-yellow-200 bg-yellow-50',
                info: 'border-blue-200 bg-blue-50',
            };
            return classes[type] || classes.info;
        };

        const getTextClass = (type) => {
            const classes = {
                success: 'text-green-800',
                error: 'text-red-800',
                warning: 'text-yellow-800',
                info: 'text-blue-800',
            };
            return classes[type] || classes.info;
        };

        const removeToast = (id) => {
            const index = toasts.value.findIndex(t => t.id === id);
            if (index > -1) {
                toasts.value.splice(index, 1);
            }
        };

        // Экспортируем функцию для показа уведомлений
        const showToast = (message, type = 'info', duration = 5000) => {
            const id = ++toastId;
            const toast = {
                id,
                message,
                type,
            };

            toasts.value.push(toast);

            // Автоматическое удаление
            if (duration > 0) {
                setTimeout(() => {
                    removeToast(id);
                }, duration);
            }

            return id;
        };

        // Делаем функцию доступной глобально
        onMounted(() => {
            window.showToast = showToast;
        });

        onUnmounted(() => {
            delete window.showToast;
        });

        return {
            toasts,
            getIcon,
            getToastClass,
            getTextClass,
            removeToast,
        };
    },
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.toast-move {
    transition: transform 0.3s ease;
}
</style>



