import { useState, useEffect } from 'react';
import { ClipboardList, Filter, Loader2 } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { OrderCard } from '@/components/miniapp/OrderCard';
import { useOrders } from '@/hooks/useOrders';
import { OrderStatus } from '@/types';

const statusFilters: { value: OrderStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'Все' },
  { value: 'new', label: 'Новые' },
  { value: 'preparing', label: 'Готовятся' },
  { value: 'in_transit', label: 'В пути' },
  { value: 'delivered', label: 'Доставлены' },
  { value: 'cancelled', label: 'Отменены' },
];

export function OrdersPage() {
  const navigate = useNavigate();
  const { orders, loading, error, loadOrders } = useOrders();
  const [statusFilter, setStatusFilter] = useState<OrderStatus | 'all'>('all');

  useEffect(() => {
    console.log('OrdersPage - Component mounted, loading orders...');
    loadOrders();
  }, [loadOrders]);

  useEffect(() => {
    console.log('OrdersPage - Orders state changed:', {
      ordersCount: orders.length,
      loading,
      error,
      orders: orders,
    });
  }, [orders, loading, error]);

  const filteredOrders = statusFilter === 'all' 
    ? orders 
    : orders.filter(order => order.status === statusFilter);

  if (loading) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Мои заказы" />
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка заказов...</p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Мои заказы" />
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <div className="flex h-24 w-24 items-center justify-center rounded-full bg-destructive/10">
            <ClipboardList className="h-12 w-12 text-destructive" />
          </div>
          <h2 className="mt-6 text-xl font-bold text-foreground">Ошибка загрузки</h2>
          <p className="mt-2 text-center text-muted-foreground">{error}</p>
          <button
            onClick={() => loadOrders()}
            className="mt-6 rounded-xl bg-primary px-8 py-3 font-semibold text-primary-foreground touch-feedback"
          >
            Попробовать снова
          </button>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  if (orders.length === 0 && !loading) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Мои заказы" />

        <div className="flex flex-col items-center justify-center px-4 py-16">
          <div className="flex h-24 w-24 items-center justify-center rounded-full bg-secondary">
            <ClipboardList className="h-12 w-12 text-muted-foreground" />
          </div>
          <h2 className="mt-6 text-xl font-bold text-foreground">Нет заказов</h2>
          <p className="mt-2 text-center text-muted-foreground">
            Здесь будет история ваших заказов
          </p>
          <button
            onClick={() => navigate('/')}
            className="mt-6 rounded-xl bg-primary px-8 py-3 font-semibold text-primary-foreground touch-feedback"
          >
            Перейти в каталог
          </button>
        </div>

        <BottomNavigation />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background pb-20">
      <MiniAppHeader title="Мои заказы" />

      {/* Status Filter */}
      <div className="px-4 py-3 border-b border-border">
        <div className="flex items-center gap-2 overflow-x-auto scrollbar-hide">
          <Filter className="h-4 w-4 text-muted-foreground flex-shrink-0" />
          {statusFilters.map((filter) => (
            <button
              key={filter.value}
              onClick={() => setStatusFilter(filter.value)}
              className={`flex-shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors touch-feedback ${
                statusFilter === filter.value
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-secondary text-secondary-foreground'
              }`}
            >
              {filter.label}
            </button>
          ))}
        </div>
      </div>

      <div className="space-y-3 px-4 py-4">
        {filteredOrders.length === 0 ? (
          <div className="py-12 text-center">
            <p className="text-muted-foreground">Нет заказов с выбранным статусом</p>
          </div>
        ) : (
          filteredOrders.map((order) => (
            <OrderCard
              key={order.id}
              order={order}
              onClick={() => navigate(`/orders/${order.orderId}`)}
            />
          ))
        )}
      </div>

      <BottomNavigation />
    </div>
  );
}
