import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/store/authStore';
import { useWebOrders } from '@/hooks/useWebOrders';
import { ordersAPI, paymentAPI } from '@/api';
import { Order, isOrderUnpaid, canCancelOrder } from '@/types';
import { OptimizedImage } from '@/components/OptimizedImage';
import { Button } from '@/components/ui/button';
import { Loader2, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';

const STATUS_LABELS: Record<string, string> = {
  new: 'Новый',
  accepted: 'Принят',
  preparing: 'В работе',
  ready_for_delivery: 'Готов к доставке',
  in_transit: 'В доставке',
  delivered: 'Доставлен',
  cancelled: 'Отменён',
};

export function WebOrderDetailPage() {
  const { orderId } = useParams();
  const navigate = useNavigate();
  const isAuth = useAuthStore((s) => s.isAuthenticated());
  const user = useAuthStore((s) => s.user);
  const { getOrderById, loadOrders } = useWebOrders();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);

  useEffect(() => {
    if (!isAuth) {
      navigate('/orders');
      return;
    }
  }, [isAuth, navigate]);

  useEffect(() => {
    const load = async () => {
      if (!orderId) return;
      setLoading(true);
      try {
        const searchParams = new URLSearchParams(window.location.search);
        const paymentParam = searchParams.get('payment');
        if (paymentParam === 'success') {
          setSyncing(true);
          await ordersAPI.syncPaymentStatus(orderId);
          await new Promise((r) => setTimeout(r, 500));
          setSyncing(false);
        }
        const o = await getOrderById(orderId);
        setOrder(o ?? null);
        if (paymentParam === 'success') loadOrders(true);
      } catch {
        setOrder(null);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [orderId, getOrderById, loadOrders]);

  const handlePayment = async () => {
    if (!order || !isOrderUnpaid(order)) return;
    try {
      const returnUrl = `${window.location.origin}/orders/${order.orderId}?payment=success`;
      const data = await paymentAPI.createYooKassaPayment(
        Number(order.id),
        order.totalAmount,
        returnUrl,
        `Оплата #${order.orderId}`,
        undefined,
        user?.email
      );
      const url = data?.confirmation_url || data?.data?.confirmation_url;
      if (url) window.location.href = url;
      else toast.error('Не удалось получить ссылку');
    } catch (e: any) {
      toast.error(e?.message || 'Ошибка');
    }
  };

  const handleCancel = async () => {
    if (!order || !canCancelOrder(order)) return;
    if (!window.confirm('Отменить заказ?')) return;
    try {
      await ordersAPI.cancelOrder(order.orderId);
      toast.success('Заказ отменён');
      loadOrders(true);
      setOrder({ ...order, status: 'cancelled' });
    } catch (e: any) {
      toast.error(e?.message || 'Ошибка');
    }
  };

  if (!isAuth) return null;

  if (loading) {
    return (
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="mt-4 text-muted-foreground">Загрузка...</p>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="container mx-auto px-4 py-8">
        <button onClick={() => navigate('/orders')} className="flex items-center gap-2 text-muted-foreground hover:text-foreground mb-4">
          <ArrowLeft className="h-4 w-4" /> Назад
        </button>
        <p className="text-muted-foreground">Заказ не найден</p>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8 max-w-2xl">
      <button onClick={() => navigate('/orders')} className="flex items-center gap-2 text-muted-foreground hover:text-foreground mb-6">
        <ArrowLeft className="h-4 w-4" /> К заказам
      </button>

      <div className="flex justify-between items-start mb-6">
        <h1 className="text-2xl font-bold">Заказ {order.orderId}</h1>
        <span className={`rounded-full px-3 py-1 text-sm font-medium ${
          order.status === 'cancelled' ? 'bg-destructive/20 text-destructive' :
          order.paymentStatus === 'succeeded' ? 'bg-green-500/20 text-green-600' :
          'bg-muted'
        }`}>
          {STATUS_LABELS[order.status] || order.status}
        </span>
      </div>

      <div className="rounded-xl border bg-card p-4 space-y-2 mb-4">
        <p><strong>Телефон:</strong> {order.phone}</p>
        <p><strong>Адрес:</strong> {order.deliveryAddress}</p>
        <p><strong>Время:</strong> {order.deliveryTime}</p>
        {order.comment && <p><strong>Комментарий:</strong> {order.comment}</p>}
      </div>

      <div className="rounded-xl border bg-card p-4 mb-4">
        <h3 className="font-semibold mb-3">Товары</h3>
        <div className="space-y-3">
          {order.items.map((item) => (
            <div key={item.id} className="flex gap-3">
              <div className="h-16 w-16 shrink-0 rounded-lg overflow-hidden bg-muted">
                <OptimizedImage src={item.productImage || ''} alt={item.productName} className="h-full w-full object-contain" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium truncate">{item.productName}</p>
                <p className="text-sm text-muted-foreground">{item.quantity} × {item.unitPrice.toLocaleString('ru-RU')} ₽</p>
              </div>
              <div className="font-semibold">{(item.quantity * item.unitPrice).toLocaleString('ru-RU')} ₽</div>
            </div>
          ))}
        </div>
        <div className="mt-4 pt-4 border-t flex justify-between text-lg font-bold">
          <span>Итого</span>
          <span>{order.totalAmount.toLocaleString('ru-RU')} ₽</span>
        </div>
      </div>

      {syncing && (
        <p className="text-sm text-muted-foreground mb-4 flex items-center gap-2">
          <Loader2 className="h-4 w-4 animate-spin" /> Обновление статуса оплаты...
        </p>
      )}

      {isOrderUnpaid(order) && (
        <div className="flex gap-3">
          <Button onClick={handlePayment} className="flex-1">Оплатить</Button>
          {canCancelOrder(order) && (
            <Button variant="outline" onClick={handleCancel}>Отменить</Button>
          )}
        </div>
      )}
    </div>
  );
}
