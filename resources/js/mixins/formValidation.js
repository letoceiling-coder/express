/**
 * Mixin для валидации форм в Vue компонентах
 */
import { parseServerErrors } from '../utils/validation.js';

export default {
    data() {
        return {
            errors: {},
            validationRules: {}, // Определяются в компоненте
        };
    },
    methods: {
        /**
         * Валидация одного поля
         */
        validateField(field, value, rules = []) {
            const fieldRules = rules.length > 0 ? rules : (this.validationRules[field] || []);
            
            for (const rule of fieldRules) {
                if (typeof rule === 'function') {
                    const error = rule(value, this.getFieldLabel(field));
                    if (error) {
                        return error;
                    }
                }
            }
            return null;
        },

        /**
         * Валидация всей формы
         */
        validateForm() {
            this.errors = {};
            let isValid = true;

            for (const [field, rules] of Object.entries(this.validationRules)) {
                const value = this.form[field];
                const error = this.validateField(field, value, rules);
                
                if (error) {
                    this.errors[field] = error;
                    isValid = false;
                }
            }

            return isValid;
        },

        /**
         * Очистка ошибок для поля
         */
        clearFieldError(field) {
            if (this.errors[field]) {
                delete this.errors[field];
            }
        },

        /**
         * Очистка всех ошибок
         */
        clearErrors() {
            this.errors = {};
        },

        /**
         * Обработка ошибок с сервера
         */
        handleServerErrors(error) {
            const errorData = error.response?.data || error.data || {};
            this.errors = parseServerErrors(errorData);
            
            // Если есть общая ошибка, показываем её
            if (this.errors._general) {
                alert(this.errors._general);
                delete this.errors._general;
            }
        },

        /**
         * Получить название поля для сообщения об ошибке
         */
        getFieldLabel(field) {
            const labels = {
                name: 'Название',
                slug: 'URL-адрес',
                description: 'Описание',
                price: 'Цена',
                quantity: 'Количество',
                sku: 'Артикул',
                email: 'Email',
                phone: 'Телефон',
                rating: 'Рейтинг',
                comment: 'Комментарий',
                subject: 'Тема',
                // Добавьте другие поля по необходимости
            };
            return labels[field] || 'Поле';
        },
    },
};



