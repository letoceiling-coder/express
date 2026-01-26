import { Category, Product, Order, CreateOrderPayload, OrderItem } from '@/types';

const API_BASE = '/api/v1';

// Получить initData из Telegram WebApp
const getInitData = (): string | null => {
  try {
    const tg = window.Telegram?.WebApp;
    if (tg?.initData) {
      return tg.initData;
    }
    return null;
  } catch (e) {
    console.warn('Failed to get initData:', e);
    return null;
  }
};

// Проверить, запущено ли приложение в Telegram WebApp
const isTelegramWebApp = (): boolean => {
  try {
    return !!window.Telegram?.WebApp;
  } catch {
    return false;
  }
};

// Утилита для работы с API
const apiRequest = async (url: string, options: RequestInit = {}) => {
  const token = localStorage.getItem('token');
  const initData = getInitData();
  const isInTelegram = isTelegramWebApp();
  
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  // Передаем initData для валидации на сервере (только если есть Telegram WebApp)
  if (initData && isInTelegram) {
    headers['X-Telegram-Init-Data'] = initData;
  }

  const fullUrl = `${API_BASE}${url}`;
  console.log('apiRequest - Making request:', { 
    url: fullUrl, 
    method: options.method || 'GET', 
    hasToken: !!token,
    hasInitData: !!initData,
    isInTelegram,
  });

  try {
    const response = await fetch(fullUrl, {
      ...options,
      headers,
    });

    console.log('apiRequest - Response received:', {
      url: fullUrl,
      status: response.status,
      statusText: response.statusText,
      ok: response.ok,
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ message: 'Ошибка запроса' }));
      console.error('apiRequest - Error response:', {
        url: fullUrl,
        status: response.status,
        errorData,
      });
      const error = new Error(errorData.message || 'Ошибка запроса');
      (error as any).response = { status: response.status, data: errorData };
      throw error;
    }

    const data = await response.json();
    console.log('apiRequest - Success response:', { url: fullUrl, dataType: typeof data, dataKeys: data && typeof data === 'object' ? Object.keys(data) : null });
    return data;
  } catch (error: any) {
    console.error('apiRequest - Fetch error:', {
      url: fullUrl,
      error: error.message,
      stack: error.stack,
    });
    throw error;
  }
};

// Categories API
export const categoriesAPI = {
  async getAll(): Promise<Category[]> {
    const params = new URLSearchParams();
    params.append('per_page', '0'); // Отключаем пагинацию для получения всех категорий
    
    const response = await apiRequest(`/categories?${params.toString()}`);
    
    // Обрабатываем разные форматы ответа: массив, объект с data (пагинация или нет)
    let categories: any[] = [];
    
    if (Array.isArray(response.data)) {
      // Если response.data - это уже массив
      categories = response.data;
    } else if (response.data && typeof response.data === 'object' && Array.isArray(response.data.data)) {
      // Если это объект пагинации с полем data
      categories = response.data.data;
    } else if (Array.isArray(response)) {
      // Если response сам по себе массив (нестандартный формат)
      categories = response;
    }
    
    // Дополнительная проверка: если categories все еще не массив, возвращаем пустой массив
    if (!Array.isArray(categories)) {
      console.warn('Categories API returned non-array data:', response);
      categories = [];
    }
    
    return categories.map((cat: any) => ({
      id: String(cat.id),
      name: cat.name,
      sortOrder: cat.sort_order || 0,
      isActive: cat.is_active !== false,
      createdAt: new Date(cat.created_at),
      updatedAt: new Date(cat.updated_at),
    }));
  },

  async updatePositions(categories: Array<{ id: string; sort_order: number }>): Promise<void> {
    await apiRequest('/categories/update-positions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ categories }),
    });
  },
};

// Products API
export const productsAPI = {
  async getAll(categoryId?: string): Promise<Product[]> {
    const params = new URLSearchParams();
    params.append('is_available', 'true');
    params.append('per_page', '0'); // Отключаем пагинацию для получения всех продуктов
    params.append('sort_by', 'sort_order'); // Сортировка по позиции
    params.append('sort_order', 'asc'); // По возрастанию
    if (categoryId && categoryId !== 'all') {
      params.append('category_id', categoryId);
    }

    const response = await apiRequest(`/products?${params.toString()}`);
    // Гарантируем, что products всегда массив
    // Обрабатываем разные форматы ответа: массив, объект с data (пагинация или нет)
    let products: any[] = [];
    
    if (Array.isArray(response.data)) {
      // Если response.data - это уже массив
      products = response.data;
    } else if (response.data && typeof response.data === 'object' && Array.isArray(response.data.data)) {
      // Если это объект пагинации с полем data
      products = response.data.data;
    } else if (Array.isArray(response)) {
      // Если response сам по себе массив (нестандартный формат)
      products = response;
    }
    
    // Дополнительная проверка: если products все еще не массив, возвращаем пустой массив
    if (!Array.isArray(products)) {
      console.warn('Products API returned non-array data:', response);
      products = [];
    }
    
    return products.map((product: any) => ({
      id: String(product.id),
      name: product.name,
      description: product.description || '',
      price: Number(product.price),
      categoryId: product.category_id ? String(product.category_id) : '',
      imageUrl: product.image?.url || '',
      webpUrl: product.image?.webp_url || undefined,
      imageVariants: product.image?.variants || undefined,
      isWeightProduct: product.is_weight_product || false,
      sortOrder: product.sort_order || 0,
      createdAt: new Date(product.created_at),
      updatedAt: new Date(product.updated_at),
    }));
  },

  async updatePositions(products: Array<{ id: string; sort_order: number }>): Promise<void> {
    await apiRequest('/products/update-positions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ products }),
    });
  },

  async getById(id: string): Promise<Product | null> {
    try {
      const response = await apiRequest(`/products/${id}`);
      const product = response.data;
      
      if (!product) return null;
      
          return {
            id: String(product.id),
            name: product.name,
            description: product.description || '',
            price: Number(product.price),
            categoryId: product.category_id ? String(product.category_id) : '',
            imageUrl: product.image?.url || '',
            webpUrl: product.image?.webp_url || undefined,
            imageVariants: product.image?.variants || undefined,
            isWeightProduct: product.is_weight_product || false,
            sortOrder: product.sort_order || 0,
            createdAt: new Date(product.created_at),
            updatedAt: new Date(product.updated_at),
          };
    } catch (error) {
      return null;
    }
  },
};

// Orders API
export const ordersAPI = {
  async create(payload: CreateOrderPayload, telegramId: number): Promise<Order> {
    console.log('ordersAPI.create - Creating order', {
      telegramId,
      telegramIdType: typeof telegramId,
      payloadPhone: payload.phone,
      payloadItemsCount: payload.items.length,
      totalAmount: payload.totalAmount,
    });
    
    // Генерируем order_id на клиенте (или можно доверить серверу)
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
    const randomNum = Math.floor(Math.random() * 1000) + 1;
    const orderId = `ORD-${dateStr}-${randomNum}`;

    const orderData = {
      order_id: orderId,
      telegram_id: telegramId,
      phone: payload.phone,
      email: payload.email || null, // Email для чека
      name: payload.name || null,
      delivery_address: payload.deliveryAddress,
      delivery_time: payload.deliveryTime || null,
      delivery_type: payload.deliveryType || 'courier',
      delivery_cost: payload.deliveryCost || 0, // Стоимость доставки
      comment: payload.comment || null,
      total_amount: payload.totalAmount,
      original_amount: payload.originalAmount || payload.totalAmount,
      discount: payload.discount || 0,
      status: 'new',
      payment_status: 'pending',
      items: payload.items.map(item => ({
        product_id: item.productId ? Number(item.productId) : null,
        product_name: item.productName || '',
        product_image: item.productImage || null,
        quantity: item.quantity,
        unit_price: item.unitPrice,
      })),
    };

    console.log('ordersAPI.create - Order data prepared', {
      orderId,
      telegram_id: orderData.telegram_id,
      itemsCount: orderData.items.length,
    });

    // ВАЖНО: Этот endpoint нужно будет создать на сервере
    // Пока используем прямое создание через OrderController (если будет метод store)
    const response = await apiRequest('/orders', {
      method: 'POST',
      body: JSON.stringify(orderData),
    });
    
    console.log('ordersAPI.create - Order created successfully', {
      orderId: response.data?.order_id,
      telegramId: response.data?.telegram_id,
    });

    const order = response.data;
    
    return {
      id: String(order.id),
      orderId: order.order_id,
      telegramId: order.telegram_id,
      status: order.status as Order['status'],
      phone: order.phone,
      name: order.name || undefined,
      deliveryAddress: order.delivery_address,
      deliveryTime: order.delivery_time,
      comment: order.comment || undefined,
      totalAmount: Number(order.total_amount),
      items: (order.items || []).map((item: any) => ({
        id: String(item.id),
        productId: item.product_id ? String(item.product_id) : '',
        productName: item.product_name,
        productImage: item.product_image || undefined,
        quantity: item.quantity,
        unitPrice: Number(item.unit_price),
        total: Number(item.total),
      })),
      paymentId: order.payment_id || undefined,
      paymentStatus: order.payment_status as Order['paymentStatus'],
      createdAt: new Date(order.created_at),
      updatedAt: new Date(order.updated_at),
    };
  },

  async getByTelegramId(telegramId: number): Promise<Order[]> {
    try {
      const url = `/orders?telegram_id=${telegramId}&sort_by=created_at&sort_order=desc&per_page=0`;
      console.log('Orders API - getByTelegramId request:', { telegramId, url });
      
      // Добавляем параметры для сортировки и отключения пагинации
      const response = await apiRequest(url);
      
      console.log('Orders API - getByTelegramId raw response:', response);
      
      // Обрабатываем разные форматы ответа: пагинация или массив
      let orders: any[] = [];
      
      // Laravel может возвращать:
      // 1. Пагинированный ответ: { data: { data: [...], current_page: 1, ... } }
      // 2. Коллекцию без пагинации: { data: [...] } - когда per_page=0
      // 3. Прямой массив: [...]
      
      console.log('Orders API - Processing response:', {
        responseType: typeof response,
        dataType: typeof response.data,
        isDataArray: Array.isArray(response.data),
        dataKeys: response.data && typeof response.data === 'object' ? Object.keys(response.data) : null,
      });
      
      if (Array.isArray(response.data)) {
        // Если response.data - это уже массив
        orders = response.data;
        console.log('Orders API - Response is direct array, count:', orders.length);
      } else if (response.data && typeof response.data === 'object') {
        // Если это объект с полем data
        if (Array.isArray(response.data.data)) {
          // Самый частый случай: { data: [...] }
          orders = response.data.data;
          console.log('Orders API - Response is object with data array, count:', orders.length);
        } else if (response.data.data && typeof response.data.data === 'object') {
          // Вложенная структура (пагинация)
          if (Array.isArray(response.data.data.data)) {
            orders = response.data.data.data;
            console.log('Orders API - Response is nested pagination, count:', orders.length);
          } else if (response.data.data.items && Array.isArray(response.data.data.items)) {
            // Альтернативный формат пагинации
            orders = response.data.data.items;
            console.log('Orders API - Response is pagination with items, count:', orders.length);
          } else {
            console.warn('Orders API - Unknown nested structure:', response.data);
            console.warn('Orders API - response.data.data keys:', Object.keys(response.data.data || {}));
          }
        } else {
          console.warn('Orders API - Response.data is not array:', response.data);
          console.warn('Orders API - response.data type:', typeof response.data);
        }
      } else {
        console.warn('Orders API - Unexpected response format:', response);
      }
      
      console.log('Orders API - getByTelegramId final result:', {
        telegramId,
        ordersCount: orders.length,
        firstOrder: orders[0],
      });
      
      return orders.map((order: any) => ({
      id: String(order.id),
      orderId: order.order_id,
      telegramId: order.telegram_id,
      status: order.status as Order['status'],
      phone: order.phone,
      name: order.name || undefined,
      deliveryAddress: order.delivery_address,
      deliveryTime: order.delivery_time,
      comment: order.comment || undefined,
      totalAmount: Number(order.total_amount),
      items: (order.items || []).map((item: any) => ({
        id: String(item.id),
        productId: item.product_id ? String(item.product_id) : '',
        productName: item.product_name,
        productImage: item.product_image || undefined,
        quantity: item.quantity,
        unitPrice: Number(item.unit_price),
        total: Number(item.total),
      })),
      paymentId: order.payment_id || undefined,
      paymentStatus: order.payment_status as Order['paymentStatus'],
      createdAt: new Date(order.created_at),
      updatedAt: new Date(order.updated_at),
    }));
    } catch (error) {
      console.error('Orders API - getByTelegramId error:', error);
      throw error;
    }
  },

  async getByOrderId(orderId: string): Promise<Order | null> {
    try {
      console.log('Orders API - getByOrderId request:', { orderId });
      
      // Получаем telegram_id для публичного запроса
      const { getTelegramUser } = await import('@/lib/telegram');
      const user = getTelegramUser();
      const telegramId = user?.id;
      
      if (!telegramId) {
        console.warn('Orders API - getByOrderId: No telegram_id, cannot fetch order');
        return null;
      }
      
      // Ищем по order_id через поиск с telegram_id для безопасности
      const searchUrl = `/orders?search=${encodeURIComponent(orderId)}&telegram_id=${telegramId}`;
      console.log('Orders API - getByOrderId search URL:', searchUrl);
      
      const response = await apiRequest(searchUrl);
      console.log('Orders API - getByOrderId raw response:', response);
      
      const orders = response.data?.data || response.data || [];
      const order = Array.isArray(orders) ? orders.find((o: any) => o.order_id === orderId) : null;
      
      if (!order) {
        console.warn('Orders API - getByOrderId: Order not found', { orderId, ordersCount: orders.length });
        return null;
      }
      
      console.log('Orders API - getByOrderId: Order found', { orderId: order.order_id });
      
      return {
        id: String(order.id),
        orderId: order.order_id,
        telegramId: order.telegram_id,
        status: order.status as Order['status'],
        phone: order.phone,
        name: order.name || undefined,
        deliveryAddress: order.delivery_address,
        deliveryTime: order.delivery_time,
        comment: order.comment || undefined,
        totalAmount: Number(order.total_amount),
        items: (order.items || []).map((item: any) => ({
          id: String(item.id),
          productId: item.product_id ? String(item.product_id) : '',
          productName: item.product_name,
          productImage: item.product_image || undefined,
          quantity: item.quantity,
          unitPrice: Number(item.unit_price),
          total: Number(item.total),
        })),
        paymentId: order.payment_id || undefined,
        paymentStatus: order.payment_status as Order['paymentStatus'],
        createdAt: new Date(order.created_at),
        updatedAt: new Date(order.updated_at),
      };
    } catch (error) {
      return null;
    }
  },

  async cancelOrder(orderId: string): Promise<Order> {
    try {
      // Находим заказ по orderId
      if (!orderId) {
        throw new Error('Не указан номер заказа');
      }

      const order = await this.getByOrderId(orderId);
      if (!order) {
        throw new Error('Заказ не найден');
      }

      // Получаем telegram_id для проверки владельца заказа
      const { getTelegramUser } = await import('@/lib/telegram');
      const user = getTelegramUser();
      const telegramId = user?.id;

      if (!telegramId) {
        throw new Error('Не удалось определить пользователя Telegram');
      }

      const response = await apiRequest(`/orders/${order.id}/cancel`, {
        method: 'POST',
        body: JSON.stringify({
          telegram_id: telegramId,
        }),
      });

      if (!response.data) {
        throw new Error(response.message || 'Ошибка отмены заказа');
      }

      // Преобразуем ответ сервера в объект Order
      const cancelledOrder = response.data;
      return {
        id: String(cancelledOrder.id),
        orderId: cancelledOrder.order_id,
        telegramId: cancelledOrder.telegram_id,
        status: cancelledOrder.status as Order['status'],
        phone: cancelledOrder.phone,
        name: cancelledOrder.name || undefined,
        deliveryAddress: cancelledOrder.delivery_address,
        deliveryTime: cancelledOrder.delivery_time,
        comment: cancelledOrder.comment || undefined,
        totalAmount: Number(cancelledOrder.total_amount),
        items: (cancelledOrder.items || []).map((item: any) => ({
          id: String(item.id),
          productId: item.product_id ? String(item.product_id) : '',
          productName: item.product_name,
          productImage: item.product_image || undefined,
          quantity: item.quantity,
          unitPrice: Number(item.unit_price),
          total: Number(item.total),
        })),
        paymentId: cancelledOrder.payment_id || undefined,
        paymentStatus: cancelledOrder.payment_status as Order['paymentStatus'],
        createdAt: new Date(cancelledOrder.created_at),
        updatedAt: new Date(cancelledOrder.updated_at),
      };
    } catch (error: any) {
      console.error('Orders API - cancelOrder error:', error);
      throw error;
    }
  },

  async getPaymentLink(orderId: string): Promise<string | null> {
    try {
      const order = await this.getByOrderId(orderId);
      if (!order) {
        throw new Error('Заказ не найден');
      }

      const user = await import('@/lib/telegram').then(m => m.getTelegramUser());
      const telegramId = user?.id;

      if (!order.paymentId) {
        // Создаем новый платеж
        const returnUrl = `${window.location.origin}/orders/${order.orderId}?payment=success`;
        const paymentData = await paymentAPI.createYooKassaPayment(
          Number(order.id),
          order.totalAmount,
          returnUrl,
          `Оплата заказа ${order.orderId}`,
          telegramId
        );
        return paymentData.confirmation_url || null;
      }

      // Если платеж уже существует, получаем его URL
      const response = await apiRequest(`/payments/yookassa/${order.paymentId}/link`);
      return response.data?.confirmation_url || null;
    } catch (error: any) {
      console.error('Orders API - getPaymentLink error:', error);
      throw error;
    }
  },

  async updatePaymentStatus(orderId: string, paymentId: string, status: 'succeeded' | 'failed'): Promise<void> {
    // Ищем заказ по order_id
    const orders = await this.getByTelegramId(0); // Получаем все заказы или создаем отдельный метод
    const order = await this.getByOrderId(orderId);
    
    if (!order) throw new Error('Заказ не найден');

    await apiRequest(`/orders/${order.id}`, {
      method: 'PUT',
      body: JSON.stringify({
        payment_id: paymentId,
        payment_status: status,
        status: status === 'succeeded' ? 'accepted' : 'cancelled',
      }),
    });
  },

  /**
   * Синхронизировать статус платежа для заказа с ЮKassa
   * @param orderId - строковый идентификатор заказа (ORD-...)
   */
  async syncPaymentStatus(orderId: string): Promise<any> {
    try {
      const order = await this.getByOrderId(orderId);
      if (!order) throw new Error('Заказ не найден');

      // Используем числовой ID заказа из БД
      const response = await apiRequest(`/orders/${order.id}/payments/sync-status`, {
        method: 'POST',
      });
      return response.data;
    } catch (error: any) {
      console.error('Orders API - syncPaymentStatus error:', error);
      // Не пробрасываем ошибку дальше, чтобы не ломать UI
      return null;
    }
  },
};

// Payment Methods API
export const paymentMethodsAPI = {
  async getAll(): Promise<any[]> {
    try {
      const response = await apiRequest('/payment-methods');
      console.log('PaymentMethods API - getAll raw response:', response);
      
      // Обрабатываем разные форматы ответа: массив, объект с data (пагинация или нет)
      let methods: any[] = [];
      
      if (Array.isArray(response.data)) {
        // Если response.data - это уже массив
        methods = response.data;
        console.log('PaymentMethods API - Response is direct array, count:', methods.length);
      } else if (response.data && typeof response.data === 'object' && Array.isArray(response.data.data)) {
        // Если это объект пагинации с полем data
        methods = response.data.data;
        console.log('PaymentMethods API - Response is object with data array, count:', methods.length);
      } else if (Array.isArray(response)) {
        // Если response сам по себе массив (нестандартный формат)
        methods = response;
        console.log('PaymentMethods API - Response is direct array (root), count:', methods.length);
      } else if (response.data && typeof response.data === 'object' && !Array.isArray(response.data)) {
        // Если response.data - объект, но не массив, возможно это один элемент
        console.warn('PaymentMethods API - Response.data is object but not array:', response.data);
        methods = [];
      }
      
      // Дополнительная проверка: если methods все еще не массив, возвращаем пустой массив
      if (!Array.isArray(methods)) {
        console.warn('PaymentMethods API returned non-array data:', response);
        methods = [];
      }
      
      console.log('PaymentMethods API - getAll final result:', {
        methodsCount: methods.length,
        firstMethod: methods[0],
      });
      
      return methods.map((method: any) => ({
        id: String(method.id),
        code: method.code,
        name: method.name,
        description: method.description || undefined,
        isEnabled: method.is_enabled,
        isDefault: method.is_default || false,
        availableForDelivery: method.available_for_delivery !== false,
        availableForPickup: method.available_for_pickup !== false,
        sortOrder: method.sort_order,
        discountType: method.discount_type,
        discountValue: Number(method.discount_value) || 0,
        minCartAmount: Number(method.min_cart_amount) || 0,
        showNotification: method.show_notification,
        notificationText: method.notification_text || undefined,
        settings: method.settings || {},
      }));
    } catch (error) {
      console.error('PaymentMethods API - getAll error:', error);
      return [];
    }
  },

  async getById(id: string | number, cartAmount?: number): Promise<any | null> {
    try {
      const url = cartAmount 
        ? `/payment-methods/${id}?cart_amount=${cartAmount}`
        : `/payment-methods/${id}`;
      const response = await apiRequest(url);
      console.log('PaymentMethods API - getById raw response:', response);
      
      // Обрабатываем разные форматы ответа
      const method = response.data || response;
      
      if (!method) {
        console.warn('PaymentMethods API - getById: method not found');
        return null;
      }
      
      const result = {
        id: String(method.id),
        code: method.code,
        name: method.name,
        description: method.description || undefined,
        isEnabled: method.is_enabled,
        sortOrder: method.sort_order,
        discountType: method.discount?.discount_type || method.discount_type,
        discountValue: Number(method.discount?.discount_value || method.discount_value) || 0,
        minCartAmount: Number(method.discount?.min_cart_amount || method.min_cart_amount) || 0,
        showNotification: method.show_notification,
        notificationText: method.notification || method.notification_text || undefined,
        settings: method.settings || {},
        discount: method.discount ? {
          discount: Number(method.discount.discount || 0),
          final_amount: Number(method.discount.final_amount || 0),
          applied: method.discount.applied || false,
        } : undefined,
      };
      
      console.log('PaymentMethods API - getById final result:', result);
      return result;
    } catch (error) {
      console.error('Payment Methods API - getById error:', error);
      return null;
    }
  },
};

// Payment Settings API (Admin)
export const paymentSettingsAPI = {
  async getYooKassa(): Promise<any | null> {
    try {
      const response = await apiRequest('/payment-settings/yookassa');
      return response.data || null;
    } catch (error: any) {
      console.error('PaymentSettings API - getYooKassa error:', error);
      throw error;
    }
  },

  async updateYooKassa(data: any): Promise<any> {
    try {
      const response = await apiRequest('/payment-settings/yookassa', {
        method: 'PUT',
        body: JSON.stringify(data),
      });
      return response.data;
    } catch (error: any) {
      console.error('PaymentSettings API - updateYooKassa error:', error);
      throw error;
    }
  },

  async testYooKassa(): Promise<any> {
    try {
      const response = await apiRequest('/payment-settings/yookassa/test', {
        method: 'POST',
      });
      return response;
    } catch (error: any) {
      console.error('PaymentSettings API - testYooKassa error:', error);
      throw error;
    }
  },
};

// About Page API (Admin)
export const aboutPageAPI = {
  async get(): Promise<any | null> {
    try {
      const response = await apiRequest('/admin/about');
      return response.data || null;
    } catch (error: any) {
      console.error('AboutPage API - get error:', error);
      throw error;
    }
  },

  async update(data: any): Promise<any> {
    try {
      const response = await apiRequest('/admin/about', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      return response.data;
    } catch (error: any) {
      console.error('AboutPage API - update error:', error);
      throw error;
    }
  },
};

// Delivery Settings API
export const deliverySettingsAPI = {
  async getSettings(): Promise<any | null> {
    try {
      const response = await apiRequest('/delivery-settings');

      // Бэкенд может вернуть как { data: {...} }, так и просто {...}.
      // Нормализуем формат, чтобы фронт везде получал один и тот же объект настроек.
      const settings = (response && typeof response === 'object')
        ? (response.data ?? response)
        : null;

      // Логирование для отладки
      if (settings) {
        console.log('[deliverySettingsAPI] Settings received:', {
          min_delivery_order_total_rub: settings.min_delivery_order_total_rub,
          free_delivery_threshold: settings.free_delivery_threshold,
          free_delivery_threshold_rub: settings.free_delivery_threshold_rub,
          allKeys: Object.keys(settings),
        });
      }

      return settings || null;
    } catch (error: any) {
      console.error('DeliverySettings API - getSettings error:', error);
      throw error;
    }
  },

  async updateSettings(data: any): Promise<any> {
    try {
      const response = await apiRequest('/delivery-settings', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      return response.data;
    } catch (error: any) {
      console.error('DeliverySettings API - updateSettings error:', error);
      throw error;
    }
  },

  async calculateCost(address: string, cartTotal: number = 0): Promise<{
    valid: boolean;
    address?: string;
    coordinates?: { latitude: number; longitude: number };
    distance?: number;
    cost?: number;
    zone?: string;
    error?: string;
  }> {
    try {
      const response = await apiRequest('/delivery/calculate-cost', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ address, cart_total: cartTotal }),
      });
      // API возвращает данные напрямую, без обертки data
      return response || { valid: false, error: 'Неизвестная ошибка' };
    } catch (error: any) {
      console.error('DeliverySettings API - calculateCost error:', error);
      return {
        valid: false,
        error: error.response?.data?.error || 'Ошибка при расчете стоимости доставки',
      };
    }
  },

  async getAddressSuggestions(query: string, city?: string): Promise<{
    success: boolean;
    suggestions: Array<{ value: string; display: string; subtitle?: string }>;
    error?: string;
  }> {
    try {
      const response = await apiRequest('/delivery/address-suggestions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query, city }),
      });
      return response || { success: false, suggestions: [] };
    } catch (error: any) {
      console.error('DeliverySettings API - getAddressSuggestions error:', error);
      return {
        success: false,
        suggestions: [],
        error: error.response?.data?.error || 'Ошибка при получении подсказок',
      };
    }
  },
};

// About Page API
export const aboutAPI = {
  async get(): Promise<any | null> {
    try {
      const response = await apiRequest('/about');
      return response.data || null;
    } catch (error: any) {
      console.error('About API - get error:', error);
      throw error;
    }
  },
};

// Legal Documents API
export const legalDocumentsAPI = {
  async getAll(): Promise<any[]> {
    try {
      const response = await apiRequest('/legal-documents');
      return response.data || [];
    } catch (error: any) {
      console.error('Legal Documents API - getAll error:', error);
      throw error;
    }
  },

  async getByType(type: string): Promise<any> {
    try {
      const response = await apiRequest(`/legal-documents/${type}`);
      return response.data;
    } catch (error: any) {
      console.error('Legal Documents API - getByType error:', error);
      throw error;
    }
  },

  async getAdmin(): Promise<any[]> {
    try {
      const response = await apiRequest('/admin/legal-documents');
      return response.data || [];
    } catch (error: any) {
      console.error('Legal Documents API - getAdmin error:', error);
      throw error;
    }
  },

  async update(documents: any[]): Promise<any[]> {
    try {
      const response = await apiRequest('/admin/legal-documents', {
        method: 'PUT',
        body: JSON.stringify({ documents }),
      });
      return response.data || [];
    } catch (error: any) {
      console.error('Legal Documents API - update error:', error);
      throw error;
    }
  },
};

// Payment API
export const paymentAPI = {
  async createYooKassaPayment(orderId: number, amount: number, returnUrl: string, description?: string, telegramId?: number, email?: string): Promise<any> {
    try {
      // Получаем telegram_id если не передан
      let finalTelegramId = telegramId;
      if (!finalTelegramId) {
        const { getTelegramUser } = await import('@/lib/telegram');
        const user = getTelegramUser();
        finalTelegramId = user?.id;
      }
      
      const requestBody: any = {
        order_id: orderId,
        amount,
        return_url: returnUrl,
      };
      
      if (description) {
        requestBody.description = description;
      }
      
      if (finalTelegramId) {
        requestBody.telegram_id = finalTelegramId;
      }
      
      // Добавляем email для отправки квитанции
      if (email) {
        requestBody.email = email;
      }
      
      const response = await apiRequest('/payments/yookassa/create', {
        method: 'POST',
        body: JSON.stringify(requestBody),
      });
      return response.data;
    } catch (error: any) {
      console.error('Payment API - createYooKassaPayment error:', error);
      throw error;
    }
  },
};
