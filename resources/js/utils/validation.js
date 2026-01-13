/**
 * Утилиты для валидации форм в Vue админ-панели
 */

/**
 * Валидация обязательного поля
 */
export const required = (value, fieldName = 'Поле') => {
    if (!value || (typeof value === 'string' && !value.trim())) {
        return `${fieldName} обязательно для заполнения`;
    }
    return null;
};

/**
 * Валидация минимальной длины строки
 */
export const minLength = (value, min, fieldName = 'Поле') => {
    if (value && typeof value === 'string' && value.length < min) {
        return `${fieldName} должно содержать минимум ${min} символов`;
    }
    return null;
};

/**
 * Валидация максимальной длины строки
 */
export const maxLength = (value, max, fieldName = 'Поле') => {
    if (value && typeof value === 'string' && value.length > max) {
        return `${fieldName} должно содержать максимум ${max} символов`;
    }
    return null;
};

/**
 * Валидация email
 */
export const email = (value, fieldName = 'Email') => {
    if (value && typeof value === 'string') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            return `${fieldName} должен быть корректным email адресом`;
        }
    }
    return null;
};

/**
 * Валидация телефона (базовая)
 */
export const phone = (value, fieldName = 'Телефон') => {
    if (value && typeof value === 'string') {
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!phoneRegex.test(value)) {
            return `${fieldName} должен содержать только цифры и символы: +, -, (, ), пробел`;
        }
    }
    return null;
};

/**
 * Валидация числа
 */
export const number = (value, fieldName = 'Число') => {
    if (value !== null && value !== undefined && value !== '') {
        if (isNaN(Number(value))) {
            return `${fieldName} должно быть числом`;
        }
    }
    return null;
};

/**
 * Валидация минимального значения числа
 */
export const min = (value, minValue, fieldName = 'Значение') => {
    const numValue = Number(value);
    if (!isNaN(numValue) && numValue < minValue) {
        return `${fieldName} должно быть не менее ${minValue}`;
    }
    return null;
};

/**
 * Валидация максимального значения числа
 */
export const max = (value, maxValue, fieldName = 'Значение') => {
    const numValue = Number(value);
    if (!isNaN(numValue) && numValue > maxValue) {
        return `${fieldName} должно быть не более ${maxValue}`;
    }
    return null;
};

/**
 * Валидация URL
 */
export const url = (value, fieldName = 'URL') => {
    if (value && typeof value === 'string') {
        try {
            new URL(value);
        } catch {
            return `${fieldName} должен быть корректным URL`;
        }
    }
    return null;
};

/**
 * Валидация рейтинга (1-5)
 */
export const rating = (value, fieldName = 'Рейтинг') => {
    const numValue = Number(value);
    if (isNaN(numValue) || numValue < 1 || numValue > 5) {
        return `${fieldName} должен быть от 1 до 5`;
    }
    return null;
};

/**
 * Валидация положительного числа
 */
export const positiveNumber = (value, fieldName = 'Число') => {
    const numValue = Number(value);
    if (!isNaN(numValue) && numValue <= 0) {
        return `${fieldName} должно быть положительным числом`;
    }
    return null;
};

/**
 * Валидация неотрицательного числа
 */
export const nonNegativeNumber = (value, fieldName = 'Число') => {
    const numValue = Number(value);
    if (!isNaN(numValue) && numValue < 0) {
        return `${fieldName} не может быть отрицательным`;
    }
    return null;
};

/**
 * Комбинированная валидация (применяет все правила)
 */
export const validate = (value, rules = [], fieldName = 'Поле') => {
    for (const rule of rules) {
        if (typeof rule === 'function') {
            const error = rule(value, fieldName);
            if (error) return error;
        }
    }
    return null;
};

/**
 * Валидация объекта формы
 */
export const validateForm = (form, schema) => {
    const errors = {};
    let isValid = true;

    for (const [field, rules] of Object.entries(schema)) {
        if (Array.isArray(rules)) {
            const error = validate(form[field], rules, field);
            if (error) {
                errors[field] = error;
                isValid = false;
            }
        }
    }

    return { errors, isValid };
};

/**
 * Очистка ошибок валидации
 */
export const clearErrors = () => {
    return {};
};

/**
 * Обработка ошибок валидации с сервера
 */
export const parseServerErrors = (errorResponse) => {
    const errors = {};
    
    if (errorResponse?.errors) {
        // Laravel формат ошибок: { "field": ["message1", "message2"] }
        for (const [field, messages] of Object.entries(errorResponse.errors)) {
            if (Array.isArray(messages) && messages.length > 0) {
                errors[field] = messages[0]; // Берем первое сообщение
            } else if (typeof messages === 'string') {
                errors[field] = messages;
            }
        }
    } else if (errorResponse?.message) {
        // Общее сообщение об ошибке
        errors._general = errorResponse.message;
    }

    return errors;
};






