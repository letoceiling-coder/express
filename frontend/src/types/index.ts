// Product & Catalog Types
export interface Category {
  id: string;
  name: string;
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
  deliveryTime: string;
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
  preparing: 'Готовится',
  ready_for_delivery: 'Готов к отправке',
  in_transit: 'В пути',
  delivered: 'Доставлен',
  cancelled: 'Отменён',
};

export const PAYMENT_STATUS_LABELS: Record<PaymentStatus, string> = {
  pending: 'Ожидает оплаты',
  succeeded: 'Оплачено',
  failed: 'Ошибка оплаты',
  cancelled: 'Отменено',
};
