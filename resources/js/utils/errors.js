/**
 * Утилиты для обработки ошибок в Vue админ-панели
 */

/**
 * Классификация ошибок API
 */
export const ErrorTypes = {
    VALIDATION: 'validation',
    AUTHENTICATION: 'authentication',
    AUTHORIZATION: 'authorization',
    NOT_FOUND: 'not_found',
    SERVER: 'server',
    NETWORK: 'network',
    UNKNOWN: 'unknown',
};

/**
 * Определить тип ошибки
 */
export const getErrorType = (error) => {
    if (!error) return ErrorTypes.UNKNOWN;

    // Ошибка валидации (422)
    if (error.response?.status === 422) {
        return ErrorTypes.VALIDATION;
    }

    // Ошибка аутентификации (401)
    if (error.response?.status === 401) {
        return ErrorTypes.AUTHENTICATION;
    }

    // Ошибка авторизации (403)
    if (error.response?.status === 403) {
        return ErrorTypes.AUTHORIZATION;
    }

    // Не найдено (404)
    if (error.response?.status === 404) {
        return ErrorTypes.NOT_FOUND;
    }

    // Ошибка сервера (500+)
    if (error.response?.status >= 500) {
        return ErrorTypes.SERVER;
    }

    // Сетевая ошибка
    if (!error.response && error.message?.includes('Network')) {
        return ErrorTypes.NETWORK;
    }

    return ErrorTypes.UNKNOWN;
};

/**
 * Получить сообщение об ошибке
 */
export const getErrorMessage = (error, defaultMessage = 'Произошла ошибка') => {
    if (!error) return defaultMessage;

    // Сообщение из response
    if (error.response?.data?.message) {
        return error.response.data.message;
    }

    // Сообщение из error
    if (error.message) {
        return error.message;
    }

    // Сообщение по типу ошибки
    const errorType = getErrorType(error);
    const messages = {
        [ErrorTypes.VALIDATION]: 'Ошибка валидации данных',
        [ErrorTypes.AUTHENTICATION]: 'Требуется авторизация',
        [ErrorTypes.AUTHORIZATION]: 'Недостаточно прав доступа',
        [ErrorTypes.NOT_FOUND]: 'Ресурс не найден',
        [ErrorTypes.SERVER]: 'Ошибка сервера. Попробуйте позже',
        [ErrorTypes.NETWORK]: 'Проблема с сетью. Проверьте подключение',
        [ErrorTypes.UNKNOWN]: defaultMessage,
    };

    return messages[errorType] || defaultMessage;
};

/**
 * Получить ошибки валидации
 */
export const getValidationErrors = (error) => {
    if (!error?.response?.data?.errors) {
        return {};
    }

    const errors = {};
    const serverErrors = error.response.data.errors;

    // Laravel формат: { "field": ["message1", "message2"] }
    for (const [field, messages] of Object.entries(serverErrors)) {
        if (Array.isArray(messages) && messages.length > 0) {
            errors[field] = messages[0]; // Берем первое сообщение
        } else if (typeof messages === 'string') {
            errors[field] = messages;
        }
    }

    return errors;
};

/**
 * Обработать ошибку API
 */
export const handleApiError = (error, options = {}) => {
    const {
        showToast = true,
        redirectOnAuth = true,
        returnValidationErrors = false,
    } = options;

    const errorType = getErrorType(error);
    const message = getErrorMessage(error);

    // Обработка ошибок аутентификации
    if (errorType === ErrorTypes.AUTHENTICATION && redirectOnAuth) {
        // Редирект на страницу входа
        if (typeof window !== 'undefined' && window.location) {
            window.location.href = '/login';
        }
        return null;
    }

    // Обработка ошибок валидации
    if (errorType === ErrorTypes.VALIDATION && returnValidationErrors) {
        return getValidationErrors(error);
    }

    // Показать уведомление
    if (showToast && typeof window !== 'undefined') {
        // Используем простой alert или можно подключить toast библиотеку
        if (window.showToast) {
            window.showToast(message, 'error');
        } else {
            console.error('Error:', message, error);
        }
    }

    return { type: errorType, message, error };
};

/**
 * Создать обработчик ошибок для async функций
 */
export const withErrorHandling = async (asyncFn, options = {}) => {
    try {
        return await asyncFn();
    } catch (error) {
        handleApiError(error, options);
        throw error;
    }
};




