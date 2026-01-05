import { useState, useCallback } from 'react';
import { ordersAPI } from '@/api';
import { Order, CreateOrderPayload } from '@/types';
import { getTelegramUser } from '@/lib/telegram';

export function useOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadOrders = useCallback(async () => {
    // Ждем немного, чтобы Telegram WebApp успел инициализироваться
    await new Promise(resolve => setTimeout(resolve, 100));
    
    let user = getTelegramUser();
    console.log('useOrders - getTelegramUser result (first try):', user);
    
    // Если пользователь не найден, пробуем еще раз
    if (!user?.id) {
      await new Promise(resolve => setTimeout(resolve, 200));
      user = getTelegramUser();
      console.log('useOrders - getTelegramUser result (second try):', user);
    }
    
    const telegramId = user?.id || 0;
    console.log('useOrders - Loading orders for telegramId:', telegramId);
    
    if (!telegramId) {
      console.warn('useOrders - No telegram user ID, returning empty orders');
      console.warn('useOrders - window.Telegram:', window.Telegram);
      setOrders([]);
      return;
    }
    
    setLoading(true);
    setError(null);
    
    try {
      console.log('useOrders - Calling ordersAPI.getByTelegramId with:', telegramId);
      const data = await ordersAPI.getByTelegramId(telegramId);
      console.log('useOrders - Received orders:', data, 'count:', data.length);
      setOrders(data);
      
      if (data.length === 0) {
        console.warn('useOrders - No orders returned for telegramId:', telegramId);
      }
    } catch (err: any) {
      console.error('useOrders - Failed to load orders:', err);
      console.error('useOrders - Error details:', {
        message: err?.message,
        response: err?.response,
        status: err?.response?.status,
        data: err?.response?.data,
      });
      setError(err?.response?.data?.message || err?.message || 'Ошибка загрузки заказов');
      setOrders([]); // Очищаем заказы при ошибке
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
