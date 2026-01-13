import { useState, useEffect } from 'react';
import { useParams, useNavigate, useSearchParams } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { StatusBadge } from '@/components/ui/status-badge';
import { useOrders } from '@/hooks/useOrders';
import { ORDER_STATUS_LABELS, PAYMENT_STATUS_LABELS, Order } from '@/types';
import { MapPin, Phone, Clock, MessageSquare, CreditCard, Package, Headphones, ShoppingBag, Loader2, CheckCircle2, XCircle } from 'lucide-react';
import { openTelegramLink } from '@/lib/telegram';
import { cn } from '@/lib/utils';
import { OptimizedImage } from '@/components/OptimizedImage';
import { toast } from 'sonner';

export function OrderDetailPage() {
  const { orderId } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { getOrderById, orders } = useOrders();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [showPaymentSuccess, setShowPaymentSuccess] = useState(false);
  const [showPaymentError, setShowPaymentError] = useState(false);

  useEffect(() => {
    const fetchOrder = async () => {
      if (!orderId) return;
      
      // Проверяем параметры URL для обработки возврата с оплаты
      const paymentStatus = searchParams.get('payment');
      if (paymentStatus === 'success') {
        setShowPaymentSuccess(true);
        toast.success('Оплата успешно выполнена!');
        // Убираем параметр из URL
        navigate(`/orders/${orderId}`, { replace: true });
      } else if (paymentStatus === 'error') {
        setShowPaymentError(true);
        toast.error('Произошла ошибка при оплате');
        // Убираем параметр из URL
        navigate(`/orders/${orderId}`, { replace: true });
      }
      
      // Сначала проверяем заказы из кеша
      const cachedOrder = orders.find(o => o.orderId === orderId);
      if (cachedOrder) {
        console.log('OrderDetailPage - Found order in cache:', cachedOrder.orderId);
        setOrder(cachedOrder);
        setLoading(false);
        return;
      }
      
      // Если не найден в кеше, загружаем с сервера
      console.log('OrderDetailPage - Order not in cache, fetching from server:', orderId);
      setLoading(true);
      const data = await getOrderById(orderId);
      setOrder(data);
      setLoading(false);
    };
    fetchOrder();
  }, [orderId, getOrderById, orders, searchParams, navigate]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Заказ" showBack />
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка заказа...</p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  if (!order) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Заказ" showBack />

        <div className="flex flex-col items-center justify-center px-4 py-16">
          <h2 className="text-xl font-bold text-foreground">Заказ не найден</h2>
          <p className="mt-2 text-center text-muted-foreground">
            Возможно, он был удалён или ссылка устарела
          </p>
          <button
            onClick={() => navigate('/orders')}
            className="mt-6 rounded-xl bg-primary px-8 py-3 font-semibold text-primary-foreground touch-feedback"
          >
            К списку заказов
          </button>
        </div>

        <BottomNavigation />
      </div>
    );
  }

  const formatDate = (date: Date) => {
    const d = new Date(date);
    return d.toLocaleDateString('ru-RU', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const handleContactSupport = () => {
    openTelegramLink('https://t.me/svoihleb_support');
  };

  return (
    <div className="min-h-screen bg-background pb-44">
      <MiniAppHeader title={`Заказ #${order.orderId}`} showBack />

      <div className="px-4 py-4 space-y-4">
        {/* Payment Success Alert */}
        {showPaymentSuccess && (
          <div className="rounded-xl border border-green-500/20 bg-green-500/10 p-4 animate-fade-in">
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 text-green-500 flex-shrink-0" />
              <div className="flex-1">
                <h3 className="text-sm font-semibold text-green-700 dark:text-green-400">
                  Оплата успешно выполнена!
                </h3>
                <p className="mt-1 text-xs text-green-600 dark:text-green-500">
                  Ваш платеж обрабатывается. Заказ будет обработан после подтверждения оплаты.
                </p>
              </div>
              <button
                onClick={() => setShowPaymentSuccess(false)}
                className="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300"
              >
                <XCircle className="h-4 w-4" />
              </button>
            </div>
          </div>
        )}

        {/* Payment Error Alert */}
        {showPaymentError && (
          <div className="rounded-xl border border-red-500/20 bg-red-500/10 p-4 animate-fade-in">
            <div className="flex items-center gap-3">
              <XCircle className="h-5 w-5 text-red-500 flex-shrink-0" />
              <div className="flex-1">
                <h3 className="text-sm font-semibold text-red-700 dark:text-red-400">
                  Ошибка при оплате
                </h3>
                <p className="mt-1 text-xs text-red-600 dark:text-red-500">
                  Произошла ошибка при обработке платежа. Заказ создан, но оплата не была выполнена.
                </p>
              </div>
              <button
                onClick={() => setShowPaymentError(false)}
                className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
              >
                <XCircle className="h-4 w-4" />
              </button>
            </div>
          </div>
        )}

        {/* Order ID & Status */}
        <div className="rounded-xl border border-border bg-card p-4">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-sm text-muted-foreground">Номер заказа</p>
              <p className="text-lg font-bold text-foreground">#{order.orderId}</p>
              <p className="mt-1 text-sm text-muted-foreground">
                {formatDate(order.createdAt)}
              </p>
            </div>
            <StatusBadge status={order.status} size="lg" />
          </div>
        </div>

        {/* Current Status */}
        <div className="rounded-xl border border-border bg-card p-4">
          <h3 className="mb-3 font-semibold text-foreground">Статус заказа</h3>
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
              <Clock className="h-6 w-6 text-primary" />
            </div>
            <div>
              <p className="font-medium text-foreground">{ORDER_STATUS_LABELS[order.status]}</p>
              {order.deliveryTime && (
                <p className="text-sm text-muted-foreground">
                  Ожидаемая доставка: {order.deliveryTime}
                </p>
              )}
            </div>
          </div>
        </div>

        {/* Delivery Info */}
        <div className="rounded-xl border border-border bg-card p-4 space-y-3">
          <h3 className="font-semibold text-foreground">Доставка</h3>
          
          <div className="flex items-start gap-3">
            <MapPin className="h-5 w-5 text-muted-foreground flex-shrink-0 mt-0.5" />
            <p className="text-sm text-foreground">{order.deliveryAddress}</p>
          </div>
          
          <div className="flex items-center gap-3">
            <Clock className="h-5 w-5 text-muted-foreground flex-shrink-0" />
            <p className="text-sm text-foreground">{order.deliveryTime}</p>
          </div>
          
          <div className="flex items-center gap-3">
            <Phone className="h-5 w-5 text-muted-foreground flex-shrink-0" />
            <p className="text-sm text-foreground">{order.phone}</p>
          </div>
          
          {order.comment && (
            <div className="flex items-start gap-3">
              <MessageSquare className="h-5 w-5 text-muted-foreground flex-shrink-0 mt-0.5" />
              <p className="text-sm text-foreground">{order.comment}</p>
            </div>
          )}
        </div>

        {/* Order Items */}
        <div className="rounded-xl border border-border bg-card overflow-hidden">
          <div className="flex items-center gap-2 p-4 border-b border-border">
            <Package className="h-5 w-5 text-muted-foreground" />
            <h3 className="font-semibold text-foreground">Состав заказа</h3>
          </div>
          
          {order.items.map((item, index) => (
            <div
              key={item.id}
              className={cn(
                'flex items-center gap-3 p-4',
                index !== order.items.length - 1 && 'border-b border-border'
              )}
            >
              {item.productImage ? (
                <div className="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-muted">
                  <OptimizedImage
                    src={item.productImage}
                    alt={item.productName}
                    className="h-full w-full rounded-lg"
                    size="thumbnail"
                    loading="lazy"
                  />
                </div>
              ) : (
                <div className="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-muted flex items-center justify-center">
                  <ShoppingBag className="h-6 w-6 text-muted-foreground" />
                </div>
              )}
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-foreground line-clamp-1">
                  {item.productName || 'Товар'}
                </p>
                <p className="text-sm text-muted-foreground">
                  {item.quantity} × {item.unitPrice.toLocaleString('ru-RU')} ₽
                </p>
              </div>
              <span className="font-semibold text-foreground">
                {item.total.toLocaleString('ru-RU')} ₽
              </span>
            </div>
          ))}
        </div>

        {/* Payment Info */}
        <div className="rounded-xl border border-border bg-card p-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <CreditCard className="h-5 w-5 text-muted-foreground" />
              <span className="text-sm text-muted-foreground">Статус оплаты</span>
            </div>
            <span className={`text-sm font-medium ${
              order.paymentStatus === 'succeeded' ? 'text-primary' : 
              order.paymentStatus === 'failed' ? 'text-destructive' : 
              'text-muted-foreground'
            }`}>
              {PAYMENT_STATUS_LABELS[order.paymentStatus]}
            </span>
          </div>
          <div className="mt-3 pt-3 border-t border-border flex items-center justify-between">
            <span className="text-lg font-semibold text-foreground">Итого</span>
            <span className="text-xl font-bold text-primary">
              {order.totalAmount.toLocaleString('ru-RU')} ₽
            </span>
          </div>
        </div>
      </div>

      {/* Bottom Actions */}
      <div className="fixed bottom-14 left-0 right-0 z-40 border-t border-border bg-background/95 backdrop-blur-sm p-4 safe-area-bottom">
        <div className="flex gap-3">
          <button
            onClick={handleContactSupport}
            className="flex-1 flex items-center justify-center gap-2 rounded-xl border border-border bg-background py-3 font-semibold text-foreground touch-feedback"
          >
            <Headphones className="h-5 w-5" />
            Поддержка
          </button>
          <button
            onClick={() => navigate('/')}
            className="flex-1 flex items-center justify-center gap-2 rounded-xl bg-primary py-3 font-semibold text-primary-foreground touch-feedback"
          >
            <ShoppingBag className="h-5 w-5" />
            В каталог
          </button>
        </div>
      </div>

      <BottomNavigation />
    </div>
  );
}
