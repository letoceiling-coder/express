import { supabase } from '@/integrations/supabase/client';
import { Category, Product, Order, CreateOrderPayload, OrderItem } from '@/types';

// Categories API
export const categoriesAPI = {
  async getAll(): Promise<Category[]> {
    const { data, error } = await supabase
      .from('categories')
      .select('*')
      .order('sort_order', { ascending: true });
    
    if (error) throw error;
    
    return (data || []).map(cat => ({
      id: cat.id,
      name: cat.name,
      createdAt: new Date(cat.created_at),
      updatedAt: new Date(cat.updated_at),
    }));
  },
};

// Products API
export const productsAPI = {
  async getAll(categoryId?: string): Promise<Product[]> {
    let query = supabase
      .from('products')
      .select('*')
      .eq('is_available', true);
    
    if (categoryId && categoryId !== 'all') {
      query = query.eq('category_id', categoryId);
    }
    
    const { data, error } = await query.order('created_at', { ascending: false });
    
    if (error) throw error;
    
    return (data || []).map(product => ({
      id: product.id,
      name: product.name,
      description: product.description,
      price: Number(product.price),
      categoryId: product.category_id || '',
      imageUrl: product.image_url || '',
      isWeightProduct: product.is_weight_product || false,
      createdAt: new Date(product.created_at),
      updatedAt: new Date(product.updated_at),
    }));
  },

  async getById(id: string): Promise<Product | null> {
    const { data, error } = await supabase
      .from('products')
      .select('*')
      .eq('id', id)
      .single();
    
    if (error) return null;
    
    return {
      id: data.id,
      name: data.name,
      description: data.description,
      price: Number(data.price),
      categoryId: data.category_id || '',
      imageUrl: data.image_url || '',
      isWeightProduct: data.is_weight_product || false,
      createdAt: new Date(data.created_at),
      updatedAt: new Date(data.updated_at),
    };
  },
};

// Orders API
export const ordersAPI = {
  async create(payload: CreateOrderPayload, telegramId: number): Promise<Order> {
    // Generate order ID
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
    const randomNum = Math.floor(Math.random() * 1000) + 1;
    const orderId = `ORD-${dateStr}-${randomNum}`;
    
    // Insert order
    const { data: orderData, error: orderError } = await supabase
      .from('orders')
      .insert({
        order_id: orderId,
        telegram_id: telegramId,
        phone: payload.phone,
        delivery_address: payload.deliveryAddress,
        delivery_time: payload.deliveryTime,
        comment: payload.comment || null,
        total_amount: payload.totalAmount,
        status: 'new',
        payment_status: 'pending',
      })
      .select()
      .single();
    
    if (orderError) throw orderError;
    
    // Insert order items
    const orderItems = payload.items.map(item => ({
      order_id: orderData.id,
      product_id: item.productId,
      product_name: item.productName || '',
      product_image: item.productImage || null,
      quantity: item.quantity,
      unit_price: item.unitPrice,
      total: item.quantity * item.unitPrice,
    }));
    
    const { data: itemsData, error: itemsError } = await supabase
      .from('order_items')
      .insert(orderItems)
      .select();
    
    if (itemsError) throw itemsError;
    
    return {
      id: orderData.id,
      orderId: orderData.order_id,
      telegramId: orderData.telegram_id,
      status: orderData.status as Order['status'],
      phone: orderData.phone,
      deliveryAddress: orderData.delivery_address,
      deliveryTime: orderData.delivery_time,
      comment: orderData.comment || undefined,
      totalAmount: Number(orderData.total_amount),
      items: (itemsData || []).map(item => ({
        id: item.id,
        productId: item.product_id || '',
        productName: item.product_name,
        productImage: item.product_image || undefined,
        quantity: item.quantity,
        unitPrice: Number(item.unit_price),
        total: Number(item.total),
      })),
      paymentId: orderData.payment_id || undefined,
      paymentStatus: orderData.payment_status as Order['paymentStatus'],
      createdAt: new Date(orderData.created_at),
      updatedAt: new Date(orderData.updated_at),
    };
  },

  async getByTelegramId(telegramId: number): Promise<Order[]> {
    const { data: orders, error } = await supabase
      .from('orders')
      .select(`
        *,
        order_items (*)
      `)
      .eq('telegram_id', telegramId)
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    
    return (orders || []).map(order => ({
      id: order.id,
      orderId: order.order_id,
      telegramId: order.telegram_id,
      status: order.status as Order['status'],
      phone: order.phone,
      deliveryAddress: order.delivery_address,
      deliveryTime: order.delivery_time,
      comment: order.comment || undefined,
      totalAmount: Number(order.total_amount),
      items: (order.order_items || []).map((item: any) => ({
        id: item.id,
        productId: item.product_id || '',
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
    const { data: order, error } = await supabase
      .from('orders')
      .select(`
        *,
        order_items (*)
      `)
      .eq('order_id', orderId)
      .single();
    
    if (error) return null;
    
    return {
      id: order.id,
      orderId: order.order_id,
      telegramId: order.telegram_id,
      status: order.status as Order['status'],
      phone: order.phone,
      deliveryAddress: order.delivery_address,
      deliveryTime: order.delivery_time,
      comment: order.comment || undefined,
      totalAmount: Number(order.total_amount),
      items: (order.order_items || []).map((item: any) => ({
        id: item.id,
        productId: item.product_id || '',
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

  async updatePaymentStatus(orderId: string, paymentId: string, status: 'succeeded' | 'failed'): Promise<void> {
    const { error } = await supabase
      .from('orders')
      .update({
        payment_id: paymentId,
        payment_status: status,
        status: status === 'succeeded' ? 'accepted' : 'cancelled',
      })
      .eq('order_id', orderId);
    
    if (error) throw error;
  },
};
