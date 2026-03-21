import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { ClipboardList, Filter, Loader2 } from 'lucide-react';
import { useAuthStore } from '@/store/authStore';
import { useWebOrders } from '@/hooks/useWebOrders';
import { AuthModal } from '@/components/web/AuthModal';
import { OrderCard } from '@/components/miniapp/OrderCard';
import { OrderStatus, Order, isOrderUnpaid, canCancelOrder } from '@/types';
import { ordersAPI, paymentAPI } from '@/api';
import { toast } from 'sonner';

const statusFilters: { value: OrderStatus | 'all' | 'pending_payment'; label: string }[] = [
  { value: 'all', label: 'Все' },
  { value: 'pending_payment', label: 'Ожидает оплаты' },
  { value: 'preparing', label: 'В работе' },
  { value: 'in_transit', label: 'В доставке' },
  { value: 'delivered', label: 'Завершён' },
];

export function WebOrdersPage() {
  const navigate = useNavigate();
  const isAuth = useAuthStore((s) => s.isAuthenticated());
  const user = useAuthStore((s) => s.user);
  const [showAuth, setShowAuth] = useState(false);
  const { orders, loading, error, loadOrders } = useWebOrders();
  const [statusFilter, setStatusFilter] = useState<OrderStatus | 'all' | 'pending_payment'>('all');

  useEffect(() => {
    if (isAuth) loadOrders(true);
  }, [isAuth, loadOrders]);

  const filteredOrders = orders.filter((o) => {
    if (statusFilter === 'all') return true;
    if (statusFilter === 'pending_payment') return o.paymentStatus === 'pending' && o.status !== 'cancelled';
    return o.status === statusFilter;
  });

  const handlePayment = async (order: Order) => {
    if (!isOrderUnpaid(order)) {
      toast.error('Заказ уже оплачен или отменен');
      return;
    }
    try {
      const returnUrl = `${window.location.origin}/orders/${order.orderId}?payment=success`;
      const paymentData = await paymentAPI.createYooKassaPayment(
        Number(order.id),
        order.totalAmount,
        returnUrl,
        `Оплата #${order.orderId}`,
        undefined,
        user?.email
      );
      const url = paymentData?.confirmation_url || paymentData?.data?.confirmation_url;
      if (url) window.location.href = url;
      else toast.error('Не удалось получить ссылку на оплату');
    } catch (e: any) {
      toast.error(e?.message || 'Ошибка создания платежа');
    }
  };

  const handleCancel = async (order: Order) => {
    if (!canCancelOrder(order)) {
      toast.error(order.status === 'cancelled' ? 'Заказ уже отменен' : 'Заказ нельзя отменить');
      return;
    }
    if (!window.confirm('Отменить заказ?')) return;
    try {
      await ordersAPI.cancelOrder(order.orderId);
      toast.success('Заказ отменен');
      loadOrders(true);
    } catch (e: any) {
      toast.error(e?.message || 'Ошибка отмены');
    }
  };

  if (!isAuth) {
    return (
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <h1 className="text-2xl font-bold">Мои заказы</h1>
        <p className="mt-4 text-center text-muted-foreground">Войдите, чтобы просматривать заказы</p>
        <button
          onClick={() => setShowAuth(true)}
          className="mt-6 rounded-xl bg-primary px-8 py-3 font-semibold text-primary-foreground"
        >
          Войти по номеру телефона
        </button>
        <button onClick={() => navigate('/')} className="mt-4 text-muted-foreground hover:text-foreground">
          В каталог
        </button>
        {showAuth && <AuthModal onClose={() => setShowAuth(false)} onSuccess={() => loadOrders(true)} />}
      </div>
    );
  }

  if (loading && orders.length === 0) {
    return (
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="mt-4 text-muted-foreground">Загрузка заказов...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <ClipboardList className="h-12 w-12 text-destructive" />
        <h2 className="mt-4 text-xl font-bold">Ошибка</h2>
        <p className="mt-2 text-muted-foreground">{error}</p>
        <button onClick={() => loadOrders(true)} className="mt-6 rounded-xl bg-primary px-6 py-2 text-primary-foreground">
          Повторить
        </button>
      </div>
    );
  }

  if (orders.length === 0) {
    return (
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <ClipboardList className="h-12 w-12 text-muted-foreground" />
        <h2 className="mt-4 text-xl font-bold">Нет заказов</h2>
        <p className="mt-2 text-muted-foreground">Здесь будет история заказов</p>
        <button
          onClick={() => navigate('/')}
          className="mt-6 rounded-xl bg-primary px-8 py-3 font-semibold text-primary-foreground"
        >
          В каталог
        </button>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold mb-4">Мои заказы</h1>

      <div className="flex gap-2 overflow-x-auto pb-3 mb-4">
        <Filter className="h-4 w-4 shrink-0 self-center" />
        {statusFilters.map((f) => (
          <button
            key={f.value}
            onClick={() => setStatusFilter(f.value)}
            className={`shrink-0 rounded-full px-4 py-2 text-sm font-medium ${
              statusFilter === f.value ? 'bg-primary text-primary-foreground' : 'bg-muted'
            }`}
          >
            {f.label}
          </button>
        ))}
      </div>

      <div className="space-y-4">
        {filteredOrders.length === 0 ? (
          <p className="text-muted-foreground text-center py-8">Нет заказов с выбранным статусом</p>
        ) : (
          filteredOrders.map((order) => (
            <OrderCard
              key={order.id}
              order={order}
              onClick={() => navigate(`/orders/${order.orderId}`)}
              onPayment={() => handlePayment(order)}
              onCancel={() => handleCancel(order)}
            />
          ))
        )}
      </div>
    </div>
  );
}
