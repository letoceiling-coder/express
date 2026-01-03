import { Category, Product, Order, CreateOrderPayload, OrderItem } from '@/types';

const API_BASE = '/api/v1';

// Утилита для работы с API
const apiRequest = async (url: string, options: RequestInit = {}) => {
  const token = localStorage.getItem('token');
  
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${url}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Ошибка запроса' }));
    throw new Error(error.message || 'Ошибка запроса');
  }

  return response.json();
};

// Categories API
export const categoriesAPI = {
  async getAll(): Promise<Category[]> {
    const response = await apiRequest('/categories');
    const categories = response.data || [];
    
    return categories.map((cat: any) => ({
      id: String(cat.id),
      name: cat.name,
      createdAt: new Date(cat.created_at),
      updatedAt: new Date(cat.updated_at),
    }));
  },
};

// Products API
export const productsAPI = {
  async getAll(categoryId?: string): Promise<Product[]> {
    const params = new URLSearchParams();
    params.append('is_available', 'true');
    if (categoryId && categoryId !== 'all') {
      params.append('category_id', categoryId);
    }

    const response = await apiRequest(`/products?${params.toString()}`);
    const products = response.data?.data || response.data || [];
    
    return products.map((product: any) => ({
      id: String(product.id),
      name: product.name,
      description: product.description || '',
      price: Number(product.price),
      categoryId: product.category_id ? String(product.category_id) : '',
      imageUrl: product.image?.url || '',
      isWeightProduct: product.is_weight_product || false,
      createdAt: new Date(product.created_at),
      updatedAt: new Date(product.updated_at),
    }));
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
        isWeightProduct: product.is_weight_product || false,
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
    // Генерируем order_id на клиенте (или можно доверить серверу)
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
    const randomNum = Math.floor(Math.random() * 1000) + 1;
    const orderId = `ORD-${dateStr}-${randomNum}`;

    const orderData = {
      order_id: orderId,
      telegram_id: telegramId,
      phone: payload.phone,
      delivery_address: payload.deliveryAddress,
      delivery_time: payload.deliveryTime,
      comment: payload.comment || null,
      total_amount: payload.totalAmount,
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

    // ВАЖНО: Этот endpoint нужно будет создать на сервере
    // Пока используем прямое создание через OrderController (если будет метод store)
    const response = await apiRequest('/orders', {
      method: 'POST',
      body: JSON.stringify(orderData),
    });

    const order = response.data;
    
    return {
      id: String(order.id),
      orderId: order.order_id,
      telegramId: order.telegram_id,
      status: order.status as Order['status'],
      phone: order.phone,
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
    const response = await apiRequest(`/orders?telegram_id=${telegramId}`);
    const orders = response.data?.data || response.data || [];
    
    return orders.map((order: any) => ({
      id: String(order.id),
      orderId: order.order_id,
      telegramId: order.telegram_id,
      status: order.status as Order['status'],
      phone: order.phone,
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
  },

  async getByOrderId(orderId: string): Promise<Order | null> {
    try {
      // Ищем по order_id через поиск
      const response = await apiRequest(`/orders?search=${orderId}`);
      const orders = response.data?.data || response.data || [];
      const order = orders.find((o: any) => o.order_id === orderId);
      
      if (!order) return null;
      
      return {
        id: String(order.id),
        orderId: order.order_id,
        telegramId: order.telegram_id,
        status: order.status as Order['status'],
        phone: order.phone,
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
};
