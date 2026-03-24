import { useState, useCallback } from 'react';
import { ordersAPI } from '@/api';
import { Order, CreateOrderPayload } from '@/types';

export function useWebOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [errorStatus, setErrorStatus] = useState<number | null>(null);

  const loadOrders = useCallback(async (force = false) => {
    setLoading(true);
    setError(null);
    setErrorStatus(null);
    try {
      const data = await ordersAPI.getMyOrders();
      setOrders(data);
    } catch (e: any) {
      setError(e?.response?.data?.message || e?.message || 'Ошибка загрузки заказов');
      setErrorStatus(e?.response?.status ?? null);
      setOrders([]);
    } finally {
      setLoading(false);
    }
  }, []);

  const createOrder = useCallback(async (payload: CreateOrderPayload): Promise<Order> => {
    setLoading(true);
    setError(null);
    setErrorStatus(null);
    try {
      const order = await ordersAPI.create(payload);
      setOrders((prev) => [order, ...prev]);
      return order;
    } catch (e: any) {
      setError(e?.response?.data?.message || e?.message || 'Ошибка создания заказа');
      setErrorStatus(e?.response?.status ?? null);
      throw e;
    } finally {
      setLoading(false);
    }
  }, []);

  const getOrderById = useCallback(async (orderId: string): Promise<Order | null> => {
    const found = orders.find((o) => o.orderId === orderId);
    if (found) return found;
    try {
      const all = await ordersAPI.getMyOrders();
      return all.find((o) => o.orderId === orderId) ?? null;
    } catch {
      return null;
    }
  }, [orders]);

  return { orders, loading, error, errorStatus, loadOrders, createOrder, getOrderById };
}
