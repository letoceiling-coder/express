import axios from 'axios';

/**
 * ⚠️ ВАЖНО: Правила использования API роутов в Vue компонентах
 * 
 * API_BASE уже содержит '/api/v1', поэтому в компонентах НЕ нужно добавлять '/v1/' или '/api/v1/'
 * 
 * ✅ ПРАВИЛЬНО:
 *   apiGet('/bots')           → /api/v1/bots
 *   apiGet('/users')          → /api/v1/users
 *   apiGet('/roles')          → /api/v1/roles
 *   apiGet('/bots/1')         → /api/v1/bots/1
 *   apiPost('/bots', data)    → /api/v1/bots
 * 
 * ❌ НЕПРАВИЛЬНО:
 *   apiGet('/v1/bots')       → /api/v1/v1/bots (ОШИБКА!)
 *   apiGet('/api/v1/bots')    → /api/v1/api/v1/bots (ОШИБКА!)
 *   apiGet('v1/bots')         → /api/v1v1/bots (ОШИБКА!)
 * 
 * Структура роутов в routes/api.php:
 *   Route::prefix('v1')->group(function () {
 *       Route::apiResource('bots', BotController::class);
 *       // Полный путь: /api/v1/bots
 *   });
 * 
 * Всегда начинайте путь с '/' и БЕЗ '/v1/'!
 */
const API_BASE = '/api/v1';

// Получить заголовки авторизации
const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    return headers;
};

// GET запрос
export const apiGet = async (url, params = {}) => {
    let fullUrl = `${API_BASE}${url}`;
    
    // Если params - объект и не пустой, добавляем параметры
    if (params && Object.keys(params).length > 0) {
        const queryString = new URLSearchParams(params).toString();
        // Если url уже содержит параметры, добавляем через &
        if (url.includes('?')) {
            fullUrl = `${fullUrl}&${queryString}`;
        } else {
            fullUrl = `${fullUrl}?${queryString}`;
        }
    }
    
    return fetch(fullUrl, {
        method: 'GET',
        headers: getAuthHeaders(),
    });
};

// POST запрос
export const apiPost = async (url, data = {}) => {
    const fullUrl = `${API_BASE}${url}`;
    
    // Если data - FormData, не устанавливаем Content-Type
    const headers = data instanceof FormData 
        ? { ...getAuthHeaders(), 'Content-Type': undefined }
        : getAuthHeaders();
    
    // Удаляем Content-Type если это FormData (браузер установит сам)
    if (data instanceof FormData) {
        delete headers['Content-Type'];
    }
    
    return fetch(fullUrl, {
        method: 'POST',
        headers,
        body: data instanceof FormData ? data : JSON.stringify(data),
    });
};

// PUT запрос
export const apiPut = async (url, data = {}) => {
    const fullUrl = `${API_BASE}${url}`;
    
    // Если data - FormData, не устанавливаем Content-Type
    const headers = data instanceof FormData 
        ? { ...getAuthHeaders(), 'Content-Type': undefined }
        : getAuthHeaders();
    
    // Удаляем Content-Type если это FormData (браузер установит сам)
    if (data instanceof FormData) {
        delete headers['Content-Type'];
    }
    
    return fetch(fullUrl, {
        method: 'PUT',
        headers,
        body: data instanceof FormData ? data : JSON.stringify(data),
    });
};

// DELETE запрос
export const apiDelete = async (url) => {
    const fullUrl = `${API_BASE}${url}`;
    
    return fetch(fullUrl, {
        method: 'DELETE',
        headers: getAuthHeaders(),
    });
};

// ============================================
// Categories API
// ============================================
export const categoriesAPI = {
    // Получить список категорий
    async getAll(params = {}) {
        const response = await apiGet('/categories', params);
        if (!response.ok) {
            throw new Error('Ошибка загрузки категорий');
        }
        return response.json();
    },

    // Получить категорию по ID
    async getById(id) {
        const response = await apiGet(`/categories/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки категории');
        }
        return response.json();
    },

    // Создать категорию
    async create(data) {
        const response = await apiPost('/categories', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка создания категории');
        }
        return response.json();
    },

    // Обновить категорию
    async update(id, data) {
        const response = await apiPut(`/categories/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления категории');
        }
        return response.json();
    },

    // Удалить категорию
    async delete(id) {
        const response = await apiDelete(`/categories/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления категории');
        }
        return true;
    },
};

// ============================================
// Media API
// ============================================
export const mediaAPI = {
    // Получить список медиа
    async getAll(params = {}) {
        const response = await apiGet('/media', params);
        if (!response.ok) {
            throw new Error('Ошибка загрузки медиа');
        }
        return response.json();
    },

    // Получить медиа по ID
    async getById(id) {
        const response = await apiGet(`/media/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки медиа');
        }
        return response.json();
    },

    // Удалить медиа
    async delete(id) {
        const response = await apiDelete(`/media/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления медиа');
        }
        return true;
    },
};

// ============================================
// Products API
// ============================================
export const productsAPI = {
    // Получить список товаров
    async getAll(params = {}) {
        const response = await apiGet('/products', params);
        if (!response.ok) {
            throw new Error('Ошибка загрузки товаров');
        }
        return response.json();
    },

    // Получить товар по ID
    async getById(id) {
        const response = await apiGet(`/products/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки товара');
        }
        return response.json();
    },

    // Создать товар
    async create(data) {
        const response = await apiPost('/products', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка создания товара');
        }
        return response.json();
    },

    // Обновить товар
    async update(id, data) {
        const response = await apiPut(`/products/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления товара');
        }
        return response.json();
    },

    // Удалить товар
    async delete(id) {
        const response = await apiDelete(`/products/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления товара');
        }
        return true;
    },

    // Получить историю изменений товара
    async getHistory(id, params = {}) {
        const response = await apiGet(`/products/${id}/history`, params);
        if (!response.ok) {
            throw new Error('Ошибка загрузки истории');
        }
        return response.json();
    },
};

// ============================================
// Orders API
// ============================================
export const ordersAPI = {
    // Получить список заказов
    async getAll(params = {}) {
        const response = await apiGet('/orders', params);
        if (!response.ok) {
            throw new Error('Ошибка загрузки заказов');
        }
        return response.json();
    },

    // Получить заказ по ID
    async getById(id) {
        const response = await apiGet(`/orders/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки заказа');
        }
        return response.json();
    },

    // Обновить заказ
    async update(id, data) {
        const response = await apiPut(`/orders/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления заказа');
        }
        return response.json();
    },

    // Изменить статус заказа
    async updateStatus(id, status) {
        const response = await apiPut(`/orders/${id}/status`, { status });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка изменения статуса');
        }
        return response.json();
    },

    // Удалить заказ
    async delete(id) {
        const response = await apiDelete(`/orders/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления заказа');
        }
        return true;
    },
};

// ============================================
// Deliveries API
// ============================================
export const deliveriesAPI = {
    // Получить список доставок
    async getAll(params = {}) {
        const response = await apiGet('/deliveries', params);
        if (!response.ok) {
            throw new Error('Ошибка загрузки доставок');
        }
        return response.json();
    },

    // Получить доставку по ID
    async getById(id) {
        const response = await apiGet(`/deliveries/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки доставки');
        }
        return response.json();
    },

    // Получить доставку для заказа
    async getByOrder(orderId) {
        const response = await apiGet(`/orders/${orderId}/delivery`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки доставки');
        }
        return response.json();
    },

    // Создать доставку
    async create(data) {
        const response = await apiPost('/deliveries', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка создания доставки');
        }
        return response.json();
    },

    // Обновить доставку
    async update(id, data) {
        const response = await apiPut(`/deliveries/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления доставки');
        }
        return response.json();
    },

    // Изменить статус доставки
    async updateStatus(id, status) {
        const response = await apiPut(`/deliveries/${id}/status`, { status });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка изменения статуса');
        }
        return response.json();
    },

    // Удалить доставку
    async delete(id) {
        const response = await apiDelete(`/deliveries/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления доставки');
        }
        return true;
    },
};

// ============================================
// Payments API
// ============================================
export const paymentsAPI = {
    // Получить список платежей
    async getAll(params = {}) {
        const response = await apiGet('/payments', params);
        if (!response.ok) {
            throw new Error('Ошибка загрузки платежей');
        }
        return response.json();
    },

    // Получить платежи для заказа
    async getByOrder(orderId) {
        const response = await apiGet(`/orders/${orderId}/payments`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки платежей');
        }
        return response.json();
    },

    // Получить платеж по ID
    async getById(id) {
        const response = await apiGet(`/payments/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки платежа');
        }
        return response.json();
    },

    // Создать платеж
    async create(data) {
        const response = await apiPost('/payments', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка создания платежа');
        }
        return response.json();
    },

    // Обновить платеж
    async update(id, data) {
        const response = await apiPut(`/payments/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления платежа');
        }
        return response.json();
    },

    // Изменить статус платежа
    async updateStatus(id, status) {
        const response = await apiPut(`/payments/${id}/status`, { status });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка изменения статуса');
        }
        return response.json();
    },

    // Возврат платежа
    async refund(id, amount = null) {
        const response = await apiPost(`/payments/${id}/refund`, amount ? { amount } : {});
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка возврата платежа');
        }
        return response.json();
    },

    // Удалить платеж
    async delete(id) {
        const response = await apiDelete(`/payments/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления платежа');
        }
        return true;
    },
};

// ============================================
// Returns API
// ============================================
export const returnsAPI = {
    // Получить все возвраты
    async getAll(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const response = await apiGet(`/returns${queryString ? `?${queryString}` : ''}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки возвратов');
        }
        return response.json();
    },

    // Получить возврат по ID
    async getById(id) {
        const response = await apiGet(`/returns/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки возврата');
        }
        return response.json();
    },

    // Получить возвраты для заказа
    async getByOrder(orderId) {
        const response = await apiGet(`/orders/${orderId}/returns`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки возвратов');
        }
        return response.json();
    },

    // Создать возврат
    async create(data) {
        const response = await apiPost('/returns', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка создания возврата');
        }
        return response.json();
    },

    // Обновить возврат
    async update(id, data) {
        const response = await apiPut(`/returns/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления возврата');
        }
        return response.json();
    },

    // Изменить статус возврата
    async updateStatus(id, status) {
        const response = await apiPut(`/returns/${id}/status`, { status });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка изменения статуса');
        }
        return response.json();
    },

    // Одобрить возврат
    async approve(id, data = {}) {
        const response = await apiPost(`/returns/${id}/approve`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка одобрения возврата');
        }
        return response.json();
    },

    // Отклонить возврат
    async reject(id, reason) {
        const response = await apiPost(`/returns/${id}/reject`, { reason });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка отклонения возврата');
        }
        return response.json();
    },

    // Удалить возврат
    async delete(id) {
        const response = await apiDelete(`/returns/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления возврата');
        }
        return true;
    },
};

// ============================================
// Complaints API
// ============================================
export const complaintsAPI = {
    // Получить все претензии
    async getAll(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const response = await apiGet(`/complaints${queryString ? `?${queryString}` : ''}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки претензий');
        }
        return response.json();
    },

    // Получить претензию по ID
    async getById(id) {
        const response = await apiGet(`/complaints/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки претензии');
        }
        return response.json();
    },

    // Получить претензии для заказа
    async getByOrder(orderId) {
        const response = await apiGet(`/orders/${orderId}/complaints`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки претензий');
        }
        return response.json();
    },

    // Создать претензию
    async create(data) {
        const response = await apiPost('/complaints', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка создания претензии');
        }
        return response.json();
    },

    // Обновить претензию
    async update(id, data) {
        const response = await apiPut(`/complaints/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления претензии');
        }
        return response.json();
    },

    // Изменить статус претензии
    async updateStatus(id, status) {
        const response = await apiPut(`/complaints/${id}/status`, { status });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка изменения статуса');
        }
        return response.json();
    },

    // Добавить комментарий к претензии
    async addComment(id, comment) {
        const response = await apiPost(`/complaints/${id}/comments`, { comment });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка добавления комментария');
        }
        return response.json();
    },

    // Удалить претензию
    async delete(id) {
        const response = await apiDelete(`/complaints/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления претензии');
        }
        return true;
    },
};

// ============================================
// Reviews API
// ============================================
export const reviewsAPI = {
    // Получить все отзывы
    async getAll(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const response = await apiGet(`/reviews${queryString ? `?${queryString}` : ''}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки отзывов');
        }
        return response.json();
    },

    // Получить отзыв по ID
    async getById(id) {
        const response = await apiGet(`/reviews/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки отзыва');
        }
        return response.json();
    },

    // Получить отзывы для заказа
    async getByOrder(orderId) {
        const response = await apiGet(`/orders/${orderId}/reviews`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки отзывов');
        }
        return response.json();
    },

    // Получить отзывы для товара
    async getByProduct(productId) {
        const response = await apiGet(`/products/${productId}/reviews`);
        if (!response.ok) {
            throw new Error('Ошибка загрузки отзывов');
        }
        return response.json();
    },

    // Создать отзыв
    async create(data) {
        const response = await apiPost('/reviews', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка создания отзыва');
        }
        return response.json();
    },

    // Обновить отзыв
    async update(id, data) {
        const response = await apiPut(`/reviews/${id}`, data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления отзыва');
        }
        return response.json();
    },

    // Изменить статус отзыва (модерация)
    async updateStatus(id, status) {
        const response = await apiPut(`/reviews/${id}/status`, { status });
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка изменения статуса');
        }
        return response.json();
    },

    // Удалить отзыв
    async delete(id) {
        const response = await apiDelete(`/reviews/${id}`);
        if (!response.ok) {
            throw new Error('Ошибка удаления отзыва');
        }
        return true;
    },
};

// ============================================
// Payment Settings API
// ============================================
export const paymentSettingsAPI = {
    // Получить настройки ЮКасса
    async getYooKassaSettings() {
        const response = await apiGet('/payment-settings/yookassa');
        if (!response.ok) {
            throw new Error('Ошибка загрузки настроек ЮКасса');
        }
        return response.json();
    },

    // Обновить настройки ЮКасса
    async updateYooKassaSettings(data) {
        const response = await apiPut('/payment-settings/yookassa', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка обновления настроек ЮКасса');
        }
        return response.json();
    },

    // Проверить подключение к ЮКасса
    async testYooKassaConnection(data) {
        const response = await apiPost('/payment-settings/yookassa/test', data);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Ошибка проверки подключения');
        }
        return response.json();
    },
};

