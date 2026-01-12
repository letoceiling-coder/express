import Swal from 'sweetalert2';

/**
 * Утилита для работы с SweetAlert2
 */
export const swal = {
    /**
     * Показать успешное уведомление
     * @param {string} message - Сообщение
     * @param {string} title - Заголовок (опционально)
     */
    success(message, title = 'Успешно') {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonText: 'ОК',
            confirmButtonColor: '#10b981',
        });
    },

    /**
     * Показать ошибку
     * @param {string} message - Сообщение об ошибке
     * @param {string} title - Заголовок (опционально)
     */
    error(message, title = 'Ошибка') {
        return Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            confirmButtonText: 'ОК',
            confirmButtonColor: '#ef4444',
        });
    },

    /**
     * Показать предупреждение
     * @param {string} message - Сообщение
     * @param {string} title - Заголовок (опционально)
     */
    warning(message, title = 'Внимание') {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: message,
            confirmButtonText: 'ОК',
            confirmButtonColor: '#f59e0b',
        });
    },

    /**
     * Показать информационное сообщение
     * @param {string} message - Сообщение
     * @param {string} title - Заголовок (опционально)
     */
    info(message, title = 'Информация') {
        return Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            confirmButtonText: 'ОК',
            confirmButtonColor: '#3b82f6',
        });
    },

    /**
     * Показать подтверждение (да/нет)
     * @param {string} message - Сообщение
     * @param {string} title - Заголовок (опционально)
     * @param {string} confirmText - Текст кнопки подтверждения
     * @param {string} cancelText - Текст кнопки отмены
     */
    confirm(message, title = 'Подтверждение', confirmText = 'Да', cancelText = 'Нет') {
        return Swal.fire({
            icon: 'question',
            title: title,
            text: message,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
        });
    },

    /**
     * Показать простое сообщение (аналог alert)
     * @param {string} message - Сообщение
     * @param {string} title - Заголовок (опционально)
     */
    alert(message, title = '') {
        return Swal.fire({
            icon: 'info',
            title: title || undefined,
            text: message,
            confirmButtonText: 'ОК',
            confirmButtonColor: '#3b82f6',
        });
    },
};

export default swal;

