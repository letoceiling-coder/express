// Product & Catalog Types
export interface Category {
  id: string;
  name: string;
  sortOrder?: number;
  isActive?: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface Product {
  id: string;
  name: string;
  description: string;
  price: number;
  categoryId: string;
  imageUrl: string;
  webpUrl?: string;
  imageVariants?: {
    thumbnail?: { webp?: string; jpeg?: string };
    medium?: { webp?: string; jpeg?: string };
    large?: { webp?: string; jpeg?: string };
  };
  isWeightProduct: boolean;
  createdAt: Date;
  updatedAt: Date;
}

// Cart Types
export interface CartItem {
  product: Product;
  quantity: number;
}

// Order Types
export type OrderStatus = 
  | 'new'
  | 'accepted'
  | 'preparing'
  | 'ready_for_delivery'
  | 'in_transit'
  | 'delivered'
  | 'cancelled';

export type PaymentStatus = 'pending' | 'succeeded' | 'failed' | 'cancelled';

export interface OrderItem {
  id: string;
  productId: string;
  productName: string;
  productImage?: string;
  quantity: number;
  unitPrice: number;
  total: number;
}

export interface Order {
  id: string;
  orderId: string; // ORD-20251220-1 format
  telegramId: number;
  status: OrderStatus;
  phone: string;
  name?: string;
  deliveryAddress: string;
  deliveryTime: string; // "15:00-16:00" or datetime
  comment?: string;
  totalAmount: number;
  items: OrderItem[];
  paymentId?: string;
  paymentStatus: PaymentStatus;
  createdAt: Date;
  updatedAt: Date;
  estimatedDeliveryTime?: Date;
}

export interface CreateOrderPayload {
  phone: string;
  deliveryAddress: string;
  deliveryTime?: string;
  deliveryType?: 'courier' | 'pickup';
  comment?: string;
  items: {
    productId: string;
    productName: string;
    productImage?: string;
    quantity: number;
    unitPrice: number;
  }[];
  totalAmount: number;
}

// User Types
export interface User {
  telegramId: number;
  firstName: string;
  phone?: string;
  createdAt: Date;
  updatedAt: Date;
}

// Status labels in Russian
export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  new: 'Новый',
  accepted: 'Принят',
  preparing: 'В работе',
  ready_for_delivery: 'Готов к отправке',
  in_transit: 'В доставке',
  delivered: 'Завершён',
  cancelled: 'Отменён',
};

// Helper to check if order is unpaid
export const isOrderUnpaid = (order: Order): boolean => {
  return order.paymentStatus === 'pending' && order.status !== 'cancelled' && order.status !== 'delivered';
};

// Helper to check if order can be cancelled
export const canCancelOrder = (order: Order): boolean => {
  // Заказ можно отменить только если:
  // 1. Он не оплачен (paymentStatus === 'pending')
  // 2. Он не отменен (status !== 'cancelled')
  // 3. Он не доставлен (status !== 'delivered')
  return order.paymentStatus === 'pending' && order.status !== 'cancelled' && order.status !== 'delivered';
};

export const PAYMENT_STATUS_LABELS: Record<PaymentStatus, string> = {
  pending: 'Ожидает оплаты',
  succeeded: 'Оплачено',
  failed: 'Ошибка оплаты',
  cancelled: 'Отменено',
};
