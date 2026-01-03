import { useState, useCallback } from 'react';
import { ordersAPI } from '@/api';
import { Order, CreateOrderPayload } from '@/types';
import { getTelegramUser } from '@/lib/telegram';

export function useOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadOrders = useCallback(async () => {
    const user = getTelegramUser();
    const telegramId = user?.id || 0;
    
    if (!telegramId) {
      // No telegram user, return empty orders
      setOrders([]);
      return;
    }
    
    setLoading(true);
    setError(null);
    
    try {
      const data = await ordersAPI.getByTelegramId(telegramId);
      setOrders(data);
    } catch (err) {
      console.error('Failed to load orders:', err);
      setError('Ошибка загрузки заказов');
    } finally {
      setLoading(false);
    }
  }, []);

  const createOrder = useCallback(async (payload: CreateOrderPayload): Promise<Order> => {
    const user = getTelegramUser();
    const telegramId = user?.id || 0;
    
    setLoading(true);
    setError(null);
    
    try {
      const order = await ordersAPI.create(payload, telegramId);
      setOrders(prev => [order, ...prev]);
      return order;
    } catch (err) {
      console.error('Failed to create order:', err);
      setError('Ошибка создания заказа');
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const getOrderById = useCallback(async (orderId: string): Promise<Order | null> => {
    try {
      return await ordersAPI.getByOrderId(orderId);
    } catch (err) {
      console.error('Failed to get order:', err);
      return null;
    }
  }, []);

  return { orders, loading, error, loadOrders, createOrder, getOrderById };
}
