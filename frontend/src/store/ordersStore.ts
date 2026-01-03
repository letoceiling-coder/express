import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { Order, CreateOrderPayload, OrderStatus } from '@/types';

interface OrdersStore {
  orders: Order[];
  createOrder: (payload: CreateOrderPayload) => Order;
  getOrderById: (orderId: string) => Order | undefined;
  updateOrderStatus: (orderId: string, status: OrderStatus) => void;
}

const generateOrderId = (): string => {
  const now = new Date();
  const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
  const randomNum = Math.floor(Math.random() * 1000) + 1;
  return `ORD-${dateStr}-${randomNum}`;
};

export const useOrdersStore = create<OrdersStore>()(
  persist(
    (set, get) => ({
      orders: [],

      createOrder: (payload: CreateOrderPayload) => {
        const orderId = generateOrderId();
        const now = new Date();
        
        const newOrder: Order = {
          id: crypto.randomUUID(),
          orderId,
          telegramId: window.Telegram?.WebApp?.initDataUnsafe?.user?.id || 0,
          status: 'new',
          phone: payload.phone,
          deliveryAddress: payload.deliveryAddress,
          deliveryTime: payload.deliveryTime,
          comment: payload.comment,
          totalAmount: payload.totalAmount,
          items: payload.items.map((item, index) => ({
            id: `${orderId}-${index}`,
            productId: item.productId,
            productName: '', // Will be filled by caller
            quantity: item.quantity,
            unitPrice: item.unitPrice,
            total: item.quantity * item.unitPrice,
          })),
          paymentStatus: 'pending',
          createdAt: now,
          updatedAt: now,
        };

        set((state) => ({
          orders: [newOrder, ...state.orders],
        }));

        return newOrder;
      },

      getOrderById: (orderId: string) => {
        return get().orders.find((order) => order.orderId === orderId || order.id === orderId);
      },

      updateOrderStatus: (orderId: string, status: OrderStatus) => {
        set((state) => ({
          orders: state.orders.map((order) =>
            order.orderId === orderId || order.id === orderId
              ? { ...order, status, updatedAt: new Date() }
              : order
          ),
        }));
      },
    }),
    {
      name: 'orders-storage',
    }
  )
);
