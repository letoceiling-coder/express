import { useState, useEffect } from 'react';
import { useParams, useNavigate, useSearchParams } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { StatusBadge } from '@/components/ui/status-badge';
import { useOrders } from '@/hooks/useOrders';
import { ORDER_STATUS_LABELS, PAYMENT_STATUS_LABELS, Order, isOrderUnpaid, canCancelOrder } from '@/types';
import { MapPin, Phone, Clock, MessageSquare, CreditCard, Package, Headphones, ShoppingBag, Loader2, CheckCircle2, XCircle } from 'lucide-react';
import { openTelegramLink, hapticFeedback } from '@/lib/telegram';
import { cn } from '@/lib/utils';
import { OptimizedImage } from '@/components/OptimizedImage';
import { toast } from 'sonner';
import { paymentAPI } from '@/api';
import { ordersAPI } from '@/api';
import { getTelegramUser } from '@/lib/telegram';
import { Button } from '@/components/ui/button';

export function OrderDetailPage() {
  const { orderId } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { getOrderById, orders } = useOrders();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [showPaymentSuccess, setShowPaymentSuccess] = useState(false);
  const [showPaymentError, setShowPaymentError] = useState(false);
  const [isProcessingPayment, setIsProcessingPayment] = useState(false);
  const [isCancelling, setIsCancelling] = useState(false);

  useEffect(() => {
    const fetchOrder = async () => {
      if (!orderId) return;
      
      // Проверяем параметры URL для обработки возврата с оплаты
      const paymentStatus = searchParams.get('payment');
      const action = searchParams.get('action');
      
      // Сначала проверяем заказы из кеша для быстрого отображения
      const cachedOrder = orders.find(o => o.orderId === orderId);
      
      // Если есть action=pay или action=cancel, обрабатываем их
      if (action === 'pay' && cachedOrder) {
        navigate(`/orders/${orderId}`, { replace: true });
        setOrder(cachedOrder);
        setLoading(false);
        handlePayment(cachedOrder);
        return;
      } else if (action === 'cancel' && cachedOrder) {
        navigate(`/orders/${orderId}`, { replace: true });
        setOrder(cachedOrder);
        setLoading(false);
        handleCancel(cachedOrder);
        return;
      }
      
      // Если возврат с оплаты - обязательно загружаем актуальные данные с сервера
      if (paymentStatus === 'success' || paymentStatus === 'error') {
        setLoading(true);
        // Загружаем актуальные данные заказа с сервера
        const freshOrder = await getOrderById(orderId);
        setOrder(freshOrder);
        setLoading(false);
        
        // Убираем параметр из URL
        navigate(`/orders/${orderId}`, { replace: true });
        
        // Строго проверяем статус оплаты перед показом сообщения
        if (paymentStatus === 'success') {
          if (freshOrder && freshOrder.paymentStatus === 'succeeded') {
            setShowPaymentSuccess(true);
            toast.success('Оплата успешно выполнена!');
          } else if (freshOrder && freshOrder.paymentStatus === 'pending') {
            // Платеж еще обрабатывается
            toast.info('Платеж обрабатывается. Проверьте статус позже.');
          } else if (freshOrder && freshOrder.paymentStatus === 'failed') {
            setShowPaymentError(true);
            toast.error('Произошла ошибка при обработке платежа');
          }
        } else if (paymentStatus === 'error') {
          setShowPaymentError(true);
          toast.error('Произошла ошибка при оплате');
        }
        
        // Обрабатываем action после проверки статуса оплаты
        if (action === 'pay' && freshOrder) {
          handlePayment(freshOrder);
        } else if (action === 'cancel' && freshOrder) {
          handleCancel(freshOrder);
        }
        return;
      }
      
      // Обычная загрузка заказа
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

  const handlePayment = async (orderData?: Order) => {
    const currentOrder = orderData || order;
    if (!currentOrder) return;

    // Проверяем, что заказ неоплачен
    if (!isOrderUnpaid(currentOrder)) {
      toast.error('Заказ уже оплачен или отменен');
      return;
    }

    setIsProcessingPayment(true);
    hapticFeedback('light');

    try {
      const user = getTelegramUser();
      const telegramId = user?.id;

      // Получаем email из Telegram WebApp для отправки квитанции
      const telegramEmail = window.Telegram?.WebApp?.initDataUnsafe?.user?.email;

      // Создаем платеж через ЮKassa
      const returnUrl = `${window.location.origin}/orders/${currentOrder.orderId}?payment=success`;
      toast.info('Создание платежа...');

      const paymentData = await paymentAPI.createYooKassaPayment(
        Number(currentOrder.id),
        currentOrder.totalAmount,
        returnUrl,
        `Оплата заказа #${currentOrder.orderId}`,
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
      toast.error('Ошибка при создании платежа. Попробуйте позже.');
      setShowPaymentError(true);
    } finally {
      setIsProcessingPayment(false);
    }
  };

  const handleCancel = async (orderData?: Order) => {
    const currentOrder = orderData || order;
    if (!currentOrder) return;

    // Проверяем, что заказ можно отменить
    console.log('OrderDetailPage - handleCancel - Order data:', {
      orderId: currentOrder.orderId,
      status: currentOrder.status,
      paymentStatus: currentOrder.paymentStatus,
      canCancel: canCancelOrder(currentOrder),
    });

    if (!canCancelOrder(currentOrder)) {
      console.log('OrderDetailPage - handleCancel - Cannot cancel order:', {
        status: currentOrder.status,
        paymentStatus: currentOrder.paymentStatus,
        isCancelled: currentOrder.status === 'cancelled',
        isDelivered: currentOrder.status === 'delivered',
        isPaid: currentOrder.paymentStatus === 'succeeded',
      });
      
      if (currentOrder.status === 'cancelled') {
        toast.error('Заказ уже отменен');
      } else if (currentOrder.status === 'delivered') {
        toast.error('Заказ уже доставлен');
      } else if (currentOrder.paymentStatus === 'succeeded') {
        toast.error('Заказ уже оплачен');
      } else {
        toast.error('Заказ нельзя отменить');
      }
      return;
    }

    // Подтверждение отмены
    if (!window.confirm('Вы уверены, что хотите отменить этот заказ?')) {
      return;
    }

    setIsCancelling(true);
    hapticFeedback('light');

    try {
      const cancelledOrder = await ordersAPI.cancelOrder(currentOrder.orderId);
      
      // Обновляем заказ данными из ответа сервера
      setOrder(cancelledOrder);
      toast.success('Заказ успешно отменен');
      
      // Обновляем список заказов (через навигацию, которая вызовет перезагрузку)
      navigate(`/orders/${currentOrder.orderId}`, { replace: true });
    } catch (error: any) {
      console.error('Ошибка при отмене заказа:', error);
      toast.error(error?.message || 'Ошибка при отмене заказа');
    } finally {
      setIsCancelling(false);
    }
  };

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
        {/* Payment Success Alert - показывается только если статус действительно succeeded */}
        {showPaymentSuccess && order && order.paymentStatus === 'succeeded' && (
          <div className="rounded-xl border border-green-500/20 bg-green-500/10 p-4 animate-fade-in">
            <div className="flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 text-green-500 flex-shrink-0" />
              <div className="flex-1">
                <h3 className="text-sm font-semibold text-green-700 dark:text-green-400">
                  Оплата успешно выполнена!
                </h3>
                <p className="mt-1 text-xs text-green-600 dark:text-green-500">
                  Ваш платеж подтвержден. Заказ будет обработан в ближайшее время.
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
            {order.status === 'cancelled' ? (
              <StatusBadge status={order.status} size="lg" />
            ) : isOrderUnpaid(order) ? (
              <span className="inline-flex items-center rounded-full bg-destructive/10 px-3 py-1.5 text-sm font-medium text-destructive">
                Ожидает оплаты
              </span>
            ) : (
              <StatusBadge status={order.status} size="lg" />
            )}
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
        {isOrderUnpaid(order) && canCancelOrder(order) ? (
          // Кнопки для неоплаченных заказов (которые можно отменить)
          <div className="flex gap-3">
            <Button
              onClick={handleCancel}
              disabled={isCancelling || isProcessingPayment}
              variant="outline"
              className="flex-1"
            >
              {isCancelling ? 'Отмена...' : 'Отменить заказ'}
            </Button>
            <Button
              onClick={() => handlePayment()}
              disabled={isProcessingPayment || isCancelling}
              className="flex-1 bg-primary text-primary-foreground"
            >
              {isProcessingPayment ? 'Создание платежа...' : 'Оплатить'}
            </Button>
          </div>
        ) : (
          // Кнопки для оплаченных заказов
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
        )}
      </div>

      <BottomNavigation />
    </div>
  );
}
