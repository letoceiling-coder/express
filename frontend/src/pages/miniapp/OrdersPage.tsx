import { useState, useEffect, useMemo } from 'react';
import { ClipboardList, Filter, Loader2, FileText } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { OrderCard } from '@/components/miniapp/OrderCard';
import { useOrders } from '@/hooks/useOrders';
import { OrderStatus, Order, isOrderUnpaid, canCancelOrder } from '@/types';
import { ordersAPI } from '@/api';
import { paymentAPI } from '@/api';
import { toast } from 'sonner';
import { getTelegramUser, hapticFeedback } from '@/lib/telegram';

const statusFilters: { value: OrderStatus | 'all' | 'pending_payment'; label: string }[] = [
  { value: 'all', label: 'Все' },
  { value: 'pending_payment', label: 'Ожидает оплаты' },
  { value: 'preparing', label: 'В работе' },
  { value: 'in_transit', label: 'В доставке' },
  { value: 'delivered', label: 'Завершён' },
];

export function OrdersPage() {
  const navigate = useNavigate();
  const { orders, loading, error, loadOrders } = useOrders();
  const [statusFilter, setStatusFilter] = useState<OrderStatus | 'all' | 'pending_payment'>('all');

  useEffect(() => {
    console.log('OrdersPage - Component mounted, checking if orders need loading...');
    console.log('OrdersPage - Current orders count:', orders.length);
    
    // Загружаем заказы только если их нет или прошло достаточно времени
    if (orders.length === 0) {
      console.log('OrdersPage - No orders, loading...');
      // Небольшая задержка, чтобы Telegram WebApp успел инициализироваться
      const timer = setTimeout(() => {
        console.log('OrdersPage - Calling loadOrders after delay...');
        loadOrders(true); // Принудительная загрузка при первом открытии
      }, 300);
      
      return () => clearTimeout(timer);
    } else {
      console.log('OrdersPage - Orders already loaded, refreshing silently...');
      // Обновляем заказы в фоне без показа загрузки
      // Принудительно обновляем, чтобы синхронизировать статусы платежей
      const timer = setTimeout(() => {
        loadOrders(true); // Принудительное обновление для синхронизации статусов
      }, 1000);
      
      return () => clearTimeout(timer);
    }
  }, []); // Запускаем только при монтировании
  
  // Обновляем заказы при возврате на страницу (например, после оплаты)
  useEffect(() => {
    const handleFocus = () => {
      console.log('OrdersPage - Window focused, refreshing orders...');
      loadOrders(true);
    };
    
    window.addEventListener('focus', handleFocus);
    return () => window.removeEventListener('focus', handleFocus);
  }, [loadOrders]);

  useEffect(() => {
    console.log('OrdersPage - Orders state changed:', {
      ordersCount: orders.length,
      loading,
      error,
      orders: orders,
    });
  }, [orders, loading, error]);

  const filteredOrders = useMemo(() => {
    if (statusFilter === 'all') {
      return orders;
    }
    if (statusFilter === 'pending_payment') {
      return orders.filter(order => order.paymentStatus === 'pending' && order.status !== 'cancelled');
    }
    return orders.filter(order => order.status === statusFilter);
  }, [orders, statusFilter]);

  const handlePayment = async (order: Order) => {
    if (!isOrderUnpaid(order)) {
      toast.error('Заказ уже оплачен или отменен');
      return;
    }

    hapticFeedback('light');

    try {
      const user = getTelegramUser();
      const telegramId = user?.id;

      // Получаем email из Telegram WebApp для отправки квитанции
      const telegramEmail = window.Telegram?.WebApp?.initDataUnsafe?.user?.email;

      // Создаем платеж через ЮKassa
      const returnUrl = `${window.location.origin}/orders/${order.orderId}?payment=success`;
      toast.info('Создание платежа...');

      const paymentData = await paymentAPI.createYooKassaPayment(
        Number(order.id),
        order.totalAmount,
        returnUrl,
        `Оплата заказа #${order.orderId}`,
        telegramId,
        telegramEmail
      );

      // Получаем URL для оплаты
      const confirmationUrl =
        paymentData?.data?.confirmation_url ||
        paymentData?.data?.yookassa_payment?.confirmation?.confirmation_url ||
        paymentData?.yookassa_payment?.confirmation?.confirmation_url ||
        paymentData?.confirmation_url;

      if (confirmationUrl) {
        // Проверяем тестовый режим
        const isTestMode = paymentData?.data?.is_test_mode ?? false;
        if (isTestMode) {
          toast.info('Тестовый режим: используется тестовая среда YooKassa', {
            duration: 3000,
          });
        }

        // Переходим на страницу оплаты
        window.location.href = confirmationUrl;
        toast.success('Переход к оплате...');
      } else {
        console.error('Confirmation URL not found in response:', paymentData);
        toast.error('Ошибка: URL для оплаты не получен');
      }
    } catch (error: any) {
      console.error('Ошибка при создании платежа:', error);
      toast.error(error?.message || 'Ошибка при создании платежа. Попробуйте позже.');
    }
  };

  const handleCancel = async (order: Order) => {
    if (!canCancelOrder(order)) {
      if (order.status === 'cancelled') {
        toast.error('Заказ уже отменен');
      } else {
        toast.error('Заказ нельзя отменить');
      }
      return;
    }

    if (!window.confirm('Вы уверены, что хотите отменить этот заказ?')) {
      return;
    }

    hapticFeedback('light');

    try {
      await ordersAPI.cancelOrder(order.orderId);
      toast.success('Заказ успешно отменен');
      
      // Обновляем список заказов
      await loadOrders(true);
    } catch (error: any) {
      console.error('Ошибка при отмене заказа:', error);
      toast.error(error?.message || 'Ошибка при отмене заказа');
    }
  };

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
    const isWebVersion = !window.Telegram?.WebApp;
    const isTelegramUserError = error.includes('Не удалось определить пользователя') || error.includes('работает только в Telegram');
    
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Мои заказы" />
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <div className="flex h-24 w-24 items-center justify-center rounded-full bg-destructive/10">
            <ClipboardList className="h-12 w-12 text-destructive" />
          </div>
          <h2 className="mt-6 text-xl font-bold text-foreground">
            {isWebVersion ? 'Требуется Telegram Mini App' : 'Ошибка загрузки'}
          </h2>
          <p className="mt-2 text-center text-muted-foreground">{error}</p>
          {isWebVersion && (
            <div className="mt-4 p-4 rounded-lg bg-secondary/50 border border-border">
              <p className="text-sm text-muted-foreground text-center">
                Для просмотра заказов откройте это приложение через Telegram бота.
              </p>
            </div>
          )}
          {!isWebVersion && !isTelegramUserError && (
            <button
              onClick={() => loadOrders(true)}
              className="mt-6 rounded-xl bg-primary px-8 py-3 font-semibold text-primary-foreground touch-feedback"
            >
              Попробовать снова
            </button>
          )}
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
              onPayment={() => handlePayment(order)}
              onCancel={() => handleCancel(order)}
            />
          ))
        )}
      </div>

      {/* Legal Documents Link */}
      <div className="px-4 pb-20">
        <button
          onClick={() => navigate('/legal-documents')}
          className="w-full flex items-center justify-center gap-2 py-3 text-sm text-muted-foreground hover:text-foreground transition-colors touch-feedback"
        >
          <FileText className="h-4 w-4" />
          <span>Политика конфиденциальности и оферта</span>
        </button>
      </div>

      <BottomNavigation />
    </div>
  );
}
